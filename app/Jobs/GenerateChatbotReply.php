<?php

namespace App\Jobs;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chatbot\ChatbotService;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateChatbotReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(
        public int $conversationId,
        public int $customerMessageId,
    ) {}

    public function handle(ChatbotService $chatbot): void
    {
        $conversation = ChatConversation::query()->find($this->conversationId);
        $message = ChatMessage::query()
            ->where('conversation_id', $this->conversationId)
            ->find($this->customerMessageId);

        if (! $conversation || ! $message || ! $message->is_from_customer) {
            return;
        }

        $chatbot->handleMessage($conversation, $message);
    }

    public function failed(Throwable $exception): void
    {
        // The customer message was not Telegram-notified upfront (the bot
        // was expected to handle it), so a dead job must still alert staff.
        app(TelegramBotService::class)->sendMessage(implode("\n", [
            '⚠️ Chatbot reply failed — please check this conversation',
            'Conversation: #'.$this->conversationId,
            'Error: '.$exception::class,
        ]));
    }
}
