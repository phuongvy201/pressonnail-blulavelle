<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
    use App\Models\CustomFile;
use App\Models\Product;
use App\Services\CustomFileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CustomFileController extends Controller
{
    protected $uploadService;

    public function __construct(CustomFileUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Upload custom files for a product
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'files' => 'required|array|min:1|max:5',
                'files.*' => 'required|file|max:10240', // 10MB max per file
                'metadata' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $productId = $request->product_id;
            $files = $request->file('files');
            $metadata = $request->input('metadata', []);

            // Get user info
            $userId = auth()->id();
            $sessionId = $userId ? null : session()->getId();

            // Check if product exists (bỏ lọc is_active để vẫn cho upload khi preview / sản phẩm tạm tắt)
            // Dùng withoutGlobalScopes để không bị global scope "active" ẩn sản phẩm.
            $product = Product::withoutGlobalScopes()->find($productId);
            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Upload files
            $uploadedFiles = $this->uploadService->uploadMultipleFiles(
                $files,
                $productId,
                $userId,
                $sessionId,
                $metadata
            );

            if (empty($uploadedFiles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files were uploaded successfully'
                ], 400);
            }

            // Prepare response data
            $fileData = [];
            foreach ($uploadedFiles as $file) {
                $fileData[] = $this->uploadService->getFileInfo($file);
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => [
                    'files' => $fileData,
                    'uploaded_count' => count($uploadedFiles),
                    'total_count' => count($files)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Custom file upload failed', [
                'error' => $e->getMessage(),
                'product_id' => $request->product_id,
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get custom files for a product
     */
    public function getFiles(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $productId = $request->product_id;
            $userId = auth()->id();
            $sessionId = $userId ? null : session()->getId();

            $files = $this->uploadService->getCustomFiles($productId, $userId, $sessionId);

            $fileData = [];
            foreach ($files as $file) {
                $fileData[] = $this->uploadService->getFileInfo($file);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'files' => $fileData,
                    'count' => count($fileData)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get custom files', [
                'error' => $e->getMessage(),
                'product_id' => $request->product_id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve files'
            ], 500);
        }
    }

    /**
     * Delete a custom file
     */
    public function delete(Request $request, $fileId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $sessionId = $userId ? null : session()->getId();

            // Find file
            $file = CustomFile::where('id', $fileId)
                ->where(function ($query) use ($userId, $sessionId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })
                ->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or access denied'
                ], 404);
            }

            // Delete file
            $deleted = $this->uploadService->deleteCustomFile($file);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete file'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete custom file', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file'
            ], 500);
        }
    }

    /**
     * Extend file expiration
     */
    public function extendExpiration(Request $request, $fileId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'hours' => 'sometimes|integer|min:1|max:168' // Max 1 week
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = auth()->id();
            $sessionId = $userId ? null : session()->getId();
            $hours = $request->input('hours', 24);

            // Find file
            $file = CustomFile::where('id', $fileId)
                ->where(function ($query) use ($userId, $sessionId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })
                ->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or access denied'
                ], 404);
            }

            // Extend expiration
            $extended = $this->uploadService->extendFileExpiration($file, $hours);

            if (!$extended) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to extend file expiration'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'File expiration extended successfully',
                'data' => [
                    'expires_at' => $file->fresh()->expires_at?->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to extend file expiration', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to extend file expiration'
            ], 500);
        }
    }

    /**
     * Get upload limits and allowed file types
     */
    public function getUploadInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'max_file_size' => '10MB',
                'max_files' => 5,
                'allowed_types' => [
                    'Images: JPEG, PNG, GIF, WebP, SVG',
                    'Videos: MP4, AVI, MOV, WMV',
                    'Documents: PDF, DOC, DOCX, TXT'
                ],
                'allowed_extensions' => [
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
                ]
            ]
        ]);
    }

    /**
     * Cleanup expired files (admin only)
     */
    public function cleanupExpired(): JsonResponse
    {
        try {
            // Check if user is admin
            if (!auth()->user() || !auth()->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $deletedCount = $this->uploadService->cleanupExpiredFiles();

            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deletedCount} expired files"
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired files', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup expired files'
            ], 500);
        }
    }
}
