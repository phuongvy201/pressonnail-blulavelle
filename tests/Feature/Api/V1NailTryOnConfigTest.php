<?php

use App\Models\Product;
use App\Support\VirtualNailSettings;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        'virtual_nail.enabled' => true,
        'virtual_nail.chatgpt2api.auth_key' => 'test-key',
        'virtual_nail.chatgpt2api.base_url' => 'http://localhost:3000/v1',
        'virtual_nail.image_api.api_key' => '',
        'virtual_nail.image_api.base_url' => '',
    ]);
});

test('v1 try-on-config returns 404 for missing product', function () {
    $this->getJson('/api/v1/nail/products/999999/try-on-config')
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'PRODUCT_NOT_FOUND');
});

test('v1 try-on-config returns 503 when feature disabled', function () {
    config(['virtual_nail.enabled' => false]);

    $productId = Schema::hasTable('products')
        ? (int) (Product::query()->value('id') ?? 1)
        : 1;

    $this->getJson('/api/v1/nail/products/'.$productId.'/try-on-config')
        ->assertStatus(503)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'FEATURE_DISABLED');
});

test('v1 try-on-config returns enabled payload when product has media', function () {
    if (! Schema::hasTable('products')) {
        $this->markTestSkipped('products table missing');
    }

    $product = Product::query()->availableForDisplay()->first();
    if (! $product) {
        $this->markTestSkipped('no displayable product seeded');
    }

    // Force a media path so MISSING_DESIGN_ASSET is avoided when possible.
    $service = app(\App\Services\VirtualNailTrialService::class);
    $hasImage = $service->resolveProductImageUrl($product) !== null;

    $response = $this->getJson('/api/v1/nail/products/'.$product->id.'/try-on-config')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.productId', $product->id)
        ->assertJsonStructure([
            'data' => [
                'productId',
                'variantId',
                'tryOnEnabled',
                'tryOnAssetVersion',
                'supportedShapes',
                'supportedLengths',
                'supportedVariants',
                'assets' => ['designImageUrl', 'handGuideUrl', 'sampleAssets'],
                'captureTips',
            ],
        ]);

    if ($hasImage) {
        $response->assertJsonPath('data.tryOnEnabled', true)
            ->assertJsonPath('data.unsupportedReason', null);
    } else {
        $response->assertJsonPath('data.tryOnEnabled', false)
            ->assertJsonPath('data.unsupportedReason', 'MISSING_DESIGN_ASSET');
    }

    expect(VirtualNailSettings::enabled())->toBeTrue();
});
