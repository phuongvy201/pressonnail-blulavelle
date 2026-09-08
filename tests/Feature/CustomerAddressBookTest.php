<?php

use App\Models\User;
use App\Services\CustomerAddressService;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('customer_addresses')) {
        $this->markTestSkipped('customer_addresses table is missing');
    }
});

test('customer can create an address and set it as default', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('customer.addresses.store'), [
        'recipient_name' => 'Jane Doe',
        'phone' => '555-1234',
        'line1' => '123 Nail Street',
        'line2' => 'Apt 4',
        'city' => 'Los Angeles',
        'state_province' => 'CA',
        'postal_code' => '90001',
        'country_code' => 'us',
        'label' => 'Home',
        'is_default_shipping' => '1',
    ])->assertRedirect();

    $address = $user->addresses()->first();
    expect($address)->not->toBeNull()
        ->and($address->country_code)->toBe('US')
        ->and($address->is_default_shipping)->toBeTrue();

    $second = app(CustomerAddressService::class)->create($user, [
        'recipient_name' => 'Work Desk',
        'line1' => '9 Office Rd',
        'city' => 'New York',
        'postal_code' => '10001',
        'country_code' => 'US',
        'is_default_shipping' => true,
        'is_default_billing' => true,
    ]);

    expect($second->fresh()->is_default_shipping)->toBeTrue();
    expect($address->fresh()->is_default_shipping)->toBeFalse();
});

test('checkout prefill uses the default shipping address', function () {
    $user = User::factory()->create([
        'name' => 'Account Name',
        'phone' => '555-0000',
    ]);

    app(CustomerAddressService::class)->create($user, [
        'recipient_name' => 'Prefill Name',
        'phone' => '555-9999',
        'line1' => '77 Sunset Blvd',
        'line2' => 'Suite 2',
        'city' => 'Hollywood',
        'state_province' => 'CA',
        'postal_code' => '90028',
        'country_code' => 'US',
        'is_default_shipping' => true,
        'is_default_billing' => true,
    ]);

    $prefill = app(CustomerAddressService::class)->checkoutPrefill($user);

    expect($prefill)->toMatchArray([
        'customer_name' => 'Prefill Name',
        'customer_phone' => '555-9999',
        'shipping_address' => "77 Sunset Blvd\nSuite 2",
        'city' => 'Hollywood',
        'state' => 'CA',
        'postal_code' => '90028',
        'country' => 'US',
    ]);
});

test('profile edit page shows address book', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('customer.profile.edit'))
        ->assertOk()
        ->assertSee('Address Book')
        ->assertSee('Add address');
});
