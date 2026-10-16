<?php

use App\Models\User;

test('a password account cannot be deleted without the current password', function () {
    $user = User::factory()->create();
    $token = $user->createToken('BluLavelle app')->plainTextToken;

    $this->withToken($token)
        ->deleteJson('/api/v1/account', ['confirmation' => true])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'CURRENT_PASSWORD_INVALID');

    expect(User::query()->find($user->id))->not->toBeNull();
});

test('a password account is anonymized from the app', function () {
    $user = User::factory()->create(['email' => 'buyer@example.com', 'phone' => '555-0100']);
    $token = $user->createToken('BluLavelle app')->plainTextToken;

    $this->withToken($token)
        ->deleteJson('/api/v1/account', [
            'confirmation' => true,
            'password' => 'password',
        ])
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    $deleted = User::withTrashed()->find($user->id);
    expect($deleted)->not->toBeNull()
        ->and($deleted->email)->toBe('deleted+'.$user->id.'@invalid.local')
        ->and($deleted->name)->toBe('Deleted User')
        ->and($deleted->phone)->toBeNull()
        ->and($deleted->anonymized_at)->not->toBeNull()
        ->and($deleted->deleted_at)->not->toBeNull()
        ->and($deleted->tokens()->count())->toBe(0);
});

test('an apple account can be deleted without a password', function () {
    $user = User::factory()->create([
        'apple_id' => 'apple-sub-delete',
        'email' => 'apple-delete@example.com',
    ]);
    $token = $user->createToken('BluLavelle app')->plainTextToken;

    $this->withToken($token)
        ->deleteJson('/api/v1/account', ['confirmation' => true])
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    $deleted = User::withTrashed()->find($user->id);
    expect($deleted->apple_id)->toBeNull()
        ->and($deleted->anonymized_at)->not->toBeNull();
});

test('a guest cannot delete an account', function () {
    $this->deleteJson('/api/v1/account', ['confirmation' => true])
        ->assertUnauthorized();
});
