<?php

namespace App\Services;

use App\Mail\GiftCardIssuedMail;
use App\Mail\GiftCardUsageMail;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use App\Models\Order;
use App\Models\OrderGiftCardUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GiftCardService
{
    public function normalizeCode(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    public function findByCode(?string $code): ?GiftCard
    {
        $normalized = $this->normalizeCode($code);
        if ($normalized === '') {
            return null;
        }

        return GiftCard::whereRaw('UPPER(code) = ?', [$normalized])->first();
    }

    public function getAvailableAmountForCheckout(string $code, float $orderAmount): float
    {
        $giftCard = $this->findByCode($code);
        if (!$giftCard || !$giftCard->isUsable()) {
            return 0.0;
        }

        return round(min((float) $giftCard->balance, max(0.0, $orderAmount)), 2);
    }

    public function applyDebitForOrder(Order $order): void
    {
        $code = $this->normalizeCode($order->gift_card_code);
        $amount = (float) ($order->gift_card_amount ?? 0);
        if ($code === '' || $amount <= 0) {
            return;
        }

        DB::transaction(function () use ($order, $code, $amount) {
            $giftCard = GiftCard::whereRaw('UPPER(code) = ?', [$code])->lockForUpdate()->first();
            if (!$giftCard || !$giftCard->isUsable()) {
                Log::warning('gift-card.debit_skipped_invalid', [
                    'order_id' => $order->id,
                    'code' => $code,
                ]);
                return;
            }

            if (OrderGiftCardUsage::where('order_id', $order->id)->where('gift_card_id', $giftCard->id)->exists()) {
                return;
            }

            $debitAmount = round(min((float) $giftCard->balance, $amount), 2);
            if ($debitAmount <= 0) {
                return;
            }

            $before = (float) $giftCard->balance;
            $after = round($before - $debitAmount, 2);
            $giftCard->update([
                'balance' => $after,
                'last_used_at' => now(),
            ]);

            GiftCardTransaction::create([
                'gift_card_id' => $giftCard->id,
                'order_id' => $order->id,
                'type' => 'debit',
                'amount' => $debitAmount,
                'balance_before' => $before,
                'balance_after' => $after,
                'currency' => $giftCard->currency,
                'meta' => ['order_number' => $order->order_number],
            ]);

            OrderGiftCardUsage::create([
                'order_id' => $order->id,
                'gift_card_id' => $giftCard->id,
                'gift_card_code' => $giftCard->code,
                'amount' => $debitAmount,
            ]);

            if (!empty($order->customer_email)) {
                try {
                    Mail::to($order->customer_email)->send(
                        new GiftCardUsageMail($giftCard, $order, $debitAmount, $after)
                    );
                } catch (\Throwable $e) {
                    Log::warning('gift-card.usage_email_failed', [
                        'order_id' => $order->id,
                        'gift_card_id' => $giftCard->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    public function processGiftCardOrderItems(Order $order): void
    {
        $items = $order->items()->with('product')->get()->filter(fn ($item) => (bool) optional($item->product)->is_gift_card);
        if ($items->isEmpty()) {
            return;
        }

        foreach ($items as $item) {
            $this->issueOrTopupFromOrderItem($order, $item->product_name, (float) $item->total_price, (array) ($item->product_options ?? []));
        }
    }

    public function issueOrTopupFromOrderItem(Order $order, string $productName, float $amount, array $productOptions = []): ?GiftCard
    {
        $customizations = (array) ($productOptions['customizations'] ?? []);
        $recipientEmail = $this->extractValue($customizations, ['recipient_email', 'email', 'receiver_email']);
        $recipientName = $this->extractValue($customizations, ['recipient_name', 'receiver_name', 'to_name']);
        $existingCode = $this->extractValue($customizations, [
            'gift_card_code',
            'existing_code',
            'topup_code',
            'top_up_code',
            'recharge_code',
            'existing_gift_card_code',
            'gift_card_top_up',
            'add_to_existing',
            'ma_the_qua_tang',
            'ma_qua_tang',
        ]);
        $message = $this->extractValue($customizations, ['message', 'gift_message']);

        $amount = round(max(0.0, $amount), 2);
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($order, $amount, $existingCode, $recipientEmail, $recipientName, $message, $productName) {
            $giftCard = $existingCode !== ''
                ? GiftCard::whereRaw('UPPER(code) = ?', [$this->normalizeCode($existingCode)])->lockForUpdate()->first()
                : null;

            $isTopup = (bool) $giftCard;
            if (!$giftCard) {
                $code = $this->generateUniqueCode();
                $giftCard = GiftCard::create([
                    'code' => $code,
                    'initial_balance' => $amount,
                    'balance' => $amount,
                    'currency' => $order->currency ?? 'USD',
                    'is_active' => true,
                    'recipient_email' => $recipientEmail ?: null,
                    'recipient_name' => $recipientName ?: null,
                    'purchaser_email' => $order->customer_email,
                    'meta' => [
                        'source_order_id' => $order->id,
                        'source_order_number' => $order->order_number,
                        'product_name' => $productName,
                        'message' => $message,
                    ],
                ]);

                GiftCardTransaction::create([
                    'gift_card_id' => $giftCard->id,
                    'order_id' => $order->id,
                    'type' => 'issue',
                    'amount' => $amount,
                    'balance_before' => 0,
                    'balance_after' => $amount,
                    'currency' => $giftCard->currency,
                    'meta' => ['source' => 'order_item_issue'],
                ]);
            } else {
                $before = (float) $giftCard->balance;
                $after = round($before + $amount, 2);
                $giftCard->update([
                    'balance' => $after,
                    'recipient_email' => $recipientEmail ?: $giftCard->recipient_email,
                    'recipient_name' => $recipientName ?: $giftCard->recipient_name,
                    'purchaser_email' => $order->customer_email ?: $giftCard->purchaser_email,
                ]);

                GiftCardTransaction::create([
                    'gift_card_id' => $giftCard->id,
                    'order_id' => $order->id,
                    'type' => 'topup',
                    'amount' => $amount,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'currency' => $giftCard->currency,
                    'meta' => ['source' => 'order_item_topup'],
                ]);
            }

            $to = $giftCard->recipient_email ?: $order->customer_email;
            if ($to) {
                try {
                    Mail::to($to)->send(new GiftCardIssuedMail($giftCard, $order, $amount, $isTopup, $message));
                } catch (\Throwable $e) {
                    Log::warning('gift-card.email_failed', [
                        'order_id' => $order->id,
                        'gift_card_id' => $giftCard->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $giftCard;
        });
    }

    private function extractValue(array $customizations, array $candidateKeys): string
    {
        foreach ($customizations as $key => $entry) {
            $normalizedKey = Str::of((string) $key)->lower()->replace([' ', '-'], '_')->value();
            foreach ($candidateKeys as $candidateKey) {
                if ($this->customizationKeyMatches($normalizedKey, (string) $candidateKey)) {
                    if (is_array($entry)) {
                        return trim((string) ($entry['value'] ?? ''));
                    }
                    return trim((string) $entry);
                }
            }
        }

        return '';
    }

    /**
     * Khớp nhãn customization với từ khóa (hỗ trợ "Top-up code" → top_up_code khớp topup_code).
     */
    private function customizationKeyMatches(string $normalizedKey, string $candidateKey): bool
    {
        $candidate = Str::of($candidateKey)->lower()->replace([' ', '-'], '_')->value();
        if ($normalizedKey === $candidate) {
            return true;
        }
        if (str_contains($normalizedKey, $candidate) || str_contains($candidate, $normalizedKey)) {
            return true;
        }

        $keyCollapsed = str_replace('_', '', $normalizedKey);
        $candCollapsed = str_replace('_', '', $candidate);
        if ($keyCollapsed === '' || $candCollapsed === '') {
            return false;
        }

        return str_contains($keyCollapsed, $candCollapsed) || str_contains($candCollapsed, $keyCollapsed);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'GC-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (GiftCard::where('code', $code)->exists());

        return $code;
    }
}
