<?php

return [

    'version' => '1.2.0',

    'token_name' => env('API_V1_TOKEN_NAME', 'ios-app'),

    'token_expiration_minutes' => env('API_V1_TOKEN_EXPIRATION', 43200), // 30 days

    'attempt_ttl_hours' => (int) env('CHECKOUT_ATTEMPT_TTL_HOURS', 24),

    'guest_cart_header' => 'X-Guest-Cart-Token',

    'upload_disk' => env('API_V1_UPLOAD_DISK', 'local'),

    'upload_ttl_minutes' => (int) env('API_V1_UPLOAD_TTL_MINUTES', 15),

    'upload_min_side' => (int) env('API_V1_UPLOAD_MIN_SIDE', 256),

    'upload_max_side' => (int) env('API_V1_UPLOAD_MAX_SIDE', 4096),

    'signed_url_ttl_minutes' => (int) env('API_V1_SIGNED_URL_TTL_MINUTES', 15),

    'try_on_ttl_hours' => (int) env('API_V1_TRY_ON_TTL_HOURS', 72),

    'try_on_daily_limit' => (int) env('API_V1_TRY_ON_DAILY_LIMIT', 20),

    /*
    | AI provider retention note for clients / privacy policy.
    | Do not use hand photos for training without explicit opt-in.
    */
    'try_on_ai_retention_note' => env(
        'API_V1_TRY_ON_AI_RETENTION_NOTE',
        'Hand and result images are stored privately for try-on only, not used for model training without opt-in, and purged on delete or after retention TTL.'
    ),

];
