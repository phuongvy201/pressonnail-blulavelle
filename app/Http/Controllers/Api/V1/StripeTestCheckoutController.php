<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class StripeTestCheckoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $disabled = $this->guard();
        if ($disabled) {
            return $disabled;
        }

        $key = trim((string) $request->header('Idempotency-Key', ''));
        if (strlen($key) < 8) {
            return ApiResponse::error(
                'IDEMPOTENCY_KEY_REQUIRED',
                'Send Idempotency-Key so a retried test payment reuses the same Stripe session.',
                422
            );
        }

        $validated = $request->validate([
            'total' => ['required', 'numeric', 'min:0.5', 'max:500'],
            'currency' => ['required', 'string', 'size:3'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
        ]);

        $replayKey = 'stripe-test-checkout:key:'.hash('sha256', $key);
        $existingId = Cache::get($replayKey);
        if (is_string($existingId)) {
            $existing = Cache::get($this->attemptKey($existingId));
            if (is_array($existing) && ! empty($existing['checkout_url'])) {
                return ApiResponse::success($this->payload($existing), ['replayed' => true]);
            }
        }

        $attemptId = 'stest_'.Str::lower(Str::random(24));
        $amount = ApiResponse::toMinor($validated['total']);
        $currency = strtolower($validated['currency']);
        Stripe::setApiKey((string) config('services.stripe.secret'));
        $returnUrl = rtrim($request->getSchemeAndHttpHost(), '/').'/api/v1/payments/stripe/test-checkout/complete';

        $session = StripeCheckoutSession::create([
            'mode' => 'payment',
            'customer_email' => $validated['customer_email'],
            'client_reference_id' => $attemptId,
            'success_url' => $returnUrl.'?attempt='.urlencode($attemptId),
            'cancel_url' => $returnUrl.'?attempt='.urlencode($attemptId).'&cancelled=1',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amount,
                    'product_data' => [
                        'name' => 'BluLavelle mobile Stripe test',
                    ],
                ],
            ]],
            'metadata' => [
                'mobile_stripe_test' => '1',
                'attempt_id' => $attemptId,
            ],
        ], [
            'idempotency_key' => 'mobile-stripe-test-'.$key,
        ]);

        $stored = [
            'attemptId' => $attemptId,
            'session_id' => $session->id,
            'checkout_url' => $session->url,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'requires_action',
        ];
        Cache::put($this->attemptKey($attemptId), $stored, now()->addHours(2));
        Cache::put($replayKey, $attemptId, now()->addHours(2));

        return ApiResponse::success($this->payload($stored), ['replayed' => false], 201);
    }

    public function show(string $attemptId): JsonResponse
    {
        $disabled = $this->guard();
        if ($disabled) {
            return $disabled;
        }

        $stored = $this->find($attemptId);
        if (! $stored) {
            return ApiResponse::error('NOT_FOUND', 'Stripe test checkout was not found.', 404);
        }

        return ApiResponse::success($this->payload($stored));
    }

    public function confirm(string $attemptId): JsonResponse
    {
        $disabled = $this->guard();
        if ($disabled) {
            return $disabled;
        }

        $stored = $this->find($attemptId);
        if (! $stored) {
            return ApiResponse::error('NOT_FOUND', 'Stripe test checkout was not found.', 404);
        }

        Stripe::setApiKey((string) config('services.stripe.secret'));
        $session = StripeCheckoutSession::retrieve($stored['session_id']);
        $paid = ($session->payment_status ?? null) === 'paid';
        $stored['status'] = $paid ? 'succeeded' : 'requires_action';
        $stored['payment_intent_id'] = is_string($session->payment_intent ?? null) ? $session->payment_intent : null;
        Cache::put($this->attemptKey($attemptId), $stored, now()->addHours(2));

        return ApiResponse::success($this->payload($stored));
    }

    public function complete(): JsonResponse
    {
        return ApiResponse::success([
            'message' => 'Return to the BluLavelle app and check the payment.',
        ]);
    }

    private function guard(): ?JsonResponse
    {
        if (! config('api_v1.stripe_test_checkout_enabled')) {
            return ApiResponse::error('NOT_FOUND', 'Stripe test checkout is disabled.', 404);
        }

        $secret = config('services.stripe.secret');
        if (! is_string($secret) || ! str_starts_with($secret, 'sk_test_')) {
            return ApiResponse::error(
                'STRIPE_TEST_KEY_REQUIRED',
                'Stripe test checkout only runs when the server uses an sk_test secret.',
                503
            );
        }

        return null;
    }

    private function find(string $attemptId): ?array
    {
        $stored = Cache::get($this->attemptKey($attemptId));

        return is_array($stored) ? $stored : null;
    }

    private function attemptKey(string $attemptId): string
    {
        return 'stripe-test-checkout:attempt:'.$attemptId;
    }

    private function payload(array $stored): array
    {
        return [
            'attemptId' => $stored['attemptId'],
            'status' => $stored['status'],
            'orderNumber' => null,
            'clientSecret' => null,
            'checkoutUrl' => $stored['checkout_url'],
            'paymentIntentId' => $stored['payment_intent_id'] ?? null,
            'amount' => $stored['amount'],
            'currency' => $stored['currency'],
        ];
    }
}
