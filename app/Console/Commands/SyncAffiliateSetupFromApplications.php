<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use Illuminate\Console\Command;

class SyncAffiliateSetupFromApplications extends Command
{
    protected $signature = 'affiliate:sync-setup-from-applications';

    protected $description = 'Copy profile and social fields from approved applications onto affiliate records';

    public function handle(): int
    {
        $updated = 0;

        Affiliate::query()->whereNotNull('user_id')->chunkById(50, function ($affiliates) use (&$updated): void {
            foreach ($affiliates as $affiliate) {
                $application = AffiliateApplication::query()
                    ->where('user_id', $affiliate->user_id)
                    ->where('status', AffiliateApplication::STATUS_APPROVED)
                    ->orderByDesc('processed_at')
                    ->first();

                if (! $application) {
                    continue;
                }

                $before = $affiliate->only([
                    'phone', 'primary_platform', 'social_links', 'content_niche',
                ]);

                $affiliate->fillFromApplication($application);
                $affiliate->saveQuietly();

                $after = $affiliate->only([
                    'phone', 'primary_platform', 'social_links', 'content_niche',
                ]);

                if ($before !== $after) {
                    $updated++;
                    $this->line("Affiliate #{$affiliate->id} ({$affiliate->code}) synced from application.");
                }
            }
        });

        $this->info("Done. Updated {$updated} affiliate(s).");

        return self::SUCCESS;
    }
}
