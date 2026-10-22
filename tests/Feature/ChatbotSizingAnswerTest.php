<?php

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Page;
use App\Models\User;
use App\Services\Chatbot\ChatbotService;

test('chatbot answers a sizing question from the sizing page not the about page', function () {
    config(['chatbot.enabled' => true, 'chatbot.ai_enabled' => false]);
    $admin = User::factory()->create();

    Page::query()->create([
        'user_id' => $admin->id,
        'title' => 'About Us',
        'slug' => 'about-us',
        'content' => '<p>WC2H 9JQ, United Kingdom. Bluprinter LLC 5900 Balcones Dr Austin TX. Find Your Next Nail Set. Explore our newest press-on nail designs and choose a style that matches your vibe today.</p>',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);
    // The real Sizing Kit lives at the dedicated /sizing-kit route, not in CMS pages.

    $conversation = ChatConversation::query()->create([
        'guest_name' => 'Guest Customer',
        'guest_email' => 'guest@example.com',
        'guest_session_id' => 'sizing-session',
        'status' => 'open',
    ]);
    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'is_from_customer' => true,
        'sender_type' => ChatMessage::SENDER_CUSTOMER,
        'body' => 'How do I choose the right nail size?',
    ]);

    $reply = app(ChatbotService::class)->handleMessage($conversation, $message);

    expect($reply)->not->toBeNull()
        ->and($reply->body)->toContain('Sizing Kit')
        ->and($reply->body)->toContain('14-10-11-10-8')
        ->and($reply->body)->toContain('/sizing-kit')
        ->and($reply->body)->not->toContain('Balcones')
        ->and($reply->body)->not->toContain('About');
});
