<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateBalanceAdjustment;
use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Support\AffiliateCommissionEligibility;
use App\Support\AffiliateSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AffiliateCommissionService
{
    public function createPendingCommissionIfEligible(Order $order): void
    {
        if ($order->payment_status !== 'paid' || ! $order->affiliate_id) {
            $this->clearAffiliateCommissionEligibility($order);

            return;
        }

        if (AffiliateCommission::query()->where('order_id', $order->id)->exists()) {
            $this->markOrderCommissionEligible($order, null);

            return;
        }

        $reason = $this->determineIneligibleReason($order);
        if ($reason !== null) {
            $this->markOrderCommissionIneligible($order, $reason);

            return;
        }

        try {
            DB::transaction(function () use ($order): void {
                $exists = AffiliateCommission::query()->where('order_id', $order->id)->lockForUpdate()->exists();
                if ($exists) {
                    return;
                }

                $affiliate = Affiliate::query()->whereKey($order->affiliate_id)->lockForUpdate()->first();
                if (! $affiliate || ! $affiliate->is_active) {
                    return;
                }

                $order->loadMissing('items.product');

                $lineTotals = $this->orderLineTotals($order);
                if ($lineTotals['eligible'] <= 0) {
                    return;
                }

                $base = $this->calculateCommissionBase($order, $lineTotals['eligible'], $lineTotals['all']);
                if ($base <= 0) {
                    return;
                }

                $rate = $affiliate->effectiveCommissionPercent();
                $amount = round($base * ($rate / 100), 2);
                if ($amount <= 0) {
                    return;
                }

                AffiliateCommission::query()->create([
                    'affiliate_id' => $affiliate->id,
                    'order_id' => $order->id,
                    'commission_base' => $base,
                    'commission_rate' => $rate,
                    'commission_amount' => $amount,
                    'original_commission_base' => $base,
                    'original_commission_amount' => $amount,
                    'currency' => $order->currency ?? 'USD',
                    'status' => AffiliateCommission::STATUS_PENDING,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('affiliate.create_commission_failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $order->refresh();
        if (AffiliateCommission::query()->where('order_id', $order->id)->exists()) {
            $this->markOrderCommissionEligible($order, null);
        } else {
            $reason = $this->determineIneligibleReason($order) ?? AffiliateCommissionEligibility::REASON_ZERO_COMMISSION;
            $this->markOrderCommissionIneligible($order, $reason);
        }
    }

    /**
     * @return non-empty-string|null Reason code when commission cannot be created
     */
    public function determineIneligibleReason(Order $order): ?string
    {
        if ($order->hasActivePaymentDispute()) {
            return AffiliateCommissionEligibility::REASON_PAYMENT_DISPUTE;
        }

        $affiliate = Affiliate::query()->find($order->affiliate_id);
        if (! $affiliate || ! $affiliate->is_active) {
            return AffiliateCommissionEligibility::REASON_AFFILIATE_INACTIVE;
        }

        if ($affiliate->user_id && $order->user_id && (int) $affiliate->user_id === (int) $order->user_id) {
            return AffiliateCommissionEligibility::REASON_SELF_PURCHASE;
        }

        if (AffiliateSettings::commissionNewCustomersOnly()) {
            $ineligible = $this->newCustomerIneligibleReason($order);
            if ($ineligible !== null) {
                return $ineligible;
            }
        }

        $order->loadMissing('items.product');
        $lineTotals = $this->orderLineTotals($order);

        if ($lineTotals['eligible'] <= 0) {
            return AffiliateCommissionEligibility::REASON_NO_ELIGIBLE_PRODUCTS;
        }

        $base = $this->calculateCommissionBase($order, $lineTotals['eligible'], $lineTotals['all']);

        if ($base <= 0) {
            return AffiliateCommissionEligibility::REASON_NO_COMMISSION_BASE;
        }

        $amount = round($base * ($affiliate->effectiveCommissionPercent() / 100), 2);
        if ($amount <= 0) {
            return AffiliateCommissionEligibility::REASON_ZERO_COMMISSION;
        }

        return null;
    }

    /**
     * Affiliate-acquired customer: first paid order on the site was via affiliate;
     * further paid orders within the attribution window (14 days) also qualify.
     */
    public function qualifiesForNewCustomerCommission(Order $order): bool
    {
        return $this->newCustomerIneligibleReason($order) === null;
    }

    /**
     * @return non-empty-string|null
     */
    public function newCustomerIneligibleReason(Order $order): ?string
    {
        $firstPaid = $this->firstPaidOrderForCustomer($order);
        if (! $firstPaid) {
            return AffiliateCommissionEligibility::REASON_RETURNING_CUSTOMER;
        }

        if (! $firstPaid->affiliate_id) {
            return AffiliateCommissionEligibility::REASON_PRIOR_NON_AFFILIATE_PURCHASE;
        }

        if ((int) $firstPaid->id === (int) $order->id) {
            return null;
        }

        $windowDays = AffiliateSettings::attributionWindowDays();
        $firstAt = $firstPaid->paid_at ?? $firstPaid->created_at;
        $orderAt = $order->paid_at ?? $order->created_at;
        if (! $firstAt || ! $orderAt) {
            return AffiliateCommissionEligibility::REASON_RETURNING_CUSTOMER;
        }

        if ($orderAt->gt($firstAt->copy()->addDays($windowDays))) {
            return AffiliateCommissionEligibility::REASON_OUTSIDE_ACQUISITION_WINDOW;
        }

        return null;
    }

    public function firstPaidOrderForCustomer(Order $order): ?Order
    {
        $query = $this->customerPaidOrdersQuery($order);
        if ($query === null) {
            return null;
        }

        return $query
            ->orderByRaw('COALESCE(paid_at, created_at) ASC')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Order>|null
     */
    private function customerPaidOrdersQuery(Order $order): ?\Illuminate\Database\Eloquent\Builder
    {
        $email = strtolower(trim((string) $order->customer_email));

        if (! $order->user_id && $email === '') {
            return null;
        }

        $query = Order::query()->where('payment_status', 'paid');

        if ($order->user_id) {
            $query->where(function ($sub) use ($order, $email) {
                $sub->where('user_id', $order->user_id);
                if ($email !== '') {
                    $sub->orWhereRaw('LOWER(TRIM(customer_email)) = ?', [$email]);
                }
            });
        } else {
            $query->whereRaw('LOWER(TRIM(customer_email)) = ?', [$email]);
        }

        return $query;
    }

    public function markOrderCommissionEligible(Order $order, ?string $note = null): void
    {
        $order->forceFill([
            'affiliate_commission_eligibility' => AffiliateCommissionEligibility::ELIGIBLE,
            'affiliate_commission_note' => $note,
        ])->saveQuietly();
    }

    public function markOrderCommissionIneligible(Order $order, string $reasonCode): void
    {
        $order->forceFill([
            'affiliate_commission_eligibility' => AffiliateCommissionEligibility::INELIGIBLE,
            'affiliate_commission_note' => $reasonCode,
        ])->saveQuietly();
    }

    public function clearAffiliateCommissionEligibility(Order $order): void
    {
        if ($order->affiliate_commission_eligibility === null && $order->affiliate_commission_note === null) {
            return;
        }

        $order->forceFill([
            'affiliate_commission_eligibility' => null,
            'affiliate_commission_note' => null,
        ])->saveQuietly();
    }

    /**
     * Void or reduce commission when order is refunded (full or partial).
     */
    public function syncRefundState(Order $order): void
    {
        $refundAmount = (float) ($order->refund_amount ?? 0);
        if ($refundAmount <= 0 && $order->payment_status === 'refunded') {
            $refundAmount = (float) ($order->total_amount ?? 0);
        }
        if ($refundAmount <= 0) {
            return;
        }

        $total = (float) ($order->total_amount ?? 0);
        if ($total <= 0) {
            return;
        }

        $ratio = min(1.0, $refundAmount / $total);
        if ($ratio <= 0) {
            return;
        }

        $this->applyCommissionReduction(
            $order,
            $ratio,
            $ratio >= 0.999
                ? 'Order fully refunded'
                : 'Order partial refund ('.round($ratio * 100, 2).'%)'
        );
    }

    /**
     * No commission while dispute is open; void permanently if merchant loses.
     */
    public function syncDisputeState(Order $order): void
    {
        if ($order->dispute_status === 'won') {
            $this->restoreCommissionAfterDisputeWon($order);

            return;
        }

        if (! $order->hasActivePaymentDispute()) {
            return;
        }

        $label = $order->dispute_status === 'lost'
            ? 'Payment dispute lost (chargeback)'
            : 'Payment dispute open';

        $this->applyCommissionReduction($order, 1.0, $label);
        if ($order->affiliate_id) {
            $this->markOrderCommissionIneligible($order, AffiliateCommissionEligibility::REASON_PAYMENT_DISPUTE);
        }
    }

    public function markCommissionPaid(AffiliateCommission $commission): void
    {
        if ($commission->status !== AffiliateCommission::STATUS_PENDING) {
            return;
        }

        $commission->loadMissing('affiliate', 'order');
        if ($commission->order?->hasActivePaymentDispute()) {
            throw ValidationException::withMessages([
                'payout' => 'Cannot pay commission while the order has an open or lost payment dispute.',
            ]);
        }

        if (! $commission->affiliate?->canReceivePayout()) {
            throw ValidationException::withMessages([
                'payout' => 'This affiliate must complete payout information in the creator portal before commissions can be marked paid.',
            ]);
        }

        $commission->update([
            'status' => AffiliateCommission::STATUS_PAID,
            'paid_at' => now(),
        ]);
        $commission->loadMissing('affiliate');
        if ($commission->affiliate) {
            app(AffiliateTierService::class)->syncTierFromSales($commission->affiliate);
        }
    }

    /**
     * @return array{all: float, eligible: float}
     */
    private function orderLineTotals(Order $order): array
    {
        $all = 0.0;
        $eligible = 0.0;

        foreach ($order->items as $item) {
            $line = (float) $item->total_price;
            $all += $line;
            $product = $item->product;
            if ($product && $product->isEligibleForAffiliateCommission()) {
                $eligible += $line;
            }
        }

        return ['all' => $all, 'eligible' => $eligible];
    }

    /**
     * Commission base on what the customer actually paid for merchandise (after volume combo discount).
     *
     * order.subtotal is stored AFTER volume discount; order_items.total_price are pre-discount list prices.
     * We allocate net subtotal to eligible lines by their share of line totals, then subtract promo discount.
     */
    private function calculateCommissionBase(Order $order, float $eligibleLineTotal, float $allLinesTotal): float
    {
        if ($eligibleLineTotal <= 0 || $allLinesTotal <= 0) {
            return 0.0;
        }

        $orderSubtotal = max(0.0, (float) $order->subtotal);
        $promoDiscount = max(0.0, (float) ($order->discount_amount ?? 0));
        $netMerchandise = max(0.0, $orderSubtotal - $promoDiscount);

        $eligibleShare = min(1.0, $eligibleLineTotal / $allLinesTotal);

        return max(0.0, round($netMerchandise * $eligibleShare, 2));
    }

    private function restoreCommissionAfterDisputeWon(Order $order): void
    {
        if ($order->payment_status !== 'paid' || $order->hasActivePaymentDispute()) {
            return;
        }

        $refundAmount = (float) ($order->refund_amount ?? 0);
        if ($refundAmount > 0) {
            return;
        }

        $commission = AffiliateCommission::query()->where('order_id', $order->id)->first();
        if (! $commission || $commission->status !== AffiliateCommission::STATUS_VOID) {
            return;
        }

        $origBase = (float) ($commission->original_commission_base ?? $commission->commission_base);
        $origAmt = (float) ($commission->original_commission_amount ?? $commission->commission_amount);
        if ($origAmt <= 0) {
            return;
        }

        try {
            DB::transaction(function () use ($commission, $origBase, $origAmt, $order): void {
                $commission->refresh();
                if ($commission->status !== AffiliateCommission::STATUS_VOID) {
                    return;
                }

                $commission->update([
                    'status' => AffiliateCommission::STATUS_PENDING,
                    'commission_base' => $origBase,
                    'commission_amount' => $origAmt,
                    'paid_at' => null,
                ]);

                AffiliateBalanceAdjustment::query()
                    ->where('affiliate_id', $commission->affiliate_id)
                    ->where('order_id', $commission->order_id)
                    ->where('type', 'clawback')
                    ->delete();

                $this->markOrderCommissionEligible($order, null);
            });
        } catch (\Throwable $e) {
            Log::error('affiliate.restore_after_dispute_won_failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function applyCommissionReduction(Order $order, float $ratio, string $note): void
    {
        $ratio = min(1.0, max(0.0, $ratio));
        if ($ratio <= 0) {
            return;
        }

        $commission = AffiliateCommission::query()->where('order_id', $order->id)->first();
        if (! $commission) {
            return;
        }

        try {
            DB::transaction(function () use ($order, $commission, $ratio, $note): void {
                $commission->refresh();

                if ($commission->status === AffiliateCommission::STATUS_VOID) {
                    return;
                }

                if ($commission->status === AffiliateCommission::STATUS_PENDING) {
                    $origBase = (float) ($commission->original_commission_base ?? $commission->commission_base);
                    $origAmt = (float) ($commission->original_commission_amount ?? $commission->commission_amount);
                    if ($ratio >= 0.999) {
                        $commission->update(['status' => AffiliateCommission::STATUS_VOID]);
                    } else {
                        $newBase = round($origBase * (1 - $ratio), 2);
                        $newAmount = round($origAmt * (1 - $ratio), 2);
                        $commission->update([
                            'commission_base' => $newBase,
                            'commission_amount' => $newAmount,
                        ]);
                    }

                    return;
                }

                if ($commission->status === AffiliateCommission::STATUS_PAID) {
                    $origAmt = (float) ($commission->original_commission_amount ?? $commission->commission_amount);
                    $claw = round($origAmt * $ratio, 2);
                    if ($claw <= 0) {
                        return;
                    }

                    if ($ratio >= 0.999) {
                        $commission->update(['status' => AffiliateCommission::STATUS_VOID]);
                    }

                    AffiliateBalanceAdjustment::query()->updateOrCreate(
                        [
                            'affiliate_id' => $commission->affiliate_id,
                            'order_id' => $order->id,
                            'type' => 'clawback',
                        ],
                        [
                            'amount' => -$claw,
                            'note' => $note,
                        ]
                    );
                }
            });
        } catch (\Throwable $e) {
            Log::error('affiliate.commission_reduction_failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
