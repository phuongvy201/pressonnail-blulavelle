<?php
require __DIR__ . '/../../hm-fulfillment-system/vendor/autoload.php';

$app = require_once __DIR__ . '/../../hm-fulfillment-system/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$task = App\Models\DesignTask::find(4);
if (!$task) {
    echo "Task not found\n";
    exit;
}

$raw = $task->getRawOriginal('mockup_file');
$casted = $task->mockup_file;
echo "Raw: " . json_encode($raw) . "\n";
echo "Casted: " . json_encode($casted) . "\n";

if ($casted && is_array($casted) && !empty($casted[0])) {
    $path = $casted[0];
    echo "First element: $path\n";

    $cleaned = str_replace(['\\/', '\\', '/'], '/', trim($path));
    $cleaned = str_replace('%2B', '+', $cleaned);
    $cleaned = rawurldecode($cleaned);
    echo "Path to helper: $cleaned\n";

    $url = getMockupFileUrl($cleaned);
    echo "URL result: " . ($url ?: 'NULL') . "\n";
}

echo "\n--- R2 config check ---\n";
$r2 = config('filesystems.disks.r2');
echo "R2 bucket: " . ($r2['bucket'] ?? 'NULL') . "\n";
echo "R2 endpoint: " . ($r2['endpoint'] ?? 'NULL') . "\n";
echo "R2 key set: " . (!empty($r2['key']) ? 'YES' : 'NO') . "\n";
echo "R2 secret set: " . (!empty($r2['secret']) ? 'YES' : 'NO') . "\n";
