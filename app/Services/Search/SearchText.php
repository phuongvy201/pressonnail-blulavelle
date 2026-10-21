<?php

namespace App\Services\Search;

use Illuminate\Support\Str;

class SearchText
{
    public function normalize(string $text): string
    {
        $ascii = Str::ascii(mb_strtolower(trim($text)));
        $ascii = preg_replace('/[^a-z0-9\s\-]/u', ' ', $ascii) ?? $ascii;

        return trim(preg_replace('/\s+/', ' ', $ascii) ?? $ascii);
    }

    /**
     * @return list<string>
     */
    public function tokens(string $text): array
    {
        $normalized = $this->normalize($text);
        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(
            preg_split('/[\s\-]+/', $normalized) ?: [],
            fn (string $token) => $token !== '',
        ));
    }

    /**
     * Expand a raw customer query into catalogue concepts. Each concept is a
     * list of alternative terms: a product matching any alternative counts
     * as matching that concept.
     *
     * @return list<list<string>>
     */
    public function concepts(string $query): array
    {
        $normalized = $this->normalize($query);
        if ($normalized === '') {
            return [];
        }

        $remaining = ' '.$normalized.' ';
        $concepts = [];

        $phrases = (array) config('search.phrases', []);
        uksort($phrases, fn (string $a, string $b) => strlen($b) <=> strlen($a));
        foreach ($phrases as $phrase => $alternatives) {
            $needle = ' '.$this->normalize((string) $phrase).' ';
            if (str_contains($remaining, $needle)) {
                $concepts[] = $this->uniqueTerms((array) $alternatives);
                $remaining = str_replace($needle, ' ', $remaining);
            }
        }

        $stopwords = array_flip((array) config('search.stopwords', []));
        $synonyms = (array) config('search.synonyms', []);
        foreach ($this->tokens($remaining) as $token) {
            if (isset($stopwords[$token])) {
                continue;
            }
            $alternatives = [$token];
            foreach ((array) ($synonyms[$token] ?? []) as $synonym) {
                foreach ($this->tokens((string) $synonym) as $synonymToken) {
                    $alternatives[] = $synonymToken;
                }
            }
            $concepts[] = $this->uniqueTerms($alternatives);
        }

        return $concepts;
    }

    /**
     * Flat unique list of every alternative term across all concepts.
     *
     * @return list<string>
     */
    public function expandedTerms(string $query): array
    {
        $terms = [];
        foreach ($this->concepts($query) as $alternatives) {
            foreach ($alternatives as $term) {
                $terms[] = $term;
            }
        }

        return $this->uniqueTerms($terms);
    }

    /**
     * Score one concept against a normalised haystack. Exact substring wins,
     * otherwise a close token (small edit distance) earns partial credit so
     * typos like "almnd" still find "almond".
     *
     * @param  list<string>  $alternatives
     * @param  list<string>  $haystackTokens
     */
    public function conceptScore(array $alternatives, string $haystack, array $haystackTokens): float
    {
        $best = 0.0;
        foreach ($alternatives as $term) {
            if ($term === '') {
                continue;
            }
            if (str_contains($haystack, $term)) {
                return 1.0;
            }
            foreach ($haystackTokens as $candidate) {
                if (abs(strlen($candidate) - strlen($term)) > 2) {
                    continue;
                }
                $distance = levenshtein($term, $candidate);
                $allowed = strlen($term) >= 6 ? 2 : 1;
                if ($distance <= $allowed) {
                    $best = max($best, 1.0 - ($distance / max(strlen($term), 1)) - 0.15);
                }
            }
        }

        return max(0.0, $best);
    }

    /**
     * @param  list<string>  $terms
     * @return list<string>
     */
    private function uniqueTerms(array $terms): array
    {
        $unique = [];
        foreach ($terms as $term) {
            $normalized = $this->normalize((string) $term);
            if ($normalized !== '') {
                $unique[$normalized] = $normalized;
            }
        }

        return array_values($unique);
    }
}
