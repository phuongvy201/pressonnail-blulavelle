<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use ZipArchive;

class GoogleDriveClient
{
    private const API_BASE = 'https://www.googleapis.com/drive/v3';

    public function isConfigured(): bool
    {
        $credentialsPath = $this->resolveCredentialsPath(
            (string) config('services.google.sheets.credentials_path')
        );

        return $credentialsPath !== '' && is_file($credentialsPath);
    }

    public static function parseFolderId(string $input): ?string
    {
        $input = trim($input);

        if (preg_match('#/folders/([a-zA-Z0-9_-]+)#', $input, $m)) {
            return $m[1];
        }

        if (preg_match('#[?&]id=([a-zA-Z0-9_-]+)#', $input, $m)) {
            return $m[1];
        }

        if (preg_match('#^[a-zA-Z0-9_-]{20,}$#', $input)) {
            return $input;
        }

        return null;
    }

    public static function parseFileId(string $input): ?string
    {
        $input = trim($input);

        if (preg_match('#/file/d/([a-zA-Z0-9_-]+)#', $input, $m)) {
            return $m[1];
        }

        if (preg_match('#[?&]id=([a-zA-Z0-9_-]+)#', $input, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Liệt kê ảnh/video trong folder Drive, trả về URL share dạng file/d/ID/view.
     *
     * @return array{images: list<string>, video: string|null}
     */
    public function listFolderMediaUrls(string $folderInput, int $maxImages = 8): array
    {
        $resolved = $this->resolveFolderMedia($folderInput, $maxImages);

        $images = [];
        foreach ($resolved['images'] as $file) {
            $images[] = 'https://drive.google.com/file/d/'.$file['id'].'/view';
        }

        $video = $resolved['video'] !== null
            ? 'https://drive.google.com/file/d/'.$resolved['video']['id'].'/view'
            : null;

        return ['images' => $images, 'video' => $video];
    }

    /**
     * Phân loại file trong folder: file nén (zip), video, ảnh rời.
     *
     * @return array{
     *     archive: array{id: string, name: string, mimeType: string}|null,
     *     video: array{id: string, name: string, mimeType: string}|null,
     *     images: list<array{id: string, name: string, mimeType: string}>
     * }
     */
    public function resolveFolderMedia(string $folderInput, int $maxImages = 8): array
    {
        $folderId = self::parseFolderId($folderInput);
        if ($folderId === null) {
            return ['archive' => null, 'video' => null, 'images' => []];
        }

        $files = $this->listFolderFiles($folderId);
        usort($files, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

        $archive = null;
        $video = null;
        $images = [];

        foreach ($files as $file) {
            $mime = strtolower($file['mimeType']);
            $ext = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($this->isArchive($mime, $ext)) {
                if ($archive === null) {
                    $archive = $file;
                }
                continue;
            }

            if ($this->isVideo($mime, $ext)) {
                if ($video === null) {
                    $video = $file;
                }
                continue;
            }

            if ($this->isImage($mime, $ext) && count($images) < $maxImages) {
                $images[] = $file;
            }
        }

        Log::info('GoogleDriveClient: resolved folder media', [
            'folder_id' => $folderId,
            'file_count' => count($files),
            'has_archive' => $archive !== null,
            'image_count' => count($images),
            'has_video' => $video !== null,
        ]);

        return [
            'archive' => $archive,
            'video' => $video,
            'images' => $images,
        ];
    }

    /**
     * @return array<int, array{id: string, name: string, mimeType: string, size: int|null}>
     */
    public function listFolderFiles(string $folderId): array
    {
        $drive = $this->createDriveService();
        if ($drive === null) {
            throw new RuntimeException('Google Drive chưa cấu hình credentials.');
        }

        $files = [];
        $pageToken = null;

        do {
            $response = $drive->files->listFiles([
                'q' => "'{$folderId}' in parents and trashed = false",
                'fields' => 'nextPageToken, files(id, name, mimeType, size)',
                'pageSize' => 200,
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
                'pageToken' => $pageToken,
            ]);

            foreach ((array) $response->getFiles() as $file) {
                $files[] = [
                    'id' => (string) $file->getId(),
                    'name' => (string) $file->getName(),
                    'mimeType' => (string) $file->getMimeType(),
                    'size' => $file->getSize() !== null ? (int) $file->getSize() : null,
                ];
            }

            $pageToken = $response->getNextPageToken();
        } while ($pageToken);

        return $files;
    }

    /**
     * Tải file binary qua Drive API (service account).
     */
    public function downloadToFile(string $fileId, string $localPath): void
    {
        $token = $this->accessToken();
        if ($token === null) {
            throw new RuntimeException('Google Drive chưa cấu hình credentials.');
        }

        $url = self::API_BASE."/files/{$fileId}?alt=media&supportsAllDrives=true";

        $response = Http::withToken($token)
            ->timeout(300)
            ->sink($localPath)
            ->get($url);

        $size = is_readable($localPath) ? (int) filesize($localPath) : 0;

        if ($response->failed() || $size === 0) {
            $message = $response->json('error.message') ?? ($size === 0 ? 'File tải về rỗng (0 byte)' : 'HTTP '.$response->status());
            Log::warning('GoogleDriveClient: download failed', [
                'file_id' => $fileId,
                'status' => $response->status(),
                'error' => $message,
            ]);
            throw new RuntimeException('Không tải được file Drive: '.$message);
        }

        Log::info('GoogleDriveClient: downloaded file', [
            'file_id' => $fileId,
            'size_bytes' => $size,
        ]);
    }

    /**
     * Giải nén ảnh từ file nén trên Drive, trả về tối đa $maxImages ảnh.
     *
     * @return list<array{local_path: string, extension: string, name: string, extract_dir: string}>
     */
    public function extractImagesFromArchive(string $fileId, string $archiveName, int $maxImages = 8): array
    {
        $tmp = $this->tempPath($archiveName);

        try {
            $this->downloadToFile($fileId, $tmp);

            return $this->extractImagesFromLocalArchive($tmp, $maxImages);
        } finally {
            $this->cleanup($tmp);
        }
    }

    /**
     * @return list<array{local_path: string, extension: string, name: string, extract_dir: string}>
     */
    public function extractImagesFromLocalArchive(string $localPath, int $maxImages = 8): array
    {
        $ext = strtolower((string) pathinfo($localPath, PATHINFO_EXTENSION));

        if ($ext !== 'zip') {
            throw new RuntimeException("Định dạng nén «{$ext}» chưa được hỗ trợ. Vui lòng dùng file .zip.");
        }

        $zip = new ZipArchive();
        if ($zip->open($localPath) !== true) {
            throw new RuntimeException('Không mở được file zip.');
        }

        $extractDir = rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR.'pondrive_extract_'.uniqid('', true);
        if (! @mkdir($extractDir, 0755, true) && ! is_dir($extractDir)) {
            $zip->close();
            throw new RuntimeException('Không tạo được thư mục giải nén tạm.');
        }

        $images = [];

        try {
            for ($i = 0; $i < $zip->numFiles && count($images) < $maxImages; $i++) {
                $entry = $zip->getNameIndex($i);
                if ($entry === false || $this->isJunkZipEntry($entry)) {
                    continue;
                }

                $entryExt = strtolower((string) pathinfo($entry, PATHINFO_EXTENSION));
                if (! $this->isImage('', $entryExt)) {
                    continue;
                }

                $stream = $zip->getStream($entry);
                if ($stream === false) {
                    continue;
                }

                $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', basename($entry)) ?: 'image';
                $localFile = $extractDir.DIRECTORY_SEPARATOR.sprintf('%02d_%s', count($images) + 1, $safeName);
                $out = fopen($localFile, 'wb');
                if ($out === false) {
                    fclose($stream);
                    continue;
                }

                stream_copy_to_stream($stream, $out);
                fclose($out);
                fclose($stream);

                if (! is_readable($localFile) || filesize($localFile) === 0) {
                    @unlink($localFile);
                    continue;
                }

                $images[] = [
                    'local_path' => $localFile,
                    'extension' => $entryExt === 'jpeg' ? 'jpg' : $entryExt,
                    'name' => basename($entry),
                    'extract_dir' => $extractDir,
                ];
            }
        } finally {
            $zip->close();
        }

        usort($images, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return $images;
    }

    /**
     * Tải file Drive ra path cục bộ (stream qua HTTP sink) — tránh nạp binary vào RAM.
     */
    public function downloadFileToTemp(string $fileId, ?int $maxBytes = null): string
    {
        $tmp = $this->tempPath('download.bin');

        try {
            $this->downloadToFile($fileId, $tmp);
            $size = is_readable($tmp) ? (int) filesize($tmp) : 0;

            if ($size === 0) {
                throw new RuntimeException('File Drive rỗng.');
            }

            if ($maxBytes !== null && $size > $maxBytes) {
                throw new RuntimeException('File Drive vượt giới hạn kích thước.');
            }

            return $tmp;
        } catch (\Throwable $e) {
            $this->cleanup($tmp);
            throw $e;
        }
    }

    /**
     * Xóa thư mục giải nén tạm (và file con).
     */
    public function cleanupExtractDirectory(string $extractDir): void
    {
        if (! is_dir($extractDir)) {
            return;
        }

        foreach (glob($extractDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($extractDir);
    }

    /**
     * Tải nội dung file qua Drive API (dùng cho video/ảnh đơn lẻ nhỏ).
     */
    public function downloadFileContent(string $fileId, ?int $maxBytes = null): string
    {
        $tmp = $this->downloadFileToTemp($fileId, $maxBytes);

        try {
            return (string) file_get_contents($tmp);
        } finally {
            $this->cleanup($tmp);
        }
    }

    private function accessToken(): ?string
    {
        $credentialsPath = $this->resolveCredentialsPath(
            (string) config('services.google.sheets.credentials_path')
        );

        if ($credentialsPath === '' || ! is_file($credentialsPath)) {
            return null;
        }

        $client = new Client();
        $client->setApplicationName('PressOnNail Product Import');
        $client->setScopes([Drive::DRIVE_READONLY]);
        $client->setAuthConfig($credentialsPath);

        $token = $client->fetchAccessTokenWithAssertion();

        return is_array($token) ? ($token['access_token'] ?? null) : null;
    }

    private function createDriveService(): ?Drive
    {
        $credentialsPath = $this->resolveCredentialsPath(
            (string) config('services.google.sheets.credentials_path')
        );

        if ($credentialsPath === '' || ! is_file($credentialsPath)) {
            return null;
        }

        $client = new Client();
        $client->setApplicationName('PressOnNail Product Import');
        $client->setScopes([Drive::DRIVE_READONLY]);
        $client->setAuthConfig($credentialsPath);

        return new Drive($client);
    }

    private function resolveCredentialsPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }

    private function tempPath(string $name): string
    {
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'tmp';

        return rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR.'pondrive_'.uniqid('', true).'.'.$ext;
    }

    private function cleanup(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function isJunkZipEntry(string $entry): bool
    {
        if (str_ends_with($entry, '/')) {
            return true;
        }

        $base = basename($entry);

        return str_starts_with($entry, '__MACOSX/')
            || str_starts_with($base, '.')
            || $base === 'Thumbs.db';
    }

    private function isImage(string $mime, string $ext): bool
    {
        if ($mime !== '' && str_starts_with($mime, 'image/')) {
            return true;
        }

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'heic'], true);
    }

    private function isVideo(string $mime, string $ext): bool
    {
        if ($mime !== '' && str_starts_with($mime, 'video/')) {
            return true;
        }

        return in_array($ext, ['mp4', 'mov', 'mpeg', 'avi', 'webm', 'mkv'], true);
    }

    private function isArchive(string $mime, string $ext): bool
    {
        if (in_array($ext, ['zip', 'rar', '7z'], true)) {
            return true;
        }

        return in_array($mime, [
            'application/zip',
            'application/x-zip-compressed',
            'multipart/x-zip',
            'application/vnd.rar',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ], true);
    }
}
