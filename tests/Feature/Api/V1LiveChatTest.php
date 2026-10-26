<?php

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Page;
use App\Models\User;

test('a guest starts a chat and only that guest token can read it', function () {
    $token = str_repeat('a', 40);

    $start = $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/live-chat', [
            'name' => 'Tra My',
            'email' => 'guest@example.com',
        ]);

    $start->assertOk()
        ->assertJsonPath('data.conversation.customerName', 'Tra My');

    $conversationId = $start->json('data.conversation.id');

    $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/live-chat/messages', ['body' => 'Where is my order?'])
        ->assertCreated()
        ->assertJsonPath('data.message.fromCustomer', true);

    $this->withHeader('X-Live-Chat-Guest', $token)
        ->getJson('/api/v1/live-chat')
        ->assertOk()
        ->assertJsonPath('data.conversation.id', $conversationId)
        ->assertJsonPath('data.messages.0.body', 'Where is my order?');

    $this->withHeader('X-Live-Chat-Guest', str_repeat('b', 40))
        ->getJson('/api/v1/live-chat')
        ->assertOk()
        ->assertJsonPath('data.conversation', null);
});

test('a guest with the same name and email resumes the open chat', function () {
    $existing = ChatConversation::create([
        'guest_email' => 'guest@example.com',
        'guest_name' => 'Tra My',
        'guest_session_id' => 'old-device',
        'status' => 'open',
    ]);
    ChatMessage::create([
        'conversation_id' => $existing->id,
        'is_from_customer' => true,
        'body' => 'Hello from last visit',
    ]);

    $this->withHeader('X-Live-Chat-Guest', str_repeat('c', 40))
        ->postJson('/api/v1/live-chat', [
            'name' => 'tra my',
            'email' => 'Guest@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('data.conversation.id', $existing->id)
        ->assertJsonPath('data.messages.0.body', 'Hello from last visit');
});

test('a signed-in customer can chat without entering a name', function () {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);
    $plain = $user->createToken('BluLavelle app')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->postJson('/api/v1/live-chat')
        ->assertOk()
        ->assertJsonPath('data.conversation.customerName', 'Ada Lovelace');

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->postJson('/api/v1/live-chat/messages', ['body' => 'I need a size guide'])
        ->assertCreated();
});

test('unread count counts shop replies after the last seen message', function () {
    $token = str_repeat('d', 40);
    $start = $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/live-chat', [
            'name' => 'Tra My',
            'email' => 'guest@example.com',
        ])
        ->assertOk();

    ChatMessage::create([
        'conversation_id' => $start->json('data.conversation.id'),
        'is_from_customer' => false,
        'body' => 'We can help with that.',
    ]);

    $this->withHeader('X-Live-Chat-Guest', $token)
        ->getJson('/api/v1/live-chat?lastSeenMessageId=0')
        ->assertOk()
        ->assertJsonPath('data.unreadCount', 1);
});

test('a bot product card url does not crash the mobile chat payload', function () {
    $token = str_repeat('e', 40);
    $conversation = ChatConversation::create([
        'guest_email' => 'guest@example.com',
        'guest_name' => 'Tra My',
        'guest_session_id' => $token,
        'status' => 'open',
    ]);
    ChatMessage::create([
        'conversation_id' => $conversation->id,
        'is_from_customer' => false,
        'sender_type' => ChatMessage::SENDER_BOT,
        'body' => "Here is a set: https://blulavelle.com/products/short-almond-rose\nMore details: https://blulavelle.com/sizing-kit",
        'metadata' => [
            'products' => [[
                'id' => 12,
                'name' => 'Short Almond Rose Set',
                'price' => 24,
                'url' => 'https://blulavelle.com/products/short-almond-rose',
                'image' => null,
            ]],
        ],
    ]);

    $this->withHeader('X-Live-Chat-Guest', $token)
        ->getJson('/api/v1/live-chat')
        ->assertOk()
        ->assertJsonPath('data.messages.0.products.0.slug', 'short-almond-rose')
        ->assertJsonPath('data.messages.0.products.0.url', 'blucommerce://product/short-almond-rose')
        ->assertJsonPath('data.messages.0.body', "Here is a set: blucommerce://product/short-almond-rose\nMore details: blucommerce://content/nail-sizing");
});

test('a mobile message is answered by the same chatbot as the website', function () {
    config([
        'chatbot.enabled' => true,
        'chatbot.ai_enabled' => false,
    ]);

    $admin = User::factory()->create();
    Page::query()->create([
        'user_id' => $admin->id,
        'title' => 'Shipping & Delivery',
        'slug' => 'shipping-delivery',
        'content' => '<p>Orders are processed in 1-7 business days. Delivery takes 10-20 business days after dispatch.</p>',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    $token = str_repeat('e', 40);
    $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/live-chat', [
            'name' => 'Tra My',
            'email' => 'guest@example.com',
        ])
        ->assertOk();

    $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/live-chat/messages', ['body' => 'How long does shipping take?'])
        ->assertCreated()
        ->assertJsonPath('data.botStatus', 'replied')
        ->assertJsonPath('data.handlingMode', ChatConversation::HANDLING_BOT)
        ->assertJsonPath('data.message.senderType', ChatMessage::SENDER_CUSTOMER);

    $thread = $this->withHeader('X-Live-Chat-Guest', $token)
        ->getJson('/api/v1/live-chat')
        ->assertOk()
        ->assertJsonPath('data.botEnabled', true)
        ->assertJsonPath('data.messages.1.senderType', ChatMessage::SENDER_BOT);

    expect($thread->json('data.messages.1.body'))->toContain('1-7 business days');
});

test('a human-handled mobile chat does not ask the bot to reply', function () {
    config(['chatbot.enabled' => true]);
    $token = str_repeat('f', 40);
    $conversation = ChatConversation::create([
        'guest_email' => 'guest@example.com',
        'guest_name' => 'Tra My',
        'guest_session_id' => $token,
        'status' => 'open',
        'handling_mode' => ChatConversation::HANDLING_HUMAN,
        'handed_off_at' => now(),
    ]);

    $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/live-chat/messages', ['body' => 'How long does shipping take?'])
        ->assertCreated()
        ->assertJsonPath('data.botStatus', 'human')
        ->assertJsonPath('data.handlingMode', ChatConversation::HANDLING_HUMAN)
        ->assertJsonPath('data.supportStatus', 'waiting');

    expect(ChatMessage::query()->where('conversation_id', $conversation->id)->where('sender_type', ChatMessage::SENDER_BOT)->count())->toBe(0);
});
