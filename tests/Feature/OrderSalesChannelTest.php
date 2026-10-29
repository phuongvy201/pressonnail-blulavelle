<?php

use App\Models\Order;
use App\Models\User;
use App\Support\SalesChannel;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

function salesChannelAdmin(): User
{
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

function salesChannelOrder(array $overrides = []): Order
{
    return Order::query()->create(array_merge([
        'order_number' => 'ORD-CH-'.uniqid(),
        'customer_name' => 'Alex Buyer',
        'customer_email' => 'alex@example.com',
        'shipping_address' => '123 Main St',
        'city' => 'Austin',
        'postal_code' => '78701',
        'country' => 'US',
        'subtotal' => 40,
        'total_amount' => 40,
        'currency' => 'USD',
        'status' => 'processing',
        'payment_status' => 'paid',
        'payment_method' => 'stripe',
        'sales_channel' => SalesChannel::WEBSITE,
    ], $overrides));
}

test('checkout from the mobile api is recorded as an app order', function () {
    $request = Request::create('/api/v1/checkout/attempts', 'POST', [
        'payment_ui' => 'checkout_session',
    ]);

    expect(SalesChannel::fromCheckoutRequest($request))->toBe(SalesChannel::APP);
});

test('website checkout is recorded as a website order', function () {
    $request = Request::create('/checkout/process', 'POST', [
        'payment_method' => 'stripe',
    ]);

    expect(SalesChannel::fromCheckoutRequest($request))->toBe(SalesChannel::WEBSITE);
});

test('admin dashboard counts paid website and app customers separately', function () {
    salesChannelOrder([
        'order_number' => 'WEB-PAID-1',
        'customer_email' => 'web@example.com',
        'total_amount' => 25,
        'sales_channel' => SalesChannel::WEBSITE,
    ]);
    salesChannelOrder([
        'order_number' => 'WEB-PAID-2',
        'customer_email' => 'web@example.com',
        'total_amount' => 15,
        'sales_channel' => SalesChannel::WEBSITE,
    ]);
    salesChannelOrder([
        'order_number' => 'APP-PAID-1',
        'customer_email' => 'app@example.com',
        'total_amount' => 50,
        'sales_channel' => SalesChannel::APP,
    ]);
    salesChannelOrder([
        'order_number' => 'APP-PENDING-1',
        'customer_email' => 'waiting@example.com',
        'payment_status' => 'pending',
        'status' => 'pending',
        'sales_channel' => SalesChannel::APP,
    ]);

    $this->actingAs(salesChannelAdmin())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('channelStats', function (array $stats) {
            return $stats['website']['orders'] === 2
                && $stats['website']['customers'] === 1
                && $stats['website']['revenue'] === 40.0
                && $stats['app']['orders'] === 1
                && $stats['app']['customers'] === 1
                && $stats['app']['revenue'] === 50.0;
        })
        ->assertSee('Website purchases')
        ->assertSee('App purchases');
});

test('admin orders list can filter purchases by sales channel', function () {
    salesChannelOrder(['order_number' => 'WEB-LIST-1', 'sales_channel' => SalesChannel::WEBSITE]);
    salesChannelOrder(['order_number' => 'APP-LIST-1', 'sales_channel' => SalesChannel::APP]);

    $this->actingAs(salesChannelAdmin())
        ->get(route('admin.orders.index', ['sales_channel' => 'app']))
        ->assertOk()
        ->assertSee('APP-LIST-1')
        ->assertDontSee('WEB-LIST-1');
});
