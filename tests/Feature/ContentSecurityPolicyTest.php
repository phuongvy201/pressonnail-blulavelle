<?php

it('sends Content-Security-Policy on html by default', function () {
    config([
        'csp.enabled' => true,
        'csp.report_only' => false,
        'csp.use_nonces' => false,
    ]);

    $response = $this->get('/');

    $response->assertHeader('Content-Security-Policy');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain('script-src')
        ->and($csp)->toContain("'unsafe-inline'")
        ->and($csp)->toContain('https://js.stripe.com')
        ->and($csp)->toContain('https://fonts.googleapis.com')
        ->and($csp)->toContain('https://connect.facebook.net')
        ->and($csp)->toContain('https://analytics.tiktok.com')
        ->and($csp)->toContain('https://s.pinimg.com')
        ->and($csp)->toContain('https://widget.trustpilot.com')
        ->and($csp)->toContain('https://images.dmca.com');
});

it('can send report-only instead of enforcing', function () {
    config([
        'csp.enabled' => true,
        'csp.report_only' => true,
        'csp.use_nonces' => false,
    ]);

    $response = $this->get('/');

    expect($response->headers->get('Content-Security-Policy'))->toBeNull();
    $report = $response->headers->get('Content-Security-Policy-Report-Only');
    expect($report)->not->toBeNull()
        ->and($report)->toContain("'unsafe-inline'");
});

it('skips Content-Security-Policy on api json', function () {
    config(['csp.enabled' => true]);

    $response = $this->getJson('/api/v1/health');

    $response->assertOk();
    expect($response->headers->get('Content-Security-Policy'))->toBeNull()
        ->and($response->headers->get('Content-Security-Policy-Report-Only'))->toBeNull();
});

it('when nonces enabled, policy includes nonce and drops unsafe-inline for scripts', function () {
    config([
        'csp.enabled' => true,
        'csp.report_only' => false,
        'csp.use_nonces' => true,
    ]);

    $response = $this->get('/');
    $csp = (string) $response->headers->get('Content-Security-Policy');

    expect($csp)->toMatch("/'nonce-[A-Za-z0-9+\/=]+'/")
        ->and($csp)->toContain("'strict-dynamic'");

    // script-src chunk should not keep unsafe-inline once nonce mode is on
    expect(preg_match("/script-src[^;]*'unsafe-inline'/", $csp))->toBe(0);
});
