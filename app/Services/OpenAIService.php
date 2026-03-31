<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenAIService
{
    public function extractKeywords($title)
    {
        $apiKey = (string) env('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            Log::warning('OPENAI_API_KEY is missing; cannot extract keywords.');
            return [];
        }

        $prompt = "Extract 8-10 SEO keywords from this product title.
- 2-4 words per keyword
- No duplicates
- Comma-separated
- No explanation

Title: {$title}";

        $model = (string) env('OPENAI_MODEL', 'gpt-5.4-nano');
        $baseUrl = rtrim((string) env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
        $endpoint = $baseUrl . '/responses';
        $timeout = (int) env('OPENAI_TIMEOUT', 60);

        $payload = [
            'model' => $model,
            'input' => $prompt,
            'temperature' => 0.3,
            'max_output_tokens' => 120,
        ];

        Log::info('OpenAI request starting.', [
            'endpoint' => $endpoint,
            'model' => $model,
            'timeout_sec' => $timeout,
            'title_len' => is_string($title) ? strlen($title) : null,
        ]);

        try {
            $startedAt = microtime(true);
            // Prefer the Responses API (current OpenAI default).
            $resp = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout($timeout)
                ->post($endpoint, $payload);
            $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
        } catch (\Throwable $e) {
            Log::error('OpenAI request failed (exception).', [
                'endpoint' => $endpoint,
                'model' => $model,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            return [];
        }

        Log::info('OpenAI request finished.', [
            'endpoint' => $endpoint,
            'model' => $model,
            'status' => $resp->status(),
            'ok' => $resp->ok(),
            'elapsed_ms' => $elapsedMs ?? null,
        ]);

        if (!$resp->ok()) {
            Log::error('OpenAI HTTP error response.', [
                'endpoint' => $endpoint,
                'model' => $model,
                'status' => $resp->status(),
                'body_preview' => Str::limit($resp->body(), 2000),
            ]);
            return [];
        }

        $data = $resp->json();

        if (!is_array($data)) {
            Log::warning('OpenAI returned non-JSON response.', [
                'endpoint' => $endpoint,
                'model' => $model,
                'status' => $resp->status(),
                'body_preview' => Str::limit($resp->body(), 2000),
            ]);
            return [];
        }

        if (isset($data['error'])) {
            Log::error('OpenAI error.', [
                'endpoint' => $endpoint,
                'status' => $resp->status(),
                'error' => $data['error'],
                'model' => $model,
            ]);
            return [];
        }

        // Responses API: prefer output_text when available, otherwise fall back to common shapes.
        $content = (string) ($data['output_text'] ?? '');
        if (trim($content) === '') {
            $content = (string) data_get($data, 'output.0.content.0.text', '');
        }
        if (trim($content) === '') {
            $content = (string) data_get($data, 'choices.0.message.content', '');
        }

        if (!is_string($content) || trim($content) === '') {
            Log::warning('OpenAI returned empty content.', ['data' => $data, 'model' => $model]);
            return [];
        }

        Log::info('OpenAI content received.', [
            'model' => $model,
            'content_preview' => Str::limit(trim($content), 500),
        ]);

        // Normalize: split by commas/newlines, trim, drop empties/duplicates.
        $rawParts = preg_split('/[,\n]+/', $content) ?: [];
        $keywords = [];
        foreach ($rawParts as $part) {
            $k = trim((string) $part);
            $k = preg_replace('/^\d+[\)\.\-]\s*/', '', $k) ?? $k; // remove "1. " / "1) "
            $k = trim($k);
            if ($k === '') {
                continue;
            }
            $keywords[] = $k;
        }

        $keywords = array_values(array_unique($keywords));
        Log::info('OpenAI keywords extracted.', [
            'model' => $model,
            'count' => count($keywords),
            'keywords_preview' => array_slice($keywords, 0, 10),
        ]);
        return $keywords;
    }
}
