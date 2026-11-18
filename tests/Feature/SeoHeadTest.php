<?php

use App\Models\Product;
use App\Models\Shop;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    config(['app.url' => 'https://blulavelle.com']);
});

test('document titles use the page name and do not fall back to home', function () {
    expect(Seo::documentTitle('', '', ''))->toBe('BluLavelle | Handcrafted Stained Glass Press-On Nails')
        ->and(Seo::documentTitle('', 'All Products', ''))->toBe('All Products | BluLavelle')
        ->and(Seo::documentTitle('BluLavelle | Handcrafted Stained Glass Press-On Nails', 'Home', ''))
        ->toBe('BluLavelle | Handcrafted Stained Glass Press-On Nails');
});

test('product seo uses the set name, shop, and first image', function () {
    $shop = new Shop(['shop_name' => 'BluVienne']);
    $product = new Product([
        'name' => 'Black Cat Stained Glass Press-On Nails',
        'slug' => 'black-cat-stained-glass',
        'description' => 'Hand-painted celestial library set with reusable nails and a custom fit.',
        'media' => [[
            'url' => 'https://cdn.example.com/black-cat.jpg',
        ]],
    ]);
    $product->setRelation('shop', $shop);

    expect(Seo::productTitle($product))->toBe('Black Cat Stained Glass Press-On Nails | BluLavelle')
        ->and(Seo::productDescription($product))->toContain('Black Cat Stained Glass Press-On Nails')
        ->and(Seo::productDescription($product))->toContain('BluVienne')
        ->and(mb_strlen(Seo::productDescription($product)))->toBeLessThanOrEqual(160)
        ->and(Seo::productImageUrl($product))->toBe('https://cdn.example.com/black-cat.jpg')
        ->and(Seo::canonicalPath('/products/black-cat-stained-glass/'))
        ->toBe('https://blulavelle.com/products/black-cat-stained-glass');
});

test('long product names stay unique without repeating the brand', function () {
    $product = new Product([
        'name' => 'Hand Painted Press On Nails Black Cat Stained Glass Celestial Library False Nails Extra Long Name',
        'slug' => 'long-name',
        'description' => 'A unique painted set.',
    ]);

    $title = Seo::productTitle($product);

    expect($title)->toEndWith('| BluLavelle')
        ->and(substr_count(mb_strtolower($title), 'blulavelle'))->toBe(1)
        ->and(mb_strlen($title))->toBeLessThanOrEqual(60);
});

test('listing canonical keeps the page and filters and drops campaign parameters', function () {
    $this->get('/products?page=2&shop=5&utm_source=newsletter')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://blulavelle.com/products?page=2&amp;shop=5"', false);

    $this->get('/products?page=1&utm_source=newsletter')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://blulavelle.com/products"', false);
});

test('duplicate page urls and trailing slashes redirect once to the short url', function () {
    expect(Seo::redirectTarget(Request::create('https://example.test/products/', 'GET')))
        ->toBe('https://blulavelle.com/products')
        ->and(Seo::redirectTarget(Request::create('https://example.test/products/?page=2&utm_source=newsletter', 'GET')))
        ->toBe('https://blulavelle.com/products?page=2');

    $this->get('/page/about-us')
        ->assertStatus(301)
        ->assertRedirect('https://blulavelle.com/about-us');

    $this->get('/page/shipping-delivery')
        ->assertStatus(301)
        ->assertRedirect('https://blulavelle.com/shipping-delivery');

    $this->get('/page/about')->assertNotFound();
});

test('the storefront head prints that title, canonical, and share image', function () {
    $html = Blade::render(<<<'BLADE'
@extends('layouts.app')
@section('meta_title', 'Black Cat Nails | BluLavelle')
@section('meta_description', 'Hand-painted black cat press-on nails by BluVienne.')
@section('canonical', 'https://blulavelle.com/products/black-cat')
@section('og_type', 'product')
@section('og_image', 'https://cdn.example.com/black-cat.jpg')
@section('content')
<p>Product</p>
@endsection
BLADE);

    expect($html)->toContain('<title>Black Cat Nails | BluLavelle</title>')
        ->and($html)->toContain('<link rel="canonical" href="https://blulavelle.com/products/black-cat">')
        ->and($html)->toContain('content="https://cdn.example.com/black-cat.jpg"')
        ->and($html)->toContain('property="og:type" content="product"')
        ->and($html)->not->toContain('BluLavelle - Home')
        ->and($html)->not->toContain('property="og:image" content="https://blulavelle.com/favicon.png"')
        ->and($html)->toContain('"@type":"Organization"')
        ->and($html)->toContain('"@type":"WebSite"');
});

test('product schema uses the real offer and skips a rating when there are no reviews', function () {
    config(['app.url' => 'https://blulavelle.com']);
    $product = new Product([
        'name' => 'Black Cat Stained Glass Press-On Nails',
        'slug' => 'black-cat',
        'sku' => 'BC-1',
        'price' => 29.99,
        'quantity' => 4,
        'description' => 'Hand-painted celestial set.',
        'media' => [['url' => 'https://cdn.example.com/black-cat.jpg']],
    ]);
    $product->setRelation('shop', new Shop(['shop_name' => 'BluVienne']));

    $json = Seo::jsonLdScript(Seo::productNodes($product, [
        ['name' => 'Home', 'url' => 'https://blulavelle.com/'],
        ['name' => 'Products', 'url' => 'https://blulavelle.com/products'],
        ['name' => $product->name, 'url' => ''],
    ]));

    expect($json)->toContain('"@type":"Product"')
        ->and($json)->toContain('"price":"29.99"')
        ->and($json)->toContain('"name":"BluVienne"')
        ->and($json)->toContain('"@type":"BreadcrumbList"')
        ->and($json)->toContain('https://blulavelle.com/products/black-cat')
        ->and($json)->not->toContain('AggregateRating');
});

test('short product titles keep the shop and long titles stay within 60 characters', function () {
    $short = new Product(['name' => 'Black Cat Nails', 'slug' => 'black-cat-nails']);
    $short->setRelation('shop', new Shop(['shop_name' => 'BluVienne']));

    $title = Seo::productTitle($short);

    expect($title)->toBe('Black Cat Nails | BluVienne | BluLavelle')
        ->and(mb_strlen($title))->toBeGreaterThanOrEqual(30)
        ->and(mb_strlen($title))->toBeLessThanOrEqual(60);

    $page = Seo::documentTitle('', 'Artistic Press-On Nails | Creative Hand-Painted Designs', '');
    expect(mb_strlen($page))->toBeLessThanOrEqual(60)
        ->and($page)->toEndWith('| BluLavelle');

    $listing = Seo::documentTitle('', Seo::listingTitle('CELESTIAL ART'), '');
    expect(mb_strlen($listing))->toBeGreaterThanOrEqual(30)
        ->and(mb_strlen($listing))->toBeLessThanOrEqual(60)
        ->and($listing)->toBe('CELESTIAL ART Press-On Nails | BluLavelle');
});

test('catalog and generated descriptions stay between 120 and 160 characters', function () {
    foreach (Seo::catalogDescriptions() as $description) {
        expect(mb_strlen($description))->toBeGreaterThanOrEqual(120)
            ->and(mb_strlen($description))->toBeLessThanOrEqual(160);
    }

    expect(mb_strlen(Seo::collectionDescription('CELESTIAL ART')))->toBeGreaterThanOrEqual(120)
        ->and(mb_strlen(Seo::collectionDescription('CELESTIAL ART')))->toBeLessThanOrEqual(160)
        ->and(mb_strlen(Seo::categoryDescription('Nail Box')))->toBeGreaterThanOrEqual(120)
        ->and(mb_strlen(Seo::categoryDescription('Nail Box')))->toBeLessThanOrEqual(160)
        ->and(mb_strlen(Seo::shopDescription('BluLavelle', 'Handmade, hand-painted press-on nails by BluLavelle.')))->toBeGreaterThanOrEqual(120)
        ->and(mb_strlen(Seo::pageDescription('about-us', 'Discover Blu Lavelle - your go-to for stylish press-on nails.')))->toBeGreaterThanOrEqual(120)
        ->and(Seo::pageDescription('about-us', str_repeat('Unique about copy. ', 8)))->toStartWith('Unique about copy.');
});

test('cms html keeps one page heading and root-relative links', function () {
    $html = '<h1>Prep the set</h1><p>Read <a href="sizing-kit">the kit</a> and <a href="https://blulavelle.com/contact">contact</a>.</p>';

    $fixed = Seo::rootRelativeHrefs(Seo::demoteExtraHeadings($html));

    expect($fixed)->not->toContain('<h1')
        ->and($fixed)->toContain('<h2>Prep the set</h2>')
        ->and($fixed)->toContain('href="/sizing-kit"')
        ->and($fixed)->toContain('href="https://blulavelle.com/contact"');
});
