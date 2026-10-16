<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\DevicePushToken;
use App\Models\NailTryOn;
use App\Models\Order;
use App\Models\VirtualNailTrial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    public function register(
        string $token,
        ?int $userId,
        ?string $guestDeviceKey,
        ?string $platform,
        ?string $appVersion,
        ?string $customerEmail = null,
        ?string $guestCartToken = null,
    ): DevicePushToken {
        $token = trim($token);
        $email = $customerEmail ? mb_strtolower(trim($customerEmail)) : null;
        $row = DevicePushToken::query()->updateOrCreate(
            ['expo_push_token' => $token],
            [
                'user_id' => $userId,
                'guest_device_key' => $guestDeviceKey,
                'guest_cart_token' => $guestCartToken,
                'customer_email' => $email,
                'platform' => $platform,
                'app_version' => $appVersion,
                'last_registered_at' => now(),
            ]
        );

        return $row;
    }

    public function unregister(string $token, ?int $userId, ?string $guestDeviceKey): void
    {
        $query = DevicePushToken::query()->where('expo_push_token', trim($token));
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($guestDeviceKey) {
            $query->where('guest_device_key', $guestDeviceKey);
        }
        $query->delete();
    }

    public function notifyOrderUpdate(Order $order): void
    {
        $copy = $this->orderCopy($order);
        $this->notifyRecipients(
            $this->tokensForOrder($order),
            $copy['title'],
            $copy['body'],
            [
                'type' => 'order_update',
                'orderNumber' => $order->order_number,
                'status' => $order->status,
            ]
        );
    }

    public function notifyPaymentReceived(Order $order): void
    {
        $number = (string) $order->order_number;
        $this->notifyRecipients(
            $this->tokensForOrder($order),
            'Payment confirmed',
            "We received payment for order {$number}. We will update you as it progresses.",
            [
                'type' => 'payment_received',
                'orderNumber' => $number,
            ]
        );
    }

    public function notifyShopChatReply(ChatConversation $conversation, ChatMessage $message): void
    {
        if ($message->is_from_customer) {
            return;
        }

        $preview = trim(Str::limit($message->body, 120));
        $this->notifyRecipients(
            $this->tokensForChat($conversation),
            'New message from BluLavelle',
            $preview !== '' ? $preview : 'The shop replied to your chat.',
            [
                'type' => 'live_chat',
                'conversationId' => $conversation->id,
                'messageId' => $message->id,
            ]
        );
    }

    public function notifyTryOnReady(NailTryOn|VirtualNailTrial $job): void
    {
        [$userId, $guestKey, $guestCart] = $this->tryOnIdentity($job);
        $id = $job instanceof NailTryOn ? (string) $job->public_id : (string) $job->uuid;
        $this->notifyRecipients(
            $this->tokensForIdentity($userId, $guestKey, null, $guestCart),
            'Your AI Preview is ready',
            'Open Try On to see your nail preview.',
            [
                'type' => 'try_on_ready',
                'tryOnId' => $id,
            ]
        );
    }

    public function notifyTryOnFailed(NailTryOn|VirtualNailTrial $job): void
    {
        [$userId, $guestKey, $guestCart] = $this->tryOnIdentity($job);
        $id = $job instanceof NailTryOn ? (string) $job->public_id : (string) $job->uuid;
        $this->notifyRecipients(
            $this->tokensForIdentity($userId, $guestKey, null, $guestCart),
            'AI Preview could not finish',
            'Open Try On to try again with another hand photo.',
            [
                'type' => 'try_on_failed',
                'tryOnId' => $id,
            ]
        );
    }

    public function notifyPromoReward(string $email, string $code, string $label, ?int $userId = null): void
    {
        $this->notifyRecipients(
            $this->tokensForIdentity($userId, null, $email),
            $label,
            "Your promo code is {$code}. Apply it at checkout.",
            [
                'type' => 'promo',
                'code' => $code,
            ]
        );
    }

    public function notifyReviewSubmitted(int $userId, string $orderNumber): void
    {
        $this->notifyRecipients(
            $this->tokensForIdentity($userId, null, null),
            'Review submitted',
            "Thanks! Your review for order {$orderNumber} is live.",
            [
                'type' => 'review_submitted',
                'orderNumber' => $orderNumber,
            ]
        );
    }

    /**
     * @param  Collection<int, string>|list<string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public function notifyRecipients(Collection|array $tokens, string $title, string $body, array $data = []): void
    {
        $list = collect($tokens)->filter()->unique()->values()->all();
        if ($list === []) {
            return;
        }
        $this->send($list, $title, $body, $data);
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        $messages = [];
        foreach ($tokens as $token) {
            if (! is_string($token) || $token === '') {
                continue;
            }
            $messages[] = [
                'to' => $token,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ];
        }

        if ($messages === []) {
            return;
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(15)
                ->post(self::ENDPOINT, $messages);

            if (! $response->successful()) {
                Log::warning('expo_push.send_failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return;
            }

            $this->pruneInvalidTokens($messages, $response->json('data') ?? []);
        } catch (\Throwable $exception) {
            Log::warning('expo_push.send_exception', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return Collection<int, string>
     */
    private function tokensForOrder(Order $order): Collection
    {
        $email = is_string($order->customer_email) ? mb_strtolower(trim($order->customer_email)) : null;

        return $this->tokensForIdentity($order->user_id ? (int) $order->user_id : null, null, $email);
    }

    /**
     * @return Collection<int, string>
     */
    private function tokensForChat(ChatConversation $conversation): Collection
    {
        $email = $conversation->customer_email;
        $email = is_string($email) ? mb_strtolower(trim($email)) : null;

        return $this->tokensForIdentity(
            $conversation->customer_user_id ? (int) $conversation->customer_user_id : null,
            is_string($conversation->guest_session_id) ? $conversation->guest_session_id : null,
            $email,
        );
    }

    /**
     * @return Collection<int, string>
     */
    private function tokensForIdentity(
        ?int $userId,
        ?string $guestDeviceKey = null,
        ?string $email = null,
        ?string $guestCartToken = null,
    ): Collection {
        if (! Schema::hasTable('device_push_tokens')) {
            return collect();
        }

        $query = DevicePushToken::query();
        $query->where(function ($builder) use ($userId, $guestDeviceKey, $email, $guestCartToken) {
            $started = false;
            if ($userId) {
                $builder->where('user_id', $userId);
                $started = true;
            }
            if ($guestDeviceKey) {
                $started ? $builder->orWhere('guest_device_key', $guestDeviceKey)
                    : $builder->where('guest_device_key', $guestDeviceKey);
                $started = true;
            }
            if ($guestCartToken && Schema::hasColumn('device_push_tokens', 'guest_cart_token')) {
                $started ? $builder->orWhere('guest_cart_token', $guestCartToken)
                    : $builder->where('guest_cart_token', $guestCartToken);
                $started = true;
            }
            if ($email) {
                $started ? $builder->orWhere('customer_email', mb_strtolower(trim($email)))
                    : $builder->where('customer_email', mb_strtolower(trim($email)));
                $started = true;
            }
            if (! $started) {
                $builder->whereRaw('1 = 0');
            }
        });

        return $query->pluck('expo_push_token');
    }

    /**
     * @return array{0: ?int, 1: ?string, 2: ?string}
     */
    private function tryOnIdentity(NailTryOn|VirtualNailTrial $job): array
    {
        if ($job instanceof NailTryOn) {
            return [
                $job->user_id ? (int) $job->user_id : null,
                null,
                is_string($job->guest_token) ? $job->guest_token : null,
            ];
        }

        return [
            $job->user_id ? (int) $job->user_id : null,
            is_string($job->session_id) ? $job->session_id : null,
            null,
        ];
    }

    /**
     * @param  list<array{to: string}>  $messages
     * @param  list<array{status?: string, details?: array{error?: string}}>|mixed  $tickets
     */
    private function pruneInvalidTokens(array $messages, mixed $tickets): void
    {
        if (! is_array($tickets)) {
            return;
        }

        foreach ($tickets as $index => $ticket) {
            if (! is_array($ticket)) {
                continue;
            }
            $error = $ticket['details']['error'] ?? null;
            if (! in_array($error, ['DeviceNotRegistered', 'InvalidCredentials'], true)) {
                continue;
            }
            $token = $messages[$index]['to'] ?? null;
            if (is_string($token) && $token !== '') {
                DevicePushToken::query()->where('expo_push_token', $token)->delete();
            }
        }
    }

    /**
     * @return array{title: string, body: string}
     */
    private function orderCopy(Order $order): array
    {
        $number = (string) $order->order_number;
        $status = (string) $order->status;

        return match ($status) {
            'processing' => [
                'title' => 'Order in production',
                'body' => "We started preparing order {$number}.",
            ],
            'shipped' => [
                'title' => 'Your set is on the way',
                'body' => $order->tracking_number
                    ? "Order {$number} shipped. Tracking: {$order->tracking_number}."
                    : "Order {$number} has shipped.",
            ],
            'delivered' => [
                'title' => 'Order delivered',
                'body' => "Order {$number} was delivered. Leave a review when you are ready.",
            ],
            'cancelled' => [
                'title' => 'Order cancelled',
                'body' => "Order {$number} was cancelled.",
            ],
            default => [
                'title' => 'Order update',
                'body' => "Order {$number} is now {$status}.",
            ],
        };
    }
}
