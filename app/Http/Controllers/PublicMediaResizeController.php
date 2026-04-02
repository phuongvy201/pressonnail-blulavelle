<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class PublicMediaResizeController extends BaseController
{
    private const MIN_W = 80;

    private const MAX_W = 1920;

    /**
     * Resize ảnh local (public/ hoặc storage/app/public/) — URL có chữ ký, cache đĩa, hỗ trợ WebP theo Accept.
     */
    public function show(Request $request): Response
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $path = (string) $request->query('p', '');
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        if (! preg_match('/\.(jpe?g|png|gif|webp)$/i', $path)) {
            abort(404);
        }

        $w = (int) $request->query('w', 800);
        $w = max(self::MIN_W, min(self::MAX_W, $w));

        $fullPath = $this->resolveLocalPath($path);
        if ($fullPath === null || ! is_readable($fullPath)) {
            abort(404);
        }

        $mtime = (int) @filemtime($fullPath);
        $wantWebp = str_contains((string) $request->header('Accept', ''), 'image/webp')
            && function_exists('imagewebp');

        $cacheDir = storage_path('framework/cache/media-resize');
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $keyBase = hash('sha256', $fullPath.'|'.$mtime.'|'.$w);
        $webpPath = $cacheDir.'/'.$keyBase.'.webp';
        $jpgPath = $cacheDir.'/'.$keyBase.'.jpg';

        if ($wantWebp && is_file($webpPath) && is_readable($webpPath)) {
            return $this->fileResponse($webpPath, 'image/webp');
        }
        if (is_file($jpgPath) && is_readable($jpgPath)) {
            return $this->fileResponse($jpgPath, 'image/jpeg');
        }

        $binary = $this->resizeToBinary($fullPath, $w, $wantWebp);
        if ($binary === null) {
            abort(500);
        }

        $mime = $binary['mime'];
        $data = $binary['data'];
        $outPath = str_contains($mime, 'webp') ? $webpPath : $jpgPath;
        @file_put_contents($outPath, $data);

        return response($data, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000, immutable, s-maxage=31536000',
        ]);
    }

    private function resolveLocalPath(string $path): ?string
    {
        $storage = storage_path('app/public/'.$path);
        if (is_file($storage)) {
            return $storage;
        }

        $public = public_path($path);
        if (is_file($public)) {
            return $public;
        }

        return null;
    }

    private function fileResponse(string $absolutePath, string $mime): Response
    {
        return response()->file($absolutePath, [
            'Cache-Control' => 'public, max-age=31536000, immutable, s-maxage=31536000',
            'Content-Type' => $mime,
        ]);
    }

    /**
     * @return ?array{data: string, mime: string}
     */
    private function resizeToBinary(string $fullPath, int $maxWidth, bool $asWebp): ?array
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $data = @file_get_contents($fullPath);
        if ($data === false) {
            return null;
        }

        $src = @imagecreatefromstring($data);
        if ($src === false) {
            return null;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($src);

            return null;
        }

        if ($srcW <= $maxWidth) {
            $dstW = $srcW;
            $dstH = $srcH;
        } else {
            $dstW = $maxWidth;
            $dstH = (int) round($srcH * ($maxWidth / $srcW));
        }

        $dst = imagecreatetruecolor($dstW, $dstH);
        if ($dst === false) {
            imagedestroy($src);

            return null;
        }

        if ($asWebp) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $transparent);
        } else {
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $white);
            imagealphablending($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($src);

        $mime = 'image/jpeg';
        ob_start();
        if ($asWebp && function_exists('imagewebp')) {
            $ok = @imagewebp($dst, null, 82);
            $out = ob_get_clean();
            if ($ok && is_string($out) && strlen($out) > 64) {
                imagedestroy($dst);

                return ['data' => $out, 'mime' => 'image/webp'];
            }
            ob_start();
        }
        imagejpeg($dst, null, 82);
        $out = ob_get_clean();
        imagedestroy($dst);

        if (! is_string($out) || $out === '') {
            return null;
        }

        return ['data' => $out, 'mime' => $mime];
    }
}
