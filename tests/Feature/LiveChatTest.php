<?php

use App\Mail\LiveChatReplyMail;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\LiveChatService;
use Illuminate\Support\Facades\Mail;

test('guest can resume previous chat history with the same name and email', function () {
    $conversation = ChatConversation::create([
        'guest_email' => 'guest@example.com',
        'guest_name' => 'Tra My',
        'guest_session_id' => 'old-session-id',
        'status' => 'open',
    ]);
    ChatMessage::create([
        'conversation_id' => $conversation->id,
        'is_from_customer' => true,
        'body' => 'Hello from last visit',
    ]);

    $start = $this->postJson('/live-chat/start', [
        'name' => 'tra my',
        'email' => 'Guest@example.com',
    ]);

    $start->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('conversation.id', $conversation->id);

    $conversation->refresh();
    expect($conversation->guest_session_id)->not->toBe('old-session-id');
    expect($conversation->messages()->pluck('body')->all())->toContain('Hello from last visit');
});

test('guest does not get another person chat when the name does not match', function () {
    $conversation = ChatConversation::create([
        'guest_email' => 'guest@example.com',
        'guest_name' => 'Tra My',
        'guest_session_id' => 'old-session-id',
        'status' => 'open',
    ]);

    $start = $this->postJson('/live-chat/start', [
        'name' => 'Someone Else',
        'email' => 'guest@example.com',
    ]);

    $start->assertOk()->assertJsonPath('success', true);
    expect($start->json('conversation.id'))->not->toBe($conversation->id);
});

test('shop reply emails the customer', function () {
    Mail::fake();

    $conversation = ChatConversation::create([
        'guest_email' => 'guest@example.com',
        'guest_name' => 'Tra My',
        'guest_session_id' => 'session-1',
        'status' => 'open',
    ]);
    $message = ChatMessage::create([
        'conversation_id' => $conversation->id,
        'is_from_customer' => false,
        'body' => 'We can help with your order.',
    ]);

    app(LiveChatService::class)->notifyCustomerOfShopReply($conversation, $message);

    Mail::assertSent(LiveChatReplyMail::class, function (LiveChatReplyMail $mail) {
        return $mail->hasTo('guest@example.com')
            && $mail->message->body === 'We can help with your order.';
    });
});

test('guest name and email stay in session so start works without typing again', function () {
    $this->postJson('/live-chat/start', [
        'name' => 'Tra My',
        'email' => 'guest@example.com',
    ])->assertOk();

    $this->getJson('/live-chat/resume-status')
        ->assertOk()
        ->assertJsonPath('can_resume', true)
        ->assertJsonPath('guest_name', 'Tra My')
        ->assertJsonPath('guest_email', 'guest@example.com');

    $this->postJson('/live-chat/start', [])
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('customer sees unread count after a shop reply', function () {
    $start = $this->postJson('/live-chat/start', [
        'name' => 'Tra My',
        'email' => 'guest@example.com',
    ])->assertOk();

    $conversationId = $start->json('conversation.id');

    ChatMessage::create([
        'conversation_id' => $conversationId,
        'is_from_customer' => false,
        'body' => 'Hello from admin',
    ]);

    $this->getJson('/live-chat/conversations/'.$conversationId.'/messages')
        ->assertOk()
        ->assertJsonPath('unread_count', 1);

    $this->getJson('/live-chat/conversations/'.$conversationId.'/messages?mark_seen=1')
        ->assertOk()
        ->assertJsonPath('unread_count', 0);
});
