<?php

use App\Models\User;

test('profile can be updated including address', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'address' => null,
    ]);

    $this->actingAs($user)
        ->putJson('/api/profile', [
            'name' => 'New Name',
            'phone' => '555-0100',
            'address' => '123 Main St',
            'city' => 'Austin',
            'state' => 'TX',
            'postalCode' => '78701',
            'country' => 'US',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('user.name', 'New Name')
        ->assertJsonPath('user.phone', '555-0100')
        ->assertJsonPath('user.address', '123 Main St')
        ->assertJsonPath('user.city', 'Austin')
        ->assertJsonPath('user.state', 'TX')
        ->assertJsonPath('user.postalCode', '78701')
        ->assertJsonPath('user.country', 'US');

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->postal_code)->toBe('78701');
});

test('address can be updated without changing name', function () {
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
        ->assertJsonPath('user.postalCode', '75201');

    expect($user->fresh()->name)->toBe('Keep Me');
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
