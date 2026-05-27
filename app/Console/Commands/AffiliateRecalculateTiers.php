<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Services\AffiliateTierService;
use Illuminate\Console\Command;

class AffiliateRecalculateTiers extends Command
{
    protected $signature = 'affiliate:recalculate-tiers {--id= : Only process affiliate with this ID}';

    protected $description = 'Recalculate affiliate tiers from attributed paid orders (monthly window + inactivity downgrade)';

    public function handle(AffiliateTierService $tierService): int
    {
        $q = Affiliate::query()->where('is_active', true);
        if ($this->option('id')) {
            $q->whereKey((int) $this->option('id'));
        }

        $count = 0;
        foreach ($q->cursor() as $affiliate) {
            $tierService->syncTierFromSales($affiliate);
            $count++;
        }

        $this->info("Processed {$count} affiliate(s).");

        return self::SUCCESS;
    }
}
