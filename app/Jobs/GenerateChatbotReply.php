<?php

namespace App\Jobs;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
}
