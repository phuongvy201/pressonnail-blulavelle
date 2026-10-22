<?php

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Page;
use App\Models\User;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Support\Facades\Http;

function telegramChatConversation(): ChatConversation
{
    return ChatConversation::query()->create([
        'guest_name' => 'Anna Guest',
        'guest_email' => 'anna@example.com',
        'guest_session_id' => 'telegram-session-'.uniqid(),
        'status' => 'open',
    ]);
}

function telegramCustomerMessage(ChatConversation $conversation, string $body): ChatMessage
{
    return ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'is_from_customer' => true,
        'sender_type' => ChatMessage::SENDER_CUSTOMER,
        'body' => $body,
    ]);
}

beforeEach(function () {
    config([
        'chatbot.enabled' => true,
        'chatbot.ai_enabled' => false,
        'services.telegram.bot_token' => 'test-token',
        'services.telegram.chat_id' => '4242',
    ]);
    Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);
});

test('successful bot replies are not notified to telegram', function () {
    $admin = User::factory()->create();
    Page::query()->create([
        'user_id' => $admin->id,
        'title' => 'Shipping & Delivery',
        'slug' => 'shipping-delivery',
        'content' => '<p>Orders are processed in 1-7 business days.</p>',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    $conversation = telegramChatConversation();
    $reply = app(ChatbotService::class)->handleMessage(
        $conversation,
        telegramCustomerMessage($conversation, 'How long does shipping take?'),
    );

    expect($reply)->not->toBeNull()
        ->and($reply->intent)->toBe('faq');
    Http::assertNothingSent();
});

test('bot fallback replies alert staff on telegram', function () {
    $conversation = telegramChatConversation();
    $reply = app(ChatbotService::class)->handleMessage(
        $conversation,
        telegramCustomerMessage($conversation, 'Zqx jkvb plm wqas?'),
    );

    expect($reply)->not->toBeNull()
        ->and($reply->intent)->toBe('fallback');
    Http::assertSent(function ($request) use ($conversation) {
        $text = (string) ($request->data()['text'] ?? '');

        return str_contains($request->url(), 'api.telegram.org')
            && str_contains($text, 'could not answer')
            && str_contains($text, 'CID: #'.$conversation->id);
    });
});

test('website customer message handled by the bot does not alert staff upfront', function () {
    $start = $this->postJson('/live-chat/start', [
        'name' => 'Anna Guest',
        'email' => 'anna@example.com',
    ])->assertOk();

    $this->postJson('/live-chat/send', [
        'conversation_id' => $start->json('conversation.id'),
        'body' => 'How long do BluLavelle nails last?',
    ])->assertOk();

    Http::assertNothingSent();
});
