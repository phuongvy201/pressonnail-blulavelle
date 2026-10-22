<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateChatbotReply;
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
            'sender_type' => ChatMessage::SENDER_CUSTOMER,
            'body' => $body,
        ]);
        $conversation->load('customer');

        // Same hybrid assistant as the website widget: the bot replies when it
        // owns the conversation, and staff are alerted only for human mode.
        $botWillHandle = (bool) config('chatbot.enabled') && $conversation->isBotHandled();
        if (! $botWillHandle) {
            $this->telegram->notifyCustomerMessage($conversation, $message);
        }

        $botStatus = 'disabled';
        if ($botWillHandle) {
            GenerateChatbotReply::dispatch((int) $conversation->id, (int) $message->id)->onQueue('chat');
            $conversation->refresh();
            $botStatus = $conversation->handling_mode === ChatConversation::HANDLING_HUMAN
                ? 'handed_off'
                : (config('queue.default') === 'sync' ? 'replied' : 'processing');
        } elseif (($conversation->handling_mode ?? ChatConversation::HANDLING_BOT) === ChatConversation::HANDLING_HUMAN) {
            $botStatus = 'human';
        }

        return ApiResponse::success([
            'message' => $this->messagePayload($message),
            ...$this->assistantState($conversation, $botStatus),
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
                ...$this->assistantState(null),
            ];
        }

        $conversation->loadMissing('customer');
        if ($lastSeen > 0) {
            $this->liveChat->markSeenUpTo($conversation, $lastSeen);
        }
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
            ...$this->assistantState($conversation),
        ];
    }

    /**
     * @return array{handlingMode: string, botEnabled: bool, botProcessing: bool, welcome: string|null, supportStatus: string|null, supportReference: string|null, supportReplyWithin: string, supportHours: string, supportEmailAlert: bool, botStatus?: string}
     */
    private function assistantState(?ChatConversation $conversation, ?string $botStatus = null): array
    {
        $botEnabled = (bool) config('chatbot.enabled');
        $state = [
            'handlingMode' => $conversation?->handling_mode ?? ChatConversation::HANDLING_BOT,
            'botEnabled' => $botEnabled,
            'botProcessing' => $conversation?->bot_processing_at !== null,
            'welcome' => $botEnabled ? (string) config('chatbot.welcome') : null,
            'supportStatus' => null,
            'supportReference' => null,
            'supportReplyWithin' => (string) config('chatbot.support_reply_within', '2 business hours'),
            'supportHours' => (string) config('chatbot.support_hours', 'Mon–Sat, 9am–6pm'),
            'supportEmailAlert' => false,
        ];

        if ($conversation) {
            $support = $this->liveChat->supportWidgetPayload($conversation);
            $state['handlingMode'] = $conversation->handling_mode ?? ChatConversation::HANDLING_BOT;
            $state['supportStatus'] = $support['support_status'];
            $state['supportReference'] = $support['support_reference'];
            $state['supportReplyWithin'] = $support['support_reply_within'];
            $state['supportHours'] = $support['support_hours'];
            $state['supportEmailAlert'] = $support['support_email_alert'];
        }

        if ($botStatus !== null) {
            $state['botStatus'] = $botStatus;
        }

        return $state;
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
     * @return array{id: int, fromCustomer: bool, senderType: string, body: string, createdAt: string|null, products: list<array{id: int, name: string, price: float, image: string|null, slug: string|null, url: string|null}>}
     */
    private function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'fromCustomer' => (bool) $message->is_from_customer,
            'senderType' => $message->senderType(),
            'body' => $message->body,
            'createdAt' => $message->created_at?->toIso8601String(),
            'products' => $this->productCards($message),
        ];
    }

    /**
     * @return list<array{id: int, name: string, price: float, image: string|null, slug: string|null, url: string|null}>
     */
    private function productCards(ChatMessage $message): array
    {
        $metadata = $message->metadata;
        $products = is_array($metadata) ? ($metadata['products'] ?? []) : [];
        if (! is_array($products)) {
            return [];
        }

        $cards = [];
        foreach ($products as $product) {
            if (! is_array($product) || ! isset($product['name']) || ! is_string($product['name']) || trim($product['name']) === '') {
                continue;
            }

            $url = isset($product['url']) && is_string($product['url']) ? $product['url'] : null;
            $slug = isset($product['slug']) && is_string($product['slug']) && $product['slug'] !== ''
                ? $product['slug']
                : null;
            if ($slug === null && $url !== null && preg_match('~/products/([^/?#]+)~', $url, $matches) === 1) {
                $slug = rawurldecode($matches[1]);
            }

            $cards[] = [
                'id' => isset($product['id']) ? (int) $product['id'] : 0,
                'name' => $product['name'],
                'price' => isset($product['price']) ? (float) $product['price'] : 0.0,
                'image' => isset($product['image']) && is_string($product['image']) ? $product['image'] : null,
                'slug' => $slug,
                'url' => $url,
            ];
        }

        return $cards;
    }
}
