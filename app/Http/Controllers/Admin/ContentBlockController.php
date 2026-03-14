<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentBlockController extends Controller
{
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
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:10240', // 10MB
        ], [
            'image.required' => 'Vui lòng chọn ảnh.',
            'image.image' => 'File phải là ảnh.',
            'image.mimes' => 'Chỉ chấp nhận ảnh: JPEG, PNG, GIF, WebP.',
            'image.max' => 'Ảnh tối đa 10MB.',
        ]);

        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = now()->format('YmdHis') . '_' . Str::random(8) . '.' . $extension;
        $dir = 'content-blocks/images';

        // Thử S3 trước, nếu lỗi (chưa cấu hình / credentials) thì fallback lưu local
        if (config('filesystems.disks.s3.key') && config('filesystems.disks.s3.bucket')) {
            try {
                $uploadedPath = Storage::disk('s3')->putFileAs(
                    $dir,
                    $file,
                    $filename,
                    [
                        'visibility' => 'public',
                        'CacheControl' => 'max-age=31536000',
                        'ContentType' => $file->getMimeType(),
                    ]
                );

                if ($uploadedPath) {
                    $url = Storage::disk('s3')->url($uploadedPath);

                    return response()->json(['success' => true, 'url' => $url]);
                }
            } catch (\Throwable $e) {
                report($e);
                // Fallback xuống local
            }
        }

        // Fallback: lưu vào storage/app/public (chạy php artisan storage:link để public)
        $path = $file->storeAs($dir, $filename, 'public');
        $url = asset('storage/' . $path);

        return response()->json(['success' => true, 'url' => $url]);
    }

    /**
     * POST /admin/api/content-blocks/upload-video
     * Upload video lên S3 (hoặc lưu local nếu S3 chưa cấu hình) cho content block (See it in action tabs).
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,webm,ogg|max:102400', // 100MB
        ], [
            'video.required' => 'Vui lòng chọn video.',
            'video.mimes' => 'Chỉ chấp nhận video: MP4, MOV, WebM, OGG.',
            'video.max' => 'Video tối đa 100MB.',
        ]);

        $file = $request->file('video');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        $filename = now()->format('YmdHis') . '_' . Str::random(8) . '.' . $extension;
        $dir = 'content-blocks/videos';

        // Thử S3 trước, nếu lỗi thì fallback lưu local
        if (config('filesystems.disks.s3.key') && config('filesystems.disks.s3.bucket')) {
            try {
                $uploadedPath = Storage::disk('s3')->putFileAs(
                    $dir,
                    $file,
                    $filename,
                    [
                        'visibility' => 'public',
                        'CacheControl' => 'max-age=31536000',
                        'ContentType' => $file->getMimeType(),
                    ]
                );

                if ($uploadedPath) {
                    $url = Storage::disk('s3')->url($uploadedPath);

                    return response()->json(['success' => true, 'url' => $url]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Fallback: lưu vào storage/app/public
        $path = $file->storeAs($dir, $filename, 'public');
        $url = asset('storage/' . $path);

        return response()->json(['success' => true, 'url' => $url]);
    }
}
