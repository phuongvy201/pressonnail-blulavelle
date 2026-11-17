<?php

use App\Models\Page;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('favicon is served and affiliate policy links redirect to the public pages', function () {
    $this->get('/favicon.ico')->assertOk()->assertHeader('content-type', 'image/png');

    $this->get('/policies/affiliate-privacy-policy')
        ->assertStatus(301)
        ->assertRedirect('/affiliate-privacy-policy');
});

test('search and the artist index are real pages', function () {
    $this->get('/search')
        ->assertOk()
        ->assertSee('<h1', false)
        ->assertSee('name="robots" content="noindex, follow"', false);

    $this->get('/shops')
        ->assertOk()
        ->assertSee('Nail Artists');
});

test('a missing page uses the branded not found screen', function () {
    $this->get('/this-page-should-not-exist-xyz')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('All products');
});

test('cms pages keep a single h1 and old duplicate urls redirect', function () {
    $user = User::factory()->create();
    Page::query()->create([
        'user_id' => $user->id,
        'title' => 'About Us',
        'slug' => 'trust-copy-sample',
        'content' => '<h1>About Us</h1><p>Welcome</p>',
        'status' => 'published',
    ]);

    $html = $this->get('/trust-copy-sample')->assertOk()->getContent();
    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('<h2>About Us</h2>');

    expect(parse_url((string) Seo::redirectTarget(Request::create('https://example.test/page/contact-us-2', 'GET')), PHP_URL_PATH))
        ->toBe('/contact-us')
        ->and(parse_url((string) Seo::redirectTarget(Request::create('https://example.test/products/', 'GET')), PHP_URL_PATH))
        ->toBe('/products');
});

test('the xml sitemap leaves out tools and json endpoints', function () {
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->not->toContain('/promo-offer')
        ->and($xml)->not->toContain('/search')
        ->and($xml)->not->toContain('/support/ticket')
        ->and($xml)->toContain('/shops');
});
