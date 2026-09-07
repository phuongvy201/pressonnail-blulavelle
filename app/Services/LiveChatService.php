<?php

namespace App\Services;

use App\Mail\LiveChatReplyMail;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class LiveChatService
{
    public const SESSION_CONVERSATION = 'live_chat.conversation_id';
    public const SESSION_NAME = 'live_chat.guest_name';
    public const SESSION_EMAIL = 'live_chat.guest_email';
    public const SESSION_LAST_SEEN = 'live_chat.last_seen_message_id';

    public function resumeOrCreateGuest(string $sessionId, ?string $name, ?string $email, $session = null): ChatConversation|string
    {
        $name = trim((string) $name);
        $email = trim((string) $email);

        if ($session) {
            if ($name === '') {
                $name = trim((string) $session->get(self::SESSION_NAME, ''));
            }
            if ($email === '') {
                $email = trim((string) $session->get(self::SESSION_EMAIL, ''));
            }

            $fromHttpSession = $this->conversationFromHttpSession($session);
            if ($fromHttpSession) {
                return $this->claimForSession(
                    $fromHttpSession,
                    $sessionId,
                    $name !== '' ? $name : (string) $fromHttpSession->guest_name,
                    $email !== '' ? $email : (string) $fromHttpSession->guest_email,
                );
            }
        }

        if ($name !== '' && $email !== '') {
            $matched = $this->findGuestByNameAndEmail($name, $email);
            if ($matched) {
                return $this->claimForSession($matched, $sessionId, $name, $email);
            }
        }

        $bySession = ChatConversation::query()
            ->where('guest_session_id', $sessionId)
            ->open()
            ->first();
        if ($bySession) {
            return $bySession;
        }

        if ($name === '' || $email === '') {
            return 'Please enter your name and email to start chat.';
        }

        return ChatConversation::create([
            'guest_email' => $email,
            'guest_name' => $name,
            'guest_session_id' => $sessionId,
            'seller_id' => $this->firstAvailableSellerId(),
            'status' => 'open',
        ]);
    }

    public function notifyCustomerOfShopReply(ChatConversation $conversation, ChatMessage $message): void
    {
        if ($message->is_from_customer) {
            return;
        }

        $email = $conversation->customer_email;
        if (!filled($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($email)->send(new LiveChatReplyMail($conversation, $message));
        } catch (\Throwable $e) {
            Log::warning('Live chat customer email failed.', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function firstAvailableSellerId(): ?int
    {
        try {
            return User::role(['admin', 'seller'])->first()?->id;
        } catch (RoleDoesNotExist) {
            return null;
        }
    }

    public function rememberInSession($session, ChatConversation $conversation): void
    {
        $previousId = (int) $session->get(self::SESSION_CONVERSATION);
        $session->put(self::SESSION_CONVERSATION, $conversation->id);
        if (filled($conversation->guest_name)) {
            $session->put(self::SESSION_NAME, $conversation->guest_name);
        }
        if (filled($conversation->guest_email)) {
            $session->put(self::SESSION_EMAIL, $conversation->guest_email);
        }
        if ($previousId !== (int) $conversation->id || !$session->has(self::SESSION_LAST_SEEN)) {
            $session->put(self::SESSION_LAST_SEEN, (int) $conversation->messages()->max('id'));
        }
    }

    public function conversationFromHttpSession($session): ?ChatConversation
    {
        $id = (int) $session->get(self::SESSION_CONVERSATION);
        if ($id < 1) {
            return null;
        }

        return ChatConversation::query()->find($id);
    }

    public function unreadSellerCount(ChatConversation $conversation, $session): int
    {
        $lastSeen = (int) $session->get(self::SESSION_LAST_SEEN, 0);

        return ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('is_from_customer', false)
            ->where('id', '>', $lastSeen)
            ->count();
    }

    public function markSeen(ChatConversation $conversation, $session): void
    {
        $session->put(self::SESSION_LAST_SEEN, (int) $conversation->messages()->max('id'));
    }

    public function guestNameFromSession($session): ?string
    {
        $name = trim((string) $session->get(self::SESSION_NAME, ''));

        return $name !== '' ? $name : null;
    }

    public function guestEmailFromSession($session): ?string
    {
        $email = trim((string) $session->get(self::SESSION_EMAIL, ''));

        return $email !== '' ? $email : null;
    }

    private function findGuestByNameAndEmail(string $name, string $email): ?ChatConversation
    {
        $emailNorm = mb_strtolower($email);
        $nameNorm = $this->normalizeName($name);

        $candidates = ChatConversation::query()
            ->whereNull('customer_user_id')
            ->whereRaw('LOWER(TRIM(guest_email)) = ?', [$emailNorm])
            ->withCount('messages')
            ->orderByDesc('messages_count')
            ->orderByDesc('id')
            ->get();

        return $candidates->first(
            fn (ChatConversation $conversation) => $this->normalizeName((string) $conversation->guest_name) === $nameNorm
        );
    }

    private function claimForSession(ChatConversation $conversation, string $sessionId, string $name, string $email): ChatConversation
    {
        $conversation->guest_session_id = $sessionId;
        $conversation->guest_name = $conversation->guest_name ?: $name;
        $conversation->guest_email = $conversation->guest_email ?: $email;
        $conversation->status = 'open';
        $conversation->save();

        return $conversation;
    }

    private function normalizeName(string $name): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);

        return mb_strtolower($collapsed);
    }
}
