<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Order;
use App\Support\AffiliateSettings;
use App\Support\AffiliateTier;
use Illuminate\Support\Carbon;

class AffiliateTierService
{
    /**
     * Recalculate tier from rolling monthly orders; downgrade one level after inactivity.
     */
    public function syncTierFromSales(Affiliate $affiliate): void
    {
        $this->evaluateTier($affiliate);
    }

    public function evaluateTier(Affiliate $affiliate): void
    {
        if ($affiliate->tier_locked) {
            return;
        }

        $evaluationDays = AffiliateSettings::tierEvaluationDays();
        $inactivityDays = AffiliateSettings::tierInactivityDays();
        $thresholds = AffiliateSettings::tierOrderThresholds();

        $orderCount = $this->countAttributedPaidOrders($affiliate, $evaluationDays);
        $performanceTier = AffiliateTier::tierForOrderCount($orderCount, $thresholds);

        $newTier = $performanceTier;

        if ($this->daysSinceLastAttributedOrder($affiliate) > $inactivityDays) {
            $newTier = AffiliateTier::downgradeOne($affiliate->tier);
        }

        $normalized = AffiliateTier::normalize($newTier);
        if ($normalized !== AffiliateTier::normalize($affiliate->tier)) {
            $affiliate->update(['tier' => $normalized]);
        }
    }

    public function countAttributedPaidOrders(Affiliate $affiliate, int $days): int
    {
        $since = now()->subDays(max(1, $days))->startOfDay();

        return (int) Order::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('payment_status', 'paid')
            ->where(function ($q) use ($since) {
                $q->where('paid_at', '>=', $since)
                    ->orWhere(function ($s) use ($since) {
                        $s->whereNull('paid_at')->where('created_at', '>=', $since);
                    });
            })
            ->count();
    }

    public function daysSinceLastAttributedOrder(Affiliate $affiliate): int
    {
        $lastAt = Order::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('payment_status', 'paid')
            ->selectRaw('MAX(COALESCE(paid_at, created_at)) as last_at')
            ->value('last_at');

        if (! $lastAt) {
            return 9999;
        }

        return (int) Carbon::parse($lastAt)->diffInDays(now());
    }
}
