<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    /**
     * @return list<float>|null
     */
    public function embed(string $text): ?array
    {
        $text = trim($text);
        if ($text === '' || ! $this->enabled()) {
            return null;
        }

        $cacheKey = 'search-embedding:'.sha1($this->model().'|'.$text);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached === [] ? null : $cached;
        }

        $apiKey = trim((string) config('search.embedding_api_key', ''));
        if ($apiKey === '') {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(max(3, (int) config('search.embedding_timeout', 10)))
                ->post(rtrim((string) config('search.embedding_base_url'), '/').'/embeddings', [
                    'model' => $this->model(),
                    'input' => $text,
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Search embedding request failed.', ['exception' => $exception::class]);
            Cache::put($cacheKey, [], now()->addMinutes(10));

            return null;
        }

        $vector = $response->json('data.0.embedding');
        if (! $response->successful() || ! is_array($vector) || $vector === []) {
            Log::warning('Search embedding provider returned no vector.', [
                'status' => $response->status(),
            ]);
            Cache::put($cacheKey, [], now()->addMinutes(10));

            return null;
        }

        $vector = array_map(fn ($value) => (float) $value, $vector);
        Cache::put($cacheKey, $vector, now()->addDay());

        return $vector;
    }

    public function enabled(): bool
    {
        return (bool) config('search.embedding_enabled', true);
    }

    public function model(): string
    {
        return (string) config('search.embedding_model', 'text-embedding-3-small');
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    public function cosine(array $a, array $b): float
    {
        $length = min(count($a), count($b));
        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
