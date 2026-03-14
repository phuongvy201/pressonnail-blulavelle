<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        PromoCode::updateOrCreate(
            ['code' => 'SAVE10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'min_order_value' => 30,
                'max_uses' => null,
                'used_count' => 0,
                'starts_at' => null,
                'expires_at' => null,
                'is_active' => true,
                'send_on_trigger' => null,
            ]
        );

        PromoCode::updateOrCreate(
            ['code' => 'FLAT5'],
            [
                'type' => 'fixed',
                'value' => 5,
                'min_order_value' => 20,
                'max_uses' => null,
                'used_count' => 0,
                'starts_at' => null,
                'expires_at' => null,
                'is_active' => true,
                'send_on_trigger' => null,
            ]
        );

        // Mã gửi qua email khi Thank you (sau khi đặt hàng)
        PromoCode::updateOrCreate(
            ['code' => 'THANKYOU10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'min_order_value' => 50,
                'max_uses' => null,
                'used_count' => 0,
                'starts_at' => null,
                'expires_at' => null,
                'is_active' => true,
                'send_on_trigger' => 'thank_you',
            ]
        );

        // Mã gửi khi thêm vào Wishlist (favorite)
        PromoCode::updateOrCreate(
            ['code' => 'FAVORITE5'],
            [
                'type' => 'fixed',
                'value' => 5,
                'min_order_value' => 25,
                'max_uses' => null,
                'used_count' => 0,
                'starts_at' => null,
                'expires_at' => null,
                'is_active' => true,
                'send_on_trigger' => 'wishlist',
            ]
        );

        // Mã gửi khi Add to cart
        PromoCode::updateOrCreate(
            ['code' => 'CART5'],
            [
                'type' => 'fixed',
                'value' => 5,
                'min_order_value' => 30,
                'max_uses' => null,
                'used_count' => 0,
                'starts_at' => null,
                'expires_at' => null,
                'is_active' => true,
                'send_on_trigger' => 'add_to_cart',
            ]
        );
    }
}
