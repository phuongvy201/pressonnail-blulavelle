<?php

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Review;
use App\Support\ContentSafety;

test('content safety detects blocked terms', function () {
    expect(ContentSafety::containsObjectionable('Please kys'))->toBeTrue();
    expect(ContentSafety::containsObjectionable('I need help with sizing'))->toBeFalse();
});

test('live chat rejects objectionable message', function () {
    $token = str_repeat('a', 40);

    $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/live-chat', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
        ])
        ->assertOk();

    $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/live-chat/messages', [
            'body' => 'go die',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'CHAT_REJECTED');
});

test('guest can report chat message and block conversation', function () {
    $token = str_repeat('b', 40);
    $start = $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/live-chat', [
            'name' => 'Sam',
            'email' => 'sam@example.com',
        ])
        ->assertOk();

    $conversationId = (int) $start->json('data.conversation.id');
    $conversation = ChatConversation::query()->findOrFail($conversationId);
    $message = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'is_from_customer' => false,
        'body' => 'Hello from the shop',
    ]);

    $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/safety/reports', [
            'targetType' => 'chat_message',
            'targetId' => $message->id,
            'reason' => 'harassment',
        ])
        ->assertCreated()
        ->assertJsonPath('data.reported', true);

    $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/safety/blocks', [
            'scope' => 'chat_conversation',
            'targetId' => $conversationId,
        ])
        ->assertOk()
        ->assertJsonPath('data.blocked', true);

    $this->withHeader('X-Live-Chat-Guest', $token)
        ->getJson('/api/v1/live-chat')
        ->assertOk()
        ->assertJsonPath('data.conversation.blocked', true);
});

test('guest can report a review', function () {
    $category = Category::query()->create([
        'name' => 'Nails',
        'slug' => 'nails-'.uniqid(),
    ]);
    $template = ProductTemplate::query()->create([
        'name' => 'Classic template',
        'category_id' => $category->id,
        'base_price' => 20,
    ]);
    $product = Product::withoutEvents(fn () => Product::query()->create([
        'template_id' => $template->id,
        'name' => 'Classic Set',
        'slug' => 'classic-'.uniqid(),
        'price' => 20,
        'status' => 'active',
    ]));
    $review = Review::query()->create([
        'product_id' => $product->id,
        'customer_name' => 'Reviewer',
        'rating' => 5,
        'review_text' => 'Nice set',
        'is_approved' => true,
    ]);

    $token = str_repeat('c', 40);
    $this->withHeader('X-Live-Chat-Guest', $token)
        ->postJson('/api/v1/safety/reports', [
            'targetType' => 'review',
            'targetId' => $review->id,
            'reason' => 'spam',
        ])
        ->assertCreated();
});
