<?php

namespace App\Services\Chatbot;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotService
{
    public function __construct(
        private readonly ChatbotKnowledgeService $knowledge,
        private readonly ChatbotAiProvider $ai,
        private readonly TelegramBotService $telegram,
    ) {}

    public function handleMessage(ChatConversation $conversation, ChatMessage $customerMessage): ?ChatMessage
    {
        $conversation->refresh();
        if (! $this->shouldReply($conversation)) {
            return null;
        }

        $existing = $this->existingReply($customerMessage);
        if ($existing) {
            return $existing;
        }

        $question = trim((string) $customerMessage->body);
        if ($question === '') {
            return null;
        }

        $handoffReason = $this->handoffReason($question);
        if ($handoffReason !== null) {
            return $this->handoff($conversation, $handoffReason, $customerMessage);
        }

        $conversation->forceFill(['bot_processing_at' => now()])->save();

        try {
            [$intent, $facts, $metadata] = $this->factsFor($conversation, $question);
            $summary = trim((string) ($facts['summary'] ?? ''));
            $composed = $this->compose($question, $facts, $conversation, $intent);

            $body = $composed['body'] ?? $summary;
            if ($body === '') {
                $body = (string) config('chatbot.fallback');
                $intent = 'fallback';
            }

            $metadata = array_merge($metadata, [
                'reply_to_message_id' => $customerMessage->id,
                'ai' => [
                    'used' => $composed !== null,
                    'model' => $composed['model'] ?? null,
                    'usage' => $composed['usage'] ?? [],
                ],
            ]);

            $reply = ChatMessage::query()->create([
                'conversation_id' => $conversation->id,
                'is_from_customer' => false,
                'sender_type' => ChatMessage::SENDER_BOT,
                'body' => $body,
                'metadata' => $metadata,
                'intent' => $intent,
                'confidence' => $composed['confidence'] ?? ($intent === 'fallback' ? 0.2 : 0.86),
            ]);

            $conversation->forceFill([
                'last_bot_intent' => $intent,
                'bot_processing_at' => null,
            ])->save();

            if ($intent === 'fallback' || (float) $reply->confidence < 0.5) {
                $this->telegram->notifyBotReply($conversation, $reply);
            }

            return $reply;
        } catch (Throwable $exception) {
            Log::warning('Chatbot could not generate a reply.', [
                'conversation_id' => $conversation->id,
                'message_id' => $customerMessage->id,
                'exception' => $exception::class,
            ]);
            $conversation->forceFill(['bot_processing_at' => null])->save();

            $fallback = ChatMessage::query()->create([
                'conversation_id' => $conversation->id,
                'is_from_customer' => false,
                'sender_type' => ChatMessage::SENDER_BOT,
                'body' => (string) config('chatbot.fallback'),
                'metadata' => [
                    'reply_to_message_id' => $customerMessage->id,
                    'quick_replies' => $this->quickReplies(),
                ],
                'intent' => 'fallback',
                'confidence' => 0.1,
            ]);

            $this->telegram->notifyBotReply($conversation, $fallback);

            return $fallback;
        }
    }

    public function handoff(
        ChatConversation $conversation,
        string $reason,
        ?ChatMessage $customerMessage = null,
    ): ChatMessage {
        $conversation->forceFill([
            'handling_mode' => ChatConversation::HANDLING_HUMAN,
            'handed_off_at' => now(),
            'last_bot_intent' => 'handoff',
            'bot_processing_at' => null,
        ])->save();

        $reply = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'is_from_customer' => false,
            'sender_type' => ChatMessage::SENDER_BOT,
            'body' => $this->supportAcknowledgement($conversation),
            'metadata' => [
                'reply_to_message_id' => $customerMessage?->id,
                'handoff_reason' => $reason,
                'support_reference' => 'SUP-'.$conversation->id,
                'support_reply_within' => (string) config('chatbot.support_reply_within', '2 business hours'),
                'support_hours' => (string) config('chatbot.support_hours', 'Mon–Sat, 9am–6pm'),
                'quick_replies' => [],
            ],
            'intent' => 'handoff',
            'confidence' => 1,
        ]);

        $lines = [
            '🙋 Chatbot handoff to support',
            'Conversation: #'.$conversation->id,
            'Customer: '.$conversation->customer_name,
            'Reason: '.$reason,
        ];
        if ($customerMessage !== null && filled($customerMessage->body)) {
            $lines[] = '';
            $lines[] = 'Customer said:';
            $lines[] = (string) $customerMessage->body;
        }
        $lines[] = '';
        $lines[] = 'Bot said:';
        $lines[] = $reply->body;

        $this->telegram->sendMessage(implode("\n", $lines));

        return $reply;
    }

    public function takeover(ChatConversation $conversation, bool $announce = false): ChatConversation
    {
        $wasBotHandled = $conversation->isBotHandled();

        $conversation->forceFill([
            'handling_mode' => ChatConversation::HANDLING_HUMAN,
            'handed_off_at' => $conversation->handed_off_at ?? now(),
            'bot_processing_at' => null,
        ])->save();

        $conversation->refresh();

        if ($announce && $wasBotHandled) {
            ChatMessage::query()->create([
                'conversation_id' => $conversation->id,
                'is_from_customer' => false,
                'sender_type' => ChatMessage::SENDER_SYSTEM,
                'body' => $this->supportAcknowledgement($conversation),
                'metadata' => [
                    'support_reference' => 'SUP-'.$conversation->id,
                    'support_reply_within' => (string) config('chatbot.support_reply_within', '2 business hours'),
                    'support_hours' => (string) config('chatbot.support_hours', 'Mon–Sat, 9am–6pm'),
                ],
                'intent' => 'handoff',
                'confidence' => 1,
            ]);
        }

        return $conversation;
    }

    public function supportAcknowledgement(ChatConversation $conversation): string
    {
        $name = trim((string) $conversation->customer_name);
        $greeting = $name !== '' && $name !== 'Customer'
            ? 'Thanks, '.$name.' — I’ve passed this to our support team.'
            : 'Thanks — I’ve passed this to our support team.';
        $replyWithin = trim((string) config('chatbot.support_reply_within', '2 business hours'));
        $hours = trim((string) config('chatbot.support_hours', 'Mon–Sat, 9am–6pm'));
        $email = trim((string) ($conversation->customer_email ?? ''));

        $lines = [
            $greeting,
            'Your request reference is #SUP-'.$conversation->id.'.',
            'We usually reply within '.$replyWithin.' ('.$hours.').',
        ];

        if (filled($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $lines[] = 'You don’t need to stay on this page. We’ll email you at '.$email.' as soon as we reply, and your conversation will be here when you return.';
        } else {
            $lines[] = 'You don’t need to stay on this page. Your conversation will be here when you return.';
        }

        return implode("\n", $lines);
    }

    public function returnToBot(ChatConversation $conversation): ChatConversation
    {
        $conversation->forceFill([
            'handling_mode' => ChatConversation::HANDLING_BOT,
            'handed_off_at' => null,
            'bot_processing_at' => null,
        ])->save();

        return $conversation->refresh();
    }

    public function shouldReply(ChatConversation $conversation): bool
    {
        return (bool) config('chatbot.enabled')
            && $conversation->status === 'open'
            && $conversation->isBotHandled();
    }

    /**
     * @return array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function factsFor(ChatConversation $conversation, string $question): array
    {
        $orderNumber = $this->knowledge->extractOrderNumber($question);
        $isOrderQuestion = $orderNumber !== null
            || preg_match('/\b(order|track|tracking|delivery status)\b/i', $question) === 1;

        if ($isOrderQuestion) {
            return $this->orderFacts($conversation, $question, $orderNumber);
        }

        $isProductRequest = preg_match(
            '/\b(show|find|recommend|suggest|looking for|buy|shop|almond|coffin|square|oval|stiletto|french)\b|\bunder\s*\$?\d/i',
            $question,
        ) === 1;

        if ($isProductRequest) {
            $products = $this->knowledge->searchProducts(
                $question,
                max(1, (int) config('chatbot.max_products', 3)),
            );

            if ($products !== []) {
                $lines = collect($products)->map(function (array $product) {
                    return sprintf(
                        '%s — $%s — %s',
                        $product['name'],
                        number_format((float) $product['price'], 2),
                        $product['url'],
                    );
                })->implode("\n");

                return [
                    'product_search',
                    [
                        'summary' => "Here are available sets that match your request:\n".$lines,
                        'products' => $products,
                    ],
                    [
                        'products' => $products,
                        'quick_replies' => ['Show different styles', 'Sizing help', 'Talk to support'],
                    ],
                ];
            }
        }

        $faq = $this->knowledge->faqMatches($question);
        if ($faq !== []) {
            $source = $faq[0];
            $summary = $source['body'];
            if (filled($source['url'])) {
                $summary .= "\n\nMore details: ".$source['url'];
            }

            return [
                'faq',
                [
                    'summary' => $summary,
                    'sources' => $faq,
                ],
                [
                    'sources' => $faq,
                    'quick_replies' => $this->quickReplies(),
                ],
            ];
        }

        if (! $isProductRequest && preg_match('/\b(nails?|sets?|styles?|colou?rs?)\b/i', $question) === 1) {
            $products = $this->knowledge->searchProducts(
                $question,
                max(1, (int) config('chatbot.max_products', 3)),
            );
            if ($products !== []) {
                $lines = collect($products)->map(fn (array $product) => sprintf(
                    '%s — $%s — %s',
                    $product['name'],
                    number_format((float) $product['price'], 2),
                    $product['url'],
                ))->implode("\n");

                return [
                    'product_search',
                    ['summary' => "Here are available sets that match your request:\n".$lines, 'products' => $products],
                    ['products' => $products, 'quick_replies' => $this->quickReplies()],
                ];
            }
        }

        return [
            'fallback',
            [
                'summary' => 'No verified store-specific facts were retrieved. Answer only general press-on nail guidance that does not depend on BluLavelle prices, stock, policies, delivery promises, or a customer order. If the question needs store-specific facts, say that you do not have confirmed information and offer support.',
            ],
            ['quick_replies' => $this->quickReplies()],
        ];
    }

    /**
     * @return array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function orderFacts(ChatConversation $conversation, string $question, ?string $orderNumber): array
    {
        if ($orderNumber === null) {
            return [
                'order_lookup',
                ['summary' => 'Please send me your order number and I’ll check its current status. If you checked out as a guest, use the same email address you used for this chat.'],
                ['quick_replies' => ['Talk to support']],
            ];
        }

        $order = $this->knowledge->findOrderForConversation($conversation, $orderNumber);
        if ($order === null) {
            return [
                'order_lookup',
                ['summary' => 'I could not verify that order for this chat. Please check the order number, sign in with the account used for the order, or choose “Talk to support” and our team will help.'],
                ['quick_replies' => ['Talk to support']],
            ];
        }

        $facts = $this->knowledge->orderFacts($order);
        $summary = sprintf(
            'Order %s is %s. Payment status: %s.',
            $facts['order_number'],
            $facts['status'],
            $facts['payment_status'],
        );
        if (filled($facts['tracking_number'])) {
            $summary .= ' Tracking number: '.$facts['tracking_number'].'.';
        } else {
            $summary .= ' Tracking is not available yet.';
        }
        $summary .= sprintf(' Total: $%s %s.', number_format((float) $facts['total'], 2), $facts['currency']);

        return [
            'order_lookup',
            ['summary' => $summary, 'order' => $facts],
            ['order' => $facts, 'quick_replies' => $this->quickReplies()],
        ];
    }

    /**
     * @param  array<string, mixed>  $facts
     * @return array{body: string, confidence: float, model?: string|null, usage?: array<string, mixed>}|null
     */
    private function compose(string $question, array $facts, ChatConversation $conversation, string $intent): ?array
    {
        if (! config('chatbot.ai_enabled')) {
            return null;
        }

        $history = $conversation->messages()
            ->latest('id')
            ->limit(max(0, (int) config('chatbot.ai.history_messages', 6)))
            ->get()
            ->reverse()
            ->map(fn (ChatMessage $message) => [
                'sender_type' => $message->senderType(),
                'body' => $message->body,
            ])
            ->values()
            ->all();

        try {
            $composed = $this->ai->compose($question, $facts, $history);
        } catch (Throwable $exception) {
            Log::warning('Chatbot AI provider threw an exception.', [
                'conversation_id' => $conversation->id,
                'exception' => $exception::class,
            ]);

            return null;
        }

        if ($composed === null || trim((string) ($composed['body'] ?? '')) === '') {
            return null;
        }

        $body = trim((string) $composed['body']);
        $summary = trim((string) ($facts['summary'] ?? ''));

        if ($intent === 'product_search') {
            foreach (($facts['products'] ?? []) as $product) {
                if (! str_contains($body, (string) $product['name'])) {
                    return null;
                }
            }
        }

        $sources = collect($facts['sources'] ?? [])->pluck('source')->all();
        if (in_array('sizing_kit', $sources, true)
            && ! (str_contains($body, 'XS') && str_contains($body, 'mm'))) {
            return [
                'body' => $summary,
                'confidence' => 0.9,
                'model' => $composed['model'] ?? null,
                'usage' => $composed['usage'] ?? [],
            ];
        }

        if ($intent === 'order_lookup' && filled($facts['order']['tracking_number'] ?? null)) {
            if (! str_contains($body, (string) $facts['order']['tracking_number'])) {
                return [
                    'body' => $summary,
                    'confidence' => 0.9,
                    'model' => $composed['model'] ?? null,
                    'usage' => $composed['usage'] ?? [],
                ];
            }
        }

        return [
            'body' => $body,
            'confidence' => max(0, min(1, (float) ($composed['confidence'] ?? 0.8))),
            'model' => $composed['model'] ?? null,
            'usage' => $composed['usage'] ?? [],
        ];
    }

    private function handoffReason(string $question): ?string
    {
        $body = mb_strtolower($question);

        if (preg_match('/\b(talk|speak|chat)\b.*\b(human|person|agent|support)\b|\b(human|real person|live agent)\b/', $body) === 1) {
            return 'Customer requested a human.';
        }

        $asksForPolicy = preg_match('/\b(policy|offer|eligible|can i return|returns or refunds)\b/', $body) === 1;
        if (! $asksForPolicy && preg_match('/\b(cancel|refund me|change (my )?(address|order)|wrong item|damaged|broken|missing item|complaint|chargeback|dispute|charged twice)\b/', $body) === 1) {
            return 'Customer needs help with an order change, refund, or problem.';
        }

        return null;
    }

    private function existingReply(ChatMessage $customerMessage): ?ChatMessage
    {
        return ChatMessage::query()
            ->where('conversation_id', $customerMessage->conversation_id)
            ->where('sender_type', ChatMessage::SENDER_BOT)
            ->get()
            ->first(function (ChatMessage $message) use ($customerMessage) {
                return (int) ($message->metadata['reply_to_message_id'] ?? 0) === (int) $customerMessage->id;
            });
    }

    /**
     * @return list<string>
     */
    private function quickReplies(): array
    {
        return ['Track my order', 'Find nails', 'Sizing help', 'Shipping', 'Talk to support'];
    }
}
