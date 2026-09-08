<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content-Security-Policy
    |--------------------------------------------------------------------------
    |
    | Set CSP_ENABLED=false to disable. CSP_REPORT_ONLY=true sends
    | Content-Security-Policy-Report-Only instead of enforcing (safer rollout).
    |
    */

    'enabled' => filter_var(env('CSP_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /*
    | Default true: browser reports violations but does NOT block scripts/styles.
    | Set CSP_REPORT_ONLY=false only after Console is clean of CSP errors.
    */
    'report_only' => filter_var(env('CSP_REPORT_ONLY', true), FILTER_VALIDATE_BOOLEAN),

    /*
    | IMPORTANT: Do NOT enable until every inline <script> has nonce="{{ csp_nonce() }}".
    | In modern browsers, a nonce in script-src disables 'unsafe-inline' for scripts,
    | so turning this on too early will break Blade onclick / inline JS.
    */
    'use_nonces' => filter_var(env('CSP_USE_NONCES', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | Optional violation report endpoint (browser POSTs JSON reports).
    | Example: https://your-domain.test/csp-report
    */
    'report_uri' => env('CSP_REPORT_URI'),

    /*
    | Extra origins appended to the listed directives (space-separated in .env).
    | Example: CSP_EXTRA_SCRIPT_SRC="https://cdn.example.com https://widgets.example.com"
    */
    'extra' => [
        'script-src' => array_values(array_filter(preg_split('/\s+/', (string) env('CSP_EXTRA_SCRIPT_SRC', '')) ?: [])),
        'style-src' => array_values(array_filter(preg_split('/\s+/', (string) env('CSP_EXTRA_STYLE_SRC', '')) ?: [])),
        'img-src' => array_values(array_filter(preg_split('/\s+/', (string) env('CSP_EXTRA_IMG_SRC', '')) ?: [])),
        'font-src' => array_values(array_filter(preg_split('/\s+/', (string) env('CSP_EXTRA_FONT_SRC', '')) ?: [])),
        'connect-src' => array_values(array_filter(preg_split('/\s+/', (string) env('CSP_EXTRA_CONNECT_SRC', '')) ?: [])),
        'frame-src' => array_values(array_filter(preg_split('/\s+/', (string) env('CSP_EXTRA_FRAME_SRC', '')) ?: [])),
        'media-src' => array_values(array_filter(preg_split('/\s+/', (string) env('CSP_EXTRA_MEDIA_SRC', '')) ?: [])),
    ],

    /*
    | Base directives for BluLavelle (storefront + admin + checkout).
    | 'unsafe-inline' / 'unsafe-eval' are required by current Blade inline scripts,
    | Alpine CDN, Vite, TinyMCE, and some payment SDKs.
    */
    'directives' => [
        'default-src' => ["'self'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'", 'https://www.paypal.com', 'https://www.sandbox.paypal.com'],
        'object-src' => ["'none'"],
        'frame-ancestors' => ["'self'"],
        'script-src' => [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            'https://js.stripe.com',
            'https://www.paypal.com',
            'https://www.sandbox.paypal.com',
            'https://www.google.com',
            'https://www.gstatic.com',
            'https://www.googletagmanager.com',
            'https://www.google-analytics.com',
            'https://unpkg.com',
            'https://cdn.jsdelivr.net',
            'https://cdn.tailwindcss.com',
            'https://cdn.tiny.cloud',
            'https://bzrcdn.openai.com',
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'ws://localhost:5173',
            'ws://127.0.0.1:5173',
        ],
        'style-src' => [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.googleapis.com',
            'https://unpkg.com',
            'https://cdn.jsdelivr.net',
            'https://cdn.tiny.cloud',
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        ],
        'img-src' => [
            "'self'",
            'data:',
            'blob:',
            'https:',
        ],
        'font-src' => [
            "'self'",
            'data:',
            'https://fonts.gstatic.com',
            'https://fonts.googleapis.com',
            'https://cdn.jsdelivr.net',
            'https://unpkg.com',
        ],
        'connect-src' => [
            "'self'",
            'https://api.stripe.com',
            'https://js.stripe.com',
            'https://www.paypal.com',
            'https://www.sandbox.paypal.com',
            'https://www.google.com',
            'https://www.gstatic.com',
            'https://www.google.com/recaptcha/',
            'https://www.googletagmanager.com',
            'https://www.google-analytics.com',
            'https://region1.google-analytics.com',
            'https://cdn.tiny.cloud',
            'https://bzrcdn.openai.com',
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'ws://localhost:5173',
            'ws://127.0.0.1:5173',
        ],
        'frame-src' => [
            "'self'",
            'https://js.stripe.com',
            'https://hooks.stripe.com',
            'https://www.paypal.com',
            'https://www.sandbox.paypal.com',
            'https://www.google.com',
            'https://www.gstatic.com',
            'https://www.googletagmanager.com',
            'https://cdn.tiny.cloud',
        ],
        'media-src' => [
            "'self'",
            'blob:',
            'data:',
            'https:',
        ],
        'worker-src' => [
            "'self'",
            'blob:',
        ],
        'manifest-src' => ["'self'"],
        'upgrade-insecure-requests' => [],
    ],

];
