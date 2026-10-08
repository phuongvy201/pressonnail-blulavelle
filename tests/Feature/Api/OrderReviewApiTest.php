<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function deliveredOrderFixture(string $status = 'delivered'): array
{
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane-'.uniqid().'@example.com']);
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
    $order = Order::query()->create([
        'order_number' => 'BL-'.uniqid(),
        'user_id' => $user->id,
        'customer_name' => $user->name,
        'customer_email' => $user->email,
        'shipping_address' => '123 Main St',
        'city' => 'Austin',
        'postal_code' => '78701',
        'country' => 'US',
        'subtotal' => 20,
        'total_amount' => 20,
        'status' => $status,
        'payment_status' => 'paid',
    ]);
    $item = OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => 20,
        'quantity' => 1,
        'total_price' => 20,
    ]);

    return compact('user', 'product', 'order', 'item');
}

test('a delivered order can receive one verified review', function () {
    Storage::fake('public');
    ['user' => $user, 'product' => $product, 'order' => $order] = deliveredOrderFixture();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/orders/'.$order->order_number.'/reviews')
        ->assertOk()
        ->assertJsonPath('data.canReview', true)
        ->assertJsonPath('data.items.0.productId', $product->id)
        ->assertJsonPath('data.items.0.canReview', true);

    $this->actingAs($user, 'sanctum')
        ->post('/api/v1/orders/'.$order->order_number.'/reviews', [
            'productId' => $product->id,
            'rating' => 5,
            'title' => 'Beautiful set',
            'reviewText' => 'The nails arrived in perfect shape.',
            'reviewImage' => UploadedFile::fake()->image('nails.jpg'),
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.review.productId', $product->id)
        ->assertJsonPath('data.review.rating', 5)
        ->assertJsonPath('data.review.isVerifiedPurchase', true)
        ->assertJsonPath('data.review.isApproved', true);

    $review = Review::query()->where('user_id', $user->id)->where('product_id', $product->id)->first();
    expect($review)->not->toBeNull()
        ->and($review->is_verified_purchase)->toBeTrue()
        ->and($review->image_url)->toStartWith('reviews/');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders/'.$order->order_number.'/reviews', [
            'productId' => $product->id,
            'rating' => 4,
            'reviewText' => 'Second try',
        ])
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'ALREADY_REVIEWED');
});

test('reviews stay closed until the order is delivered', function () {
    ['user' => $user, 'product' => $product, 'order' => $order] = deliveredOrderFixture('shipped');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/orders/'.$order->order_number.'/reviews')
        ->assertOk()
        ->assertJsonPath('data.canReview', false)
        ->assertJsonPath('data.items.0.reviewBlockReason', 'not_delivered');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders/'.$order->order_number.'/reviews', [
            'productId' => $product->id,
            'rating' => 5,
            'reviewText' => 'Too early',
        ])
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'ORDER_NOT_DELIVERED');

    expect(Review::query()->count())->toBe(0);
});

test('a customer cannot review a product that is not on the delivered order', function () {
    ['user' => $user, 'order' => $order] = deliveredOrderFixture();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders/'.$order->order_number.'/reviews', [
            'productId' => 999999,
            'rating' => 5,
            'reviewText' => 'Not my item',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'PRODUCT_NOT_IN_ORDER');
});
