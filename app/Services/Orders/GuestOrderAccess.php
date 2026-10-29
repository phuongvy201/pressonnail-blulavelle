<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class GuestOrderAccess
{
    public const OTP_TTL_SECONDS = 600;

    public const OTP_MAX_ATTEMPTS = 5;

    /**
     * @return array{order: Order}|array{error: string, status: int}
     */
    public function findByNumberAndEmail(string $orderNumber, string $email): array
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->whereRaw('LOWER(customer_email) = ?', [strtolower(trim($email))])
            ->with(['items.product'])
            ->first();

        if (! $order) {
            return ['error' => 'Order not found. Check the order number and email from your confirmation.', 'status' => 404];
        }

        return ['order' => $order];
    }

    public function findByLookupToken(string $token): ?Order
    {
        if (! preg_match('/^[a-z0-9]{32,64}$/', $token)) {
            return null;
        }

        return Order::query()
            ->where('lookup_token', $token)
            ->with(['items.product'])
            ->first();
    }

    /**
     * @return array{sent: bool}|array{error: string, status: int}
     */
    public function sendTrackOtp(Order $order): array
    {
        $code = (string) random_int(100000, 999999);
        $key = $this->otpCacheKey($order->order_number, (string) $order->customer_email);

        Cache::put($key, [
            'code' => $code,
            'attempts' => 0,
        ], now()->addSeconds(self::OTP_TTL_SECONDS));

        try {
            Mail::to($order->customer_email)->send(new \App\Mail\OrderTrackOtpMail(
                $order->order_number,
                $code,
                (string) ($order->customer_name ?: 'there'),
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return ['error' => 'We could not send a verification email right now. Please try again shortly.', 'status' => 503];
        }

        return ['sent' => true];
    }

    /**
     * @return array{order: Order}|array{error: string, status: int}
     */
    public function verifyTrackOtp(string $orderNumber, string $email, string $otp): array
    {
        $found = $this->findByNumberAndEmail($orderNumber, $email);
        if (isset($found['error'])) {
            return $found;
        }

        /** @var Order $order */
        $order = $found['order'];
        $key = $this->otpCacheKey($order->order_number, (string) $order->customer_email);
        $payload = Cache::get($key);

        if (! is_array($payload) || empty($payload['code'])) {
            return ['error' => 'That code has expired. Request a new verification code.', 'status' => 422];
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            Cache::forget($key);

            return ['error' => 'Too many incorrect codes. Request a new verification code.', 'status' => 429];
        }

        if (! hash_equals((string) $payload['code'], trim($otp))) {
            $payload['attempts'] = $attempts + 1;
            Cache::put($key, $payload, now()->addSeconds(self::OTP_TTL_SECONDS));

            return ['error' => 'That verification code is incorrect.', 'status' => 422];
        }

        Cache::forget($key);
        $order->ensureLookupToken();

        return ['order' => $order->fresh(['items.product'])];
    }

    /**
     * Attach guest orders to a verified account email.
     */
    public function claimForVerifiedUser(User $user): int
    {
        if (! $user->hasVerifiedEmail() || ! is_string($user->email) || $user->email === '') {
            return 0;
        }

        return Order::query()
            ->whereNull('user_id')
            ->whereRaw('LOWER(customer_email) = ?', [strtolower($user->email)])
            ->update(['user_id' => $user->id]);
    }

    /**
     * Public guest payload — PII is masked.
     *
     * @return array<string, mixed>
     */
    public function publicPayload(Order $order): array
    {
        $order->ensureLookupToken();

        return [
            'orderNumber' => $order->order_number,
            'lookupToken' => $order->lookup_token,
            'status' => $order->status,
            'paymentStatus' => $order->payment_status,
            'totalAmount' => (float) $order->total_amount,
            'currency' => $order->currency ?? 'USD',
            'trackingNumber' => $order->tracking_number,
            'createdAt' => $order->created_at?->toIso8601String(),
            'customerName' => $this->maskName((string) $order->customer_name),
            'customerEmail' => $this->maskEmail((string) $order->customer_email),
            'customerPhone' => $this->maskPhone($order->customer_phone),
            'city' => $order->city,
            'state' => $order->state,
            'postalCode' => $this->maskPostalCode($order->postal_code),
            'country' => $order->country,
            'items' => $order->items->map(fn ($item) => [
                'productName' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'totalPrice' => (float) $item->total_price,
            ])->values()->all(),
        ];
    }

    public function maskEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if (! str_contains($email, '@')) {
            return '***';
        }
        [$local, $domain] = explode('@', $email, 2);
        $keep = max(1, min(2, strlen($local)));

        return substr($local, 0, $keep).str_repeat('*', max(1, strlen($local) - $keep)).'@'.$domain;
    }

    public function maskPhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }

    public function maskName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if ($parts === []) {
            return 'Customer';
        }
        $first = $parts[0];
        if (count($parts) === 1) {
            return $first;
        }

        return $first.' '.strtoupper(substr((string) end($parts), 0, 1)).'.';
    }

    public function maskPostalCode(?string $postal): ?string
    {
        if ($postal === null || trim($postal) === '') {
            return null;
        }
        $value = trim($postal);
        if (strlen($value) <= 3) {
            return '***';
        }

        return substr($value, 0, 2).str_repeat('*', max(1, strlen($value) - 3)).substr($value, -1);
    }

    private function otpCacheKey(string $orderNumber, string $email): string
    {
        return 'order-track-otp:'.hash('sha256', strtolower(trim($orderNumber)).'|'.strtolower(trim($email)));
    }
}
