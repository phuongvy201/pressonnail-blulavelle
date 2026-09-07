<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    public function isEnabled(): bool
    {
        return filled(config('services.telegram.bot_token'))
            && filled(config('services.telegram.chat_id'));
    }

    public function notifyCustomerMessage(ChatConversation $conversation, ChatMessage $message): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $customerName = $conversation->customer_name;
        $customerEmail = $conversation->customer_email ?? 'N/A';
        $conversationId = $conversation->id;
        $body = trim((string) $message->body);

        $text = implode("\n", [
            '💬 New customer message',
            "CID: #{$conversationId}",
            "Customer: {$customerName}",
            "Email: {$customerEmail}",
            '',
            $body,
            '',
            "Reply command: /r {$conversationId} your message",
        ]);

        $this->sendMessage($text);
    }

    public function notifySellerMessage(ChatConversation $conversation, ChatMessage $message): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $conversationId = $conversation->id;
        $body = trim((string) $message->body);
        $text = implode("\n", [
            '✅ Seller replied from admin panel',
            "CID: #{$conversationId}",
            '',
            $body,
        ]);

        $this->sendMessage($text);
    }

    public function sendMessage(string $text): bool
    {
        $botToken = (string) config('services.telegram.bot_token');
        $chatId = (string) config('services.telegram.chat_id');
        if ($botToken === '' || $chatId === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->retry(3, 400, function ($exception) {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    return $exception instanceof RequestException
                        && $exception->response
                        && $exception->response->serverError();
                }, throw: false)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]);

            if (!$response->ok()) {
                Log::warning('TelegramBotService sendMessage failed.', [
                    'status' => $response->status(),
                    'body' => $this->redactSecrets((string) $response->body()),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('TelegramBotService sendMessage exception.', [
                'error' => $this->redactSecrets($e->getMessage()),
            ]);
            return false;
        }
    }

    public function isAuthorizedUpdate(array $update): bool
    {
        $allowedIds = config('services.telegram.allowed_user_ids', []);
        $allowedIds = is_array($allowedIds) ? array_filter(array_map('trim', $allowedIds)) : [];

        $fromId = (string) data_get($update, 'message.from.id', data_get($update, 'edited_message.from.id', ''));
        $chatId = (string) data_get($update, 'message.chat.id', data_get($update, 'edited_message.chat.id', ''));
        $configuredChatId = (string) config('services.telegram.chat_id', '');

        if (!empty($allowedIds)) {
            return in_array($fromId, $allowedIds, true);
        }

        return $configuredChatId !== '' && $chatId === $configuredChatId;
    }

    public function redactSecrets(string $text): string
    {
        return preg_replace('/bot\d+:[A-Za-z0-9_-]+/', 'bot{TOKEN}', $text) ?? $text;
    }
}
