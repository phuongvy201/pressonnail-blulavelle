<?php

return [

    'version' => '1.0.0',

    'token_name' => env('API_V1_TOKEN_NAME', 'ios-app'),

    'token_expiration_minutes' => env('API_V1_TOKEN_EXPIRATION', 43200), // 30 days

    'attempt_ttl_hours' => (int) env('CHECKOUT_ATTEMPT_TTL_HOURS', 24),

    'guest_cart_header' => 'X-Guest-Cart-Token',

];
