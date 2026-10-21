<?php

use App\Jobs\SendLiveChatReplyEmailIfUnread;
use App\Mail\LiveChatReplyMail;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chatbot\ChatbotService;
use App\Services\LiveChatService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

function supportNotificationConversation(array $attributes = []): ChatConversation
{
    return ChatConversation::query()->create(array_merge([
        'guest_name' => 'Anna Guest',
        'guest_email' => 'anna@example.com',
        'guest_session_id' => 'support-notification-session',
        'status' => 'open',
    ], $attributes));
}

function supportAdminMessage(ChatConversation $conversation, string $body): ChatMessage
{
    return ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'is_from_customer' => false,
        'sender_type' => ChatMessage::SENDER_ADMIN,
        'body' => $body,
    ]);
}

test('handoff acknowledgement gives the customer a reference time and email reassurance', function () {
    config([
        'chatbot.support_reply_within' => '2 business hours',
        'chatbot.support_hours' => 'Mon–Sat, 9am–6pm',
    ]);
    $conversation = supportNotificationConversation();
    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'is_from_customer' => true,
        'sender_type' => ChatMessage::SENDER_CUSTOMER,
        'body' => 'Talk to a human please',
    ]);

    $reply = app(ChatbotService::class)->handoff($conversation, 'Customer requested a human.', $message);

    expect($reply->body)
        ->toContain('Anna Guest')
        ->toContain('#SUP-'.$conversation->id)
        ->toContain('2 business hours')
        ->toContain('Mon–Sat, 9am–6pm')
        ->toContain('anna@example.com')
        ->toContain('You don’t need to stay on this page')
        ->and($reply->metadata['support_reference'] ?? null)->toBe('SUP-'.$conversation->id);
});

test('shop reply only schedules the unread email instead of emailing immediately', function () {
    Mail::fake();
    Queue::fake();
    config(['chatbot.reply_email_delay_minutes' => 5]);
    $conversation = supportNotificationConversation();
    $message = supportAdminMessage($conversation, 'We have updated your order.');

    app(LiveChatService::class)->notifyCustomerOfShopReply($conversation, $message);

    Mail::assertNothingSent();
    Queue::assertPushed(SendLiveChatReplyEmailIfUnread::class, function (SendLiveChatReplyEmailIfUnread $job) use ($conversation, $message) {
        return $job->conversationId === $conversation->id && $job->messageId === $message->id;
    });
});

test('unread shop reply email is sent once and batches later replies', function () {
    Mail::fake();
    $conversation = supportNotificationConversation();
    $first = supportAdminMessage($conversation, 'First support reply.');
    supportAdminMessage($conversation, 'Second support reply.');

    $job = new SendLiveChatReplyEmailIfUnread($conversation->id, $first->id);
    $job->handle(app(LiveChatService::class));
    $job->handle(app(LiveChatService::class));

    Mail::assertSentCount(1);
    Mail::assertSent(LiveChatReplyMail::class, function (LiveChatReplyMail $mail) use ($conversation) {
        $bodies = collect($mail->replyMessages)->pluck('body')->all();

        return $mail->hasTo('anna@example.com')
            && str_contains($mail->envelope()->subject, 'support team replied')
            && $bodies === ['First support reply.', 'Second support reply.']
            && ($mail->supportReference ?? null) === 'SUP-'.$conversation->id;
    });
});

test('shop reply email is skipped after the customer has seen the chat', function () {
    Mail::fake();
    $conversation = supportNotificationConversation();
    $message = supportAdminMessage($conversation, 'Seen support reply.');

    app(LiveChatService::class)->markSeen($conversation, session());
    (new SendLiveChatReplyEmailIfUnread($conversation->id, $message->id))
        ->handle(app(LiveChatService::class));

    Mail::assertNothingSent();
    expect($conversation->refresh()->customer_last_seen_message_id)->toBe($message->id);
});
