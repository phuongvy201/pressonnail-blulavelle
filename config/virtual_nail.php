<?php

return [

    'enabled' => filter_var(env('VIRTUAL_NAIL_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Provider strategy
    |--------------------------------------------------------------------------
    | Try-on cần /images/edits (ảnh tay + prompt), không dùng /images/generations.
    | 1. chatgpt2api (pool healthy) — giữ tay + móng sản phẩm ~90% chính xác
    | 2. Fallback: New API POST {OPENAI_BASE_URL}/images/edits
    | Docs: https://docs.newapi.pro/en/docs/api/ai-model/images/openai/post-v1-images-edits
    */
    'prefer_browser_pool' => filter_var(env('VIRTUAL_NAIL_PREFER_BROWSER_POOL', true), FILTER_VALIDATE_BOOLEAN),

    'chatgpt2api' => [
        'base_url' => rtrim((string) env('CHATGPT2API_BASE_URL', 'http://localhost:3000/v1'), '/'),
        'auth_key' => (string) env('CHATGPT2API_AUTH_KEY', ''),
        'model' => (string) env('CHATGPT2API_IMAGE_MODEL', 'gpt-image-2'),
        'timeout' => (int) env('CHATGPT2API_TIMEOUT', 180),
        'health_timeout' => (int) env('CHATGPT2API_HEALTH_TIMEOUT', 5),
    ],

    'image_api' => [
        'api_key' => (string) env('OPENAI_API_KEY', ''),
        'base_url' => rtrim((string) env('OPENAI_BASE_URL', 'https://sv.devquote.shop/v1'), '/'),
        'model' => (string) env('VIRTUAL_NAIL_IMAGE_MODEL', env('OPENAI_IMAGE_MODEL', 'gpt-image-2')),
        'size' => (string) env('VIRTUAL_NAIL_IMAGE_SIZE', '1024x1024'),
        'response_format' => (string) env('VIRTUAL_NAIL_IMAGE_RESPONSE_FORMAT', 'b64_json'),
        'timeout' => (int) env('VIRTUAL_NAIL_IMAGE_TIMEOUT', env('OPENAI_TIMEOUT', 300)),
    ],

    'max_upload_kb' => (int) env('VIRTUAL_NAIL_MAX_UPLOAD_KB', 8192),

    'browse_rate_limit' => (int) env('VIRTUAL_NAIL_BROWSE_RATE_LIMIT', 60),

    'rate_limit' => (int) env('VIRTUAL_NAIL_RATE_LIMIT', 10),

    'async' => filter_var(env('VIRTUAL_NAIL_ASYNC', true), FILTER_VALIDATE_BOOLEAN),

    'history_limit' => (int) env('VIRTUAL_NAIL_HISTORY_LIMIT', 20),

    'shapes' => ['Almond', 'Coffin', 'Square', 'Round', 'Oval', 'Stiletto', 'Squoval'],

    'lengths' => ['Short', 'Medium', 'Long', 'Extra Long'],

    'default_shape' => 'Almond',

    'default_length' => 'Medium',

    'picker_limit' => (int) env('VIRTUAL_NAIL_PICKER_LIMIT', 48),

    'trending_searches' => [
        'French',
        'Floral',
        'Ombre',
        'Glitter',
        'Chiefs',
        'Wedding',
        'Sports',
    ],

];
