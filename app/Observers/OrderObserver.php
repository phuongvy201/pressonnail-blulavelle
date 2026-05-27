<?php

namespace App\Observers;

use App\Models\Affiliate;
use App\Models\Order;
use App\Services\AffiliateCommissionService;
use App\Services\AffiliateTierService;
use App\Services\GiftCardService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function saved(Order $order): void
    {
        $becamePaid = ($order->wasRecentlyCreated && $order->payment_status === 'paid')
            || (!$order->wasRecentlyCreated && $order->wasChanged('payment_status') && $order->payment_status === 'paid');

        if ($becamePaid) {
            try {
                /** @var GiftCardService $giftCardService */
                $giftCardService = app(GiftCardService::class);
                $giftCardService->applyDebitForOrder($order);
                $giftCardService->processGiftCardOrderItems($order);
            } catch (\Throwable $e) {
                Log::error('gift-card.order_observer_failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                app(AffiliateCommissionService::class)->createPendingCommissionIfEligible($order);
            } catch (\Throwable $e) {
                Log::error('affiliate.order_observer_commission_failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($order->affiliate_id) {
                try {
                    $affiliate = Affiliate::query()->find($order->affiliate_id);
                    if ($affiliate) {
                        app(AffiliateTierService::class)->evaluateTier($affiliate);
                    }
                } catch (\Throwable $e) {
                    Log::error('affiliate.order_observer_tier_failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $refundTouched = ! $order->wasRecentlyCreated && (
            ($order->wasChanged('payment_status') && $order->payment_status === 'refunded')
            || ($order->wasChanged('refund_status') && $order->refund_status === 'completed')
            || $order->wasChanged('refund_amount')
        );

        if ($refundTouched) {
            try {
                app(AffiliateCommissionService::class)->syncRefundState($order);
            } catch (\Throwable $e) {
                Log::error('affiliate.order_observer_refund_sync_failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $disputeTouched = ! $order->wasRecentlyCreated && $order->wasChanged('dispute_status');

        if ($disputeTouched) {
            try {
                app(AffiliateCommissionService::class)->syncDisputeState($order);
            } catch (\Throwable $e) {
                Log::error('affiliate.order_observer_dispute_sync_failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
