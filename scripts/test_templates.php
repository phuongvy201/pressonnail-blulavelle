<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$templates = \App\Models\ProductTemplate::query()->take(5)->get(['id', 'name']);
foreach ($templates as $t) {
    echo "{$t->id} | {$t->name}" . PHP_EOL;
}

echo PHP_EOL . '--- Product templates count ---' . PHP_EOL;
echo 'Total templates: ' . \App\Models\ProductTemplate::count() . PHP_EOL;
echo 'Active templates: ' . \App\Models\ProductTemplate::where('is_active', true)->count() . PHP_EOL;