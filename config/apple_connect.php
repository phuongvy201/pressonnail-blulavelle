<?php

return [
    /*
    | App Store Connect → Users and Access → Integrations → App Store Connect API.
    | The first request for a new report type needs an Admin key. After that,
    | a Sales and Reports key can download reports that already exist.
    */
    'issuer_id' => env('APPLE_CONNECT_ISSUER_ID', ''),
    'key_id' => env('APPLE_CONNECT_KEY_ID', ''),
    // PEM text, or an absolute path to the AuthKey_XXXX.p8 file. Never commit the key.
    'private_key' => env('APPLE_CONNECT_PRIVATE_KEY', ''),
    'app_id' => env('APPLE_CONNECT_APP_ID', env('MOBILE_APP_APPLE_ID', '6826066333')),
];
