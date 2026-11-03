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

test('an unknown cms page returns not found instead of a missing argument error', function () {
    $this->get('/no-such-cms-page-'.uniqid())
        ->assertNotFound();
});
