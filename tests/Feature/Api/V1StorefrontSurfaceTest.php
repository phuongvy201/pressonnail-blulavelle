<?php

use App\Support\ApiV1\PayloadNormalizer;

it('converts money fields to minor units recursively', function () {
    $normalized = PayloadNormalizer::moneyToMinor([
        'price' => 32.99,
        'nested' => [
            'subtotal' => '10.50',
            'rating' => 4.5,
        ],
        'items' => [
            ['line_total' => 5.0, 'qty' => 2],
        ],
        'summary' => [
            'bulk_discount' => 5.0,
            'bulk_discount_percent' => 20,
            'gift_card_discount' => 1.25,
            'total_price' => 40.0,
        ],
    ]);

    expect($normalized['price'])->toBe(3299)
        ->and($normalized['nested']['subtotal'])->toBe(1050)
        ->and($normalized['nested']['rating'])->toBe(4.5)
        ->and($normalized['items'][0]['line_total'])->toBe(500)
        ->and($normalized['items'][0]['qty'])->toBe(2)
        ->and($normalized['summary']['bulk_discount'])->toBe(500)
        ->and($normalized['summary']['bulk_discount_percent'])->toBe(20)
        ->and($normalized['summary']['gift_card_discount'])->toBe(125)
        ->and($normalized['summary']['total_price'])->toBe(4000);
});

it('v1 catalog products returns api envelope', function () {
    $response = $this->getJson('/api/v1/catalog/products');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'data']);
});

it('v1 cart returns api envelope for guest', function () {
    $response = $this->getJson('/api/v1/cart');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'data']);
});

it('v1 auth captcha returns api envelope', function () {
    $this->getJson('/api/v1/auth/captcha')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'data' => ['required', 'siteKey', 'provider']]);
});
