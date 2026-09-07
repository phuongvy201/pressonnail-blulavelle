<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local backup directory
    |--------------------------------------------------------------------------
    |
    | Used as a temporary staging area while building the zip, and as fallback
    | storage only when S3 is disabled. With S3 enabled, zips are not kept here
    | (local_keep defaults to 0).
    |
    */
    'disk_path' => storage_path('app/backups'),

    'include_public_files' => true,

    /*
    |--------------------------------------------------------------------------
    | Grandfather-father-son retention
    |--------------------------------------------------------------------------
    |
    | Applied to the S3 archive when S3 is enabled; otherwise to local files.
    |
    */
    'retention' => [
        'daily' => (int) env('BACKUP_KEEP_DAILY', 7),
        'weekly' => (int) env('BACKUP_KEEP_WEEKLY', 4),
        'monthly' => (int) env('BACKUP_KEEP_MONTHLY', 12),
    ],

    /*
    | How many newest zip files to keep on the local server disk.
    | Default 0: with S3 enabled, nothing is retained on the server after upload.
    */
    'local_keep' => (int) env('BACKUP_LOCAL_KEEP', 0),

    /*
    |--------------------------------------------------------------------------
    | AWS S3 (primary off-server storage)
    |--------------------------------------------------------------------------
    */
    's3' => [
        'enabled' => filter_var(env('BACKUP_S3_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'disk' => env('BACKUP_S3_DISK', 's3_backups'),
        'prefix' => trim((string) env('BACKUP_S3_PREFIX', 'site-backups'), '/'),
    ],
];
