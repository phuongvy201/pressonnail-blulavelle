<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Log;

class ProductMediaWebpService
{
    public const DEFAULT_S3_PUBLIC_BASE = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/';

    /** @var list<string> */
    private const CONVERTIBLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];

    public static function defaultS3BaseUrl(): string
    {
        $fromEnv = config('filesystems.disks.s3.url');

        return is_string($fromEnv) && $fromEnv !== ''
            ? rtrim($fromEnv, '/').'/'
            : self::DEFAULT_S3_PUBLIC_BASE;
    }

    /**
     * Các prefix URL public có thể dùng cho cùng bucket (config .env, path-style, virtual-host).
     *
     * @return list<string>
     */
    public static function candidatePublicBasePrefixes(): array
    {
        $out = [];
        $cfg = config('filesystems.disks.s3.url');
        if (is_string($cfg) && $cfg !== '') {
            $out[] = rtrim($cfg, '/').'/';
        }
        $out[] = self::DEFAULT_S3_PUBLIC_BASE;

        $bucket = config('filesystems.disks.s3.bucket');
        $region = config('filesystems.disks.s3.region');
        if (! is_string($region) || $region === '') {
            $region = 'us-east-1';
        }
        if (is_string($bucket) && $bucket !== '') {
            $out[] = "https://{$bucket}.s3.{$region}.amazonaws.com/";
            $out[] = "https://{$bucket}.s3.amazonaws.com/";
        }

        return array_values(array_unique(array_filter($out)));
    }

    /**
     * @return array{base: string, key: string}|null
     */
    public static function resolvePublicUrlToKey(string $url): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        foreach (self::candidatePublicBasePrefixes() as $base) {
            if (str_starts_with($url, $base)) {
                $key = ltrim(substr($url, strlen($base)), '/');

                return $key !== '' ? ['base' => $base, 'key' => $key] : null;
            }
        }

        return null;
    }

    public static function urlToKey(string $url, string $s3BaseUrl): ?string
    {
        $url = trim($url);
        if ($url === '' || ! str_starts_with($url, $s3BaseUrl)) {
            return null;
        }

        $key = ltrim(substr($url, strlen($s3BaseUrl)), '/');

        return $key !== '' ? $key : null;
    }

    public static function webpKeyFromImageKey(string $imageKey): string
    {
        $dir = pathinfo($imageKey, PATHINFO_DIRNAME);
        $base = pathinfo($imageKey, PATHINFO_FILENAME);

        if ($dir === '.' || $dir === '') {
            return $base.'.webp';
        }

        return $dir.'/'.$base.'.webp';
    }

    public static function webpUrlFromImageUrl(string $imageUrl, ?string $s3BaseUrl = null): ?string
    {
        $resolved = self::resolvePublicUrlToKey($imageUrl);
        if ($resolved !== null) {
            return $resolved['base'].self::webpKeyFromImageKey($resolved['key']);
        }
        if ($s3BaseUrl !== null) {
            $key = self::urlToKey($imageUrl, $s3BaseUrl);

            return $key !== null ? $s3BaseUrl.self::webpKeyFromImageKey($key) : null;
        }

        return null;
    }

    public static function isConvertiblePath(string $pathOrUrl): bool
    {
        $ext = strtolower(pathinfo(parse_url($pathOrUrl, PHP_URL_PATH) ?? $pathOrUrl, PATHINFO_EXTENSION));

        return $ext !== '' && $ext !== 'webp' && in_array($ext, self::CONVERTIBLE_EXTENSIONS, true);
    }

    /**
     * Encode raster image bytes to WebP. Trả null nếu GD không hỗ trợ hoặc lỗi decode.
     */
    public static function encodeToWebp(string $binary, int $quality = 85): ?string
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $img = @imagecreatefromstring($binary);
        if ($img === false) {
            return null;
        }

        // GIF palette / transparency: promote to truecolor for webp (nếu GD hỗ trợ)
        if (function_exists('imageistruecolor') && function_exists('imagepalettetotruecolor') && ! imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }

        imagealphablending($img, true);
        imagesavealpha($img, true);

        ob_start();
        $ok = imagewebp($img, null, max(0, min(100, $quality)));
        imagedestroy($img);
        $out = ob_get_clean();

        return $ok && $out !== false && $out !== '' ? $out : null;
    }

    /**
     * Tải object S3, encode WebP, upload cạnh file gốc (cùng folder, đuôi .webp).
     *
     * @return array{webp_url: string|null, skipped: bool, error: string|null}
     */
    public static function ensureWebpOnS3(
        Filesystem $s3,
        string $imageUrl,
        bool $dryRun,
        bool $forceRegenerate
    ): array {
        $resolved = self::resolvePublicUrlToKey($imageUrl);
        if ($resolved === null) {
            return ['webp_url' => null, 'skipped' => true, 'error' => 'not_our_s3'];
        }

        $key = $resolved['key'];
        $publicBase = $resolved['base'];

        if (! self::isConvertiblePath($key)) {
            return ['webp_url' => null, 'skipped' => true, 'error' => 'not_convertible'];
        }

        $webpKey = self::webpKeyFromImageKey($key);
        $webpUrl = $publicBase.$webpKey;

        if (! $forceRegenerate && $s3->exists($webpKey)) {
            return ['webp_url' => $webpUrl, 'skipped' => false, 'error' => null];
        }

        if (! $s3->exists($key)) {
            Log::warning('ProductMediaWebp: source key missing on S3.', ['key' => $key]);

            return ['webp_url' => null, 'skipped' => true, 'error' => 'source_missing'];
        }

        $binary = $s3->get($key);
        if ($binary === false || $binary === '') {
            return ['webp_url' => null, 'skipped' => true, 'error' => 'empty_source'];
        }

        $webpBinary = self::encodeToWebp($binary, 85);
        if ($webpBinary === null) {
            Log::warning('ProductMediaWebp: encode failed (GD/WebP).', ['key' => $key]);

            return ['webp_url' => null, 'skipped' => true, 'error' => 'encode_failed'];
        }

        if ($dryRun) {
            return ['webp_url' => $webpUrl, 'skipped' => false, 'error' => null];
        }

        if (! self::putWebpToS3($s3, $webpKey, $webpBinary)) {
            return ['webp_url' => null, 'skipped' => true, 'error' => 'upload_failed'];
        }

        return ['webp_url' => $webpUrl, 'skipped' => false, 'error' => null];
    }

    /**
     * Upload WebP: dùng S3 putObject không kèm ACL.
     * Bucket bật "Bucket owner enforced" / tắt ACL thì Flysystem put(..., visibility) thường thất bại.
     */
    private static function putWebpToS3(Filesystem $disk, string $webpKey, string $webpBinary): bool
    {
        if ($disk instanceof AwsS3V3Adapter) {
            $bucket = $disk->getConfig()['bucket'] ?? null;
            if (! is_string($bucket) || $bucket === '') {
                Log::error('ProductMediaWebp: thiếu bucket trong cấu hình disk s3.');

                return false;
            }

            try {
                $disk->getClient()->putObject([
                    'Bucket' => $bucket,
                    'Key' => $webpKey,
                    'Body' => $webpBinary,
                    'ContentType' => 'image/webp',
                    'CacheControl' => 'max-age=31536000, public',
                ]);

                return true;
            } catch (\Throwable $e) {
                Log::error('ProductMediaWebp: putObject thất bại.', [
                    'key' => $webpKey,
                    'message' => $e->getMessage(),
                ]);

                return false;
            }
        }

        $put = $disk->put($webpKey, $webpBinary, [
            'ContentType' => 'image/webp',
            'CacheControl' => 'max-age=31536000, public',
        ]);

        return (bool) $put;
    }
}
