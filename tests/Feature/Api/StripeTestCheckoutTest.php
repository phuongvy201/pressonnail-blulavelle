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

test('stripe test checkout refuses a live secret', function () {
    config([
        'api_v1.stripe_test_checkout_enabled' => true,
        'services.stripe.secret' => 'sk_live_not_for_tests',
    ]);

    $this->postJson('/api/v1/payments/stripe/test-checkout', [
        'total' => 12.5,
        'currency' => 'USD',
        'customer_email' => 'buyer@example.com',
    ], ['Idempotency-Key' => 'test-key-12345678'])
        ->assertStatus(503)
        ->assertJsonPath('error.code', 'STRIPE_TEST_KEY_REQUIRED');
});
