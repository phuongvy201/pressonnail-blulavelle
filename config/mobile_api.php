<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile API version
    |--------------------------------------------------------------------------
    */
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Sanctum token settings
    |--------------------------------------------------------------------------
    */
    'token_name' => env('MOBILE_API_TOKEN_NAME', 'mobile-app'),

    'token_expiration_minutes' => env('MOBILE_API_TOKEN_EXPIRATION', null),

    /*
    |--------------------------------------------------------------------------
    | Guest cart header (Flutter stores and sends on each request)
    |--------------------------------------------------------------------------
    */
    'guest_cart_header' => 'X-Guest-Cart-Token',

    /*
    |--------------------------------------------------------------------------
    | Cache TTL for authenticated checkout state (promo, gift card, discount mode)
    |--------------------------------------------------------------------------
    */
    'checkout_state_ttl_days' => (int) env('MOBILE_CHECKOUT_STATE_TTL_DAYS', 30),

];
