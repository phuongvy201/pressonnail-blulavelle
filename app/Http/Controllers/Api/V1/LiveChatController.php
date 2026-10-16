<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\UgcBlock;
use App\Services\LiveChatService;
use App\Services\TelegramBotService;
use App\Support\ApiV1\ApiResponse;
use App\Support\ContentSafety;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LiveChatController extends Controller
{
    public function __construct(
        private readonly LiveChatService $liveChat,
        private readonly TelegramBotService $telegram,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->payload(
            $this->openConversation($request),
            (int) $request->query('lastSeenMessageId', 0),
        ));
    }

    public function start(Request $request): JsonResponse
    {
        if ($request->filled('website')) {
            return ApiResponse::error('CHAT_REJECTED', 'Invalid request.', 422);
        }

        $user = $request->user();
        $conversation = $this->openConversation($request);

        if (! $conversation && $user) {
            $guest = $this->guestConversation($request);
            if ($guest) {
                $guest->customer_user_id = $user->id;
                $guest->save();
                $conversation = $guest->fresh();
            }
        }

        if (! $conversation) {
            if ($user) {
                $conversation = ChatConversation::create([
                    'customer_user_id' => $user->id,
                    'seller_id' => $this->liveChat->firstAvailableSellerId(),
                    'status' => 'open',
                ]);
            } else {
                $token = $this->guestToken($request);
                if ($token === null) {
                    return ApiResponse::error('CHAT_GUEST_TOKEN', 'A guest chat token is required.', 422);
                }

                $started = $this->liveChat->resumeOrCreateGuest(
                    $token,
                    $request->input('name'),
                    $request->input('email'),
                );
                if (is_string($started)) {
                    return ApiResponse::error('CHAT_DETAILS_REQUIRED', 'Enter your name and email to start the chat.', 422);
                }
                $conversation = $started;
            }
        }

        return ApiResponse::success($this->payload($conversation, (int) $request->input('lastSeenMessageId', 0)));
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);
        $body = trim($validated['body']);
        if ($body === '') {
            return ApiResponse::error('CHAT_EMPTY', 'Enter a message before sending.', 422);
        }

        if (ContentSafety::containsObjectionable($body)) {
            return ApiResponse::error('CHAT_REJECTED', 'That message includes language that is not allowed.', 422);
        }

        $conversation = $this->openConversation($request);
        if (! $conversation) {
            return ApiResponse::error('CHAT_NOT_STARTED', 'Start a chat before sending a message.', 422);
        }

        if ($this->isBlocked($request, $conversation)) {
            return ApiResponse::error('CHAT_BLOCKED', 'You blocked this chat. Unblock it to send another message.', 403);
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'is_from_customer' => true,
            'body' => $body,
        ]);
        $conversation->load('customer');
        $this->telegram->notifyCustomerMessage($conversation, $message);

        return ApiResponse::success([
            'message' => $this->messagePayload($message),
        ], null, 201);
    }

    private function openConversation(Request $request): ?ChatConversation
    {
        $user = $request->user();
        if ($user) {
            return ChatConversation::query()
                ->where('customer_user_id', $user->id)
                ->open()
                ->latest('id')
                ->first();
        }

        return $this->guestConversation($request);
    }

    private function guestConversation(Request $request): ?ChatConversation
    {
        $token = $this->guestToken($request);
        if ($token === null) {
            return null;
        }

        return ChatConversation::query()
            ->where('guest_session_id', $token)
            ->whereNull('customer_user_id')
            ->open()
            ->latest('id')
            ->first();
    }

    private function guestToken(Request $request): ?string
    {
        $token = $request->header('X-Live-Chat-Guest');
        if (! is_string($token) || preg_match('/^[A-Za-z0-9_-]{32,64}$/', $token) !== 1) {
            return null;
        }

        return $token;
    }

    /**
     * @return array{conversation: array{id: int, customerName: string, blocked: bool}|null, messages: list<array{id: int, fromCustomer: bool, body: string, createdAt: string|null}>, unreadCount: int}
     */
    private function payload(?ChatConversation $conversation, int $lastSeen): array
    {
        if (! $conversation) {
            return [
                'conversation' => null,
                'messages' => [],
                'unreadCount' => 0,
            ];
        }

        $conversation->loadMissing('customer');
        $blocked = $this->isBlocked(request(), $conversation);
        $messages = $conversation->messages()->orderBy('id')->get();
        if ($blocked) {
            $messages = $messages->filter(fn (ChatMessage $message) => (bool) $message->is_from_customer)->values();
        }

        return [
            'conversation' => [
                'id' => $conversation->id,
                'customerName' => $conversation->customer_name,
                'blocked' => $blocked,
            ],
            'messages' => $messages->map(fn (ChatMessage $message) => $this->messagePayload($message))->values()->all(),
            'unreadCount' => $blocked ? 0 : $messages->filter(
                fn (ChatMessage $message) => ! $message->is_from_customer && $message->id > $lastSeen
            )->count(),
        ];
    }

    private function isBlocked(Request $request, ChatConversation $conversation): bool
    {
        if (! Schema::hasTable('ugc_blocks')) {
            return false;
        }

        $user = $request->user();
        if ($user) {
            return UgcBlock::query()
                ->where('scope', 'chat_conversation')
                ->where('target_id', $conversation->id)
                ->where('blocker_key', 'user:'.$user->id)
                ->exists();
        }

        $token = $this->guestToken($request);
        if ($token === null) {
            return false;
        }

        return UgcBlock::query()
            ->where('scope', 'chat_conversation')
            ->where('target_id', $conversation->id)
            ->where('blocker_key', 'guest:'.$token)
            ->exists();
    }

    /**
     * @return array{id: int, fromCustomer: bool, body: string, createdAt: string|null}
     */
    private function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'fromCustomer' => (bool) $message->is_from_customer,
            'body' => $message->body,
            'createdAt' => $message->created_at?->toIso8601String(),
        ];
    }
}
