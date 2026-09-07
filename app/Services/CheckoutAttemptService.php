<?php

namespace App\Services;

use App\Http\Controllers\CheckoutController;
use App\Models\CheckoutAttempt;
use App\Models\Order;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        return ApiResponse::success($this->payload($attempt), [
            'replayed' => $replayed,
        ]);
    }

    public function show(CheckoutAttempt $attempt): JsonResponse
    {
        if ($attempt->isExpired() && ! in_array($attempt->status, ['succeeded', 'failed'], true)) {
            $attempt->update(['status' => 'expired']);
        }

        return ApiResponse::success($this->payload($attempt->fresh()));
    }

    public function confirm(Request $request, CheckoutAttempt $attempt): JsonResponse
    {
        if ($attempt->status === 'succeeded') {
            return ApiResponse::success($this->payload($attempt), ['replayed' => true]);
        }

        $pi = $attempt->payment_intent_id ?: $request->input('payment_intent_id');
        if (! $pi) {
            return ApiResponse::error('PAYMENT_INTENT_REQUIRED', 'payment_intent_id is required to confirm.', 422);
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
            'errorCode' => $attempt->error_code,
            'result' => $this->normalizeResult($attempt, $order),
            'createdAt' => ApiResponse::iso($attempt->created_at),
            'updatedAt' => ApiResponse::iso($attempt->updated_at),
            'expiresAt' => ApiResponse::iso($attempt->expires_at),
        ];
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
