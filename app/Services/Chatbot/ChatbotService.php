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

        $offTopicReason = $this->offTopicReason($question);
        if ($offTopicReason !== null) {
            return $this->handoff($conversation, $offTopicReason, $customerMessage);
        }

        $conversation->forceFill(['bot_processing_at' => now()])->save();

        try {
            [$intent, $facts, $metadata] = $this->factsFor($conversation, $question);

            if ($this->shouldHandoffUnknown($intent, $facts)) {
                $reason = (string) ($facts['handoff_reason'] ?? 'Bot could not answer with verified store data.');

                return $this->handoff($conversation, $reason, $customerMessage);
            }

            if ($intent === 'faq' && ! $this->faqFactsOnTopic($question, $facts)) {
                return $this->handoff(
                    $conversation,
                    'FAQ match was off-topic for the customer question.',
                    $customerMessage,
                );
            }

            $summary = trim((string) ($facts['summary'] ?? ''));
            $composed = $this->compose($question, $facts, $conversation, $intent);

            $body = $composed['body'] ?? $summary;
            if ($body === '') {
                if ((bool) config('chatbot.handoff_on_unknown', true)) {
                    return $this->handoff(
                        $conversation,
                        'Bot generated an empty answer.',
                        $customerMessage,
                    );
                }
                $body = (string) config('chatbot.fallback');
                $intent = 'fallback';
            }

            $confidence = $composed['confidence'] ?? ($intent === 'fallback' ? 0.2 : 0.86);
            if ((bool) config('chatbot.handoff_on_unknown', true)
                && ($intent === 'fallback' || (float) $confidence < 0.5)) {
                return $this->handoff(
                    $conversation,
                    'Bot confidence too low to answer reliably.',
                    $customerMessage,
                );
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
                'confidence' => $confidence,
            ]);

            $conversation->forceFill([
                'last_bot_intent' => $intent,
                'bot_processing_at' => null,
            ])->save();

            return $reply;
        } catch (Throwable $exception) {
            Log::warning('Chatbot could not generate a reply.', [
                'conversation_id' => $conversation->id,
                'message_id' => $customerMessage->id,
                'exception' => $exception::class,
            ]);
            $conversation->forceFill(['bot_processing_at' => null])->save();

            if ((bool) config('chatbot.handoff_on_unknown', true)) {
                return $this->handoff(
                    $conversation,
                    'Bot error while generating a reply.',
                    $customerMessage,
                );
            }

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
        if ($this->knowledge->isPreviousSizeQuestion($question)) {
            return $this->previousSizeFacts($conversation);
        }

        $orderNumber = $this->knowledge->extractOrderNumber($question);
        $isOrderQuestion = $orderNumber !== null
            || preg_match('/\b(order|track|tracking|delivery status)\b/i', $question) === 1;

        if ($isOrderQuestion) {
            return $this->orderFacts($conversation, $question, $orderNumber);
        }

        $isProductRequest = $this->knowledge->isProductInquiry($question);

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

            // Named / specific set with no catalog match — do not dump About Us.
            return [
                'unknown',
                [
                    'summary' => '',
                    'handoff_reason' => 'No matching product found for the customer’s request.',
                ],
                ['quick_replies' => []],
            ];
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

        // Stay focused: no loose “any nails/styles” product dump without a clear product ask.
        return [
            'unknown',
            [
                'summary' => '',
                'handoff_reason' => 'No verified FAQ or product facts for this question.',
            ],
            ['quick_replies' => []],
        ];
    }

    /**
     * Clearly unrelated to BluLavelle store support — hand off immediately.
     */
    private function offTopicReason(string $question): ?string
    {
        if (preg_match(
            '/\b(weather|forecast|temperature|joke|riddle|poem|recipe|cook(?:ing)?|bitcoin|crypto|stock market|politics|election|homework|write (?:me )?(?:a |some )?code|programming)\b/i',
            $question,
        ) === 1) {
            return 'Question is outside BluLavelle store support topics.';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function shouldHandoffUnknown(string $intent, array $facts): bool
    {
        if (! (bool) config('chatbot.handoff_on_unknown', true)) {
            return false;
        }

        return in_array($intent, ['unknown', 'fallback'], true)
            || filled($facts['handoff_reason'] ?? null);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function previousSizeFacts(ChatConversation $conversation): array
    {
        $sizingUrl = route('sizing-kit.index');
        $email = $conversation->customer_email;
        $measureAgain = ' To measure again, use the Sizing Kit: '.$sizingUrl;

        if (! $conversation->customer_user_id && ! filled($conversation->guest_email)) {
            return [
                'previous_size',
                ['summary' => 'I can’t look up a size you chose before because this chat has no account or email yet. Send your order number, or the email you used at checkout, and I’ll read the size saved on that order.'.$measureAgain],
                ['quick_replies' => ['Talk to support']],
            ];
        }

        $order = $this->knowledge->recentOrdersForConversation($conversation)->first();
        if ($order === null) {
            $who = filled($email) ? ' for '.$email : '';

            return [
                'previous_size',
                ['summary' => 'I don’t see a past order'.$who.', so I can’t tell which nail size you chose last time. Send your order number and I’ll read the size from it.'.$measureAgain],
                ['quick_replies' => ['Talk to support']],
            ];
        }

        $lines = [];
        foreach ($order->items as $item) {
            $size = $this->knowledge->sizeLabelFromOptions($item->product_options);
            if ($size === null) {
                continue;
            }
            $lines[] = trim((string) $item->product_name).' — '.$size;
        }

        if ($lines === []) {
            return [
                'previous_size',
                [
                    'summary' => 'Your latest order '.$order->order_number.' doesn’t list a nail size on the items, so I can’t tell which size you picked. Send another order number if you ordered more than once.'.$measureAgain,
                    'order_number' => $order->order_number,
                    'sources' => [['title' => 'Your orders', 'source' => 'previous_size']],
                ],
                ['quick_replies' => ['Talk to support']],
            ];
        }

        return [
            'previous_size',
            [
                'summary' => 'On your latest order '.$order->order_number.', the size saved at checkout was: '.implode('; ', $lines).'. That is the size you chose last time.'.$measureAgain,
                'order_number' => $order->order_number,
                'sources' => [['title' => 'Your orders', 'source' => 'previous_size']],
            ],
            ['quick_replies' => ['Talk to support']],
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
        $model = $composed['model'] ?? null;
        $usage = $composed['usage'] ?? [];

        if ($intent === 'product_search') {
            foreach (($facts['products'] ?? []) as $product) {
                if (! str_contains($body, (string) $product['name'])) {
                    return null;
                }
            }
        }

        if ($intent === 'previous_size') {
            $savedOrderNumber = (string) ($facts['order_number'] ?? '');
            $recitesMeasuringGuide = str_contains($body, '14-10-11') || str_contains($body, 'thumb to pinky');
            $dropsSavedOrder = $savedOrderNumber !== '' && ! str_contains($body, $savedOrderNumber);
            if ($recitesMeasuringGuide || $dropsSavedOrder) {
                return [
                    'body' => $summary,
                    'confidence' => 0.9,
                    'model' => $model,
                    'usage' => $usage,
                ];
            }
        }

        $sources = collect($facts['sources'] ?? [])->pluck('source')->all();
        if (in_array('sizing_kit', $sources, true)
            && (! str_contains($body, 'Sizing Kit') || preg_match('/\bX{0,1}S\b|\b\d+\s*mm\b/i', $body) === 1)) {
            return [
                'body' => $summary,
                'confidence' => 0.9,
                'model' => $model,
                'usage' => $usage,
            ];
        }

        if ($intent === 'order_lookup' && filled($facts['order']['tracking_number'] ?? null)) {
            if (! str_contains($body, (string) $facts['order']['tracking_number'])) {
                return [
                    'body' => $summary,
                    'confidence' => 0.9,
                    'model' => $model,
                    'usage' => $usage,
                ];
            }
        }

        // FAQ / general: if AI drifts off the verified summary, serve the summary.
        if ($summary !== '' && in_array($intent, ['faq', 'order_lookup', 'previous_size'], true)
            && ! $this->aiAnswerGrounded($body, $summary, $facts)) {
            return [
                'body' => $summary,
                'confidence' => 0.88,
                'model' => $model,
                'usage' => $usage,
            ];
        }

        if ($this->looksLikeBrandDump($body) && ! $this->isBrandQuestion($question)) {
            return $summary !== '' ? [
                'body' => $summary,
                'confidence' => 0.85,
                'model' => $model,
                'usage' => $usage,
            ] : null;
        }

        return [
            'body' => $body,
            'confidence' => max(0, min(1, (float) ($composed['confidence'] ?? 0.8))),
            'model' => $model,
            'usage' => $usage,
        ];
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function faqFactsOnTopic(string $question, array $facts): bool
    {
        if ($this->isBrandQuestion($question)) {
            return true;
        }

        $sources = collect($facts['sources'] ?? []);
        if ($sources->contains(fn ($source) => str_starts_with((string) ($source['source'] ?? ''), 'page:about'))) {
            return false;
        }

        $summary = mb_strtolower(trim((string) ($facts['summary'] ?? '')));
        if ($summary === '') {
            return false;
        }

        if ($this->looksLikeBrandDump($summary) && ! $this->isBrandQuestion($question)) {
            return false;
        }

        $questionTerms = $this->contentTerms($question);
        if ($questionTerms === []) {
            return true;
        }

        $hit = 0;
        foreach ($questionTerms as $term) {
            if (str_contains($summary, $term)) {
                $hit++;
            }
        }

        // At least one meaningful question term must appear in the verified facts,
        // or the source must be an allowlisted policy/sizing page for that topic.
        if ($hit > 0) {
            return true;
        }

        foreach ($sources as $source) {
            $key = (string) ($source['source'] ?? '');
            $title = mb_strtolower((string) ($source['title'] ?? ''));
            if ($key === 'sizing_kit' && preg_match('/\b(size|sizing|fit)\b/i', $question) === 1) {
                return true;
            }
            if (str_contains($key, 'shipping') && preg_match('/\b(ship|deliver)\b/i', $question) === 1) {
                return true;
            }
            if ((str_contains($key, 'return') || str_contains($key, 'refund') || str_contains($title, 'return'))
                && preg_match('/\b(return|exchange|refund)\b/i', $question) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function aiAnswerGrounded(string $body, string $summary, array $facts): bool
    {
        $bodyLower = mb_strtolower($body);
        $summaryTerms = $this->contentTerms($summary);
        if ($summaryTerms === []) {
            return true;
        }

        $hit = 0;
        foreach ($summaryTerms as $term) {
            if (str_contains($bodyLower, $term)) {
                $hit++;
            }
        }

        $needed = min(3, max(1, (int) ceil(count($summaryTerms) * 0.25)));
        if ($hit >= $needed) {
            return true;
        }

        foreach (($facts['sources'] ?? []) as $source) {
            $url = (string) ($source['url'] ?? '');
            if ($url !== '' && str_contains($body, $url)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeBrandDump(string $text): bool
    {
        return preg_match(
            '/welcome to blu\s*lavelle|press-on nails for every style|discover premium press-on|elevate your look|craft and curate press-on/i',
            $text,
        ) === 1;
    }

    private function isBrandQuestion(string $question): bool
    {
        return preg_match(
            '/\b(about (you|blulavelle|blu lavelle)|who (are|is) (you|blulavelle)|your story|company)\b/i',
            $question,
        ) === 1;
    }

    /**
     * @return list<string>
     */
    private function contentTerms(string $text): array
    {
        $stop = [
            'a', 'an', 'and', 'are', 'can', 'do', 'for', 'from', 'have', 'how', 'i', 'is', 'it',
            'me', 'my', 'of', 'please', 'the', 'to', 'what', 'when', 'where', 'with', 'you', 'your',
            'more', 'details', 'http', 'https', 'www', 'com', 'page',
        ];
        $words = preg_split('/[^a-z0-9]+/i', mb_strtolower($text)) ?: [];
        $terms = [];
        foreach ($words as $word) {
            $word = trim($word);
            if ($word === '' || strlen($word) < 3 || in_array($word, $stop, true) || is_numeric($word)) {
                continue;
            }
            $terms[] = $word;
        }

        return array_values(array_unique($terms));
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
