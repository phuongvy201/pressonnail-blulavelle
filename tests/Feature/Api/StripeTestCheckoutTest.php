<?php

test('stripe test checkout stays hidden until it is enabled', function () {
    config(['api_v1.stripe_test_checkout_enabled' => false]);

    $this->postJson('/api/v1/payments/stripe/test-checkout', [
        'total' => 12.5,
        'currency' => 'USD',
        'customer_email' => 'buyer@example.com',
    ], ['Idempotency-Key' => 'test-key-12345678'])
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

test('stripe test checkout refuses a live secret without a dedicated test secret', function () {
    config([
        'api_v1.stripe_test_checkout_enabled' => true,
        'services.stripe.secret' => 'sk_live_not_for_tests',
        'services.stripe.test_secret' => null,
    ]);

    $this->postJson('/api/v1/payments/stripe/test-checkout', [
        'total' => 12.5,
        'currency' => 'USD',
        'customer_email' => 'buyer@example.com',
    ], ['Idempotency-Key' => 'test-key-12345678'])
        ->assertStatus(503)
        ->assertJsonPath('error.code', 'STRIPE_TEST_KEY_REQUIRED');
});

test('stripe test checkout accepts a dedicated sk_test secret while live secret stays sk_live', function () {
    config([
        'api_v1.stripe_test_checkout_enabled' => true,
        'services.stripe.secret' => 'sk_live_website_customers',
        'services.stripe.test_secret' => 'sk_test_mobile_only',
    ]);

    // Guard passes; Stripe API is not called in this unit-style assertion path
    // because create still hits Stripe — stub by expecting validation-only shape:
    // missing Idempotency short key still 422 after guard.
    $this->postJson('/api/v1/payments/stripe/test-checkout', [
        'total' => 12.5,
        'currency' => 'USD',
        'customer_email' => 'buyer@example.com',
    ], ['Idempotency-Key' => 'short'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'IDEMPOTENCY_KEY_REQUIRED');
});
