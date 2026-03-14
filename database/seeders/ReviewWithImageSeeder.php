<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;

/**
 * Import reviews có hình ảnh để hiển thị trên trang chủ (testimonials).
 * Chạy: php artisan db:seed --class=ReviewWithImageSeeder
 */
class ReviewWithImageSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::first();
        if (!$product) {
            $this->command->warn('Chưa có sản phẩm. Chạy ProductSeeder trước.');
            return;
        }

        $reviews = [
            [
                'customer_name' => 'Sarah F.',
                'customer_email' => 'sarah.f@example.com',
                'rating' => 5,
                'review_text' => 'Salon quality at home was the dream. These press-on nails have changed the way I do my nails forever. So many compliments!',
                'title' => "I'm totally blown away.",
                'image_url' => 'storage/images/c768ab6feb861eabf2beb33c0fb2cebc.jpg',
                'is_verified_purchase' => true,
            ],
            [
                'customer_name' => 'Susan T.',
                'customer_email' => 'susan.t@example.com',
                'rating' => 5,
                'review_text' => "I've only been using these for a week, but they look amazing. Application was so easy. My nails have never looked better!",
                'title' => 'Smooth, chic, and easy.',
                'image_url' => 'storage/images/44ad1fa40f4f3b0b55214cf29e1dd8a2.jpg',
                'is_verified_purchase' => true,
            ],
            [
                'customer_name' => 'Mariah B.',
                'customer_email' => 'mariah.b@example.com',
                'rating' => 5,
                'review_text' => "Money well spent! I can't believe what I used to pay for salon manicures. These do the same thing, minus the wait and the cost.",
                'title' => 'You have to try this!',
                'image_url' => 'storage/images/1769484507_zFot4Im9WW.png',
                'is_verified_purchase' => true,
            ],
        ];

        foreach ($reviews as $data) {
            Review::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'customer_email' => $data['customer_email'],
                ],
                array_merge($data, [
                    'product_id' => $product->id,
                    'user_id' => null,
                    'is_approved' => true,
                ])
            );
        }

        $this->command->info('Đã import ' . count($reviews) . ' review có hình ảnh cho trang chủ.');
    }
}
