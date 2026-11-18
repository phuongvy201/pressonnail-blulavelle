<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

function duplicateHeadingProduct(string $name, string $slug, string $description, float $price = 24): Product
{
    $category = Category::query()->create(['name' => 'Nails', 'slug' => 'nails-'.uniqid()]);
    $template = ProductTemplate::query()->create([
        'name' => 'Press-on template',
        'category_id' => $category->id,
        'base_price' => 20,
    ]);

    return Product::withoutEvents(fn () => Product::query()->create([
        'template_id' => $template->id,
        'name' => $name,
        'slug' => $slug,
        'price' => $price,
        'description' => $description,
        'media' => ['/images/set.jpg'],
        'quantity' => 5,
        'status' => 'active',
    ]));
}

test('identical product listings collapse to the oldest url', function () {
    $original = duplicateHeadingProduct('Black Cat Nails', 'black-cat', 'Same handmade set.');
    $copy = duplicateHeadingProduct('Black Cat Nails', 'black-cat-copy', 'Same handmade set.');
    Cache::flush();

    expect(Seo::preferredProductSlug($copy))->toBe('black-cat')
        ->and(Seo::preferredProductSlug($original))->toBe('black-cat')
        ->and(Seo::redirectedProductIds())->toBe([$copy->id]);

    $this->get('/products/black-cat-copy')
        ->assertStatus(301)
        ->assertRedirect('/products/black-cat');

    expect($this->get('/sitemap.xml')->getContent())->not->toContain('/products/black-cat-copy');
});

test('products that only share a name keep their own url and a distinct heading', function () {
    $original = duplicateHeadingProduct('Black Cat Nails', 'black-cat', 'Celestial library set.');
    $other = duplicateHeadingProduct('Black Cat Nails', 'black-cat-ombre', 'Soft ombre set.');
    Cache::flush();

    expect(Seo::preferredProductSlug($other))->toBe('black-cat-ombre')
        ->and(Seo::productHeading($original))->toBe('Black Cat Nails')
        ->and(Seo::productHeading($other))->toBe('Black Cat Nails (black-cat-ombre)');
});
