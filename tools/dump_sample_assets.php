<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$publicId = $argv[1] ?? 'asset_VCgVcscEwKedUluKIOVIdFdJ';

$rows = \App\Models\ApiUploadAsset::query()
    ->where('public_id', $publicId)
    ->orWhere('public_id', 'like', 'asset_sample_%')
    ->orWhere('public_id', 'like', 'asset_hand_%')
    ->orWhere('public_id', 'like', 'asset_V%')
    ->orderByDesc('id')
    ->limit(20)
    ->get(['id', 'public_id', 'purpose', 'status', 'path', 'disk', 'byte_size', 'mime_type', 'checksum_sha256', 'created_at', 'deleted_at'])
    ->map(fn ($a) => [
        'id' => $a->id,
        'public_id' => $a->public_id,
        'purpose' => $a->purpose,
        'status' => $a->status,
        'disk' => $a->disk,
        'path' => $a->path,
        'byte' => $a->byte_size,
        'mime' => $a->mime_type,
        'has_checksum' => ! empty($a->checksum_sha256),
        'created' => $a->created_at?->toDateTimeString(),
        'deleted' => $a->deleted_at?->toDateTimeString(),
    ])
    ->values()
    ->all();

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
