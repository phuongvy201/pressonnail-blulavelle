<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use ProtoneMedia\LaravelFFMpeg\FFMpeg\ImageFormat;

/**
 * Tạo ảnh preview (poster) từ video bằng FFmpeg.
 * Dùng frame tại thời điểm cho trước (mặc định 1 giây).
 */
class VideoThumbnailService
{
    /** Thư mục tạm trên disk local để lưu poster trước khi upload S3 */
    public const TEMP_POSTER_DIR = 'video-posters';

    /** Các extension coi là video */
    protected static array $videoExtensions = ['mp4', 'mov', 'avi', 'webm', 'ogg'];

    public static function isVideoFile(UploadedFile|string $file): bool
    {
        $ext = $file instanceof UploadedFile
            ? strtolower($file->getClientOriginalExtension())
            : strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($ext, self::$videoExtensions, true);
    }

    /**
     * Tạo poster từ file video (upload hoặc đường dẫn local).
     * Trả về đường dẫn file ảnh tạm (trên disk 'local') để controller upload lên S3,
     * hoặc null nếu lỗi.
     *
     * @param  UploadedFile|string  $video  File upload hoặc path tuyệt đối tới video
     * @param  float  $atSeconds  Lấy frame tại giây thứ mấy (mặc định 1)
     * @return string|null  Đường dẫn tương đối trên disk 'local' (e.g. video-posters/xxx.jpg) hoặc null
     */
    public function generatePoster(UploadedFile|string $video, float $atSeconds = 1): ?string
    {
        $relativePath = self::TEMP_POSTER_DIR . '/' . uniqid('poster_', true) . '.jpg';

        try {
            if ($video instanceof UploadedFile) {
                FFMpeg::open($video)
                    ->getFrameFromSeconds($atSeconds)
                    ->export()
                    ->inFormat(new ImageFormat)
                    ->toDisk('local')
                    ->save($relativePath);
            } else {
                // Path tuyệt đối: mở từ filesystem tạm (file đã download về)
                $path = $video;
                if (!is_file($path)) {
                    Log::warning('VideoThumbnailService: file not found', ['path' => $path]);
                    return null;
                }
                $dir = dirname($path);
                $name = basename($path);
                $disk = Storage::build([
                    'driver' => 'local',
                    'root' => $dir,
                ]);
                FFMpeg::fromFilesystem($disk)
                    ->open($name)
                    ->getFrameFromSeconds($atSeconds)
                    ->export()
                    ->inFormat(new ImageFormat)
                    ->toDisk('local')
                    ->save($relativePath);
            }

            return $relativePath;
        } catch (\Throwable $e) {
            Log::warning('VideoThumbnailService: failed to generate poster', [
                'video' => $video instanceof UploadedFile ? $video->getClientOriginalName() : $video,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Xóa file poster tạm sau khi đã upload lên S3 (gọi từ controller).
     */
    public function deleteTempPoster(string $relativePath): void
    {
        try {
            if (Storage::disk('local')->exists($relativePath)) {
                Storage::disk('local')->delete($relativePath);
            }
        } catch (\Throwable $e) {
            Log::debug('VideoThumbnailService: could not delete temp poster', ['path' => $relativePath]);
        }
    }
}
