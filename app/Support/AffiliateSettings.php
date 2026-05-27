<?php

namespace App\Support;

class AffiliateSettings
{
    /**
     * Commission % per tier, merged with config defaults.
     *
     * @return array{basic: float, silver: float, gold: float, diamond: float}
     */
    public static function tierRates(): array
    {
        $base = [
            'basic' => (float) data_get(config('affiliate.tier_rates'), 'basic', 7.0),
            'silver' => (float) data_get(config('affiliate.tier_rates'), 'silver', 10.0),
            'gold' => (float) data_get(config('affiliate.tier_rates'), 'gold', 12.0),
            'diamond' => (float) data_get(config('affiliate.tier_rates'), 'diamond', 15.0),
        ];

        $raw = Settings::get('affiliate.tier_rates');
        if (! is_string($raw) || $raw === '') {
            return self::normalizeRates($base);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return self::normalizeRates($base);
        }

        foreach (['basic', 'silver', 'gold', 'diamond'] as $k) {
            if (isset($decoded[$k]) && is_numeric($decoded[$k])) {
                $base[$k] = (float) $decoded[$k];
            }
        }

        // Legacy admin JSON keys
        if (isset($decoded['medium']) && is_numeric($decoded['medium'])) {
            $base['silver'] = (float) $decoded['medium'];
        }
        if (isset($decoded['high']) && is_numeric($decoded['high'])) {
            $base['gold'] = (float) $decoded['high'];
        }

        return self::normalizeRates($base);
    }

    /**
     * @param  array<string, mixed>  $rates
     * @return array{basic: float, silver: float, gold: float, diamond: float}
     */
    private static function normalizeRates(array $rates): array
    {
        $out = [];
        foreach (['basic', 'silver', 'gold', 'diamond'] as $k) {
            $v = isset($rates[$k]) && is_numeric($rates[$k]) ? (float) $rates[$k] : 0.0;
            $out[$k] = max(0.0, min(100.0, $v));
        }

        return $out;
    }

    /** Rolling window for tier evaluation (default 30 days ≈ monthly refresh). */
    public static function tierEvaluationDays(): int
    {
        $v = Settings::get('affiliate.tier_evaluation_days');
        if ($v === null || $v === '') {
            return max(1, (int) config('affiliate.tier_evaluation_days', 30));
        }

        return max(1, min(365, (int) $v));
    }

    public static function tierInactivityDays(): int
    {
        $v = Settings::get('affiliate.tier_inactivity_days');
        if ($v === null || $v === '') {
            return max(1, (int) config('affiliate.tier_inactivity_days', 60));
        }

        return max(1, min(3650, (int) $v));
    }

    /**
     * Minimum attributed paid orders in evaluation window to reach tier.
     *
     * @return array{silver: int, gold: int, diamond: int}
     */
    public static function tierOrderThresholds(): array
    {
        $defaults = [
            'silver' => (int) data_get(config('affiliate.tier_order_thresholds'), 'silver', 20),
            'gold' => (int) data_get(config('affiliate.tier_order_thresholds'), 'gold', 50),
            'diamond' => (int) data_get(config('affiliate.tier_order_thresholds'), 'diamond', 100),
        ];

        $raw = Settings::get('affiliate.tier_order_thresholds');
        if (! is_string($raw) || $raw === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        foreach (['silver', 'gold', 'diamond'] as $k) {
            if (isset($decoded[$k]) && is_numeric($decoded[$k])) {
                $defaults[$k] = max(1, (int) $decoded[$k]);
            }
        }

        if (isset($decoded['medium']) && is_numeric($decoded['medium'])) {
            $defaults['silver'] = max(1, (int) $decoded['medium']);
        }

        return $defaults;
    }

    /** @deprecated Use tierEvaluationDays() */
    public static function tierRollingDays(): int
    {
        return self::tierEvaluationDays();
    }

    /** @deprecated Use tierOrderThresholds() */
    public static function tierThresholdMediumUsd(): float
    {
        return (float) self::tierOrderThresholds()['silver'];
    }

    /** @deprecated Use tierOrderThresholds() */
    public static function tierThresholdHighUsd(): float
    {
        return (float) self::tierOrderThresholds()['gold'];
    }

    /**
     * When true, commission only on the customer's first paid order on the store
     * (matched by user_id and/or checkout email; guests without email are not eligible).
     */
    public static function commissionNewCustomersOnly(): bool
    {
        $v = Settings::get('affiliate.commission_new_customers_only');
        if ($v === null || $v === '') {
            return (bool) config('affiliate.commission_new_customers_only', true);
        }

        return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }

    /** Days for ref cookie and new-customer commission follow-up orders (default 14). */
    public static function attributionWindowDays(): int
    {
        $minutes = (int) config('affiliate.cookie_ttl_minutes', 14 * 24 * 60);

        return max(1, (int) round($minutes / 1440));
    }
}
