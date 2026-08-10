<?php

namespace App\Support;

class VirtualNailSettings
{
    public const KEY_ENABLED = 'virtual_nail.enabled';

    public const KEY_PREFER_BROWSER_POOL = 'virtual_nail.prefer_browser_pool';

    public const KEY_SHAPES = 'virtual_nail.shapes';

    public const KEY_LENGTHS = 'virtual_nail.lengths';

    public const KEY_DEFAULT_SHAPE = 'virtual_nail.default_shape';

    public const KEY_DEFAULT_LENGTH = 'virtual_nail.default_length';

    public const KEY_PROMPT_TEMPLATE = 'virtual_nail.prompt_template';

    public const KEY_NOTICE_TITLE = 'virtual_nail.shape_length_notice_title';

    public const KEY_NOTICE_TEXT = 'virtual_nail.shape_length_notice_text';

    public const KEY_CAPTURE_TIPS = 'virtual_nail.capture_tips';

    public const KEY_BROWSE_RATE_LIMIT = 'virtual_nail.browse_rate_limit';

    public const KEY_RATE_LIMIT = 'virtual_nail.rate_limit';

    public static function defaultPromptTemplate(): string
    {
        return implode("\n", [
            'Virtual try-on: place the press-on nail design from the reference image onto every visible natural fingernail in the hand photo.',
            'CRITICAL ORIENTATION: each press-on must follow the real nail bed.',
            'The free tip / free edge of every nail must point toward the fingertip (away from the wrist and palm).',
            'The cuticle/base of each nail sits near the knuckle side of the nail bed — never reverse or flip nails.',
            'Do NOT attach nails upside-down, sideways, or with tips pointing toward the palm/wrist.',
            'Shape: {shape}. Length: {length}. Apply consistently on all visible fingers.',
            'Match the reference design colors, pattern, and finish exactly; keep perspective warped to each finger.',
            'Preserve the original hand pose, skin, lighting, and background — only change the nail surfaces.',
            'Photorealistic beauty photo, seamless cuticle blend, no floating or misaligned nails.',
            'Nail set name: {product_name}.',
        ]);
    }

    /**
     * @return list<string>
     */
    public static function defaultCaptureTips(): array
    {
        return [
            'Use a plain, light background (white or neutral works best)',
            'Spread fingers naturally and keep your palm flat',
            'Align your hand inside the on-camera guide',
            'Avoid shadows, blur, and busy backgrounds',
        ];
    }

    public static function enabled(): bool
    {
        $fallback = filter_var(config('virtual_nail.enabled'), FILTER_VALIDATE_BOOLEAN);

        return self::bool(self::KEY_ENABLED, $fallback);
    }

    public static function preferBrowserPool(): bool
    {
        $fallback = filter_var(config('virtual_nail.prefer_browser_pool', true), FILTER_VALIDATE_BOOLEAN);

        return self::bool(self::KEY_PREFER_BROWSER_POOL, $fallback);
    }

    /**
     * @return list<string>
     */
    public static function shapes(): array
    {
        return self::stringList(
            self::KEY_SHAPES,
            array_values(array_filter((array) config('virtual_nail.shapes', [])))
        );
    }

    /**
     * @return list<string>
     */
    public static function lengths(): array
    {
        return self::stringList(
            self::KEY_LENGTHS,
            array_values(array_filter((array) config('virtual_nail.lengths', [])))
        );
    }

    public static function defaultShape(): string
    {
        $shapes = self::shapes();
        $value = trim((string) Settings::get(self::KEY_DEFAULT_SHAPE, config('virtual_nail.default_shape', 'Almond')));

        if ($value !== '' && in_array($value, $shapes, true)) {
            return $value;
        }

        return $shapes[0] ?? 'Almond';
    }

    public static function defaultLength(): string
    {
        $lengths = self::lengths();
        $value = trim((string) Settings::get(self::KEY_DEFAULT_LENGTH, config('virtual_nail.default_length', 'Medium')));

        if ($value !== '' && in_array($value, $lengths, true)) {
            return $value;
        }

        return $lengths[0] ?? 'Medium';
    }

    public static function promptTemplate(): string
    {
        $value = (string) Settings::get(self::KEY_PROMPT_TEMPLATE, '');

        return trim($value) !== '' ? $value : self::defaultPromptTemplate();
    }

    public static function noticeTitle(): string
    {
        $value = trim((string) Settings::get(self::KEY_NOTICE_TITLE, ''));

        return $value !== '' ? $value : 'For the best AI preview';
    }

    public static function noticeText(): string
    {
        $value = trim((string) Settings::get(self::KEY_NOTICE_TEXT, ''));

        return $value !== ''
            ? $value
            : 'Choose the shape and length that match the nail design you selected. A mismatch (e.g. Stiletto design with Square / Short) can look off or less realistic.';
    }

    /**
     * @return list<string>
     */
    public static function captureTips(): array
    {
        return self::stringList(self::KEY_CAPTURE_TIPS, self::defaultCaptureTips());
    }

    public static function browseRateLimit(): int
    {
        return max(30, (int) Settings::get(
            self::KEY_BROWSE_RATE_LIMIT,
            (int) config('virtual_nail.browse_rate_limit', 60)
        ));
    }

    public static function tryRateLimit(): int
    {
        return max(1, (int) Settings::get(
            self::KEY_RATE_LIMIT,
            (int) config('virtual_nail.rate_limit', 10)
        ));
    }

    /**
     * @return array{
     *   enabled: bool,
     *   prefer_browser_pool: bool,
     *   shapes: list<string>,
     *   lengths: list<string>,
     *   default_shape: string,
     *   default_length: string,
     *   prompt_template: string,
     *   notice_title: string,
     *   notice_text: string,
     *   capture_tips: list<string>,
     *   browse_rate_limit: int,
     *   rate_limit: int
     * }
     */
    public static function allForAdmin(): array
    {
        return [
            'enabled' => self::enabled(),
            'prefer_browser_pool' => self::preferBrowserPool(),
            'shapes' => self::shapes(),
            'lengths' => self::lengths(),
            'default_shape' => self::defaultShape(),
            'default_length' => self::defaultLength(),
            'prompt_template' => self::promptTemplate(),
            'notice_title' => self::noticeTitle(),
            'notice_text' => self::noticeText(),
            'capture_tips' => self::captureTips(),
            'browse_rate_limit' => self::browseRateLimit(),
            'rate_limit' => self::tryRateLimit(),
        ];
    }

    public static function buildPrompt(string $shape, string $length, string $productName): string
    {
        $template = self::promptTemplate();
        $replaced = str_replace(
            ['{shape}', '{length}', '{product_name}', '{productName}'],
            [$shape, $length, $productName, $productName],
            $template
        );

        $lines = preg_split("/\r\n|\n|\r/", $replaced) ?: [];
        $parts = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            // Drop trailing "Nail set name: ." when product name empty
            if (preg_match('/^Nail set name:\s*\.?$/i', $line)) {
                continue;
            }
            $parts[] = $line;
        }

        return implode(' ', $parts);
    }

    /**
     * @param  list<string>  $fallback
     * @return list<string>
     */
    private static function stringList(string $key, array $fallback): array
    {
        $raw = Settings::get($key, null);
        if ($raw === null || $raw === '') {
            return array_values(array_filter(array_map('trim', $fallback)));
        }

        if (is_array($raw)) {
            return array_values(array_unique(array_filter(array_map(
                static fn ($v) => trim((string) $v),
                $raw
            ))));
        }

        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            return array_values(array_unique(array_filter(array_map(
                static fn ($v) => trim((string) $v),
                $decoded
            ))));
        }

        $lines = preg_split("/\r\n|\n|\r|,/", (string) $raw) ?: [];

        return array_values(array_unique(array_filter(array_map('trim', $lines))));
    }

    private static function bool(string $key, bool $fallback): bool
    {
        $raw = Settings::get($key, null);
        if ($raw === null || $raw === '') {
            return $fallback;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }
}
