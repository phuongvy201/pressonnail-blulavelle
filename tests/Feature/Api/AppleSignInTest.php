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
    config(['services.apple.client_id' => 'com.blulavelle.app', 'services.apple.client_ids' => 'com.blulavelle.app']);

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
    config(['services.apple.client_id' => 'com.blulavelle.app', 'services.apple.client_ids' => 'com.blulavelle.app']);
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
    config(['services.apple.client_id' => 'com.blulavelle.app', 'services.apple.client_ids' => 'com.blulavelle.app']);

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
    $options = [
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'private_key_bits' => 2048,
    ];
    $opensslConfig = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf';
    if (is_file($opensslConfig)) {
        $options['config'] = $opensslConfig;
    }
    $key = openssl_pkey_new($options);
    if ($key === false) {
        throw new RuntimeException('Unable to create a test Apple signing key.');
    }

    $details = openssl_pkey_get_details($key);
    $kid = 'test-apple-key';
    Http::fake([
        'https://appleid.apple.com/auth/keys' => Http::response([
            'keys' => [[
                'kty' => 'RSA',
                'alg' => 'RS256',
                'kid' => $kid,
                'n' => appleBase64Url($details['rsa']['n']),
                'e' => appleBase64Url($details['rsa']['e']),
            ]],
        ]),
    ]);

    $header = appleBase64Url((string) json_encode(['alg' => 'RS256', 'kid' => $kid]));
    $payload = appleBase64Url((string) json_encode(array_merge([
        'iss' => 'https://appleid.apple.com',
        'aud' => 'com.blulavelle.app',
        'sub' => 'apple-subject-1',
        'exp' => time() + 300,
        'nonce' => hash('sha256', $rawNonce),
    ], $claims)));
    $input = $header.'.'.$payload;
    if (! openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Unable to sign the test Apple token.');
    }

    return $input.'.'.appleBase64Url($signature);
}

function appleBase64Url(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}
