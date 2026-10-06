<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Helper to simulate API calls via Laravel's testing layer
function callEndpoint(string $method, string $uri, array $payload = [], array $headers = []): array {
    $req = Request::create($uri, $method, [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], json_encode($payload));

    foreach ($headers as $name => $value) {
        $req->headers->set($name, $value);
    }

    try {
        $response = app()->handle($req);
        $body = $response->getContent();
        $decoded = json_decode($body, true);
        return [
            'status' => $response->getStatusCode(),
            'body' => $decoded ?? $body,
        ];
    } catch (\Throwable $e) {
        return [
            'status' => 500,
            'body' => ['error' => $e->getMessage(), 'class' => get_class($e)],
        ];
    }
}

echo "===== V1 Virtual Nail Try-On API tests =====" . PHP_EOL . PHP_EOL;

// 1. Status
echo "[1] GET /api/v1/virtual-nail/status\n";
$r = callEndpoint('GET', '/api/v1/virtual-nail/status');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 2. Picker meta
echo "[2] GET /api/v1/virtual-nail/picker-meta\n";
$r = callEndpoint('GET', '/api/v1/virtual-nail/picker-meta');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 3. Products
echo "[3] GET /api/v1/virtual-nail/products\n";
$r = callEndpoint('GET', '/api/v1/virtual-nail/products?perPage=3');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 4. Suggestions
echo "[4] GET /api/v1/virtual-nail/products/suggestions\n";
$r = callEndpoint('GET', '/api/v1/virtual-nail/products/suggestions?q=french&limit=5');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 5. History
echo "[5] GET /api/v1/virtual-nail/history\n";
$r = callEndpoint('GET', '/api/v1/virtual-nail/history');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 6. Pending (this is the one with the known bug)
echo "[6] GET /api/v1/virtual-nail/pending\n";
$r = callEndpoint('GET', '/api/v1/virtual-nail/pending');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 7. Try-on config for a fake product
echo "[7] GET /api/v1/nail/products/1/try-on-config\n";
$r = callEndpoint('GET', '/api/v1/nail/products/1/try-on-config');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 8. Nail try-ons list
echo "[8] GET /api/v1/nail/try-ons\n";
$r = callEndpoint('GET', '/api/v1/nail/try-ons');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 9. Try-on store (will fail because no products)
echo "[9] POST /api/v1/nail/try-ons (no idempotency key)\n";
$r = callEndpoint('POST', '/api/v1/nail/try-ons', [
    'handImageAssetId' => 'asset_hand_01hxyz',
    'productId' => 1,
]);
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 10. Try-on show
echo "[10] GET /api/v1/nail/try-ons/tryon_01hxyz\n";
$r = callEndpoint('GET', '/api/v1/nail/try-ons/tryon_01hxyz');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 11. Presign upload (with valid auth)
echo "[11] POST /api/v1/uploads/presign\n";
$r = callEndpoint('POST', '/api/v1/uploads/presign', [
    'purpose' => 'virtual_try_on_hand',
    'mimeType' => 'image/jpeg',
    'size' => 1048576,
    'checksum' => 'sha256:e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
], ['X-Guest-Cart-Token' => 'test-guest-token-1234567890abcdef1234567890ab']);
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;