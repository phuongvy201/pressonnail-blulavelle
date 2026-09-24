<?php

namespace App\Services;

use App\Http\Controllers\CheckoutController;
use App\Models\CheckoutAttempt;
use App\Models\Order;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class CheckoutAttemptService
{
    public function __construct(
        private CheckoutIdempotencyService $idempotency,
        private CheckoutController $checkout,
    ) {}

    public function createOrResume(Request $request): JsonResponse
    {
        $key = $this->idempotency->extractKey($request);
        if ($key === null) {
            return ApiResponse::error(
                'IDEMPOTENCY_KEY_REQUIRED',
                'Send Idempotency-Key so retries recover the same checkout attempt.',
                422
            );
        }

        $hash = $this->idempotency->hashRequest($request);
        $existing = CheckoutAttempt::query()->where('idempotency_key', $key)->first();

        if ($existing) {
            if ($existing->request_hash !== $hash) {
                return ApiResponse::error(
                    'IDEMPOTENCY_KEY_REUSED',
                    'Idempotency-Key was already used with a different cart or address.',
                    409
                );
            }

            if ($existing->status === 'requires_action' && $existing->order && $this->usesCheckoutSession($request)) {
                $this->ensureStripeCheckoutSession($existing);
                $existing = $existing->fresh();
            } elseif ($existing->status === 'requires_action' && $existing->order && ! data_get($existing->response_body, 'client_secret')) {
                if (! config('api_v1.stripe_checkout_enabled')) {
                    return ApiResponse::error('STRIPE_CHECKOUT_DISABLED', 'Stripe checkout is not enabled for the mobile API.', 503);
                }
                $this->ensureStripeIntent($existing);
                $existing = $existing->fresh();
            }

            return ApiResponse::success($this->payload($existing), [
                'replayed' => true,
            ]);
        }

        $attempt = CheckoutAttempt::create([
            'idempotency_key' => $key,
            'request_hash' => $hash,
            'status' => 'processing',
            'user_id' => $request->user()?->id,
            'guest_token' => $request->attributes->get('mobile_guest_cart_token')
                ?: $request->header(config('api_v1.guest_cart_header', 'X-Guest-Cart-Token')),
            'payment_intent_id' => $request->input('payment_intent_id'),
            'request_snapshot' => $this->safeSnapshot($request),
            'locked_until' => now()->addSeconds((int) config('mobile_api.checkout_idempotency.lock_seconds', 60)),
            'expires_at' => now()->addHours((int) config('api_v1.attempt_ttl_hours', 24)),
        ]);

        $request->headers->set('Accept', 'application/json');
        $response = $this->checkout->process($request);
        $this->ingestCheckoutResponse($attempt, $response);

        $attempt = $attempt->fresh();
        $replayed = $response instanceof JsonResponse
            && $response->headers->get('Idempotency-Replayed') === 'true';

        if ($attempt->status === 'failed') {
            return ApiResponse::error(
                $attempt->error_code ?: 'CHECKOUT_FAILED',
                is_array($attempt->response_body) ? (string) ($attempt->response_body['message'] ?? 'Checkout failed.') : 'Checkout failed.',
                $this->httpStatusFor($attempt)
            );
        }

        if ($attempt->status === 'requires_action' && $attempt->order) {
            try {
                if (! config('api_v1.stripe_checkout_enabled')) {
                    return ApiResponse::error('STRIPE_CHECKOUT_DISABLED', 'Stripe checkout is not enabled for the mobile API.', 503);
                }
                if ($this->usesCheckoutSession($request)) {
                    $this->ensureStripeCheckoutSession($attempt);
                } else {
                    $this->ensureStripeIntent($attempt);
                }
            } catch (\Throwable $exception) {
                report($exception);

                return ApiResponse::error(
                    'STRIPE_INITIALIZATION_FAILED',
                    'The order was created, but Stripe could not start the payment. Please retry checkout.',
                    502
                );
            }
        }

        return ApiResponse::success($this->payload($attempt), [
            'replayed' => $replayed,
        ]);
    }

    public function show(CheckoutAttempt $attempt): JsonResponse
    {
        if ($attempt->isExpired() && ! in_array($attempt->status, ['succeeded', 'failed'], true)) {
            $attempt->update(['status' => 'expired']);
        }

        $attempt = $attempt->fresh();
        if ($attempt->status === 'requires_action' && $attempt->order && ! $attempt->payment_intent_id) {
            abort_unless(config('api_v1.stripe_checkout_enabled'), 503, 'Stripe checkout is not enabled for the mobile API.');
            $this->ensureStripeIntent($attempt);
        }

        return ApiResponse::success($this->payload($attempt->fresh()));
    }

    public function confirm(Request $request, CheckoutAttempt $attempt): JsonResponse
    {
        if ($attempt->status === 'succeeded') {
            return ApiResponse::success($this->payload($attempt), ['replayed' => true]);
        }

        $pi = $attempt->payment_intent_id;
        if (! $pi) {
            return ApiResponse::error('PAYMENT_INTENT_REQUIRED', 'payment_intent_id is required to confirm.', 422);
        }

        $checkoutSessionId = data_get($attempt->response_body, 'checkout_session_id');
        if ($checkoutSessionId) {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeCheckoutSession::retrieve($checkoutSessionId);
            $paymentIntentId = is_string($session->payment_intent ?? null)
                ? $session->payment_intent
                : ($session->payment_intent->id ?? null);
            if (($session->payment_status ?? null) === 'paid' && $paymentIntentId) {
                $this->markSucceededFromIntent($attempt, $paymentIntentId);
            } else {
                $attempt->update(['status' => 'requires_action']);
            }

            return ApiResponse::success($this->payload($attempt->fresh()));
        }
        $requestedPi = $request->input('paymentIntentId', $request->input('payment_intent_id'));
        if ($requestedPi && ! hash_equals($pi, (string) $requestedPi)) {
            return ApiResponse::error('PAYMENT_INTENT_MISMATCH', 'This payment intent does not belong to the checkout attempt.', 409);
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $intent = PaymentIntent::retrieve($pi);
        $attempt->payment_intent_id = $pi;

        if ($intent->status === 'succeeded') {
            $this->markSucceededFromIntent($attempt, $intent->id);

            return ApiResponse::success($this->payload($attempt->fresh()));
        }

        if ($intent->status === 'requires_action' || $intent->status === 'requires_confirmation') {
            $attempt->update([
                'status' => 'requires_action',
                'error_code' => null,
            ]);

            return ApiResponse::success($this->payload($attempt->fresh()));
        }

        $attempt->update([
            'status' => 'failed',
            'error_code' => 'PAYMENT_FAILED',
        ]);

        return ApiResponse::error('PAYMENT_FAILED', 'Payment was not completed.', 402);
    }

    public function retry(Request $request, CheckoutAttempt $attempt): JsonResponse
    {
        if (in_array($attempt->status, ['succeeded', 'processing'], true) && ! $attempt->isExpired()) {
            return ApiResponse::success($this->payload($attempt), ['replayed' => true]);
        }

        if (! in_array($attempt->status, ['failed', 'expired'], true)) {
            return ApiResponse::error(
                'RETRY_NOT_ALLOWED',
                'This attempt cannot be retried in its current status.',
                409
            );
        }

        $request->headers->set('Idempotency-Key', $attempt->idempotency_key);
        $request->headers->set('Accept', 'application/json');

        foreach ($attempt->request_snapshot ?? [] as $field => $value) {
            if (! $request->has($field)) {
                $request->merge([$field => $value]);
            }
        }

        $attempt->update([
            'status' => 'processing',
            'error_code' => null,
            'locked_until' => now()->addSeconds(60),
        ]);

        $response = $this->checkout->process($request);
        $this->ingestCheckoutResponse($attempt, $response);

        return ApiResponse::success($this->payload($attempt->fresh()), ['replayed' => false]);
    }

    public function payload(CheckoutAttempt $attempt): array
    {
        $order = $attempt->order;

        return [
            'attemptId' => $attempt->id,
            'status' => $attempt->isExpired() && $attempt->status === 'processing' ? 'expired' : $attempt->status,
            'idempotencyKey' => $attempt->idempotency_key,
            'orderId' => $attempt->order_id,
            'orderNumber' => $order?->order_number,
            'paymentIntentId' => $attempt->payment_intent_id,
            'clientSecret' => data_get($attempt->response_body, 'client_secret'),
            'checkoutUrl' => data_get($attempt->response_body, 'checkout_url'),
            'amount' => $order ? ApiResponse::toMinor($order->total_amount) : null,
            'currency' => strtolower((string) ($order?->currency ?: 'usd')),
            'errorCode' => $attempt->error_code,
            'result' => $this->normalizeResult($attempt, $order),
            'createdAt' => ApiResponse::iso($attempt->created_at),
            'updatedAt' => ApiResponse::iso($attempt->updated_at),
            'expiresAt' => ApiResponse::iso($attempt->expires_at),
        ];
    }

    private function usesCheckoutSession(Request $request): bool
    {
        return $request->input('payment_ui') === 'checkout_session';
    }

    private function ensureStripeCheckoutSession(CheckoutAttempt $attempt): void
    {
        $secret = config('services.stripe.secret');
        if (! is_string($secret) || $secret === '') {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }
        Stripe::setApiKey($secret);

        $body = $attempt->response_body ?? [];
        if (! empty($body['checkout_session_id'])) {
            $existing = StripeCheckoutSession::retrieve($body['checkout_session_id']);
            if (($existing->status ?? null) === 'open' && ! empty($existing->url)) {
                $body['checkout_url'] = $existing->url;
                $attempt->update(['response_body' => $body]);
                return;
            }
            if (($existing->status ?? null) === 'complete') {
                return;
            }
        }

        $order = $attempt->order;
        if (! $order || $order->payment_status === 'paid') {
            return;
        }
        $amount = ApiResponse::toMinor($order->total_amount);
        if ($amount < 50) {
            throw new \RuntimeException('Stripe requires a minimum payment amount of 50 minor units.');
        }

        $sessionGeneration = (int) ($body['checkout_session_generation'] ?? 0) + 1;
        $session = StripeCheckoutSession::create([
            'mode' => 'payment',
            'success_url' => route('checkout.success', ['orderNumber' => $order->order_number]) . '?checkout_attempt=' . urlencode($attempt->id),
            'cancel_url' => route('checkout.index'),
            'client_reference_id' => $attempt->id,
            'customer_email' => $order->customer_email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower((string) ($order->currency ?: 'usd')),
                    'unit_amount' => $amount,
                    'product_data' => ['name' => 'BluLavelle order ' . $order->order_number],
                ],
            ]],
            'metadata' => [
                'checkout_attempt_id' => $attempt->id,
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
            'payment_intent_data' => ['metadata' => [
                'checkout_attempt_id' => $attempt->id,
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ]],
        ], [
            'idempotency_key' => 'checkout-session-' . $attempt->id . '-' . $sessionGeneration,
        ]);

        $body['checkout_session_id'] = $session->id;
        $body['checkout_url'] = $session->url;
        $body['checkout_session_generation'] = $sessionGeneration;
        $attempt->update(['response_body' => $body]);
    }

    private function ensureStripeIntent(CheckoutAttempt $attempt): void
    {
        $secret = config('services.stripe.secret');
        if (! is_string($secret) || $secret === '') {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }
        Stripe::setApiKey($secret);

        if ($attempt->payment_intent_id) {
            $intent = PaymentIntent::retrieve($attempt->payment_intent_id);
            $this->storeClientSecret($attempt, $intent->client_secret);

            return;
        }

        $order = $attempt->order;
        if (! $order || $order->payment_status === 'paid') {
            return;
        }

        $amount = ApiResponse::toMinor($order->total_amount);
        if ($amount < 50) {
            throw new \RuntimeException('Stripe requires a minimum payment amount of 50 minor units.');
        }

        $intent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => strtolower((string) ($order->currency ?: 'usd')),
            'automatic_payment_methods' => ['enabled' => true],
            'receipt_email' => $order->customer_email,
            'metadata' => [
                'checkout_attempt_id' => $attempt->id,
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
        ], [
            'idempotency_key' => 'checkout-attempt-' . $attempt->id,
        ]);

        $order->update(['payment_id' => $intent->id]);
        $attempt->update(['payment_intent_id' => $intent->id]);
        $this->storeClientSecret($attempt->fresh(), $intent->client_secret);
    }

    private function storeClientSecret(CheckoutAttempt $attempt, ?string $clientSecret): void
    {
        $body = $attempt->response_body ?? [];
        $body['client_secret'] = $clientSecret;
        $attempt->update(['response_body' => $body]);
    }

    private function ingestCheckoutResponse(CheckoutAttempt $attempt, mixed $response): void
    {
        if (! $response instanceof JsonResponse) {
            $attempt->update(['status' => 'failed', 'error_code' => 'CHECKOUT_FAILED']);

            return;
        }

        $body = json_decode($response->getContent() ?: 'null', true) ?: [];
        $statusCode = $response->getStatusCode();

        $orderNumber = $body['order_number'] ?? null;
        $orderId = $body['order_id'] ?? null;
        if ($orderNumber && ! $orderId) {
            $orderId = Order::where('order_number', $orderNumber)->value('id');
        }

        $status = 'failed';
        $error = $body['error'] ?? null;
        if ($statusCode >= 500) {
            $status = 'failed';
            $error = 'INTERNAL_ERROR';
        } elseif (! empty($body['payment_completed']) || ($body['success'] ?? false) && empty($body['payment_pending'])) {
            $status = 'succeeded';
            $error = null;
        } elseif (! empty($body['requires_3ds']) || ! empty($body['payment_pending'])) {
            $status = 'requires_action';
            $error = null;
        } elseif ($statusCode === 409) {
            $error = strtoupper((string) ($body['error'] ?? 'CONFLICT'));
        } elseif ($body['success'] ?? false) {
            $status = 'processing';
            $error = null;
        }

        $attempt->update([
            'status' => $status,
            'order_id' => $orderId,
            'payment_intent_id' => $attempt->payment_intent_id ?: ($body['transaction_id'] ?? $attempt->payment_intent_id),
            'response_body' => $body,
            'error_code' => is_string($error) ? $error : null,
            'locked_until' => null,
        ]);
    }

    private function markSucceededFromIntent(CheckoutAttempt $attempt, string $paymentIntentId): void
    {
        $order = $attempt->order
            ?: Order::where('payment_id', $paymentIntentId)->first();

        if ($order && $order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'payment_id' => $paymentIntentId,
                'payment_transaction_id' => $paymentIntentId,
                'paid_at' => now(),
            ]);
        }

        $attempt->update([
            'status' => 'succeeded',
            'order_id' => $order?->id ?? $attempt->order_id,
            'payment_intent_id' => $paymentIntentId,
            'error_code' => null,
        ]);
    }

    private function normalizeResult(CheckoutAttempt $attempt, ?Order $order): ?array
    {
        if (! $order) {
            return $attempt->response_body;
        }

        return [
            'orderId' => $order->id,
            'orderNumber' => $order->order_number,
            'paymentStatus' => $order->payment_status,
            'orderStatus' => $order->status,
            'totalAmount' => ApiResponse::toMinor($order->total_amount),
            'currency' => $order->currency,
        ];
    }

    private function safeSnapshot(Request $request): array
    {
        $keys = [
            'customer_name', 'customer_email', 'customer_phone', 'shipping_address',
            'city', 'state', 'postal_code', 'country', 'payment_method',
            'shipping_cost', 'shipping_zone_id', 'currency', 'gift_card_code',
            'payment_intent_id', 'paypal_order_id', 'paypal_payer_id',
        ];

        $out = [];
        foreach ($keys as $key) {
            if ($request->has($key)) {
                $out[$key] = $request->input($key);
            }
        }

        return $out;
    }

    private function httpStatusFor(CheckoutAttempt $attempt): int
    {
        return match ($attempt->status) {
            'failed' => 400,
            default => 200,
        };
    }
}
