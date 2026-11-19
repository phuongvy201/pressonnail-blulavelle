<?php

namespace App\Console\Commands;

use App\Services\Apple\AppStoreAnalyticsSync;
use App\Support\Apple\AppStoreConnectToken;
use Illuminate\Console\Command;
use Throwable;

class SyncAppleAnalyticsCommand extends Command
{
    protected $signature = 'apple:sync-analytics {--days=14 : How many recent processing dates to pull}';

    protected $description = 'Download App Store download analytics into the admin dashboard';

    public function handle(AppStoreAnalyticsSync $sync): int
    {
        if (! AppStoreConnectToken::configured()) {
            $this->comment('App Store Connect API is not configured.');

            return self::SUCCESS;
        }

        try {
            $result = $sync->sync((int) $this->option('days'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($result['report'].': '.$result['instances'].' new instance(s), '.$result['rows'].' row(s).');

        return self::SUCCESS;
    }
}
