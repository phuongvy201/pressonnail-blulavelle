<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// use App\Models\ApiToken; // Public endpoints: no token required
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    /**
     * Create S3 client with acceleration enabled
     */
    private function createAcceleratedS3Client(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => config('filesystems.disks.s3.region', 'us-east-1'),
            'credentials' => [
                'key' => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
            'use_accelerate_endpoint' => true,
            'use_path_style_endpoint' => false, // S3 Accelerate requires virtual-hosted style
        ]);
    }

    /**
     * Generate presigned URLs for direct upload to S3
     */
    public function generatePresignedUrls(Request $request)
    {
        // Public endpoint: no API token or permission checks

        // Validate request - support both old and new field names
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1|max:10',
            'files.*.filename' => 'required_without:files.*.name|string|max:255',
            'files.*.name' => 'required_without:files.*.filename|string|max:255',
            'files.*.content_type' => 'required_without:files.*.type|string|in:image/jpeg,image/jpg,image/png,image/webp,video/mp4,video/avi,video/mov,video/webm',
            'files.*.type' => 'required_without:files.*.content_type|string|in:image/jpeg,image/jpg,image/png,image/webp,video/mp4,video/avi,video/mov,video/webm',
            'files.*.file_size' => 'nullable|integer|max:104857600', // 100MB max
            'files.*.size' => 'nullable|integer|max:104857600', // 100MB max
            'product_id' => 'nullable|exists:products,id', // For existing products
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        }

        try {
            // Initialize S3 client (accelerated)
            $s3Client = $this->createAcceleratedS3Client();

            $bucket = config('filesystems.disks.s3.bucket');
            $presignedUrls = [];
            $expiresIn = 15 * 60; // 15 minutes

            // Debug: Log current time
            Log::info('Current server time', [
                'now' => now()->toISOString(),
                'timestamp' => time(),
                'expires_in' => $expiresIn
            ]);

            foreach ($request->input('files') as $index => $file) {
                // Support both old and new field names
                $originalName = $file['filename'] ?? $file['name'];
                $contentType = $file['content_type'] ?? $file['type'];
                $fileSize = $file['file_size'] ?? $file['size'] ?? 0;

                // Generate unique filename
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $filename = time() . '_' . uniqid() . '_' . $index . '.' . $extension;

                // Determine folder based on file type
                $folder = strpos($contentType, 'video/') === 0 ? 'products/videos' : 'products/images';
                $key = $folder . '/' . $filename;

                // Generate presigned URL using correct AWS SDK v3 syntax
                $cmd = $s3Client->getCommand('PutObject', [
                    'Bucket' => $bucket,
                    'Key' => $key,
                    'ContentType' => $contentType,
                ]);

                $presignedRequest = $s3Client->createPresignedRequest($cmd, '+15 minutes');
                $presignedUrl = (string) $presignedRequest->getUri();

                // Debug: Log presigned URL details
                Log::info('Generated presigned URL', [
                    'key' => $key,
                    'expires_in' => $expiresIn,
                    'url' => $presignedUrl,
                    'current_time' => now()->toISOString(),
                    'expires_at' => now()->addSeconds($expiresIn)->toISOString()
                ]);

                // Generate accelerated public URL for after upload
                $publicUrl = "https://{$bucket}.s3-accelerate.amazonaws.com/{$key}";

                $presignedUrls[] = [
                    'index' => $index,
                    'filename' => $filename,
                    'original_name' => $originalName,
                    'type' => $contentType,
                    'size' => $fileSize,
                    'upload_url' => (string) $presignedUrl,
                    'final_url' => $publicUrl,
                    'key' => $key,
                    'expires_in' => $expiresIn,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Presigned URLs generated successfully',
                'data' => [
                    'presigned_urls' => $presignedUrls,
                    'expires_in' => $expiresIn,
                    'bucket' => $bucket,
                ]
            ], 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        } catch (AwsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate presigned URLs: ' . $e->getMessage()
            ], 500)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate presigned URLs: ' . $e->getMessage()
            ], 500)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        }
    }

    /**
     * Confirm upload completion and update product
     */
    public function confirmUpload(Request $request)
    {
        // Public endpoint: no API token required

        // Validate request
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'uploaded_files' => 'required|array',
            'uploaded_files.*.key' => 'required|string',
            'uploaded_files.*.public_url' => 'required|url',
            'uploaded_files.*.type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = \App\Models\Product::findOrFail($request->product_id);

            // Get current media
            $currentMedia = $product->media ?? [];

            // Add new uploaded files to media
            foreach ($request->uploaded_files as $file) {
                $currentMedia[] = $file['public_url'];
            }

            // Update product with new media
            $product->update([
                'media' => $currentMedia
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Upload confirmed and product updated',
                'data' => [
                    'product_id' => $product->id,
                    'media_count' => count($currentMedia),
                    'media' => $currentMedia
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize multipart upload (returns UploadId and key)
     */
    public function initMultipart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file.name' => 'required_without:file.filename|string|max:255',
            'file.filename' => 'required_without:file.name|string|max:255',
            'file.type' => 'required_without:file.content_type|string',
            'file.content_type' => 'required_without:file.type|string',
            'file.size' => 'required|integer|min:1',
            'product_id' => 'nullable|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        }

        try {
            $s3 = $this->createAcceleratedS3Client();
            $bucket = config('filesystems.disks.s3.bucket');

            $originalName = data_get($request->all(), 'file.filename') ?? data_get($request->all(), 'file.name');
            $contentType = data_get($request->all(), 'file.content_type') ?? data_get($request->all(), 'file.type');
            $size = (int) data_get($request->all(), 'file.size');

            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $folder = strpos($contentType, 'video/') === 0 ? 'products/videos' : 'products/images';
            $key = $folder . '/' . $filename;

            $result = $s3->createMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $key,
                'ContentType' => $contentType,
            ]);

            $uploadId = $result['UploadId'] ?? null;

            return response()->json([
                'success' => true,
                'message' => 'Multipart upload initialized',
                'data' => [
                    'bucket' => $bucket,
                    'key' => $key,
                    'upload_id' => $uploadId,
                    'accelerate_url' => "https://{$bucket}.s3-accelerate.amazonaws.com/{$key}",
                    'suggested_part_size' => max(5 * 1024 * 1024, min(15 * 1024 * 1024, (int) ceil($size / 8))),
                ],
            ], 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        } catch (AwsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to init multipart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate presigned URLs for multipart parts
     */
    public function getMultipartPartUrls(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'upload_id' => 'required|string',
            'parts' => 'required|integer|min:1|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        }

        try {
            $s3 = $this->createAcceleratedS3Client();
            $bucket = config('filesystems.disks.s3.bucket');
            $key = $request->input('key');
            $uploadId = $request->input('upload_id');
            $parts = (int) $request->input('parts');

            $urls = [];
            for ($partNumber = 1; $partNumber <= $parts; $partNumber++) {
                $cmd = $s3->getCommand('UploadPart', [
                    'Bucket' => $bucket,
                    'Key' => $key,
                    'UploadId' => $uploadId,
                    'PartNumber' => $partNumber,
                ]);
                $presigned = $s3->createPresignedRequest($cmd, '+15 minutes');
                $urls[] = [
                    'part_number' => $partNumber,
                    'url' => (string) $presigned->getUri(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Multipart part URLs generated',
                'data' => [
                    'parts' => $urls,
                ],
            ], 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        } catch (AwsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate part URLs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete multipart upload
     */
    public function completeMultipart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'upload_id' => 'required|string',
            'parts' => 'required|array|min:1',
            'parts.*.PartNumber' => 'required|integer|min:1',
            'parts.*.ETag' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        }

        try {
            $s3 = $this->createAcceleratedS3Client();
            $bucket = config('filesystems.disks.s3.bucket');

            $result = $s3->completeMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $request->input('key'),
                'UploadId' => $request->input('upload_id'),
                'MultipartUpload' => [
                    'Parts' => $request->input('parts'),
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Multipart upload completed',
                'data' => $result->toArray(),
            ], 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        } catch (AwsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete multipart: ' . $e->getMessage()
            ], 500);
        }
    }

    // Token validation removed
}
