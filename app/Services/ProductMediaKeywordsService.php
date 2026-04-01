<?php

namespace App\Services;

/**
 * Gắn chuỗi keywords (SEO/alt) cho từng phần tử trong mảng media của sản phẩm:
 * 1–2 keyword lấy từ meta_keywords (lần lượt theo thứ tự media) + bộ keyword cố định.
 */
class ProductMediaKeywordsService
{
    /** @var list<string> */
    public const FIXED_KEYWORDS = [
        'press on nails',
        'custom press on nails',
        'fake nails',
        'Nails Art',
        'stained glass nails',
        'gold press on nails',
        '3d press on nails',
        'luxury press on nails',
        'handmade press on nails',
    ];

    /**
     * @return list<string>
     */
    public static function parseMetaKeywords(?string $metaKeywords): array
    {
        if ($metaKeywords === null || trim($metaKeywords) === '') {
            return [];
        }
        $parts = preg_split('/\s*[,;]\s*/', $metaKeywords, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $t = trim((string) $p);
            if ($t === '' || in_array($t, $out, true)) {
                continue;
            }
            $out[] = $t;
        }

        return $out;
    }

    /**
     * Chuỗi keywords cho ảnh tại vị trí $mediaIndex (0-based), cùng quy tắc với attachKeywordsToMedia:
     * 1–2 keyword từ meta_keywords (theo cursor lần lượt) + bộ keyword cố định.
     */
    public static function keywordsStringForMediaIndex(int $mediaIndex, ?string $metaKeywords): string
    {
        $metaList = self::parseMetaKeywords($metaKeywords);
        $nMeta = count($metaList);
        $cursor = 0;

        for ($i = 0; $i < $mediaIndex; $i++) {
            if ($nMeta > 0) {
                $cursor += min(2, $nMeta);
            }
        }

        $fromMeta = [];
        if ($nMeta > 0) {
            $num = min(2, $nMeta);
            for ($t = 0; $t < $num; $t++) {
                $fromMeta[] = $metaList[$cursor % $nMeta];
                $cursor++;
            }
        }

        $merged = [];
        foreach ($fromMeta as $k) {
            if (! in_array($k, $merged, true)) {
                $merged[] = $k;
            }
        }
        foreach (self::FIXED_KEYWORDS as $k) {
            if (! in_array($k, $merged, true)) {
                $merged[] = $k;
            }
        }

        return implode(', ', $merged);
    }

    /**
     * @param  list<mixed>|null  $media
     * @return list<mixed>
     */
    public static function attachKeywordsToMedia(?array $media, ?string $metaKeywords): array
    {
        if ($media === null || $media === []) {
            return [];
        }

        $metaList = self::parseMetaKeywords($metaKeywords);
        $nMeta = count($metaList);
        $cursor = 0;

        $out = [];
        foreach ($media as $item) {
            if (! is_string($item) && ! is_array($item)) {
                $out[] = $item;

                continue;
            }

            $row = self::normalizeMediaRow($item);
            $fromMeta = [];
            if ($nMeta > 0) {
                $num = min(2, $nMeta);
                for ($t = 0; $t < $num; $t++) {
                    $fromMeta[] = $metaList[$cursor % $nMeta];
                    $cursor++;
                }
            }

            $merged = [];
            foreach ($fromMeta as $k) {
                if (! in_array($k, $merged, true)) {
                    $merged[] = $k;
                }
            }
            foreach (self::FIXED_KEYWORDS as $k) {
                if (! in_array($k, $merged, true)) {
                    $merged[] = $k;
                }
            }

            $row['keywords'] = implode(', ', $merged);
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeMediaRow(string|array $item): array
    {
        if (is_string($item)) {
            return ['type' => 'image', 'url' => $item];
        }

        return $item;
    }
}
