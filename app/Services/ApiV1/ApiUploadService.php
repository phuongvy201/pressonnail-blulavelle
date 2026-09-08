<?php

namespace App\Services\ApiV1;

use App\Models\ApiUploadAsset;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ApiUploadService
{
    public function __construct()
    {
    }

    public function ownerFrom(Request $request): array
    {
        return [
            'user_id' => $request->user()?->id,
            'guest_token' => $request->header(config('api_v1.guest_cart_header', 'X-Guest-Cart-Token')),
        ];
    }

    public function findOwned(string $publicId, Request $request): ?ApiUploadAsset
    {
        $asset = ApiUploadAsset::query()->where('public_id', $publicId)->first();
        if (! $asset) {
            return null;
        }

        $owner = $this->ownerFrom($request);
        if (! $asset->isOwnedBy($owner['user_id'], $owner['guest_token'])) {
            return null;
        }

        return $asset;
    }

    public function presign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', 'string', 'in:'.ApiUploadAsset::PURPOSE_VIRTUAL_TRY_ON_HAND],
            'mimeType' => ['required', 'string', 'in:image/jpeg,image/jpg,image/png,image/webp'],
            'size' => ['required', 'integer', 'min:1024', 'max:'.($this->maxBytes())],
            'checksum' => ['required', 'string', 'regex:/^(sha256:)?[a-fA-F0-9]{64}$/'],
        ]);

        $owner = $this->ownerFrom($request);
        if (! $owner['user_id'] && ! $owner['guest_token']) {
            return ApiResponse::error(
                'UNAUTHENTICATED',
                'Send Authorization Bearer or X-Guest-Cart-Token before uploading.',
                401
            );
        }

        $checksum = strtolower(preg_replace('/^sha256:/i', '', $validated['checksum']));
        $ttlMinutes = (int) config('api_v1.upload_ttl_minutes', 15);
        $token = Str::random(40);

        $asset = ApiUploadAsset::query()->create([
            'user_id' => $owner['user_id'],
            'guest_token' => $owner['guest_token'],
            'purpose' => $validated['purpose'],
            'mime_type' => strtolower($validated['mimeType']) === 'image/jpg' ? 'image/jpeg' : strtolower($validated['mimeType']),
            'expected_size' => (int) $validated['size'],
            'checksum_sha256' => $checksum,
            'disk' => config('api_v1.upload_disk', 'local'),
            'status' => ApiUploadAsset::STATUS_PENDING,
            'upload_token' => hash('sha256', $token),
            'upload_expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        $uploadUrl = URL::temporarySignedRoute(
            'api.v1.uploads.content',
            now()->addMinutes($ttlMinutes),
            ['uploadId' => $asset->public_id, 'uploadToken' => $token]
        );

        return ApiResponse::success([
            'uploadId' => $asset->public_id,
            'uploadUrl' => $uploadUrl,
            'method' => 'PUT',
            'headers' => [
                'Content-Type' => $asset->mime_type,
                'X-Upload-Token' => $token,
            ],
            'expiresAt' => ApiResponse::iso($asset->upload_expires_at),
            'purpose' => $asset->purpose,
            'maxBytes' => $this->maxBytes(),
        ], null, 201);
    }

    public function putContent(Request $request, string $uploadId): JsonResponse
    {
        $asset = ApiUploadAsset::query()->where('public_id', $uploadId)->first();
        if (! $asset || $asset->trashed()) {
            return ApiResponse::error('NOT_FOUND', 'Upload not found.', 404);
        }

        if ($asset->isExpired() || ($asset->upload_expires_at && $asset->upload_expires_at->isPast())) {
            $asset->update(['status' => ApiUploadAsset::STATUS_EXPIRED]);

            return ApiResponse::error('ASSET_EXPIRED', 'Upload window expired. Request a new presign.', 410);
        }

        $token = (string) ($request->header('X-Upload-Token') ?: $request->query('uploadToken'));
        if ($token === '' || ! hash_equals((string) $asset->upload_token, hash('sha256', $token))) {
            return ApiResponse::error('FORBIDDEN', 'Invalid upload token.', 403);
        }

        if ($asset->status === ApiUploadAsset::STATUS_READY) {
            return ApiResponse::success([
                'uploadId' => $asset->public_id,
                'status' => $asset->status,
            ]);
        }

        $binary = $request->getContent();
        if ($binary === '' || $binary === false) {
            return ApiResponse::error('UNSUPPORTED_IMAGE', 'Empty upload body.', 422);
        }

        $size = strlen($binary);
        if ($size > $this->maxBytes()) {
            return ApiResponse::error('UNSUPPORTED_IMAGE', 'Image exceeds maximum allowed size.', 422);
        }

        if (abs($size - (int) $asset->expected_size) > max(1024, (int) ($asset->expected_size * 0.02))) {
            return ApiResponse::error('UNSUPPORTED_IMAGE', 'Uploaded size does not match declared size.', 422);
        }

        $checksum = hash('sha256', $binary);
        if (! hash_equals($asset->checksum_sha256, $checksum)) {
            return ApiResponse::error('UNSUPPORTED_IMAGE', 'Checksum mismatch.', 422);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($binary) ?: '';
        if (! in_array($detected, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return ApiResponse::error('UNSUPPORTED_IMAGE', 'MIME type is not an allowed image.', 422);
        }

        $cleaned = $this->stripExif($binary, $detected);
        if ($cleaned === null) {
            return ApiResponse::error('UNSUPPORTED_IMAGE', 'Could not process image pixels.', 422);
        }

        [$width, $height] = $this->dimensions($cleaned['binary']);
        $minSide = (int) config('api_v1.upload_min_side', 256);
        $maxSide = (int) config('api_v1.upload_max_side', 4096);
        if ($width < $minSide || $height < $minSide) {
            return ApiResponse::error('UNSUPPORTED_IMAGE', 'Image resolution is too small.', 422);
        }
        if ($width > $maxSide || $height > $maxSide) {
            return ApiResponse::error('UNSUPPORTED_IMAGE', 'Image resolution is too large.', 422);
        }

        $ext = match ($cleaned['mime']) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $path = 'api-uploads/'.$asset->public_id.'/original.'.$ext;
        Storage::disk($asset->disk)->put($path, $cleaned['binary']);

        $asset->update([
            'path' => $path,
            'mime_type' => $cleaned['mime'],
            'byte_size' => strlen($cleaned['binary']),
            'width' => $width,
            'height' => $height,
            'checksum_sha256' => hash('sha256', $cleaned['binary']),
            'status' => ApiUploadAsset::STATUS_UPLOADING,
        ]);

        return ApiResponse::success([
            'uploadId' => $asset->public_id,
            'status' => $asset->status,
            'bytesReceived' => (int) $asset->byte_size,
        ]);
    }

    public function complete(Request $request, string $uploadId): JsonResponse
    {
        $asset = $this->findOwned($uploadId, $request);
        if (! $asset) {
            return ApiResponse::error('ASSET_NOT_OWNED', 'Upload not found or not owned by caller.', 403);
        }

        if ($asset->status === ApiUploadAsset::STATUS_READY) {
            return ApiResponse::success($this->readyPayload($asset));
        }

        if ($asset->isExpired()) {
            return ApiResponse::error('ASSET_EXPIRED', 'Upload expired.', 410);
        }

        if (! $asset->path || ! Storage::disk($asset->disk)->exists($asset->path)) {
            return ApiResponse::error('UNSUPPORTED_IMAGE', 'Upload content missing. PUT binary first.', 422);
        }

        $asset->update([
            'status' => ApiUploadAsset::STATUS_READY,
            'completed_at' => now(),
            'upload_token' => null,
        ]);

        return ApiResponse::success($this->readyPayload($asset->fresh()));
    }

    public function destroy(Request $request, string $uploadId): JsonResponse
    {
        $asset = $this->findOwned($uploadId, $request);
        if (! $asset) {
            return ApiResponse::error('ASSET_NOT_OWNED', 'Upload not found or not owned by caller.', 403);
        }

        $this->purge($asset);

        return ApiResponse::success(['deleted' => true, 'uploadId' => $uploadId]);
    }

    public function purge(ApiUploadAsset $asset): void
    {
        if ($asset->path) {
            Storage::disk($asset->disk)->delete($asset->path);
        }

        $asset->update([
            'status' => ApiUploadAsset::STATUS_DELETED,
            'path' => null,
            'upload_token' => null,
        ]);
        $asset->delete();
    }

    public function createOutputFromBinary(
        string $binary,
        string $mime,
        ?int $userId,
        ?string $guestToken
    ): ApiUploadAsset {
        $publicId = 'asset_out_'.Str::lower((string) Str::ulid());
        $ext = $mime === 'image/png' ? 'png' : 'jpg';
        $path = 'api-uploads/'.$publicId.'/result.'.$ext;
        $disk = config('api_v1.upload_disk', 'local');
        Storage::disk($disk)->put($path, $binary);
        [$width, $height] = $this->dimensions($binary);

        return ApiUploadAsset::query()->create([
            'public_id' => $publicId,
            'user_id' => $userId,
            'guest_token' => $guestToken,
            'purpose' => ApiUploadAsset::PURPOSE_VIRTUAL_TRY_ON_OUTPUT,
            'mime_type' => $mime,
            'expected_size' => strlen($binary),
            'checksum_sha256' => hash('sha256', $binary),
            'disk' => $disk,
            'path' => $path,
            'width' => $width,
            'height' => $height,
            'byte_size' => strlen($binary),
            'status' => ApiUploadAsset::STATUS_READY,
            'completed_at' => now(),
        ]);
    }

    public function temporaryUrl(ApiUploadAsset $asset, int $minutes = 15): ?string
    {
        if (! $asset->isReady() || ! $asset->path) {
            return null;
        }

        return URL::temporarySignedRoute(
            'api.v1.uploads.download',
            now()->addMinutes($minutes),
            ['uploadId' => $asset->public_id]
        );
    }

    public function streamDownload(string $uploadId)
    {
        $asset = ApiUploadAsset::query()->where('public_id', $uploadId)->first();
        if (! $asset || ! $asset->isReady() || ! $asset->path) {
            abort(404);
        }

        $disk = Storage::disk($asset->disk);
        if (! $disk->exists($asset->path)) {
            abort(404);
        }

        return response($disk->get($asset->path), 200, [
            'Content-Type' => $asset->mime_type,
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => 'inline; filename="'.$asset->public_id.'"',
        ]);
    }

    private function readyPayload(ApiUploadAsset $asset): array
    {
        return [
            'uploadId' => $asset->public_id,
            'handImageAssetId' => $asset->public_id,
            'status' => $asset->status,
            'mimeType' => $asset->mime_type,
            'size' => $asset->byte_size,
            'width' => $asset->width,
            'height' => $asset->height,
            'checksum' => 'sha256:'.$asset->checksum_sha256,
            'completedAt' => ApiResponse::iso($asset->completed_at),
        ];
    }

    private function maxBytes(): int
    {
        return ((int) config('virtual_nail.max_upload_kb', 8192)) * 1024;
    }

    /**
     * @return array{binary: string, mime: string}|null
     */
    private function stripExif(string $binary, string $mime): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return ['binary' => $binary, 'mime' => $mime];
        }

        $img = @imagecreatefromstring($binary);
        if ($img === false) {
            return null;
        }

        ob_start();
        if ($mime === 'image/png') {
            imagesavealpha($img, true);
            imagepng($img, null, 6);
            $outMime = 'image/png';
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            imagewebp($img, null, 85);
            $outMime = 'image/webp';
        } else {
            imagejpeg($img, null, 90);
            $outMime = 'image/jpeg';
        }
        $out = ob_get_clean();
        imagedestroy($img);

        if ($out === false || $out === '') {
            return null;
        }

        return ['binary' => $out, 'mime' => $outMime];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function dimensions(string $binary): array
    {
        $info = @getimagesizefromstring($binary);
        if (! is_array($info)) {
            return [0, 0];
        }

        return [(int) $info[0], (int) $info[1]];
    }
}
