<?php

namespace App\Jobs;

use App\Services\LiveChatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendLiveChatReplyEmailIfUnread implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $conversationId,
        public int $messageId,
    ) {}

    public function handle(LiveChatService $liveChat): void
    {
        $liveChat->sendReplyEmailIfUnread($this->conversationId, $this->messageId);
    }
}
