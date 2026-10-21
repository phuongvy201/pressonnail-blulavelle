<?php

namespace App\Services\Chatbot;

interface ChatbotAiProvider
{
    /**
     * Compose a customer-facing answer from facts that Laravel has already
     * verified. Return null when the provider cannot answer safely.
     *
     * @param  array<string, mixed>  $facts
     * @param  list<array{sender_type: string, body: string}>  $history
     * @return array{body: string, confidence: float, model?: string|null, usage?: array<string, mixed>}|null
     */
    public function compose(string $question, array $facts, array $history = []): ?array;
}
