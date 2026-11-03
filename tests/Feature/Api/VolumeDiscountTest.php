<?php

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Support\Settings;

function volumeDiscountProduct(float $price = 20, bool $giftCard = false, bool $displayable = false): Product
{
    $category = Category::query()->create([
        'name' => 'Nails',
        'slug' => 'nails-'.uniqid(),
    ]);
    $template = ProductTemplate::query()->create([
        'name' => 'Classic template',
        'category_id' => $category->id,
        'base_price' => $price,
    ]);

    return Product::withoutEvents(fn () => Product::query()->create([
        'template_id' => $template->id,
        'name' => 'Classic Set',
        'slug' => 'classic-'.uniqid(),
        'price' => $price,
        'status' => 'active',
        'description' => 'Classic set',
        'is_gift_card' => $giftCard,
        'quantity' => $displayable ? 5 : 0,
        'media' => $displayable ? [['url' => 'https://example.com/nail.jpg', 'type' => 'image']] : null,
    ]));
}

function volumeDiscountLine(User $user, Product $product, int $quantity, float $price): Cart
{
    return Cart::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'price' => $price,
    ]);
}

beforeEach(function () {
    Settings::set('pricing.bulk_discounts', json_encode([
        ['min_qty' => 2, 'percent' => 15],
        ['min_qty' => 3, 'percent' => 17],
        ['min_qty' => 5, 'percent' => 18],
    ]));
    Settings::set('pricing.volume_discount_excluded_product_ids', null);
});

test('eligible products across the cart share one quantity tier', function (array $lines, int $percent) {
    $user = User::factory()->create();
    $subtotal = 0.0;

    foreach ($lines as [$quantity, $price]) {
        $product = volumeDiscountProduct($price);
        volumeDiscountLine($user, $product, $quantity, $price);
        $subtotal += $quantity * $price;
    }

    $eligibleQty = array_sum(array_column($lines, 0));
    $amount = round($subtotal * ($percent / 100), 2);

    $cart = $this->actingAs($user)->getJson('/api/cart')->assertOk()->json('summary');
    $checkout = $this->actingAs($user)->getJson('/api/checkout')->assertOk()->json('summary');

    expect($cart['bulk_discount_eligible_quantity'])->toBe($eligibleQty)
        ->and($cart['bulk_discount_percent'])->toEqual($percent)
        ->and($cart['bulk_discount'])->toEqual($amount)
        ->and($checkout['bulk_discount_percent'])->toEqual($cart['bulk_discount_percent'])
        ->and($checkout['bulk_discount'])->toEqual($cart['bulk_discount']);
})->with([
    'two different sets' => [[[1, 20.0], [1, 20.0]], 15],
    'three different sets' => [[[1, 20.0], [1, 20.0], [1, 20.0]], 17],
    'four different sets' => [[[1, 20.0], [1, 20.0], [1, 20.0], [1, 20.0]], 17],
    'five different sets' => [[[1, 20.0], [1, 20.0], [1, 20.0], [1, 20.0], [1, 20.0]], 18],
    'mixed quantities of two products' => [[[3, 10.0], [2, 20.0]], 18],
]);

test('two variant lines of one eligible product count toward the same total', function () {
    $user = User::factory()->create();
    $product = volumeDiscountProduct(20);
    volumeDiscountLine($user, $product, 1, 20);
    volumeDiscountLine($user, $product, 1, 20);

    $this->actingAs($user)
        ->getJson('/api/cart')
        ->assertOk()
        ->assertJsonPath('summary.bulk_discount_eligible_quantity', 2)
        ->assertJsonPath('summary.bulk_discount_percent', 15)
        ->assertJsonPath('summary.bulk_discount', 6);
});

test('a gift card does not erase the discount on eligible products', function () {
    $user = User::factory()->create();
    volumeDiscountLine($user, volumeDiscountProduct(20), 1, 20);
    volumeDiscountLine($user, volumeDiscountProduct(20), 1, 20);
    volumeDiscountLine($user, volumeDiscountProduct(50, true), 4, 50);

    $this->actingAs($user)
        ->getJson('/api/cart')
        ->assertOk()
        ->assertJsonPath('summary.bulk_discount_eligible_quantity', 2)
        ->assertJsonPath('summary.bulk_discount_percent', 15)
        ->assertJsonPath('summary.bulk_discount', 6);
});

test('an excluded product does not count or receive the global discount', function () {
    $user = User::factory()->create();
    $kept = volumeDiscountProduct(20);
    $alsoKept = volumeDiscountProduct(20);
    $excluded = volumeDiscountProduct(20);
    Settings::set('pricing.volume_discount_excluded_product_ids', json_encode([$excluded->id]));

    volumeDiscountLine($user, $kept, 1, 20);
    volumeDiscountLine($user, $alsoKept, 1, 20);
    volumeDiscountLine($user, $excluded, 3, 20);

    $this->actingAs($user)
        ->getJson('/api/cart')
        ->assertOk()
        ->assertJsonPath('summary.bulk_discount_eligible_quantity', 2)
        ->assertJsonPath('summary.bulk_discount_percent', 15)
        ->assertJsonPath('summary.bulk_discount', 6)
        ->assertJsonPath('summary.volume_discount_excluded_product_ids.0', $excluded->id);
});

test('the product page projects the cart quantity plus the selected quantity once', function () {
    $user = User::factory()->create();
    $inCart = volumeDiscountProduct(20);
    $current = volumeDiscountProduct(20, false, true);
    volumeDiscountLine($user, $inCart, 1, 20);
    volumeDiscountLine($user, $current, 1, 20);

    expect(Cart::projectedEligibleQuantity(2, 1, true))->toBe(3);
    expect(Cart::projectedEligibleQuantity(2, 1, false))->toBe(2);

    $this->actingAs($user)
        ->get(route('products.show', $current->slug))
        ->assertOk()
        ->assertSee('var CART_ELIGIBLE_QTY = 2', false);
});

test('the cart page and checkout use the same global rate', function () {
    $user = User::factory()->create();
    volumeDiscountLine($user, volumeDiscountProduct(20), 1, 20);
    volumeDiscountLine($user, volumeDiscountProduct(20), 1, 20);
    volumeDiscountLine($user, volumeDiscountProduct(20), 1, 20);
    volumeDiscountLine($user, volumeDiscountProduct(20), 1, 20);

    $this->actingAs($user)
        ->get(route('cart.index'))
        ->assertOk()
        ->assertSee('-17%', false);

    $this->actingAs($user)
        ->get(route('checkout.index'))
        ->assertOk()
        ->assertSee('const CHECKOUT_BULK_DISCOUNT_PERCENT = 17', false)
        ->assertSee('const CHECKOUT_BULK_DISCOUNT = 13.6', false);
});
