<?php

namespace App\Support;

use App\Models\Collection;

class CollectionKeywordRules
{
    public const DEFAULT_MATCH_IN = ['name', 'description', 'meta_keywords'];

    /**
     * @return list<string>
     */
    public static function keywordsFromCollection(Collection $collection): array
    {
        return self::keywordsFromRules(is_array($collection->auto_rules) ? $collection->auto_rules : []);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<string>
     */
    public static function keywordsFromRules(array $rules): array
    {
        $raw = $rules['keywords'] ?? [];

        if (is_string($raw)) {
            return self::parseKeywordInput($raw);
        }

        if (! is_array($raw)) {
            return [];
        }

        $keywords = [];
        foreach ($raw as $item) {
            if (! is_string($item)) {
                continue;
            }
            $item = trim($item);
            if ($item !== '') {
                $keywords[] = $item;
            }
        }

        return self::normalizeKeywords($keywords);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    public static function isEnabled(array $rules): bool
    {
        // Có keywords trong rules → auto-assign đang bật (flag false chỉ do lỗi form cũ).
        return self::keywordsFromRules($rules) !== [];
    }

    /**
     * Checkbox "keyword_auto_assign" (không dùng hidden value=0 — tránh boolean sai trên PHP).
     */
    public static function isCheckboxChecked(mixed $value): bool
    {
        if (is_array($value)) {
            return in_array('1', $value, true) || in_array(1, $value, true) || in_array(true, $value, true);
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function isEnabledForCollection(Collection $collection): bool
    {
        if ($collection->status !== 'active') {
            return false;
        }

        return self::isEnabled(is_array($collection->auto_rules) ? $collection->auto_rules : []);
    }

    /**
     * @return list<string>
     */
    public static function parseKeywordInput(string $input): array
    {
        if (trim($input) === '') {
            return [];
        }

        $parts = preg_split('/[\r\n,;]+/', $input) ?: [];

        return self::normalizeKeywords($parts);
    }

    /**
     * @param  list<string>  $parts
     * @return list<string>
     */
    public static function normalizeKeywords(array $parts): array
    {
        $out = [];
        $seen = [];

        foreach ($parts as $part) {
            if (! is_string($part)) {
                continue;
            }
            $part = trim($part);
            if ($part === '' || mb_strlen($part) < 2) {
                continue;
            }
            $key = mb_strtolower($part);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $part;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $existing
     * @return array<string, mixed>|null
     */
    public static function buildFromRequest(bool $enabled, string $keywordInput, ?array $existing = null): ?array
    {
        $keywords = self::parseKeywordInput($keywordInput);
        $rules = is_array($existing) ? $existing : [];

        if ($keywords === [] && trim($keywordInput) === '' && ! $enabled) {
            if ($rules === []) {
                return null;
            }

            $rules['auto_assign_enabled'] = false;

            return $rules;
        }

        $rules['keywords'] = $keywords;
        // Có nhập keyword → luôn bật auto-assign trừ khi user bỏ tick checkbox.
        $rules['auto_assign_enabled'] = $keywords !== [] && ($enabled || trim($keywordInput) !== '');
        $rules['match_in'] = $rules['match_in'] ?? self::DEFAULT_MATCH_IN;
        $rules['match_mode'] = $rules['match_mode'] ?? 'contains_any';
        $rules['case_insensitive'] = $rules['case_insensitive'] ?? true;

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<string>
     */
    public static function matchInFromRules(array $rules): array
    {
        $fields = $rules['match_in'] ?? self::DEFAULT_MATCH_IN;

        if (! is_array($fields)) {
            return self::DEFAULT_MATCH_IN;
        }

        $allowed = array_flip(self::DEFAULT_MATCH_IN);
        $filtered = [];
        foreach ($fields as $field) {
            if (is_string($field) && isset($allowed[$field])) {
                $filtered[] = $field;
            }
        }

        return $filtered !== [] ? $filtered : self::DEFAULT_MATCH_IN;
    }

    public static function keywordsDisplayString(Collection $collection): string
    {
        return implode("\n", self::keywordsFromCollection($collection));
    }
}
