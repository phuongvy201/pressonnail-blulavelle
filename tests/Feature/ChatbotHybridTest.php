<?php

use App\Models\Category;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Services\Chatbot\ChatbotAiProvider;
use App\Services\Chatbot\ChatbotService;
use Spatie\Permission\Models\Role;

function hybridChatConversation(array $attributes = []): ChatConversation
{
    return ChatConversation::query()->create(array_merge([
        'guest_name' => 'Guest Customer',
        'guest_email' => 'guest@example.com',
        'guest_session_id' => 'chatbot-session',
        'status' => 'open',
    ], $attributes));
}

function hybridCustomerMessage(ChatConversation $conversation, string $body): ChatMessage
{
    return ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'is_from_customer' => true,
        'sender_type' => ChatMessage::SENDER_CUSTOMER,
        'body' => $body,
    ]);
}

function hybridBotReply(ChatConversation $conversation): ?ChatMessage
{
    return ChatMessage::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_type', ChatMessage::SENDER_BOT)
        ->latest('id')
        ->first();
}

beforeEach(function () {
    config([
        'chatbot.enabled' => true,
        'chatbot.ai_enabled' => true,
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
                'confidence' => 0.91,
                'model' => 'test-model',
                'usage' => ['total_tokens' => 12],
            ];
        }
    };
    app()->instance(ChatbotAiProvider::class, $provider);
});

test('chatbot answers a shipping question from published store knowledge', function () {
    $admin = User::factory()->create();
    Page::query()->create([
        'user_id' => $admin->id,
        'title' => 'Shipping & Delivery',
        'slug' => 'shipping-delivery',
        'content' => '<p>Orders are processed in 1-7 business days. Delivery takes 10-20 business days after dispatch.</p>',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    $conversation = hybridChatConversation();
    $message = hybridCustomerMessage($conversation, 'How long does shipping take?');

    $reply = app(ChatbotService::class)->handleMessage($conversation, $message);

    expect($reply)->not->toBeNull()
        ->and($reply->sender_type)->toBe(ChatMessage::SENDER_BOT)
        ->and($reply->intent)->toBe('faq')
        ->and($reply->body)->toContain('1-7 business days');
});

test('chatbot recommends only available products and includes real prices and links', function () {
    $category = Category::query()->create(['name' => 'Nails', 'slug' => 'nails-'.uniqid()]);
    $template = ProductTemplate::query()->create([
        'name' => 'Press-on template',
        'category_id' => $category->id,
        'base_price' => 20,
    ]);

    $available = Product::withoutEvents(fn () => Product::query()->create([
        'template_id' => $template->id,
        'name' => 'Short Almond Rose Set',
        'slug' => 'short-almond-rose-'.uniqid(),
        'price' => 24,
        'description' => 'Short almond press-on nails in rose pink.',
        'color' => 'rose pink',
        'media' => ['/images/rose.jpg'],
        'quantity' => 5,
        'status' => 'active',
    ]));
    Product::withoutEvents(fn () => Product::query()->create([
        'template_id' => $template->id,
        'name' => 'Short Almond Sold Out Set',
        'slug' => 'short-almond-sold-out-'.uniqid(),
        'price' => 19,
        'description' => 'Short almond press-on nails.',
        'color' => 'rose pink',
        'media' => ['/images/sold-out.jpg'],
        'quantity' => 0,
        'status' => 'active',
    ]));

    $conversation = hybridChatConversation();
    $message = hybridCustomerMessage($conversation, 'Show me short almond nails under $30');

    $reply = app(ChatbotService::class)->handleMessage($conversation, $message);

    expect($reply)->not->toBeNull()
        ->and($reply->intent)->toBe('product_search')
        ->and($reply->body)->toContain('Short Almond Rose Set')
        ->and($reply->body)->not->toContain('Sold Out')
        ->and($reply->metadata['products'][0]['id'])->toBe($available->id)
        ->and($reply->metadata['products'][0]['price'])->toEqual(24.0)
        ->and($reply->metadata['products'][0]['url'])->toContain('/products/'.$available->slug);
});

test('chatbot returns order status only for the conversation owner', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    $other = User::factory()->create(['email' => 'other@example.com']);

    Order::query()->create([
        'order_number' => 'ORD-OWNER-1001',
        'user_id' => $owner->id,
        'customer_name' => $owner->name,
        'customer_email' => $owner->email,
        'shipping_address' => '123 Main',
        'city' => 'Austin',
        'postal_code' => '78701',
        'country' => 'US',
        'subtotal' => 30,
        'total_amount' => 35,
        'status' => 'shipped',
        'payment_status' => 'paid',
        'tracking_number' => 'TRACK-OWNER-1',
    ]);
    Order::query()->create([
        'order_number' => 'ORD-OTHER-2002',
        'user_id' => $other->id,
        'customer_name' => $other->name,
        'customer_email' => $other->email,
        'shipping_address' => '456 Main',
        'city' => 'Austin',
        'postal_code' => '78701',
        'country' => 'US',
        'subtotal' => 30,
        'total_amount' => 35,
        'status' => 'processing',
        'payment_status' => 'paid',
        'tracking_number' => 'TRACK-OTHER-2',
    ]);

    $conversation = hybridChatConversation([
        'customer_user_id' => $owner->id,
        'guest_email' => null,
        'guest_name' => null,
        'guest_session_id' => null,
    ]);

    $ownReply = app(ChatbotService::class)->handleMessage(
        $conversation,
        hybridCustomerMessage($conversation, 'Where is order ORD-OWNER-1001?'),
    );
    expect($ownReply->body)->toContain('TRACK-OWNER-1');

    $otherReply = app(ChatbotService::class)->handleMessage(
        $conversation,
        hybridCustomerMessage($conversation, 'Where is order ORD-OTHER-2002?'),
    );
    expect($otherReply->body)->not->toContain('TRACK-OTHER-2')
        ->and($otherReply->body)->toContain('could not verify');
});

test('chatbot hands sensitive requests to a human without changing the order', function () {
    $owner = User::factory()->create(['email' => 'cancel@example.com']);
    $order = Order::query()->create([
        'order_number' => 'ORD-CANCEL-3003',
        'user_id' => $owner->id,
        'customer_name' => $owner->name,
        'customer_email' => $owner->email,
        'shipping_address' => '123 Main',
        'city' => 'Austin',
        'postal_code' => '78701',
        'country' => 'US',
        'subtotal' => 30,
        'total_amount' => 35,
        'status' => 'pending',
        'payment_status' => 'paid',
    ]);

    $conversation = hybridChatConversation([
        'customer_user_id' => $owner->id,
        'guest_email' => null,
        'guest_name' => null,
        'guest_session_id' => null,
    ]);
    $message = hybridCustomerMessage($conversation, 'Please cancel order ORD-CANCEL-3003');

    $reply = app(ChatbotService::class)->handleMessage($conversation, $message);

    expect($reply->intent)->toBe('handoff')
        ->and($conversation->refresh()->handling_mode)->toBe(ChatConversation::HANDLING_HUMAN)
        ->and($order->refresh()->status)->toBe('pending');
});

test('customer can request a human and the bot stops replying afterwards', function () {
    $conversation = hybridChatConversation();

    app(ChatbotService::class)->handleMessage(
        $conversation,
        hybridCustomerMessage($conversation, 'Talk to a human please'),
    );

    expect($conversation->refresh()->handling_mode)->toBe(ChatConversation::HANDLING_HUMAN);

    $reply = app(ChatbotService::class)->handleMessage(
        $conversation,
        hybridCustomerMessage($conversation, 'How long does shipping take?'),
    );

    expect($reply)->toBeNull();
});

test('website send endpoint stores the customer message and bot reply', function () {
    $start = $this->postJson('/live-chat/start', [
        'name' => 'Guest Customer',
        'email' => 'guest@example.com',
    ])->assertOk();
    $conversationId = $start->json('conversation.id');

    $this->postJson('/live-chat/send', [
        'conversation_id' => $conversationId,
        'body' => 'How long do BluLavelle nails last?',
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('bot_status', 'replied');

    $this->getJson('/live-chat/conversations/'.$conversationId.'/messages')
        ->assertOk()
        ->assertJsonPath('handling_mode', ChatConversation::HANDLING_BOT)
        ->assertJsonFragment(['sender_type' => ChatMessage::SENDER_BOT]);
});

test('admin can take over and return a conversation to the bot', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $conversation = hybridChatConversation();

    $this->actingAs($admin)
        ->postJson('/admin/live-chat/'.$conversation->id.'/takeover')
        ->assertOk()
        ->assertJsonPath('handling_mode', ChatConversation::HANDLING_HUMAN);

    $this->actingAs($admin)
        ->postJson('/admin/live-chat/'.$conversation->id.'/return-to-bot')
        ->assertOk()
        ->assertJsonPath('handling_mode', ChatConversation::HANDLING_BOT);
});
