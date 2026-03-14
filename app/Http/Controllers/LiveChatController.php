<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveChatController extends Controller
{
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
        } else {
            $email = $request->input('email');
            $name = $request->input('name');
            $conversation = ChatConversation::where('guest_session_id', $sessionId)
                ->open()
                ->first();

            // Đã có conversation theo session => resume, không cần name/email
            if ($conversation) {
                // nothing to do
            } elseif (!$email || !$name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter your name and email to start chat.',
                ], 422);
            } else {
                $conversation = ChatConversation::create([
                    'guest_email' => $email,
                    'guest_name' => $name,
                    'guest_session_id' => $sessionId,
                    'seller_id' => $this->getFirstAvailableSeller(),
                    'status' => 'open',
                ]);
            }
        }

        if (!$conversation && $userId) {
            $conversation = ChatConversation::create([
                'customer_user_id' => $userId,
                'seller_id' => $this->getFirstAvailableSeller(),
                'status' => 'open',
            ]);
        }

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
        return response()->json(['success' => true, 'messages' => $messages]);
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
        return null;
    }

    private function getFirstAvailableSeller(): ?int
    {
        $user = User::role(['admin', 'seller'])->first();
        return $user?->id;
    }
}
