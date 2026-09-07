<?php

namespace App\Services;

use App\Models\CheckoutIdempotencyKey;
use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckoutIdempotencyService
{
    public const HEADER = 'Idempotency-Key';

    public function handle(Request $request, bool $required, callable $callback): mixed
    {
        $key = $this->extractKey($request);

        if ($key === null) {
            if ($required) {
                return $this->jsonError(
                    'Send Idempotency-Key header (UUID) so network retries do not create a second order.',
                    'IDEMPOTENCY_KEY_REQUIRED',
                    422
                );
            }

            return $callback();
        }

        if (! $this->isValidKey($key)) {
            return $this->jsonError(
                'Idempotency-Key must be 16–128 characters (letters, numbers, hyphen, underscore).',
                'IDEMPOTENCY_KEY_INVALID',
                422
            );
        }

        $hash = $this->hashRequest($request);
        $record = $this->claimOrLoad($key, $hash, $request);

        if ($record instanceof JsonResponse) {
            return $record;
        }

        if ($record->status === CheckoutIdempotencyKey::STATUS_COMPLETED && is_array($record->response_body)) {
            return $this->replay($record);
        }

        if ($record->order_id) {
            $order = Order::find($record->order_id);
            if ($order) {
                $response = $this->orderReplayResponse($order);
                $this->storeResponse($record, $response);

                return $this->withReplayHeader($response, false);
            }
        }

        try {
            $response = $callback();
            $this->storeResponse($record, $response);

            return $response;
        } catch (\Throwable $exception) {
            $this->markFailed($record);
            throw $exception;
        }
    }

    public function attachOrder(Request $request, Order $order): void
    {
        $key = $this->extractKey($request);
        if ($key === null) {
            return;
        }

        CheckoutIdempotencyKey::query()
            ->where('key', $key)
            ->update(['order_id' => $order->id]);
    }

    public function extractKey(Request $request): ?string
    {
        $key = $request->header(self::HEADER)
            ?? $request->header('X-Idempotency-Key')
            ?? $request->input('idempotencyKey')
            ?? $request->input('idempotency_key');

        if (! is_string($key)) {
            return null;
        }

        $key = trim($key);

        return $key === '' ? null : $key;
    }

    private function claimOrLoad(string $key, string $hash, Request $request): CheckoutIdempotencyKey|JsonResponse
    {
        $existing = CheckoutIdempotencyKey::query()->where('key', $key)->first();

        if ($existing) {
            return $this->resolveExisting($existing, $hash);
        }

        $lockSeconds = $this->lockSeconds();
        $ttlHours = $this->ttlHours();

        try {
            return CheckoutIdempotencyKey::create([
                'key' => $key,
                'request_hash' => $hash,
                'status' => CheckoutIdempotencyKey::STATUS_PROCESSING,
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'locked_until' => now()->addSeconds($lockSeconds),
                'expires_at' => now()->addHours($ttlHours),
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }

            $existing = CheckoutIdempotencyKey::query()->where('key', $key)->first();
            if (! $existing) {
                throw $exception;
            }

            return $this->resolveExisting($existing, $hash);
        }
    }

    private function resolveExisting(CheckoutIdempotencyKey $existing, string $hash): CheckoutIdempotencyKey|JsonResponse
    {
        if ($existing->isExpired()) {
            $existing->delete();

            return CheckoutIdempotencyKey::create([
                'key' => $existing->key,
                'request_hash' => $hash,
                'status' => CheckoutIdempotencyKey::STATUS_PROCESSING,
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'locked_until' => now()->addSeconds($this->lockSeconds()),
                'expires_at' => now()->addHours($this->ttlHours()),
            ]);
        }

        if ($existing->request_hash !== $hash) {
            return $this->jsonError(
                'Idempotency-Key was already used with a different checkout payload.',
                'IDEMPOTENCY_KEY_REUSED',
                409
            );
        }

        if ($existing->status === CheckoutIdempotencyKey::STATUS_COMPLETED) {
            return $existing;
        }

        if ($existing->isLockActive() && $existing->status === CheckoutIdempotencyKey::STATUS_PROCESSING) {
            return $this->jsonError(
                'A request with this Idempotency-Key is already in progress. Retry with the same key after a short delay.',
                'IDEMPOTENCY_CONFLICT',
                409
            )->header('Retry-After', '2');
        }

        $existing->update([
            'status' => CheckoutIdempotencyKey::STATUS_PROCESSING,
            'locked_until' => now()->addSeconds($this->lockSeconds()),
        ]);

        return $existing->fresh();
    }

    private function storeResponse(CheckoutIdempotencyKey $record, mixed $response): void
    {
        if (! $response instanceof JsonResponse) {
            $this->markFailed($record);

            return;
        }

        $status = $response->getStatusCode();

        if ($status >= 500) {
            $this->markFailed($record);

            return;
        }

        $body = json_decode($response->getContent() ?: 'null', true);
        if (! is_array($body)) {
            $this->markFailed($record);

            return;
        }

        $orderId = $record->order_id;
        if (! $orderId && isset($body['order_id'])) {
            $orderId = (int) $body['order_id'];
        }
        if (! $orderId && ! empty($body['order_number'])) {
            $orderId = Order::where('order_number', $body['order_number'])->value('id');
        }

        $record->update([
            'status' => CheckoutIdempotencyKey::STATUS_COMPLETED,
            'http_status' => $status,
            'response_body' => $body,
            'order_id' => $orderId,
            'locked_until' => null,
        ]);
    }

    private function markFailed(CheckoutIdempotencyKey $record): void
    {
        $record->update([
            'status' => CheckoutIdempotencyKey::STATUS_FAILED,
            'locked_until' => null,
        ]);
    }

    private function replay(CheckoutIdempotencyKey $record): JsonResponse
    {
        Log::info('checkout.idempotency_replay', [
            'key' => $record->key,
            'order_id' => $record->order_id,
            'http_status' => $record->http_status,
        ]);

        return $this->withReplayHeader(
            response()->json($record->response_body, $record->http_status ?: 200),
            true
        );
    }

    private function orderReplayResponse(Order $order): JsonResponse
    {
        $paid = $order->payment_status === 'paid';

        return response()->json([
            'success' => true,
            'message' => $paid ? 'Payment completed successfully' : 'Order created successfully',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total_amount' => (float) $order->total_amount,
            'payment_method' => $order->payment_method,
            'payment_completed' => $paid,
            'payment_pending' => ! $paid,
        ]);
    }

    private function withReplayHeader(JsonResponse $response, bool $replayed): JsonResponse
    {
        $response->headers->set('Idempotency-Replayed', $replayed ? 'true' : 'false');

        return $response;
    }

    public function hashRequest(Request $request): string
    {
        $payload = [
            'customer_email' => strtolower((string) $request->input('customer_email')),
            'customer_name' => (string) $request->input('customer_name'),
            'shipping_address' => (string) $request->input('shipping_address'),
            'city' => (string) $request->input('city'),
            'state' => (string) $request->input('state'),
            'postal_code' => (string) $request->input('postal_code'),
            'country' => (string) $request->input('country'),
            'payment_method' => (string) $request->input('payment_method'),
            'payment_intent_id' => (string) $request->input('payment_intent_id'),
            'paypal_order_id' => (string) $request->input('paypal_order_id'),
            'card_token' => (string) $request->input('card_token'),
            'shipping_cost' => (string) $request->input('shipping_cost'),
            'gift_card_code' => (string) $request->input('gift_card_code'),
            'currency' => (string) $request->input('currency'),
        ];

        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function isValidKey(string $key): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]{16,128}$/', $key);
    }

    private function jsonError(string $message, string $error, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error,
        ], $status);
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $code = (string) ($exception->errorInfo[1] ?? $exception->getCode());

        return in_array($code, ['1062', '23000'], true)
            || str_contains($exception->getMessage(), 'UNIQUE constraint')
            || str_contains($exception->getMessage(), 'Duplicate entry');
    }

    private function lockSeconds(): int
    {
        return (int) config('mobile_api.checkout_idempotency.lock_seconds', 60);
    }

    private function ttlHours(): int
    {
        return (int) config('mobile_api.checkout_idempotency.ttl_hours', 24);
    }
}
