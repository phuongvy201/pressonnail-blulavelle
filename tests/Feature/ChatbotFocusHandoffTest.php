<?php

use App\Models\Category;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Services\Chatbot\ChatbotAiProvider;
use App\Services\Chatbot\ChatbotService;

beforeEach(function () {
    config([
        'chatbot.enabled' => true,
        'chatbot.ai_enabled' => true,
        'chatbot.handoff_on_unknown' => true,
    ]);

    $provider = new class implements ChatbotAiProvider
    {
        public function compose(string $question, array $facts, array $history = []): ?array
        {
            $summary = trim((string) ($facts['summary'] ?? ''));
            if ($summary === '') {
                return null;
            }

            return [
                'body' => $summary,
                'confidence' => 0.92,
                'model' => 'test-model',
                'usage' => ['total_tokens' => 8],
            ];
        }
    };
    app()->instance(ChatbotAiProvider::class, $provider);
});

function focusChatConversation(): ChatConversation
{
    return ChatConversation::query()->create([
        'guest_name' => 'Focus Guest',
        'guest_email' => 'focus@example.com',
        'guest_session_id' => 'focus-session-'.uniqid(),
        'status' => 'open',
    ]);
}

function focusAsk(string $body): ChatMessage
{
    $conversation = focusChatConversation();

    return app(ChatbotService::class)->handleMessage(
        $conversation,
        ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'is_from_customer' => true,
            'sender_type' => ChatMessage::SENDER_CUSTOMER,
            'body' => $body,
        ]),
    );
}

function focusPublishPage(string $title, string $slug, string $html): void
{
    $admin = User::factory()->create();
    Page::query()->create([
        'user_id' => $admin->id,
        'title' => $title,
        'slug' => $slug,
        'content' => $html,
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);
}

test('core shipping question is answered from verified FAQ facts', function () {
    focusPublishPage(
        'Shipping & Delivery',
        'shipping-delivery',
        '<p>Orders are processed in 1-7 business days. Delivery takes 10-20 business days after dispatch.</p>',
    );

    $reply = focusAsk('How long does shipping take?');

    expect($reply->intent)->toBe('faq')
        ->and($reply->body)->toContain('1-7 business days')
        ->and($reply->metadata['ai']['used'])->toBeTrue()
        ->and(ChatConversation::query()->find($reply->conversation_id)->handling_mode)
        ->toBe(ChatConversation::HANDLING_BOT);
});

test('core returns question is answered from verified FAQ facts', function () {
    focusPublishPage(
        'Returns & Exchanges',
        'returns-exchanges-policy',
        '<p>Unopened sets can be returned within 14 days of delivery for a refund or exchange.</p>',
    );

    $reply = focusAsk('What is your return policy?');

    expect($reply->intent)->toBe('faq')
        ->and($reply->body)->toContain('14 days')
        ->and(ChatConversation::query()->find($reply->conversation_id)->handling_mode)
        ->toBe(ChatConversation::HANDLING_BOT);
});

test('core product recommend question answers only when catalog matches', function () {
    $category = Category::query()->create(['name' => 'Nails', 'slug' => 'nails-'.uniqid()]);
    $template = ProductTemplate::query()->create([
        'name' => 'Press-on template',
        'category_id' => $category->id,
        'base_price' => 22,
    ]);
    Product::withoutEvents(fn () => Product::query()->create([
        'template_id' => $template->id,
        'name' => 'French Tip Almond Set',
        'slug' => 'french-tip-'.uniqid(),
        'price' => 26,
        'description' => 'Classic french tip almond press-on nails.',
        'color' => 'french',
        'media' => ['/images/french.jpg'],
        'quantity' => 4,
        'status' => 'active',
    ]));

    $reply = focusAsk('Show me french tip almond nails');

    expect($reply->intent)->toBe('product_search')
        ->and($reply->body)->toContain('French Tip Almond Set')
        ->and(ChatConversation::query()->find($reply->conversation_id)->handling_mode)
        ->toBe(ChatConversation::HANDLING_BOT);
});

test('vague nail chat without a clear ask is handed to support', function () {
    $category = Category::query()->create(['name' => 'Nails', 'slug' => 'nails-'.uniqid()]);
    $template = ProductTemplate::query()->create([
        'name' => 'Press-on template',
        'category_id' => $category->id,
        'base_price' => 20,
    ]);
    Product::withoutEvents(fn () => Product::query()->create([
        'template_id' => $template->id,
        'name' => 'Random Rose Set',
        'slug' => 'random-rose-'.uniqid(),
        'price' => 21,
        'description' => 'Rose press-on nails.',
        'color' => 'rose',
        'media' => ['/images/rose.jpg'],
        'quantity' => 2,
        'status' => 'active',
    ]));
    focusPublishPage(
        'About Us',
        'about-us',
        '<p>Welcome to Blu Lavelle Press-on Nails for Every Style.</p>',
    );

    $reply = focusAsk('Just chatting, nothing specific to ask right now');

    expect($reply->intent)->toBe('handoff')
        ->and($reply->body)->not->toContain('Random Rose Set')
        ->and($reply->body)->not->toContain('About Us')
        ->and(ChatConversation::query()->find($reply->conversation_id)->handling_mode)
        ->toBe(ChatConversation::HANDLING_HUMAN);
});

test('off-topic weather question is handed to support', function () {
    $reply = focusAsk('What is the weather in Austin tomorrow?');

    expect($reply->intent)->toBe('handoff')
        ->and(ChatConversation::query()->find($reply->conversation_id)->handling_mode)
        ->toBe(ChatConversation::HANDLING_HUMAN)
        ->and($reply->metadata['handoff_reason'])->toContain('outside BluLavelle');
});

test('off-topic joke request is handed to support', function () {
    $reply = focusAsk('Tell me a joke about cats');

    expect($reply->intent)->toBe('handoff')
        ->and(ChatConversation::query()->find($reply->conversation_id)->handling_mode)
        ->toBe(ChatConversation::HANDLING_HUMAN);
});

test('unmatched named set is handed to support instead of inventing an answer', function () {
    focusPublishPage(
        'About Us',
        'about-us',
        '<p>Welcome to Blu Lavelle Press-on Nails for Every Style Discover premium press-on nail sets.</p>',
    );

    $reply = focusAsk('What about the unicorn galaxy chrome press-on nails');

    expect($reply->intent)->toBe('handoff')
        ->and($reply->body)->not->toContain('about-us')
        ->and(ChatConversation::query()->find($reply->conversation_id)->handling_mode)
        ->toBe(ChatConversation::HANDLING_HUMAN);
});

test('bot still answers from verified facts when AI phrasing is disabled', function () {
    config(['chatbot.ai_enabled' => false]);
    focusPublishPage(
        'Shipping & Delivery',
        'shipping-delivery',
        '<p>Orders are processed in 1-7 business days.</p>',
    );

    $reply = focusAsk('How long does shipping take?');

    expect($reply->intent)->toBe('faq')
        ->and($reply->body)->toContain('1-7 business days')
        ->and($reply->metadata['ai']['used'])->toBeFalse()
        ->and(ChatConversation::query()->find($reply->conversation_id)->handling_mode)
        ->toBe(ChatConversation::HANDLING_BOT);
});

test('low-confidence AI rewrite is discarded in favor of verified shipping facts', function () {
    $provider = new class implements ChatbotAiProvider
    {
        public function compose(string $question, array $facts, array $history = []): ?array
        {
            return [
                'body' => 'Maybe something about shipping?',
                'confidence' => 0.2,
                'model' => 'test-model',
                'usage' => [],
            ];
        }
    };
    app()->instance(ChatbotAiProvider::class, $provider);

    focusPublishPage(
        'Shipping & Delivery',
        'shipping-delivery',
        '<p>Orders are processed in 1-7 business days.</p>',
    );

    $reply = focusAsk('How long does shipping take?');

    expect($reply->intent)->toBe('faq')
        ->and($reply->body)->toContain('1-7 business days')
        ->and(ChatConversation::query()->find($reply->conversation_id)->handling_mode)
        ->toBe(ChatConversation::HANDLING_BOT);
});

test('AI brand-story dump is replaced by verified shipping facts', function () {
    $provider = new class implements ChatbotAiProvider
    {
        public function compose(string $question, array $facts, array $history = []): ?array
        {
            return [
                'body' => 'Welcome to Blu Lavelle Press-on Nails for Every Style. Discover premium press-on nail sets designed to elevate your look.',
                'confidence' => 0.95,
                'model' => 'test-model',
                'usage' => [],
            ];
        }
    };
    app()->instance(ChatbotAiProvider::class, $provider);

    focusPublishPage(
        'Shipping & Delivery',
        'shipping-delivery',
        '<p>Orders are processed in 1-7 business days. Delivery takes 10-20 business days after dispatch.</p>',
    );

    $reply = focusAsk('How long does shipping take?');

    expect($reply->intent)->toBe('faq')
        ->and($reply->body)->toContain('1-7 business days')
        ->and($reply->body)->not->toContain('Welcome to Blu Lavelle')
        ->and(ChatConversation::query()->find($reply->conversation_id)->handling_mode)
        ->toBe(ChatConversation::HANDLING_BOT);
});

test('portrait photo upload how-to is handed to support when no verified instructions exist', function () {
    $reply = focusAsk('For the personalized portrait nails, where do I upload a photo?');

    expect($reply->intent)->toBe('handoff')
        ->and($reply->body)->toContain('support team')
        ->and($reply->metadata['handoff_reason'])->not->toBeEmpty()
        ->and(ChatConversation::query()->find($reply->conversation_id)->handling_mode)
        ->toBe(ChatConversation::HANDLING_HUMAN);
});

test('AI answer that ignores verified facts falls back to the summary', function () {
    $provider = new class implements ChatbotAiProvider
    {
        public function compose(string $question, array $facts, array $history = []): ?array
        {
            return [
                'body' => 'Nails are a fun fashion accessory and you should always smile.',
                'confidence' => 0.9,
                'model' => 'test-model',
                'usage' => [],
            ];
        }
    };
    app()->instance(ChatbotAiProvider::class, $provider);

    focusPublishPage(
        'Returns & Exchanges',
        'returns-exchanges-policy',
        '<p>Unopened sets can be returned within 14 days of delivery for a refund or exchange.</p>',
    );

    $reply = focusAsk('What is your return policy?');

    expect($reply->intent)->toBe('faq')
        ->and($reply->body)->toContain('14 days')
        ->and($reply->body)->not->toContain('always smile');
});
