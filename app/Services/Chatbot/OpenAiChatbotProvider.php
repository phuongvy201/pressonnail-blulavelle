<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OpenAiChatbotProvider implements ChatbotAiProvider
{
    public function compose(string $question, array $facts, array $history = []): ?array
    {
        if (! config('chatbot.ai_enabled')) {
            return null;
        }

        $apiKey = trim((string) config('chatbot.ai.api_key'));
        if ($apiKey === '') {
            return null;
        }

        $model = (string) config('chatbot.ai.model');
        $baseUrl = rtrim((string) config('chatbot.ai.base_url'), '/');
        $endpoint = $baseUrl.'/responses';
        $timeout = max(5, (int) config('chatbot.ai.timeout'));

        $system = implode("\n", [
            'You are Blu Assistant for BluLavelle, a press-on nail store.',
            'Answer in English, briefly and helpfully.',
            'Use ONLY the verified facts supplied by the server. Never invent prices, discounts, stock, policies, delivery promises, order status, tracking numbers, or product links.',
            'If the facts do not answer the question, return {"answer":"__NO_ANSWER__","confidence":0}.',
            'Do not claim that you cancelled, refunded, changed, or submitted anything.',
            'Return ONLY valid JSON in this shape: {"answer":"...","confidence":0.0}.',
        ]);

        $user = json_encode([
            'customer_question' => $question,
            'verified_facts' => $facts,
            'recent_conversation' => $history,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout($timeout)
                ->post($endpoint, [
                    'model' => $model,
                    'input' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => (string) $user],
                    ],
                    'temperature' => 0.2,
                    'max_output_tokens' => 500,
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Chatbot AI request failed.', [
                'provider' => config('chatbot.ai.provider'),
                'model' => $model,
                'exception' => $exception::class,
            ]);

            return null;
        }

        if (! $response->ok()) {
            Log::warning('Chatbot AI returned an error.', [
                'provider' => config('chatbot.ai.provider'),
                'model' => $model,
                'status' => $response->status(),
            ]);

            return null;
        }

        $content = (string) ($response->json('output_text') ?? '');
        if ($content === '') {
            $content = (string) data_get($response->json(), 'output.0.content.0.text', '');
        }
        if ($content === '') {
            $content = (string) data_get($response->json(), 'choices.0.message.content', '');
        }

        $decoded = $this->decodeAnswer($content);
        if ($decoded === null) {
            return null;
        }

        $answer = trim((string) ($decoded['answer'] ?? ''));
        if ($answer === '' || $answer === '__NO_ANSWER__') {
            return null;
        }

        $usage = [];
        if (is_array($response->json('usage'))) {
            foreach (['input_tokens', 'output_tokens', 'total_tokens'] as $key) {
                if (is_numeric($response->json('usage.'.$key))) {
                    $usage[$key] = (int) $response->json('usage.'.$key);
                }
            }
        }

        return [
            'body' => Str::limit($answer, 1800, ''),
            'confidence' => max(0, min(1, (float) ($decoded['confidence'] ?? 0.75))),
            'model' => $model,
            'usage' => $usage,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeAnswer(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $content, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Some compatible providers ignore the JSON instruction. Accept plain
        // text only for a rewrite of verified facts; the caller still checks
        // product/order facts before using it.
        return ['answer' => $content, 'confidence' => 0.7];
    }
}
