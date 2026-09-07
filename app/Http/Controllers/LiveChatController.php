<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\LiveChatService;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveChatController extends Controller
{
    public function __construct(
        private readonly TelegramBotService $telegramBot,
        private readonly LiveChatService $liveChat,
    ) {
    }

    /**
     * Kiểm tra có conversation đang mở theo session/user (GET, luôn 200 — tránh POST /start → 422 khi load trang).
     */
    public function resumeStatus(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $session = $request->session();
        $sessionId = $session->getId();
        $conversation = null;

        if ($userId) {
            $conversation = ChatConversation::where('customer_user_id', $userId)->open()->first();
        } else {
            $conversation = ChatConversation::where('guest_session_id', $sessionId)->open()->first()
                ?: $this->liveChat->conversationFromHttpSession($session);
        }

        $unread = $conversation ? $this->liveChat->unreadSellerCount($conversation, $session) : 0;

        return response()->json([
            'can_resume' => (bool) $conversation
                || (filled($this->liveChat->guestNameFromSession($session))
                    && filled($this->liveChat->guestEmailFromSession($session))),
            'unread_count' => $unread,
            'guest_name' => $this->liveChat->guestNameFromSession($session),
            'guest_email' => $this->liveChat->guestEmailFromSession($session),
        ]);
    }

    /**
     * Bắt đầu hoặc lấy conversation hiện tại (khách hoặc user).
     * Guest: có thể gửi rỗng để "resume" theo session (không cần nhập lại tên/email).
     * Honeypot: nếu field "website" được gửi và có giá trị => coi là bot, từ chối.
     */
    public function startOrGet(Request $request): JsonResponse
    {
        // Honeypot: bot thường điền vào field ẩn
        if ($request->filled('website')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request.',
            ], 422);
        }

        $userId = auth()->id();
        $sessionId = $request->session()->getId();

        if ($userId) {
            $conversation = ChatConversation::where('customer_user_id', $userId)
                ->open()
                ->first();
            if (!$conversation) {
                $conversation = ChatConversation::create([
                    'customer_user_id' => $userId,
                    'seller_id' => $this->liveChat->firstAvailableSellerId(),
                    'status' => 'open',
                ]);
            }
        } else {
            $conversation = $this->liveChat->resumeOrCreateGuest(
                $sessionId,
                $request->input('name'),
                $request->input('email'),
                $request->session(),
            );
            if (is_string($conversation)) {
                return response()->json([
                    'success' => false,
                    'message' => $conversation,
                ], 422);
            }
        }

        $this->liveChat->rememberInSession($request->session(), $conversation);

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'customer_name' => $conversation->customer_name,
            ],
        ]);
    }

    /**
     * Lấy danh sách tin nhắn của conversation (phía khách).
     */
    public function messages(Request $request, int $conversationId): JsonResponse
    {
        $conversation = $this->resolveConversationForCustomer($request, $conversationId);
        if (!$conversation) {
            return response()->json(['success' => false, 'message' => 'Conversation not found.'], 404);
        }
        $messages = $conversation->messages()->orderBy('id')->get()->map(function (ChatMessage $m) {
            return [
                'id' => $m->id,
                'is_from_customer' => $m->is_from_customer,
                'body' => $m->body,
                'created_at' => $m->created_at->toIso8601String(),
            ];
        });
        $unread = $this->liveChat->unreadSellerCount($conversation, $request->session());
        if ($request->boolean('mark_seen')) {
            $this->liveChat->markSeen($conversation, $request->session());
            $unread = 0;
        }

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'unread_count' => $unread,
        ]);
    }

    /**
     * Gửi tin nhắn (phía khách).
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate(['conversation_id' => 'required|integer', 'body' => 'required|string|max:2000']);
        $conversation = $this->resolveConversationForCustomer($request, (int) $request->conversation_id);
        if (!$conversation) {
            return response()->json(['success' => false, 'message' => 'Conversation not found.'], 404);
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'is_from_customer' => true,
            'body' => $request->body,
        ]);

        $this->telegramBot->notifyCustomerMessage($conversation, $message);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'is_from_customer' => true,
                'body' => $message->body,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    private function resolveConversationForCustomer(Request $request, int $conversationId): ?ChatConversation
    {
        $userId = auth()->id();
        $sessionId = $request->session()->getId();

        $conversation = ChatConversation::find($conversationId);
        if (!$conversation) {
            return null;
        }
        if ($userId && $conversation->customer_user_id === $userId) {
            return $conversation;
        }
        if (!$userId && $conversation->guest_session_id === $sessionId) {
            return $conversation;
        }
        if (!$userId && (int) $request->session()->get(LiveChatService::SESSION_CONVERSATION) === (int) $conversation->id) {
            $conversation->guest_session_id = $sessionId;
            $conversation->save();

            return $conversation;
        }
        return null;
    }
}
