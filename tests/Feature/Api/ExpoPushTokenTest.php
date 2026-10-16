<?php

use App\Models\DevicePushToken;
use App\Models\Order;
use App\Models\User;
use App\Services\ExpoPushService;
use Illuminate\Support\Facades\Http;

test('guest can register an expo push token', function () {
    $device = str_repeat('p', 40);

    $this->withHeader('X-Live-Chat-Guest', $device)
        ->postJson('/api/v1/push-tokens', [
            'token' => 'ExponentPushToken[test-token-guest]',
            'platform' => 'android',
            'email' => 'guest@example.com',
        ])
        ->assertCreated()
        ->assertJsonPath('data.registered', true);

    expect(DevicePushToken::query()->where('expo_push_token', 'ExponentPushToken[test-token-guest]')->exists())->toBeTrue();
});

test('order status change sends expo push to matching tokens', function () {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => [['status' => 'ok']]], 200),
    ]);

    $user = User::factory()->create(['email' => 'buyer@example.com']);
    DevicePushToken::query()->create([
        'user_id' => $user->id,
        'expo_push_token' => 'ExponentPushToken[order-token]',
        'platform' => 'ios',
        'last_registered_at' => now(),
    ]);

    $order = Order::query()->create([
        'order_number' => 'BL-PUSH-'.uniqid(),
        'user_id' => $user->id,
        'customer_name' => $user->name,
        'customer_email' => $user->email,
        'shipping_address' => '123 Main',
        'city' => 'Austin',
        'postal_code' => '78701',
        'country' => 'US',
        'subtotal' => 20,
        'total_amount' => 20,
        'status' => 'processing',
        'payment_status' => 'pending',
    ]);

    $order->update([
        'status' => 'shipped',
        'tracking_number' => 'TRACK123',
    ]);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'exp.host')
        && str_contains($request->body(), 'ExponentPushToken[order-token]')
        && str_contains($request->body(), 'TRACK123'));
});

test('shop chat reply and payment events send expo pushes', function () {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => [['status' => 'ok']]], 200),
    ]);

    $user = User::factory()->create(['email' => 'chat@example.com']);
    DevicePushToken::query()->create([
        'user_id' => $user->id,
        'customer_email' => $user->email,
        'expo_push_token' => 'ExponentPushToken[chat-token]',
        'last_registered_at' => now(),
    ]);

    $conversation = \App\Models\ChatConversation::query()->create([
        'customer_user_id' => $user->id,
        'status' => 'open',
    ]);
    $message = \App\Models\ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'is_from_customer' => false,
        'body' => 'Your size guide is ready',
    ]);

    app(ExpoPushService::class)->notifyShopChatReply($conversation, $message);

    $order = Order::query()->create([
        'order_number' => 'BL-PAY-'.uniqid(),
        'user_id' => $user->id,
        'customer_name' => $user->name,
        'customer_email' => $user->email,
        'shipping_address' => '123 Main',
        'city' => 'Austin',
        'postal_code' => '78701',
        'country' => 'US',
        'subtotal' => 20,
        'total_amount' => 20,
        'status' => 'pending',
        'payment_status' => 'pending',
    ]);
    app(ExpoPushService::class)->notifyPaymentReceived($order);

    Http::assertSent(fn ($request) => str_contains($request->body(), 'live_chat'));
    Http::assertSent(fn ($request) => str_contains($request->body(), 'payment_received'));
});

test('expo push service prunes device-not-registered tokens', function () {
    Http::fake([
        'exp.host/*' => Http::response([
            'data' => [[
                'status' => 'error',
                'details' => ['error' => 'DeviceNotRegistered'],
            ]],
        ], 200),
    ]);

    DevicePushToken::query()->create([
        'expo_push_token' => 'ExponentPushToken[dead]',
        'guest_device_key' => str_repeat('q', 40),
        'last_registered_at' => now(),
    ]);

    app(ExpoPushService::class)->send(
        ['ExponentPushToken[dead]'],
        'Hello',
        'World',
        ['type' => 'order_update'],
    );

    expect(DevicePushToken::query()->where('expo_push_token', 'ExponentPushToken[dead]')->exists())->toBeFalse();
});
