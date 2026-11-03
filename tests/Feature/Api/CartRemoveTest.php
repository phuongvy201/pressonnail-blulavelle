<?php

use App\Listeners\SyncCartOnLogin;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

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

test('a signed-in shopper can remove a guest cart line from the same session', function () {
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

    $sessionId = str_repeat('a', 40);
    $line = Cart::query()->create([
        'session_id' => $sessionId,
        'user_id' => null,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 20,
    ]);

    $this->actingAs($user);
    session()->start();
    session()->setId($sessionId);

    $response = app(\App\Http\Controllers\Api\CartController::class)->remove($line->id);
    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['success'])->toBeTrue();

    expect(Cart::query()->find($line->id))->toBeNull();
});

test('login moves guest cart lines saved before the session id changes', function () {
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
    $guestSessionId = 'guest-session-before-login';
    $line = Cart::query()->create([
        'session_id' => $guestSessionId,
        'user_id' => null,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 20,
    ]);

    $request = Request::create('/login', 'POST');
    $request->attributes->set('cart_session_id', $guestSessionId);
    $this->app->instance('request', $request);

    (new SyncCartOnLogin())->handle(new Login('web', $user, false));

    $line->refresh();
    expect($line->user_id)->toBe($user->id);
    expect($line->session_id)->toBeNull();
});

test('removing a missing cart line does not return a server error', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->deleteJson('/api/cart/remove/999999999')
        ->assertNotFound()
        ->assertJsonPath('success', false);
});
