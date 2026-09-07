<?php

use App\Models\User;
use App\Services\RecaptchaVerifier;
use Illuminate\Support\Facades\Http;

test('captcha config is public and reports when recaptcha is off', function () {
    config(['services.recaptcha.secret_key' => null, 'services.recaptcha.site_key' => null]);

    $this->getJson('/api/auth/captcha')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'required' => false,
            'siteKey' => null,
            'provider' => 'recaptcha',
        ]);
});

test('captcha config exposes site key when recaptcha is enabled', function () {
    config([
        'services.recaptcha.secret_key' => 'test-secret',
        'services.recaptcha.site_key' => 'test-site-key',
    ]);

    $this->getJson('/api/auth/captcha')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'required' => true,
            'siteKey' => 'test-site-key',
            'provider' => 'recaptcha',
        ]);
});

test('mobile captcha config matches storefront', function () {
    config([
        'services.recaptcha.secret_key' => 'test-secret',
        'services.recaptcha.site_key' => 'mobile-site-key',
    ]);

    $this->getJson('/api/mobile/v1/auth/captcha')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'required' => true,
            'siteKey' => 'mobile-site-key',
            'provider' => 'recaptcha',
        ]);
});

test('register requires recaptcha token when secret is configured', function () {
    config(['services.recaptcha.secret_key' => 'test-secret']);

    $this->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'passwordConfirmation' => 'password',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['recaptchaToken']);
});

test('register accepts recaptcha token when verification succeeds', function () {
    config(['services.recaptcha.secret_key' => 'test-secret']);

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
    ]);

    $this->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'passwordConfirmation' => 'password',
        'recaptchaToken' => 'valid-token',
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('authenticated', true);

    expect(User::where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('recaptcha verifier is a no-op without a secret key', function () {
    config(['services.recaptcha.secret_key' => null]);

    app(RecaptchaVerifier::class)->verify(request());

    expect(app(RecaptchaVerifier::class)->isEnabled())->toBeFalse();
});
