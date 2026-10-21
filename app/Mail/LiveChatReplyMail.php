<?php

namespace App\Mail;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LiveChatReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var list<ChatMessage> */
    public array $replyMessages;

    public string $supportReference;

    /**
     * @param  list<ChatMessage>|null  $replyMessages
     */
    public function __construct(
        public ChatConversation $conversation,
        public ChatMessage $message,
        ?array $replyMessages = null,
    ) {
        $this->replyMessages = $replyMessages ?: [$message];
        $this->supportReference = 'SUP-'.$conversation->id;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Our support team replied to your message',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.live-chat-reply',
        );
    }
}
