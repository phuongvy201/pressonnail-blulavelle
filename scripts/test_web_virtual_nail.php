<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

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

echo "===== Web (non-V1) Virtual Nail API tests =====" . PHP_EOL . PHP_EOL;

// 1. Status
echo "[1] GET /api/virtual-nail/status\n";
$r = callEndpoint('GET', '/api/virtual-nail/status');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 2. Picker meta
echo "[2] GET /api/virtual-nail/picker-meta\n";
$r = callEndpoint('GET', '/api/virtual-nail/picker-meta');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 3. Products
echo "[3] GET /api/virtual-nail/products\n";
$r = callEndpoint('GET', '/api/virtual-nail/products?perPage=3');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 4. Suggestions
echo "[4] GET /api/virtual-nail/products/suggestions\n";
$r = callEndpoint('GET', '/api/virtual-nail/products/suggestions?q=french&limit=5');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 5. History
echo "[5] GET /api/virtual-nail/history\n";
$r = callEndpoint('GET', '/api/virtual-nail/history');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 6. Pending
echo "[6] GET /api/virtual-nail/pending\n";
$r = callEndpoint('GET', '/api/virtual-nail/pending');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 7. Virtual-nail try (no body, will fail validation)
echo "[7] POST /api/virtual-nail/try (no body)\n";
$r = callEndpoint('POST', '/api/virtual-nail/try', []);
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;

// 8. Trial status
echo "[8] GET /api/virtual-nail/trials/non-existent-uuid/status\n";
$r = callEndpoint('GET', '/api/virtual-nail/trials/non-existent-uuid/status');
echo "  -> status: {$r['status']}\n";
echo "  -> body: " . json_encode($r['body']) . PHP_EOL . PHP_EOL;