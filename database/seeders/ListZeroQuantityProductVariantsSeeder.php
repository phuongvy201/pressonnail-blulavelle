<?php

namespace Database\Seeders;

use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ListZeroQuantityProductVariantsSeeder extends Seeder
{
    /**
     * Liệt kê mọi product variant đang có quantity = 0 (không đổi dữ liệu).
     *
     * Chạy: php artisan db:seed --class=ListZeroQuantityProductVariantsSeeder
     */
    public function run(): void
    {
        $variants = ProductVariant::query()
            ->with(['product:id,name,slug', 'template:id,name'])
            ->where('quantity', 0)
            ->orderBy('id')
            ->get();

        if ($variants->isEmpty()) {
            $this->command->info('Không có variant nào với quantity = 0.');

            return;
        }

        $this->command->info('Tìm thấy '.$variants->count().' variant có quantity = 0:');
        $this->command->newLine();

        $rows = $variants->map(fn (ProductVariant $variant) => [
            $variant->id,
            $variant->sku ?: '—',
            $variant->variant_name ?: '—',
            $variant->product_id ?? '—',
            $variant->product?->name ?: '—',
            $variant->quantity,
        ])->all();

        $this->command->table(
            ['ID', 'SKU', 'Variant', 'Product ID', 'Product', 'Qty'],
            $rows,
        );
    }
}
