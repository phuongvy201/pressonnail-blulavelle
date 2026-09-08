<?php

use App\Jobs\ProcessNailTryOnJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
});

function makeJpegBytes(int $width = 400, int $height = 400): string
{
    $img = imagecreatetruecolor($width, $height);
    $bg = imagecolorallocate($img, 200, 180, 160);
    imagefilledrectangle($img, 0, 0, $width, $height, $bg);
    ob_start();
    imagejpeg($img, null, 90);
    $binary = ob_get_clean();
    imagedestroy($img);

    return $binary;
}

it('presigns completes hand upload for guest', function () {
    $binary = makeJpegBytes();
    $checksum = hash('sha256', $binary);
    $guest = str_repeat('a', 40);

    $presign = $this->withHeaders([
        'X-Guest-Cart-Token' => $guest,
        'Accept' => 'application/json',
    ])->postJson('/api/v1/uploads/presign', [
        'purpose' => 'virtual_try_on_hand',
        'mimeType' => 'image/jpeg',
        'size' => strlen($binary),
        'checksum' => 'sha256:'.$checksum,
    ]);

    $presign->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['uploadId', 'uploadUrl', 'headers', 'expiresAt']]);

    $uploadId = $presign->json('data.uploadId');
    $uploadUrl = $presign->json('data.uploadUrl');
    $token = $presign->json('data.headers.X-Upload-Token');

    $put = $this->call(
        'PUT',
        $uploadUrl,
        [],
        [],
        [],
        [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_UPLOAD_TOKEN' => $token,
            'CONTENT_TYPE' => 'image/jpeg',
        ],
        $binary
    );
    expect($put->getStatusCode())->toBe(200);

    $this->withHeaders([
        'X-Guest-Cart-Token' => $guest,
        'Accept' => 'application/json',
    ])->postJson('/api/v1/uploads/'.$uploadId.'/complete')
        ->assertOk()
        ->assertJsonPath('data.handImageAssetId', $uploadId)
        ->assertJsonPath('data.status', 'ready');
});

it('rejects foreign guest from completing upload', function () {
    $binary = makeJpegBytes();
    $checksum = hash('sha256', $binary);

    $presign = $this->withHeaders([
        'X-Guest-Cart-Token' => str_repeat('b', 40),
        'Accept' => 'application/json',
    ])->postJson('/api/v1/uploads/presign', [
        'purpose' => 'virtual_try_on_hand',
        'mimeType' => 'image/jpeg',
        'size' => strlen($binary),
        'checksum' => $checksum,
    ])->assertCreated();

    $uploadId = $presign->json('data.uploadId');

    $this->withHeaders([
        'X-Guest-Cart-Token' => str_repeat('c', 40),
        'Accept' => 'application/json',
    ])->postJson('/api/v1/uploads/'.$uploadId.'/complete')
        ->assertForbidden()
        ->assertJsonPath('error.code', 'ASSET_NOT_OWNED');
});

it('requires idempotency key for try-on create', function () {
    $this->withHeaders([
        'X-Guest-Cart-Token' => str_repeat('d', 40),
        'Accept' => 'application/json',
    ])->postJson('/api/v1/nail/try-ons', [
        'handImageAssetId' => 'asset_hand_missing',
        'productId' => 1,
    ])->assertStatus(400)
        ->assertJsonPath('error.code', 'IDEMPOTENCY_KEY_REQUIRED');
});

it('rejects try-on for missing product with stable code', function () {
    $binary = makeJpegBytes();
    $checksum = hash('sha256', $binary);
    $guest = str_repeat('f', 40);

    $presign = $this->withHeaders([
        'X-Guest-Cart-Token' => $guest,
        'Accept' => 'application/json',
    ])->postJson('/api/v1/uploads/presign', [
        'purpose' => 'virtual_try_on_hand',
        'mimeType' => 'image/jpeg',
        'size' => strlen($binary),
        'checksum' => $checksum,
    ])->assertCreated();

    $uploadId = $presign->json('data.uploadId');
    $token = $presign->json('data.headers.X-Upload-Token');
    $uploadUrl = $presign->json('data.uploadUrl');

    $this->call('PUT', $uploadUrl, [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_UPLOAD_TOKEN' => $token,
        'CONTENT_TYPE' => 'image/jpeg',
    ], $binary);

    $this->withHeaders([
        'X-Guest-Cart-Token' => $guest,
        'Accept' => 'application/json',
    ])->postJson('/api/v1/uploads/'.$uploadId.'/complete')->assertOk();

    $this->withHeaders([
        'X-Guest-Cart-Token' => $guest,
        'Accept' => 'application/json',
        'Idempotency-Key' => 'tryon-missing-product-001',
    ])->postJson('/api/v1/nail/try-ons', [
        'handImageAssetId' => $uploadId,
        'productId' => 999999,
        'handSide' => 'auto',
    ])->assertStatus(422)
        ->assertJsonPath('error.code', 'TRY_ON_NOT_AVAILABLE_FOR_PRODUCT');

    Queue::assertNothingPushed();
});
