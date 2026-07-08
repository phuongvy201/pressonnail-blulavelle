<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product import (CSV / Google Sheet)
    |--------------------------------------------------------------------------
    |
    | Import chạy qua queue — cần worker: php artisan queue:work --queue=imports,default
    |
    */

    'max_rows' => (int) env('PRODUCT_IMPORT_MAX_ROWS', 2000),

    'queue' => env('PRODUCT_IMPORT_QUEUE', 'imports'),

    'row_job_timeout' => (int) env('PRODUCT_IMPORT_ROW_TIMEOUT', 900),

    'progress_ttl_seconds' => (int) env('PRODUCT_IMPORT_PROGRESS_TTL', 86400),

];
