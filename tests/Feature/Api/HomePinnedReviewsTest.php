<?php

use App\Models\Review;

test('home reviews returns only approved reviews pinned to the homepage', function () {
    $pinnedProduct = smartSearchProduct('Pinned Ivory Bridal Nails');
    $otherProduct = smartSearchProduct('Everyday Neon Nails');

    $pinned = Review::query()->create([
        'product_id' => $pinnedProduct->id,
        'customer_name' => 'Ava',
        'rating' => 5,
        'title' => 'Wedding ready',
        'review_text' => 'These looked lovely for the celebration.',
        'is_approved' => true,
        'show_on_home' => true,
    ]);
    Review::query()->create([
        'product_id' => $otherProduct->id,
        'customer_name' => 'Mia',
        'rating' => 4,
        'review_text' => 'Nice everyday set.',
        'is_approved' => true,
        'show_on_home' => false,
    ]);
    Review::query()->create([
        'product_id' => $pinnedProduct->id,
        'customer_name' => 'Nora',
        'rating' => 2,
        'review_text' => 'Waiting for approval.',
        'is_approved' => false,
        'show_on_home' => true,
    ]);

    $response = $this->getJson('/api/v1/home/reviews');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.summary.count', 1)
        ->assertJsonPath('data.summary.average', 5)
        ->assertJsonPath('data.reviews.0.id', $pinned->id)
        ->assertJsonPath('data.reviews.0.product.slug', $pinnedProduct->slug)
        ->assertJsonCount(1, 'data.reviews');
});
