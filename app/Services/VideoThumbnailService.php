<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;

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
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => config('services.ffmpeg.binaries', 'ffmpeg'),
                'ffprobe.binaries' => config('services.ffmpeg.ffprobe', 'ffprobe'),
                'timeout' => (int) config('services.ffmpeg.timeout', 3600),
                'ffmpeg.threads' => (int) config('services.ffmpeg.threads', 12),
            ]);

            $videoPath = $video instanceof UploadedFile ? $video->getRealPath() : $video;
            if (!$videoPath || !is_file($videoPath)) {
                Log::warning('VideoThumbnailService: file not found', ['path' => $videoPath]);
                return null;
            }

            $absoluteOutputPath = Storage::disk('local')->path($relativePath);
            $outputDir = dirname($absoluteOutputPath);
            if (!is_dir($outputDir)) {
                @mkdir($outputDir, 0775, true);
            }

            $ffmpeg
                ->open($videoPath)
                ->frame(TimeCode::fromSeconds((int) max(0, floor($atSeconds))))
                ->save($absoluteOutputPath);

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
