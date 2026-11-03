<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QueryRewriter
{
    private const CIRCUIT_KEY = 'search-rewrite:circuit-open';

    public function __construct(private readonly SearchText $text) {}

    /**
     * Turn a natural-language search into catalogue keywords. Short queries
     * and phrases the synonym table already understands stay unchanged.
     */
    public function expand(string $query): string
    {
        $query = trim($query);
        if (! $this->eligible($query)) {
            return $query;
        }

        $cacheKey = 'search-rewrite:'.sha1($this->text->normalize($query));
        $cached = Cache::get($cacheKey);
        if (is_string($cached)) {
            return $cached === '' ? $query : $cached;
        }

        if (Cache::has(self::CIRCUIT_KEY)) {
            return $query;
        }

        $keywords = $this->requestKeywords($query);
        if ($keywords === null) {
            $this->pause();
            Cache::put($cacheKey, '', now()->addMinutes(10));

            return $query;
        }

        Cache::put($cacheKey, $keywords, now()->addHours(12));

        return $keywords;
    }

    private function eligible(string $query): bool
    {
        if (app()->runningUnitTests() && ! config('search.query_rewrite_in_tests')) {
            return false;
        }
        if (! config('search.query_rewrite_enabled', true) || ! config('chatbot.ai_enabled', true)) {
            return false;
        }
        if (trim((string) config('chatbot.ai.api_key')) === '' || $query === '') {
            return false;
        }

        $normalized = $this->text->normalize($query);
        if ($normalized === '') {
            return false;
        }

        $padded = ' '.$normalized.' ';
        foreach (array_keys((array) config('search.phrases', [])) as $phrase) {
            $needle = ' '.$this->text->normalize((string) $phrase).' ';
            if ($needle !== '  ' && str_contains($padded, $needle)) {
                return false;
            }
        }

        return count($this->text->tokens($normalized)) >= 6 || str_contains($query, '?');
    }

    private function requestKeywords(string $query): ?string
    {
        $apiKey = trim((string) config('chatbot.ai.api_key'));
        $baseUrl = rtrim((string) config('chatbot.ai.base_url'), '/');
        $model = (string) config('chatbot.ai.model');
        $timeout = max(2, (int) config('search.query_rewrite_timeout', 4));

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout($timeout)
                ->post($baseUrl.'/responses', [
                    'model' => $model,
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => 'Rewrite a press-on nail shop search into catalogue keywords. Keep any color, length, shape, or finish the customer named. Add occasion words such as wedding, bridal, prom, party, or everyday when the sentence implies them. Reply with 2 to 8 English keywords only.',
                        ],
                        ['role' => 'user', 'content' => $query],
                    ],
                    'temperature' => 0,
                    'max_output_tokens' => 40,
                ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $content = (string) ($response->json('output_text') ?? '');
        if ($content === '') {
            $content = (string) data_get($response->json(), 'output.0.content.0.text', '');
        }
        if ($content === '') {
            $content = (string) data_get($response->json(), 'choices.0.message.content', '');
        }

        $tokens = array_slice($this->text->tokens($content), 0, 8);
        if ($tokens === []) {
            return null;
        }

        return implode(' ', $tokens);
    }

    private function pause(): void
    {
        $opened = Cache::add(self::CIRCUIT_KEY, 1, now()->addMinutes(15));
        if ($opened) {
            Log::warning('Search query rewrite paused after a provider error.');
        }
    }
}
