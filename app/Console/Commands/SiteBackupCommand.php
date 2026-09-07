<?php

namespace App\Console\Commands;

use App\Services\SiteBackupService;
use Illuminate\Console\Command;
use Throwable;

class SiteBackupCommand extends Command
{
    protected $signature = 'site:backup';

    protected $description = 'Create a zip backup of the database and public storage files (upload to S3 when enabled)';

    public function handle(SiteBackupService $backups): int
    {
        try {
            $backup = $backups->create();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup created: '.$backup['filename']);
        if (! empty($backup['uploaded_to_s3'])) {
            $this->line('Uploaded to S3: '.$backup['s3_key']);
            $this->line('Local zip removed (BACKUP_LOCAL_KEEP=0).');
        } elseif (! empty($backup['path'])) {
            $this->line($backup['path']);
            $this->comment('S3 upload skipped (BACKUP_S3_ENABLED=false or credentials missing).');
        }

        return self::SUCCESS;
    }
}
