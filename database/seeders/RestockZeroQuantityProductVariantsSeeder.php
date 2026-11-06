<?php

namespace Database\Seeders;

use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RestockZeroQuantityProductVariantsSeeder extends Seeder
{
    /**
     * Đổi quantity = 0 → 100 cho mọi product variant.
     *
     * Chạy: php artisan db:seed --class=RestockZeroQuantityProductVariantsSeeder
     *
     * Nên chạy ListZeroQuantityProductVariantsSeeder trước để xem danh sách.
     */
    public function run(): void
    {
        $targetQuantity = 100;

        $count = ProductVariant::query()->where('quantity', 0)->count();
        if ($count === 0) {
            $this->command->info('Không có variant nào với quantity = 0 để cập nhật.');

            return;
        }

        $updated = DB::transaction(function () use ($targetQuantity) {
            return ProductVariant::query()
                ->where('quantity', 0)
                ->update(['quantity' => $targetQuantity]);
        });

        $this->command->info("Đã cập nhật {$updated} variant: quantity 0 → {$targetQuantity}.");
    }
}
