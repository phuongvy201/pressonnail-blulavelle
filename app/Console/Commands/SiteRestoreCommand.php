<?php

namespace App\Console\Commands;

use App\Services\SiteBackupService;
use Illuminate\Console\Command;
use Throwable;

class SiteRestoreCommand extends Command
{
    protected $signature = 'site:restore {filename : Zip filename inside storage/app/backups} {--force : Skip confirmation}';

    protected $description = 'Restore the website from a backup zip';

    public function handle(SiteBackupService $backups): int
    {
        $filename = (string) $this->argument('filename');

        if (! $this->option('force') && ! $this->confirm('This overwrites the current database and public files. Continue?')) {
            return self::SUCCESS;
        }

        try {
            $backups->restoreByFilename($filename);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Restored from '.$filename);

        return self::SUCCESS;
    }
}
