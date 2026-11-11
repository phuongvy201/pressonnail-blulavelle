<?php

return [
    /*
    |--------------------------------------------------------------------------
    | BluLavelle iOS / Android store links
    |--------------------------------------------------------------------------
    */
    'apple_app_id' => env('MOBILE_APP_APPLE_ID', '6826066333'),
    'ios_store_url' => env('MOBILE_APP_IOS_STORE_URL', 'https://apps.apple.com/app/blulavelle/id6826066333'),
    'android_store_url' => env('MOBILE_APP_ANDROID_STORE_URL', ''),
    // Keep false until Play Store listing is live.
    'android_enabled' => filter_var(env('MOBILE_APP_ANDROID_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    // App Store Connect → Analytics → Campaigns provider token (optional).
    'campaign_provider_token' => env('MOBILE_APP_CAMPAIGN_PT', ''),
];
