<?php

namespace App\Services;

use App\Models\CustomFile;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;

class CustomFileUploadService
{
    protected $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'video/mp4',
        'video/avi',
        'video/mov',
        'video/wmv',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];

    protected $maxFileSize = 10 * 1024 * 1024; // 10MB

    protected $allowedExtensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg',
        'mp4',
        'avi',
        'mov',
        'wmv',
        'pdf',
        'doc',
        'docx',
        'txt'
    ];

    public function uploadCustomFile(
        UploadedFile $file,
        int $productId,
        ?int $userId = null,
        ?string $sessionId = null,
        array $metadata = []
    ): CustomFile {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename and path
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = $this->generateFilename($originalName, $productId, $userId, $sessionId);
        // Đường dẫn lưu trong disk (public storage)
        $filePath = $this->getStoragePath($filename);

        try {
            // Đẩy ảnh vào storage (disk public)
            $disk = 'public';

            // Log trước khi upload để debug
            Log::info('CustomFileUploadService: chuẩn bị upload custom file vào storage', [
                'disk' => $disk,
                'product_id' => $productId,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'original_name' => $originalName,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'storage_path' => $filePath,
                'dir' => dirname($filePath),
                'basename' => basename($filePath),
            ]);

            // Upload file vào storage/public
            $uploadedPath = Storage::disk($disk)->putFileAs(
                dirname($filePath),
                $file,
                basename($filePath),
                [
                    'visibility' => 'public',
                ]
            );

            if (!$uploadedPath) {
                Log::error('CustomFileUploadService: putFileAs trả về false', [
                    'disk' => $disk,
                    'storage_path' => $filePath,
                ]);
                throw new Exception('Failed to upload file to storage (putFileAs returned false)');
            }

            // Get full URL (storage/public => /storage/...)
            $fileUrl = Storage::disk($disk)->url($uploadedPath);

            // Create database record
            $customFile = CustomFile::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'product_id' => $productId,
                'original_name' => $originalName,
                'filename' => basename($filename),
                'file_path' => $uploadedPath,
                'file_url' => $fileUrl,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_extension' => $extension,
                'metadata' => array_merge($metadata, [
                    'uploaded_at' => now()->toISOString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                ]),
                'status' => 'processed',
                'expires_at' => now()->addHours(24), // Files expire after 24 hours
            ]);

            return $customFile;
        } catch (Exception $e) {
            Log::error('CustomFileUploadService: lỗi khi upload custom file vào storage', [
                'product_id' => $productId,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'file_name' => $originalName,
                'storage_path' => $filePath ?? null,
                'uploaded_path' => $uploadedPath ?? null,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'previous_exception' => $e->getPrevious() ? get_class($e->getPrevious()) : null,
                'previous_message' => $e->getPrevious() ? $e->getPrevious()->getMessage() : null,
            ]);

            // Clean up uploaded file if database save fails
            if (isset($uploadedPath)) {
                Storage::disk($disk ?? 'public')->delete($uploadedPath);
            }

            throw new Exception('File upload failed: ' . $e->getMessage());
        }
    }

    public function uploadMultipleFiles(
        array $files,
        int $productId,
        ?int $userId = null,
        ?string $sessionId = null,
        array $metadata = []
    ): array {
        $uploadedFiles = [];

        foreach ($files as $file) {
            try {
                $uploadedFile = $this->uploadCustomFile($file, $productId, $userId, $sessionId, $metadata);
                $uploadedFiles[] = $uploadedFile;
            } catch (Exception $e) {
                // Log error but continue with other files
                \Log::error('Failed to upload custom file: ' . $e->getMessage(), [
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        return $uploadedFiles;
    }

    public function getCustomFiles(
        int $productId,
        ?int $userId = null,
        ?string $sessionId = null
    ): \Illuminate\Database\Eloquent\Collection {
        return CustomFile::forUser($userId, $sessionId)
            ->forProduct($productId)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function deleteCustomFile(CustomFile $customFile): bool
    {
        try {
            // Delete from S3
            Storage::disk('s3')->delete($customFile->file_path);

            // Delete from database
            $customFile->delete();

            return true;
        } catch (Exception $e) {
            \Log::error('Failed to delete custom file: ' . $e->getMessage(), [
                'custom_file_id' => $customFile->id,
                'file_path' => $customFile->file_path,
            ]);

            return false;
        }
    }

    public function cleanupExpiredFiles(): int
    {
        $expiredFiles = CustomFile::expired()->get();
        $deletedCount = 0;

        foreach ($expiredFiles as $file) {
            if ($this->deleteCustomFile($file)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    public function extendFileExpiration(CustomFile $customFile, int $hours = 24): bool
    {
        try {
            $customFile->extendExpiration($hours);
            return true;
        } catch (Exception $e) {
            \Log::error('Failed to extend file expiration: ' . $e->getMessage(), [
                'custom_file_id' => $customFile->id,
            ]);
            return false;
        }
    }

    protected function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new Exception('File size exceeds maximum allowed size of ' . $this->formatBytes($this->maxFileSize));
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $this->getAllowedTypesList()));
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('File extension not allowed. Allowed extensions: ' . implode(', ', $this->allowedExtensions));
        }

        // Additional security checks
        $this->performSecurityChecks($file);
    }

    protected function performSecurityChecks(UploadedFile $file): void
    {
        // Check for malicious file content
        $content = file_get_contents($file->getPathname());

        // Check for executable content
        if (preg_match('/<\?php|<\?=|javascript:|vbscript:|onload=/i', $content)) {
            throw new Exception('File contains potentially malicious content');
        }

        // Check file header for image files
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $this->validateImageFile($file, $content);
        }
    }

    protected function validateImageFile(UploadedFile $file, string $content): void
    {
        $mimeType = $file->getMimeType();

        // Check file header
        $header = substr($content, 0, 10);

        switch ($mimeType) {
            case 'image/jpeg':
                if (!str_starts_with($header, "\xFF\xD8\xFF")) {
                    throw new Exception('Invalid JPEG file format');
                }
                break;
            case 'image/png':
                if (!str_starts_with($header, "\x89PNG")) {
                    throw new Exception('Invalid PNG file format');
                }
                break;
            case 'image/gif':
                if (!str_starts_with($header, "GIF8")) {
                    throw new Exception('Invalid GIF file format');
                }
                break;
        }
    }

    protected function generateFilename(string $originalName, int $productId, ?int $userId, ?string $sessionId): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        $ownerPart = $userId ? "user_{$userId}" : "session_{$sessionId}";

        // Ví dụ: custom_user_1_product_35_20260402080924_abcd1234.webp
        return "custom_{$ownerPart}_product_{$productId}_{$timestamp}_{$random}.{$extension}";
    }

    protected function getStoragePath(string $filename): string
    {
        // $filename đã bao gồm đầy đủ prefix thư mục (vd: "custom_files/user_1/product_35/xxx.webp")
        // Không được prepend thêm "custom_files/" lần nữa để tránh path kiểu "custom_files/custom_files/..."
        return $filename;
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    protected function getAllowedTypesList(): array
    {
        return [
            'Images: JPEG, PNG, GIF, WebP, SVG',
            'Videos: MP4, AVI, MOV, WMV',
            'Documents: PDF, DOC, DOCX, TXT'
        ];
    }

    public function getFileInfo(CustomFile $customFile): array
    {
        return [
            'id' => $customFile->id,
            'original_name' => $customFile->original_name,
            'file_url' => $customFile->file_url,
            'file_size' => $customFile->formatted_file_size,
            'mime_type' => $customFile->mime_type,
            'is_image' => $customFile->is_image,
            'is_video' => $customFile->is_video,
            'is_document' => $customFile->is_document,
            'uploaded_at' => $customFile->created_at->format('Y-m-d H:i:s'),
            'expires_at' => $customFile->expires_at?->format('Y-m-d H:i:s'),
            'is_expired' => $customFile->is_expired,
        ];
    }
}



