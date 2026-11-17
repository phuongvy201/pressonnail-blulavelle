<?php

use Illuminate\Http\Request;

test('the catch-all page route does not match the homepage with an empty slug', function () {
    $route = collect(app('router')->getRoutes()->getRoutes())
        ->first(fn ($route) => $route->getName() === 'pages.show');

    expect($route)->not->toBeNull();
    expect($route->matches(Request::create('/', 'GET')))->toBeFalse();
    expect($route->matches(Request::create('/about-us', 'GET')))->toBeTrue();
    expect($route->matches(Request::create('/admin', 'GET')))->toBeFalse();
});

test('the old vietnamese about page redirects to the english about page', function () {
    $this->get('/about')
        ->assertStatus(301)
        ->assertRedirect('/about-us');
});

test('llms.txt states the confirmed company facts and return window', function () {
    $text = file_get_contents(public_path('llms.txt'));

    expect($text)->toContain('16342615')
        ->and($text)->toContain('1301 E Arapaho Rd')
        ->and($text)->toContain('within 30 days of delivery')
        ->and($text)->toContain('https://blulavelle.com/about-us');
});

test('public cms links use the short route', function () {
    expect(parse_url(route('pages.show', 'about-us'), PHP_URL_PATH))->toBe('/about-us')
        ->and(parse_url(page_url('/page/about-us'), PHP_URL_PATH))->toBe('/about-us')
        ->and(parse_url(page_url('https://blulavelle.com/page/faqs'), PHP_URL_PATH))->toBe('/faqs');
});

test('an unknown cms page returns not found instead of a missing argument error', function () {
    $this->get('/no-such-cms-page-'.uniqid())
        ->assertNotFound();
});
