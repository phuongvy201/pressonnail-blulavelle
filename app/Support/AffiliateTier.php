<?php

namespace App\Support;

class AffiliateTier
{
    public const BASIC = 'basic';

    public const SILVER = 'silver';

    public const GOLD = 'gold';

    public const DIAMOND = 'diamond';

    /** @var list<string> */
    public const ALL = [self::BASIC, self::SILVER, self::GOLD, self::DIAMOND];

    /** @var array<string, string> */
    private const LEGACY_MAP = [
        'medium' => self::SILVER,
        'high' => self::GOLD,
    ];

    public static function normalize(?string $tier): string
    {
        $tier = strtolower(trim((string) $tier));

        if (isset(self::LEGACY_MAP[$tier])) {
            return self::LEGACY_MAP[$tier];
        }

        return in_array($tier, self::ALL, true) ? $tier : self::BASIC;
    }

    public static function rank(?string $tier): int
    {
        return match (self::normalize($tier)) {
            self::DIAMOND => 4,
            self::GOLD => 3,
            self::SILVER => 2,
            default => 1,
        };
    }

    public static function label(?string $tier): string
    {
        return match (self::normalize($tier)) {
            self::DIAMOND => 'Diamond',
            self::GOLD => 'Gold',
            self::SILVER => 'Silver',
            default => 'Basic',
        };
    }

    public static function meetsMinimum(string $affiliateTier, ?string $minimumTier): bool
    {
        $min = $minimumTier ? self::normalize($minimumTier) : self::BASIC;

        return self::rank($affiliateTier) >= self::rank($min);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::BASIC => 'Basic',
            self::SILVER => 'Silver',
            self::GOLD => 'Gold',
            self::DIAMOND => 'Diamond',
        ];
    }

    /**
     * Tier earned from attributed paid-order count in the evaluation window.
     *
     * @param  array<string, int>  $thresholds  silver, gold, diamond => min orders
     */
    public static function tierForOrderCount(int $orderCount, array $thresholds): string
    {
        $silver = max(1, (int) ($thresholds['silver'] ?? 20));
        $gold = max($silver + 1, (int) ($thresholds['gold'] ?? 50));
        $diamond = max($gold + 1, (int) ($thresholds['diamond'] ?? 100));

        if ($orderCount >= $diamond) {
            return self::DIAMOND;
        }
        if ($orderCount >= $gold) {
            return self::GOLD;
        }
        if ($orderCount >= $silver) {
            return self::SILVER;
        }

        return self::BASIC;
    }

    public static function nextTier(?string $tier): ?string
    {
        return match (self::normalize($tier)) {
            self::BASIC => self::SILVER,
            self::SILVER => self::GOLD,
            self::GOLD => self::DIAMOND,
            default => null,
        };
    }

    public static function downgradeOne(?string $tier): string
    {
        return match (self::normalize($tier)) {
            self::DIAMOND => self::GOLD,
            self::GOLD => self::SILVER,
            self::SILVER => self::BASIC,
            default => self::BASIC,
        };
    }

    /**
     * @param  array<string, int>  $thresholds
     */
    public static function orderThresholdForTier(?string $tier, array $thresholds): ?int
    {
        return match (self::normalize($tier)) {
            self::BASIC => (int) ($thresholds['silver'] ?? 20),
            self::SILVER => (int) ($thresholds['gold'] ?? 50),
            self::GOLD => (int) ($thresholds['diamond'] ?? 100),
            default => null,
        };
    }
}
