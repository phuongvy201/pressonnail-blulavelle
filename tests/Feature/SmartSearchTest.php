<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductEmbedding;
use App\Models\ProductTemplate;
use App\Services\Chatbot\ChatbotKnowledgeService;
use App\Services\Search\EmbeddingService;
use App\Services\SearchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function smartSearchProduct(string $name, array $attributes = []): Product
{
    $category = Category::query()->create(['name' => 'Nails', 'slug' => 'nails-'.uniqid()]);
    $template = ProductTemplate::query()->create([
        'name' => 'Press-on template',
        'category_id' => $category->id,
        'base_price' => 20,
    ]);

    return Product::withoutEvents(fn () => Product::query()->create(array_merge([
        'template_id' => $template->id,
        'name' => $name,
        'slug' => str($name)->slug().'-'.uniqid(),
        'price' => 24,
        'description' => 'Press-on nail set.',
        'media' => ['/images/set.jpg'],
        'quantity' => 5,
        'status' => 'active',
    ], $attributes)));
}

beforeEach(function () {
    config(['search.embedding_enabled' => false]);
});

test('smart search understands Vietnamese without diacritics and synonyms', function () {
    $match = smartSearchProduct('Short Almond Blush Pink Press On Nails', [
        'color' => 'blush pink',
        'description' => 'Short almond nails in soft pink for parties.',
    ]);
    smartSearchProduct('Long Stiletto Black Press On Nails', ['color' => 'black']);

    $results = app(SearchService::class)->buildProductSearchQuery('mong ngan mau hong di tiec', [], false, 12, 6);

    expect($results->pluck('id')->all())->toContain($match->id);
});

test('smart search tolerates a misspelled shape', function () {
    $match = smartSearchProduct('Medium Almond Rose Press On Nails', ['color' => 'rose']);

    $results = app(SearchService::class)->buildProductSearchQuery('almnd rose nails', [], false, 12, 6);

    expect($results->first()?->id)->toBe($match->id);
});

test('smart search ranks a semantic match that shares no keywords', function () {
    config(['search.embedding_enabled' => true]);
    $bridal = smartSearchProduct('Ivory Pearl Bridal Set', [
        'description' => 'Soft white glossy finish for brides.',
    ]);
    $other = smartSearchProduct('Neon Sport Set', [
        'description' => 'Bright colours for everyday wear.',
    ]);
    ProductEmbedding::query()->create([
        'product_id' => $bridal->id,
        'model' => 'test-embedding',
        'content_hash' => 'bridal',
        'vector' => [1.0, 0.0],
    ]);
    ProductEmbedding::query()->create([
        'product_id' => $other->id,
        'model' => 'test-embedding',
        'content_hash' => 'other',
        'vector' => [0.0, 1.0],
    ]);
    app()->instance(EmbeddingService::class, new class extends EmbeddingService
    {
        public function embed(string $text): ?array
        {
            return [1.0, 0.0];
        }

        public function model(): string
        {
            return 'test-embedding';
        }
    });

    $results = app(SearchService::class)->buildProductSearchQuery('something elegant for a wedding', [], false, 12, 6);

    expect($results->first()?->id)->toBe($bridal->id);
});

test('smart search understands English occasion phrases and shape aliases', function () {
    $match = smartSearchProduct('Luxury Coffin Glitter Press On Nails', [
        'color' => 'rose gold',
        'description' => 'Sparkle coffin nails made for prom and glam nights.',
    ]);
    smartSearchProduct('Short Square Matte Black Press On Nails', ['color' => 'black']);

    $results = app(SearchService::class)->buildProductSearchQuery('sparkly ballerina nails for prom night', [], false, 12, 6);

    expect($results->first()?->id)->toBe($match->id);
});

test('smart search understands a natural English wedding question', function () {
    $match = smartSearchProduct('Ivory Pearl Bridal Press On Nails', [
        'color' => 'ivory',
        'description' => 'Elegant ivory nails with pearl accents for brides.',
    ]);
    smartSearchProduct('Neon Green Stiletto Press On Nails', ['color' => 'green']);

    $results = app(SearchService::class)->buildProductSearchQuery('What should I wear for a wedding?', [], false, 12, 6);

    expect($results->first()?->id)->toBe($match->id);
});

test('smart search maps English slang for pointed nails', function () {
    $match = smartSearchProduct('Long Stiletto Crimson Press On Nails', [
        'color' => 'crimson',
        'description' => 'Bold long stiletto nails in deep red.',
    ]);

    $results = app(SearchService::class)->buildProductSearchQuery('pointy red claws under $30', [], false, 12, 6);

    expect($results->first()?->id)->toBe($match->id);
});

test('chatbot product search uses the same Vietnamese understanding', function () {
    $match = smartSearchProduct('Short Almond Blush Pink Press On Nails', [
        'color' => 'blush pink',
        'description' => 'Short almond nails in soft pink.',
    ]);

    $results = app(ChatbotKnowledgeService::class)->searchProducts('mong ngan mau hong', 3);

    expect(collect($results)->pluck('id')->all())->toContain($match->id);
});

test('mobile catalog search finds products from a Vietnamese query', function () {
    $match = smartSearchProduct('Short Almond Blush Pink Press On Nails', [
        'color' => 'blush pink',
        'description' => 'Short almond nails in soft pink.',
    ]);

    $response = $this->getJson('/api/v1/catalog/products?search='.urlencode('mong ngan mau hong'));

    $response->assertOk();
    $ids = collect($response->json('data.items') ?? $response->json('data') ?? [])
        ->pluck('id')
        ->all();
    expect($ids)->toContain($match->id);
});

test('a natural language search uses chatbot keywords when no phrase matches', function () {
    config([
        'search.query_rewrite_enabled' => true,
        'search.query_rewrite_in_tests' => true,
        'search.embedding_enabled' => false,
        'chatbot.ai_enabled' => true,
        'chatbot.ai.api_key' => 'test-key',
        'chatbot.ai.base_url' => 'https://chat.test/v1',
        'chatbot.ai.model' => 'test-model',
    ]);
    Cache::flush();
    Http::fake([
        'https://chat.test/v1/responses' => Http::response([
            'output_text' => 'bridal wedding elegant',
        ], 200),
    ]);

    $match = smartSearchProduct('Ivory Pearl Bridal Press On Nails', [
        'color' => 'ivory',
        'description' => 'Pearl accents for a formal celebration.',
    ]);
    smartSearchProduct('Neon Green Stiletto Press On Nails', ['color' => 'green']);

    $results = app(SearchService::class)->buildProductSearchQuery('help me pick a set for my sisters big day', [], false, 12, 6);
    $again = app(SearchService::class)->buildProductSearchQuery('help me pick a set for my sisters big day', [], false, 12, 6);

    expect($results->first()?->id)->toBe($match->id)
        ->and($again->first()?->id)->toBe($match->id);
    Http::assertSentCount(1);
});

test('a failed embedding provider pauses further embedding requests', function () {
    config([
        'search.embedding_enabled' => true,
        'search.embedding_api_key' => 'test-key',
        'search.embedding_base_url' => 'https://embeddings.test/v1',
        'search.embedding_model' => 'text-embedding-3-small',
    ]);
    Cache::flush();
    Http::fake([
        'https://embeddings.test/v1/embeddings' => Http::response('<html>502</html>', 502),
    ]);

    $service = app(EmbeddingService::class);

    expect($service->embed('pink nails'))->toBeNull()
        ->and($service->embed('blue nails'))->toBeNull();

    Http::assertSentCount(1);
});
