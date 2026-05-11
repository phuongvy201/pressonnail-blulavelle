<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\GiftCardService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (!$order->wasChanged('payment_status') || $order->payment_status !== 'paid') {
            return;
        }

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
    }
}
