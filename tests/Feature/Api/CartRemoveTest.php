<?php

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;

test('a signed-in shopper can remove a cart line', function () {
    $user = User::factory()->create();
    $category = Category::query()->create([
        'name' => 'Nails',
        'slug' => 'nails-'.uniqid(),
    ]);
    $template = ProductTemplate::query()->create([
        'name' => 'Classic template',
        'category_id' => $category->id,
        'base_price' => 20,
    ]);
    $product = Product::withoutEvents(fn () => Product::query()->create([
        'template_id' => $template->id,
        'name' => 'Classic Set',
        'slug' => 'classic-'.uniqid(),
        'price' => 20,
        'status' => 'active',
    ]));
    $line = Cart::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 20,
    ]);
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withToken($token)
        ->deleteJson('/api/v1/cart/'.$line->id)
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Cart::query()->find($line->id))->toBeNull();
});
