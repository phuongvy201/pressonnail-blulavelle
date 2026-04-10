<?php

use App\Models\Product;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('products:export {--format=csv : Export format: csv|json} {--path= : Custom relative path inside storage/app}', function () {
    $format = strtolower((string) $this->option('format'));
    if (!in_array($format, ['csv', 'json'], true)) {
        $this->error('Invalid format. Use --format=csv or --format=json');
        return self::FAILURE;
    }

    $defaultFileName = 'products_' . now()->format('Ymd_His') . '.' . $format;
    $relativePath = trim((string) ($this->option('path') ?: ('exports/' . $defaultFileName)));
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

    if ($relativePath === '') {
        $this->error('Invalid path.');
        return self::FAILURE;
    }

    $products = Product::query()
        ->with(['template:id,name', 'shop:id,shop_name', 'user:id,name,email'])
        ->orderBy('id')
        ->get();

    Storage::disk('local')->makeDirectory(dirname($relativePath));
    $absolutePath = storage_path('app/' . $relativePath);

    if ($format === 'json') {
        $payload = $products->map(function (Product $product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => $product->price,
                'list_price' => $product->list_price,
                'quantity' => $product->quantity,
                'status' => $product->status,
                'template' => $product->template?->name,
                'shop' => $product->shop?->shop_name,
                'owner_name' => $product->user?->name,
                'owner_email' => $product->user?->email,
                'created_at' => optional($product->created_at)->toDateTimeString(),
                'updated_at' => optional($product->updated_at)->toDateTimeString(),
            ];
        })->values();

        Storage::disk('local')->put(
            $relativePath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    } else {
        $handle = fopen($absolutePath, 'wb');
        if ($handle === false) {
            $this->error('Unable to create export file at: ' . $absolutePath);
            return self::FAILURE;
        }

        fputcsv($handle, [
            'id',
            'name',
            'slug',
            'sku',
            'price',
            'list_price',
            'quantity',
            'status',
            'template',
            'shop',
            'owner_name',
            'owner_email',
            'created_at',
            'updated_at',
        ]);

        foreach ($products as $product) {
            fputcsv($handle, [
                $product->id,
                $product->name,
                $product->slug,
                $product->sku,
                $product->price,
                $product->list_price,
                $product->quantity,
                $product->status,
                $product->template?->name,
                $product->shop?->shop_name,
                $product->user?->name,
                $product->user?->email,
                optional($product->created_at)->toDateTimeString(),
                optional($product->updated_at)->toDateTimeString(),
            ]);
        }

        fclose($handle);
    }

    $this->info('Export completed.');
    $this->line('Products: ' . $products->count());
    $this->line('Format: ' . strtoupper($format));
    $this->line('File: ' . $absolutePath);

    return self::SUCCESS;
})->purpose('Export all products to storage/app file (csv or json).');
