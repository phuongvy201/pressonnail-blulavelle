<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "products: " . route('api.virtual-nail.products') . PHP_EOL;
echo "products.suggestions: " . route('api.virtual-nail.products.suggestions') . PHP_EOL;
echo "picker-meta: " . route('api.virtual-nail.picker-meta') . PHP_EOL;
echo "try: " . route('api.virtual-nail.try') . PHP_EOL;
echo "history: " . route('api.virtual-nail.history') . PHP_EOL;
echo "pending: " . route('api.virtual-nail.pending') . PHP_EOL;
echo "trials.status: " . route('api.virtual-nail.trials.status', ['uuid' => 'test']) . PHP_EOL;