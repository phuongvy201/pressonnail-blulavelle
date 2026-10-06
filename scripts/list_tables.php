<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = \DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $vals = array_values((array) $t);
    echo $vals[0] . PHP_EOL;
}

echo PHP_EOL . '--- Counts ---' . PHP_EOL;
echo 'products: ' . \DB::table('products')->count() . PHP_EOL;
echo 'nail_try_ons: ' . \DB::table('nail_try_ons')->count() . PHP_EOL;
echo 'virtual_nail_trials: ' . \DB::table('virtual_nail_trials')->count() . PHP_EOL;
echo 'product_collections: ' . \DB::table('product_collections')->count() . PHP_EOL;
echo 'users: ' . \DB::table('users')->count() . PHP_EOL;