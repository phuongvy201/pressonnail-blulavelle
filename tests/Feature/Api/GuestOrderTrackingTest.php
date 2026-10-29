<?php

use App\Mail\OrderTrackOtpMail;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\GuestOrderAccess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

function guestOrderFixture(?User $owner = null): Order
{
    return Order::query()->create([
        'order_number' => 'BL-GUEST-'.uniqid(),
        'user_id' => $owner?->id,
        'customer_name' => 'Alex Guest',
        'customer_email' => 'guest-'.uniqid().'@example.com',
        'customer_phone' => '5125550199',
        'shipping_address' => '123 Main St',
        'city' => 'Austin',
        'postal_code' => '78701',
        'country' => 'US',
        'subtotal' => 20,
        'total_amount' => 25,
        'status' => 'processing',
        'payment_status' => 'paid',
    ]);
}

test('legacy track requires otp instead of returning the order', function () {
    $order = guestOrderFixture();

    $this->postJson('/api/v1/orders/track', [
        'orderNumber' => $order->order_number,
        'email' => $order->customer_email,
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'OTP_REQUIRED');
});

test('guest can request and verify an email otp to view a masked order', function () {
    Mail::fake();
    $order = guestOrderFixture();
    expect($order->lookup_token)->not->toBeEmpty();

    $this->postJson('/api/v1/orders/track/otp', [
        'orderNumber' => $order->order_number,
        'email' => $order->customer_email,
    ])
        ->assertOk()
        ->assertJsonPath('data.sent', true);

    Mail::assertSent(OrderTrackOtpMail::class);

    $cache = Cache::get('order-track-otp:'.hash('sha256', strtolower($order->order_number).'|'.strtolower($order->customer_email)));
    expect($cache)->toBeArray()->and($cache['code'])->toHaveLength(6);

    $this->postJson('/api/v1/orders/track/verify', [
        'orderNumber' => $order->order_number,
        'email' => $order->customer_email,
        'otp' => $cache['code'],
    ])
        ->assertOk()
        ->assertJsonPath('data.order.orderNumber', $order->order_number)
        ->assertJsonPath('data.order.lookupToken', $order->fresh()->lookup_token)
        ->assertJsonMissingPath('data.order.shippingAddress');

    $email = data_get(
        $this->postJson('/api/v1/orders/track/verify', [
            'orderNumber' => $order->order_number,
            'email' => $order->customer_email,
            'otp' => $cache['code'],
        ])->json(),
        'error.code'
    );
    // OTP is single-use
    expect($email)->toBe('OTP_INVALID');
});

test('lookup token returns the guest order without authentication', function () {
    $order = guestOrderFixture();

    $this->getJson('/api/v1/orders/lookup/'.$order->lookup_token)
        ->assertOk()
        ->assertJsonPath('data.order.orderNumber', $order->order_number)
        ->assertJsonPath('data.order.city', 'Austin');
});

test('claim attaches guest orders only when the email is verified', function () {
    $guest = guestOrderFixture();
    $unverified = User::factory()->create([
        'email' => $guest->customer_email,
        'email_verified_at' => null,
    ]);

    $this->actingAs($unverified, 'sanctum')
        ->postJson('/api/v1/orders/claim')
        ->assertForbidden()
        ->assertJsonPath('error.code', 'EMAIL_NOT_VERIFIED');

    expect($guest->fresh()->user_id)->toBeNull();

    $unverified->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($unverified, 'sanctum')
        ->postJson('/api/v1/orders/claim')
        ->assertOk()
        ->assertJsonPath('data.claimed', 1);

    expect($guest->fresh()->user_id)->toBe($unverified->id);
});

test('social-style verified login claim helper attaches matching guest orders', function () {
    $guest = guestOrderFixture();
    $user = User::factory()->create([
        'email' => $guest->customer_email,
        'email_verified_at' => now(),
    ]);

    expect(app(GuestOrderAccess::class)->claimForVerifiedUser($user))->toBe(1);
    expect($guest->fresh()->user_id)->toBe($user->id);
});
