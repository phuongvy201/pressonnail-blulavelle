<?php

namespace App\Services;

use App\Mail\PromoCodeRewardMail;
use App\Models\PromoCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PromoCodeSendService
{
    public const TRIGGER_THANK_YOU = 'thank_you';
    public const TRIGGER_WISHLIST = 'wishlist';
    public const TRIGGER_ADD_TO_CART = 'add_to_cart';
    public const TRIGGER_CHECKOUT_FAIL = 'checkout_fail';

    /** Throttle: gửi tối đa 1 email per user per trigger trong 24h (cho wishlist, add_to_cart, checkout_fail) */
    public const THROTTLE_HOURS = 24;

    /**
     * Gửi email chứa mã promo cho trigger tương ứng.
     * Trả về true nếu đã gửi, false nếu không gửi (không có mã / throttle / lỗi).
     */
    public function sendForTrigger(string $email, string $trigger, ?int $userId = null, bool $throttle = true): bool
    {
        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $promo = PromoCode::where('send_on_trigger', $trigger)
            ->where('is_active', true)
            ->first();

        if (!$promo) {
            Log::debug('PromoCodeSendService: No active promo for trigger.', ['trigger' => $trigger]);
            return false;
        }

        if ($throttle && in_array($trigger, [self::TRIGGER_WISHLIST, self::TRIGGER_ADD_TO_CART, self::TRIGGER_CHECKOUT_FAIL], true)) {
            $throttleKey = $userId !== null ? "uid_{$userId}" : 'email_' . md5(strtolower($email));
            $cacheKey = "promo_sent_{$trigger}_{$throttleKey}";
            if (Cache::has($cacheKey)) {
                return false;
            }
        }

        try {
            $triggerLabels = [
                self::TRIGGER_THANK_YOU => 'Thanks for your order!',
                self::TRIGGER_WISHLIST => 'Thanks for adding to your favorites!',
                self::TRIGGER_ADD_TO_CART => 'Thanks for adding to your cart!',
                self::TRIGGER_CHECKOUT_FAIL => 'Your checkout did not complete - here is 20% OFF!',
            ];
            $label = $triggerLabels[$trigger] ?? 'Here’s your reward';
            $description = $this->promoDescription($promo);

            Mail::to($email)->send(new PromoCodeRewardMail($email, $promo->code, $label, $description));

            if ($throttle && in_array($trigger, [self::TRIGGER_WISHLIST, self::TRIGGER_ADD_TO_CART, self::TRIGGER_CHECKOUT_FAIL], true)) {
                Cache::put($cacheKey, true, now()->addHours(self::THROTTLE_HOURS));
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('PromoCodeSendService: Failed to send promo email.', [
                'trigger' => $trigger,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Gửi promo cho trang Thank you (mỗi đơn chỉ gửi 1 lần). Gọi từ CheckoutController::success.
     */
    public function sendThankYouIfNotSent(string $orderCustomerEmail, $order): bool
    {
        if ($order->promo_email_sent_at !== null) {
            return false;
        }

        $sent = $this->sendForTrigger($orderCustomerEmail, self::TRIGGER_THANK_YOU, null, false);

        if ($sent && isset($order->id)) {
            $order->update(['promo_email_sent_at' => now()]);
        }

        return $sent;
    }

    private function promoDescription(PromoCode $promo): ?string
    {
        if ($promo->type === 'percentage') {
            return (int) $promo->value . '% off, effective immediately at checkout.';
        }
        if ($promo->type === 'fixed') {
            return '$' . number_format((float) $promo->value, 0) . ' off, effective immediately at checkout.';
        }
        return null;
    }
}
