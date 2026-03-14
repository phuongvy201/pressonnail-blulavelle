<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveChatController extends Controller
{
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
                'body' => $m->body,
                'created_at' => $m->created_at->toIso8601String(),
            ];
        });
        return response()->json(['success' => true, 'messages' => $messages]);
    }

    public function reply(Request $request, ChatConversation $conversation): JsonResponse
    {
        $request->validate(['body' => 'required|string|max:2000']);
        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'is_from_customer' => false,
            'body' => $request->body,
        ]);
        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'is_from_customer' => false,
                'body' => $message->body,
                'created_at' => $message->created_at->toIso8601String(),
            ],
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
