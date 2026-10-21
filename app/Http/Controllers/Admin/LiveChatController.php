<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chatbot\ChatbotService;
use App\Services\LiveChatService;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveChatController extends Controller
{
    public function __construct(
        private readonly TelegramBotService $telegramBot,
        private readonly LiveChatService $liveChat,
        private readonly ChatbotService $chatbot,
    ) {
    }

    public function index(): View
    {
        $conversations = ChatConversation::with(['customer', 'seller', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->latest('updated_at')
            ->paginate(20);
        return view('admin.live-chat.index', compact('conversations'));
    }

    public function show(ChatConversation $conversation): View
    {
        $conversation->load(['customer', 'seller', 'messages']);
        $conversation->messages()->where('is_from_customer', true)->whereNull('read_at')->update(['read_at' => now()]);
        return view('admin.live-chat.show', compact('conversation'));
    }

    public function messages(ChatConversation $conversation): JsonResponse
    {
        $messages = $conversation->messages()->orderBy('id')->get()->map(function (ChatMessage $m) {
            return [
                'id' => $m->id,
                'is_from_customer' => $m->is_from_customer,
                'sender_type' => $m->senderType(),
                'body' => $m->body,
                'metadata' => $m->metadata ?? [],
                'intent' => $m->intent,
                'created_at' => $m->created_at->toIso8601String(),
            ];
        });
        return response()->json([
            'success' => true,
            'messages' => $messages,
            'handling_mode' => $conversation->handling_mode ?? ChatConversation::HANDLING_BOT,
        ]);
    }

    public function reply(Request $request, ChatConversation $conversation): JsonResponse
    {
        $request->validate(['body' => 'required|string|max:2000']);
        $this->chatbot->takeover($conversation);
        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'is_from_customer' => false,
            'sender_type' => ChatMessage::SENDER_ADMIN,
            'body' => $request->body,
        ]);

        $this->telegramBot->notifySellerMessage($conversation, $message);
        $this->liveChat->notifyCustomerOfShopReply($conversation, $message);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'is_from_customer' => false,
                'sender_type' => ChatMessage::SENDER_ADMIN,
                'body' => $message->body,
                'created_at' => $message->created_at->toIso8601String(),
            ],
            'handling_mode' => ChatConversation::HANDLING_HUMAN,
        ]);
    }

    public function takeover(ChatConversation $conversation): JsonResponse
    {
        $conversation = $this->chatbot->takeover($conversation, announce: true);

        return response()->json([
            'success' => true,
            'handling_mode' => $conversation->handling_mode,
        ]);
    }

    public function returnToBot(ChatConversation $conversation): JsonResponse
    {
        $conversation = $this->chatbot->returnToBot($conversation);

        return response()->json([
            'success' => true,
            'handling_mode' => $conversation->handling_mode,
        ]);
    }

    /**
     * API for admin widget: list conversations (JSON).
     */
    public function apiConversations(): JsonResponse
    {
        $userId = auth()->id();
        $conversations = ChatConversation::with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->where('seller_id', $userId)
            ->open()
            ->latest('updated_at')
            ->limit(50)
            ->get();

        $list = $conversations->map(function (ChatConversation $c) {
            $last = $c->messages->first();
            $unread = ChatMessage::where('conversation_id', $c->id)
                ->where('is_from_customer', true)
                ->whereNull('read_at')
                ->count();
            return [
                'id' => $c->id,
                'customer_name' => $c->customer_name,
                'customer_email' => $c->customer_email,
                'handling_mode' => $c->handling_mode ?? ChatConversation::HANDLING_BOT,
                'last_bot_intent' => $c->last_bot_intent,
                'last_message' => $last ? [
                    'body' => \Illuminate\Support\Str::limit($last->body, 60),
                    'created_at' => $last->created_at->toIso8601String(),
                ] : null,
                'unread_count' => $unread,
                'updated_at' => $c->updated_at->toIso8601String(),
            ];
        });

        return response()->json(['success' => true, 'conversations' => $list]);
    }

    /**
     * Mark customer messages in conversation as read (e.g. when opening in widget).
     */
    public function markRead(ChatConversation $conversation): JsonResponse
    {
        if ($conversation->seller_id !== auth()->id()) {
            return response()->json(['success' => false], 403);
        }
        $conversation->messages()->where('is_from_customer', true)->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }
}
