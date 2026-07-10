<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentBlockController extends Controller
{
    /**
     * Đường dẫn file tạm an toàn (Windows đôi khi getRealPath() rỗng với UploadedFile).
     */
    protected function localPathForUploadedFile(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return null;
        }

        foreach ([$file->getRealPath(), $file->getPathname()] as $path) {
            if (is_string($path) && $path !== '' && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Lưu file lên S3 (nếu cấu hình) hoặc storage/public — trả về URL public.
     */
    protected function storeContentBlockFile(UploadedFile $file, string $dir, string $filename): string
    {
        $localPath = $this->localPathForUploadedFile($file);
        if ($localPath === null) {
            throw new \RuntimeException('Không đọc được file upload. Vui lòng chọn lại ảnh hoặc video.');
        }

        $mime = $file->getMimeType() ?: 'application/octet-stream';

        if (config('filesystems.disks.s3.key') && config('filesystems.disks.s3.bucket')) {
            try {
                $uploadedPath = Storage::disk('s3')->putFileAs(
                    $dir,
                    $localPath,
                    $filename,
                    [
                        'visibility' => 'public',
                        'CacheControl' => 'max-age=31536000',
                        'ContentType' => $mime,
                    ]
                );

                if ($uploadedPath) {
                    return Storage::disk('s3')->url($uploadedPath);
                }
            } catch (\Throwable $e) {
                report($e);
                Log::warning('ContentBlock upload: S3 failed, falling back to public disk', [
                    'dir' => $dir,
                    'filename' => $filename,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $stored = Storage::disk('public')->putFileAs($dir, $localPath, $filename, [
            'visibility' => 'public',
        ]);

        if (! $stored) {
            throw new \RuntimeException('Lưu file thất bại (storage/public). Chạy php artisan storage:link nếu chưa có.');
        }

        return asset('storage/'.str_replace('\\', '/', $stored));
    }

    /**
     * GET /admin/api/content-blocks?keys=home.hero,home.bestsellers
     * Trả về nội dung các block (để edit mode load hoặc merge).
     */
    public function index(Request $request): JsonResponse
    {
        $keys = $request->query('keys');
        if (is_string($keys)) {
            $keys = array_map('trim', explode(',', $keys));
        } else {
            $keys = [];
        }
        if (empty($keys)) {
            $blocks = ContentBlock::all()->keyBy('block_key');
        } else {
            $blocks = ContentBlock::whereIn('block_key', $keys)->get()->keyBy('block_key');
        }
        $content = [];
        foreach ($blocks as $key => $block) {
            $content[$key] = $block->content ?? [];
        }
        return response()->json(['blocks' => $content]);
    }

    /**
     * PUT /admin/api/content-blocks
     * Body: { "key": "home.hero", "content": { "heading": "...", ... } }
     * Cập nhật một block (merge với content hiện có).
     */
    public function update(Request $request): JsonResponse
    {
        $input = $request->validate([
            'key' => 'required|string|max:128',
            'content' => 'required|array',
        ]);
        $key = trim($input['key']);
        if ($key === '') {
            return response()->json(['message' => 'Invalid block key'], 422);
        }
        $block = ContentBlock::firstOrNew(['block_key' => $key], ['content' => []]);
        $current = is_array($block->content) ? $block->content : [];
        $block->content = array_merge($current, $input['content']);
        $block->save();
        return response()->json([
            'block_key' => $key,
            'content' => $block->content,
        ]);
    }

    /**
     * POST /admin/api/content-blocks/upload-image
     * Upload ảnh lên S3 (hoặc lưu local nếu S3 chưa cấu hình), dùng cho inline edit trang chủ.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            $isCommunityGif = $request->boolean('community_gif');

            if ($isCommunityGif) {
                $request->validate([
                    'image' => 'required|file|mimes:gif|max:20480', // 20MB — BluLavelle Community
                ], [
                    'image.required' => 'Vui lòng chọn GIF.',
                    'image.mimes' => 'Chỉ chấp nhận file GIF.',
                    'image.max' => 'GIF tối đa 20MB.',
                ]);
            } else {
                $request->validate([
                    'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:10240', // 10MB
                ], [
                    'image.required' => 'Vui lòng chọn ảnh.',
                    'image.image' => 'File phải là ảnh.',
                    'image.mimes' => 'Chỉ chấp nhận ảnh: JPEG, PNG, GIF, WebP.',
                    'image.max' => 'Ảnh tối đa 10MB.',
                ]);
            }

            $file = $request->file('image');
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $filename = now()->format('YmdHis').'_'.Str::random(8).'.'.$extension;
            $url = $this->storeContentBlockFile($file, 'content-blocks/images', $filename);

            return response()->json(['success' => true, 'url' => $url]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Upload ảnh thất bại.',
            ], 500);
        }
    }

    /**
     * POST /admin/api/content-blocks/upload-video
     * Upload video lên S3 (hoặc lưu local nếu S3 chưa cấu hình) cho content block (See it in action tabs).
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'video' => 'required|file|mimes:mp4,mov,webm,ogg|max:102400', // 100MB
            ], [
                'video.required' => 'Vui lòng chọn video.',
                'video.mimes' => 'Chỉ chấp nhận video: MP4, MOV, WebM, OGG.',
                'video.max' => 'Video tối đa 100MB.',
            ]);

            $file = $request->file('video');
            $extension = strtolower($file->getClientOriginalExtension() ?: 'mp4');
            $filename = now()->format('YmdHis').'_'.Str::random(8).'.'.$extension;
            $url = $this->storeContentBlockFile($file, 'content-blocks/videos', $filename);

            return response()->json(['success' => true, 'url' => $url]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Upload video thất bại.',
            ], 500);
        }
    }
}
