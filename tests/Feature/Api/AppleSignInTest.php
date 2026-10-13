<?php

use App\Models\User;
use App\Services\SocialIdentityVerifier;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

test('apple sign-in rejects a token that is not a signed identity token', function () {
    $this->postJson('/api/v1/auth/social', [
        'provider' => 'apple',
        'idToken' => 'not-a-jwt',
        'nonce' => 'raw-nonce',
    ])->assertStatus(422)
        ->assertJsonPath('error.code', 'SOCIAL_AUTH_FAILED');
});

test('apple sign-in creates an account from a verified identity token', function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    config(['services.apple.client_id' => 'com.bluprinter.blucommerce', 'services.apple.client_ids' => 'com.bluprinter.blucommerce']);

    $rawNonce = 'raw-nonce-value';
    $token = appleIdentityToken($rawNonce, [
        'email' => 'ada@privaterelay.appleid.com',
        'email_verified' => 'true',
    ]);

    $user = app(SocialIdentityVerifier::class)->findOrCreate(
        app(SocialIdentityVerifier::class)->profile('apple', $token, '', $rawNonce, 'Ada Lovelace')
    );

    expect($user->email)->toBe('ada@privaterelay.appleid.com')
        ->and($user->name)->toBe('Ada Lovelace')
        ->and($user->apple_id)->not->toBeEmpty()
        ->and($user->hasRole('customer'))->toBeTrue();
});

test('a later apple sign-in matches apple id when apple omits the email', function () {
    config(['services.apple.client_id' => 'com.bluprinter.blucommerce', 'services.apple.client_ids' => 'com.bluprinter.blucommerce']);
    $existing = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@privaterelay.appleid.com',
        'apple_id' => 'apple-subject-1',
    ]);

    $profile = app(SocialIdentityVerifier::class)->profile(
        'apple',
        appleIdentityToken('second-nonce', ['sub' => 'apple-subject-1']),
        '',
        'second-nonce',
        'Someone Else',
    );

    expect($profile['email'])->toBe($existing->email)
        ->and($profile['name'])->toBe('Ada Lovelace')
        ->and($profile['providerId'])->toBe('apple-subject-1');
});

test('apple sign-in rejects a token for another app', function () {
    config(['services.apple.client_id' => 'com.bluprinter.blucommerce', 'services.apple.client_ids' => 'com.bluprinter.blucommerce']);

    expect(fn () => app(SocialIdentityVerifier::class)->profile(
        'apple',
        appleIdentityToken('raw-nonce', ['aud' => 'com.example.other']),
        '',
        'raw-nonce',
    ))->toThrow(InvalidArgumentException::class, 'This Apple account is not allowed for BluLavelle.');
});

/**
 * @param  array<string, mixed>  $claims
 */
function appleIdentityToken(string $rawNonce, array $claims = []): string
{
    $key = openssl_pkey_new([
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name' => 'prime256v1',
    ]);
    if ($key === false) {
        throw new RuntimeException('Unable to create a test Apple signing key.');
    }

    $details = openssl_pkey_get_details($key);
    $kid = 'test-apple-key';
    Http::fake([
        'https://appleid.apple.com/auth/keys' => Http::response([
            'keys' => [[
                'kty' => 'EC',
                'crv' => 'P-256',
                'kid' => $kid,
                'x' => appleBase64Url($details['ec']['x']),
                'y' => appleBase64Url($details['ec']['y']),
            ]],
        ]),
    ]);

    $header = appleBase64Url((string) json_encode(['alg' => 'ES256', 'kid' => $kid]));
    $payload = appleBase64Url((string) json_encode(array_merge([
        'iss' => 'https://appleid.apple.com',
        'aud' => 'com.bluprinter.blucommerce',
        'sub' => 'apple-subject-1',
        'exp' => time() + 300,
        'nonce' => hash('sha256', $rawNonce),
    ], $claims)));
    $input = $header.'.'.$payload;
    if (! openssl_sign($input, $der, $key, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Unable to sign the test Apple token.');
    }

    return $input.'.'.appleBase64Url(appleDerToRaw($der));
}

function appleBase64Url(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function appleDerToRaw(string $der): string
{
    $offset = 2;
    $read = function () use ($der, &$offset): string {
        $offset++;
        $length = ord($der[$offset++]);
        $value = substr($der, $offset, $length);
        $offset += $length;

        return $value;
    };

    return str_pad(ltrim($read(), "\x00"), 32, "\x00", STR_PAD_LEFT)
        .str_pad(ltrim($read(), "\x00"), 32, "\x00", STR_PAD_LEFT);
}
