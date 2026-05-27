<?php

namespace App\Console\Commands;

use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Services\AffiliateCommissionService;
use Illuminate\Console\Command;

class BackfillAffiliateCommissions extends Command
{
    protected $signature = 'affiliate:backfill-commissions {--order= : Specific order ID}';

    protected $description = 'Create pending affiliate commissions for paid orders that have affiliate_id but no commission row';

    public function handle(AffiliateCommissionService $service): int
    {
        $query = Order::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('affiliate_id');

        if ($this->option('order')) {
            $query->whereKey((int) $this->option('order'));
        }

        $created = 0;
        $synced = 0;
        foreach ($query->get() as $order) {
            $had = AffiliateCommission::query()->where('order_id', $order->id)->exists();
            $service->createPendingCommissionIfEligible($order);
            $synced++;
            if (! $had && AffiliateCommission::query()->where('order_id', $order->id)->exists()) {
                $created++;
                $this->line("Order #{$order->id}: commission created.");
            }
        }

        $this->info("Done. Synced {$synced} order(s), created {$created} new commission(s).");

        return self::SUCCESS;
    }
}
