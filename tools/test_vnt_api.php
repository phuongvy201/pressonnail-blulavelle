<?php
// Comprehensive virtual-nail API test
// Tests both /api/v1 (iOS) and /api (web) namespaces

$base = 'http://pressonnail.test';
$results = [];

function test($label, $url, $method = 'GET', $body = null, $expectCode = 200) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'X-Request-ID: vnt-test-' . uniqid(),
            'Accept: application/json',
        ],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        if (is_array($body) && isset($body['__multipart'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body['__multipart']);
            $headers = $body['__headers'] ?? [];
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
                'X-Request-ID: vnt-test-' . uniqid(),
                'Accept: application/json',
            ], $headers));
        }
    }
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($resp, 0, $headerSize);
    $body = substr($resp, $headerSize);
    curl_close($ch);

    $ok = $code === $expectCode;
    $bodyPreview = strlen($body) > 300 ? substr($body, 0, 300) . '...' : $body;
    $line = sprintf(
        "%s [%d] %s %s\n   body: %s",
        $ok ? '[OK]' : '[FAIL]',
        $code,
        $method,
        $url,
        $bodyPreview
    );
    return ['ok' => $ok, 'code' => $code, 'expected' => $expectCode, 'line' => $line, 'body' => $body];
}

echo "=== Virtual Nail API Test ===\n\n";

$tests = [
    ['1. v1 status',         $base . '/api/v1/virtual-nail/status',          'GET', null, 200],
    ['2. v1 picker-meta',    $base . '/api/v1/virtual-nail/picker-meta',     'GET', null, 200],
    ['3. v1 products',       $base . '/api/v1/virtual-nail/products',        'GET', null, 200],
    ['4. v1 products (perPage=5)', $base . '/api/v1/virtual-nail/products?perPage=5', 'GET', null, 200],
    ['5. v1 productSuggestions', $base . '/api/v1/virtual-nail/products/suggestions?q=almond', 'GET', null, 200],
    ['6. v1 product options 999', $base . '/api/v1/virtual-nail/products/999/options', 'GET', null, 404],
    ['7. v1 try (no image)',  $base . '/api/v1/virtual-nail/try',             'POST', [], 422],
    ['8. v1 history',        $base . '/api/v1/virtual-nail/history',         'GET', null, 200],
    ['9. v1 pending',        $base . '/api/v1/virtual-nail/pending',         'GET', null, 200],
    ['10. v1 trial-status random uuid', $base . '/api/v1/virtual-nail/trials/00000000-0000-0000-0000-000000000000/status', 'GET', null, 404],
    ['11. v1 trial-result random uuid', $base . '/api/v1/virtual-nail/trials/00000000-0000-0000-0000-000000000000/result', 'GET', null, 404],

    ['12. non-v1 status',     $base . '/api/virtual-nail/status',             'GET', null, 200],
    ['13. non-v1 picker-meta',$base . '/api/virtual-nail/picker-meta',        'GET', null, 200],
    ['14. non-v1 products',   $base . '/api/virtual-nail/products',           'GET', null, 200],
    ['15. non-v1 history',    $base . '/api/virtual-nail/history',            'GET', null, 200],

    // NailBox / Nail Try-On (iOS specific)
    ['16. v1 nail try-on-config product 1',  $base . '/api/v1/nail/products/1/try-on-config',  'GET', null, 200],
    ['17. v1 nail try-on-config missing',     $base . '/api/v1/nail/products/999999/try-on-config',  'GET', null, 404],
    ['18. v1 nail try-ons list',              $base . '/api/v1/nail/try-ons',  'GET', null, 200],
    ['19. v1 nail try-ons create (no body)',  $base . '/api/v1/nail/try-ons',  'POST', [], 422],
    ['20. v1 uploads presign',                $base . '/api/v1/uploads/presign', 'POST', '{"purpose":"virtual_try_on_hand","mimeType":"image/jpeg","size":1000,"checksum":"sha256:test"}', 422],
];

$total = 0; $ok = 0;
foreach ($tests as $t) {
    $total++;
    $r = test(...$t);
    if ($r['ok']) $ok++;
    echo $r['line'] . "\n\n";
}

echo "\n=== Summary: $ok / $total passed ===\n";
