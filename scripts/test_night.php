<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Products: " . \App\Models\Product::count() . PHP_EOL;
echo "Templates: " . \App\Models\ProductTemplate::count() . PHP_EOL;
echo "Shops: " . \App\Models\Shop::count() . PHP_EOL;
echo "Categories: " . \App\Models\Category::count() . PHP_EOL;
echo "Collections: " . \App\Models\Collection::count() . PHP_EOL;
echo "Users: " . \App\Models\User::count() . PHP_EOL;
echo "Virtual Nail Trials: " . \App\Models\VirtualNailTrial::count() . PHP_EOL;
echo "Nail TryOns: " . \App\Models\NailTryOn::count() . PHP_EOL;