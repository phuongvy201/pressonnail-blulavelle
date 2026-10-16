<?php

namespace App\Support;

class ContentSafety
{
    /**
     * Basic English objectionable-word screen for chat and reviews.
     * Keeps the list intentionally short and public-facing safe.
     *
     * @var list<string>
     */
    private const BLOCKED_TERMS = [
        'kill yourself',
        'kys',
        'nigger',
        'nigga',
        'faggot',
        'child porn',
        'childporn',
        'rape you',
        'go die',
    ];

    public static function containsObjectionable(string $text): bool
    {
        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', trim($text)) ?? '');
        if ($normalized === '') {
            return false;
        }

        foreach (self::BLOCKED_TERMS as $term) {
            if (str_contains($normalized, $term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function reportReasons(): array
    {
        return [
            'spam',
            'harassment',
            'hate',
            'sexual',
            'scam',
            'other',
        ];
    }
}
