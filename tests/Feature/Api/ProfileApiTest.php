<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('customer_addresses')) {
        $this->markTestSkipped('customer_addresses table is missing');
    }
});

test('profile update changes personal fields only not address book', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'address' => 'Legacy St',
    ]);

    $this->actingAs($user)
        ->putJson('/api/profile', [
            'name' => 'New Name',
            'phone' => '555-0100',
            'address' => 'Should Be Ignored',
            'city' => 'Ignored City',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('user.name', 'New Name')
        ->assertJsonPath('user.phone', '555-0100');

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->address)->toBe('Legacy St')
        ->and($user->addresses()->count())->toBe(0);
});

test('address endpoint upserts default shipping address in address book', function () {
    $user = User::factory()->create([
        'name' => 'Keep Me',
        'address' => 'Old St',
    ]);

    $this->actingAs($user)
        ->putJson('/api/profile/address', [
            'address' => '456 Oak Ave',
            'city' => 'Dallas',
            'state' => 'TX',
            'postalCode' => '75201',
            'country' => 'US',
        ])
        ->assertOk()
        ->assertJsonPath('user.name', 'Keep Me')
        ->assertJsonPath('user.address', '456 Oak Ave')
        ->assertJsonPath('user.postalCode', '75201')
        ->assertJsonPath('defaultAddress.line1', '456 Oak Ave')
        ->assertJsonPath('defaultAddress.city', 'Dallas')
        ->assertJsonPath('defaultAddress.isDefaultShipping', true);

    expect($user->fresh()->name)->toBe('Keep Me');
    expect($user->addresses()->count())->toBe(1);
    expect($user->addresses()->first()->is_default_shipping)->toBeTrue();
});

test('password can be updated via api', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/password', [
            'currentPassword' => 'password',
            'password' => 'new-password',
            'passwordConfirmation' => 'new-password',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(password_verify('new-password', $user->fresh()->password))->toBeTrue();
});

test('guest cannot update profile', function () {
    $this->putJson('/api/profile', [
        'name' => 'Hacker',
    ])->assertUnauthorized();
});

test('empty wishlist list is available to guests', function () {
    $this->getJson('/api/wishlist')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('count', 0)
        ->assertJsonPath('items', []);
});

test('mobile profile address update requires auth', function () {
    $this->putJson('/api/mobile/v1/profile/address', [
        'city' => 'Austin',
    ])->assertUnauthorized();
});
