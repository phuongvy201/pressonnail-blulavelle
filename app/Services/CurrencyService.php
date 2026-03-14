<?php

namespace App\Services;

use App\Models\GmcConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    protected static ?string $currentCurrency = null;
    protected static ?float $currentCurrencyRate = null;
    protected static ?string $currentDomain = null;

    /**
     * Lấy domain hiện tại từ request
     */
    public static function getCurrentDomain(): ?string
    {
        if (self::$currentDomain !== null) {
            return self::$currentDomain;
        }

        if (!request()) {
            return null;
        }

        $host = request()->getHost();
        // Loại bỏ port nếu có
        $host = explode(':', $host)[0];
        // Loại bỏ www. nếu có
        $host = preg_replace('/^www\./', '', $host);

        self::$currentDomain = $host;
        return $host;
    }

    /**
     * Lấy currency cho domain hiện tại
     * Nếu domain có nhiều GMC config, lấy config active đầu tiên
     */
    public static function getCurrencyForDomain(?string $domain = null): string
    {
        $domain = $domain ?? self::getCurrentDomain();

        if (!$domain) {
            return 'USD'; // Default currency
        }

        // Cache key để tránh query database nhiều lần
        $cacheKey = "currency_for_domain_{$domain}";

        return Cache::remember($cacheKey, 3600, function () use ($domain) {
            return 'USD';
        });
    }

    /**
     * Lấy currency rate cho domain hiện tại
     */
    public static function getCurrencyRateForDomain(?string $domain = null): ?float
    {
        $domain = $domain ?? self::getCurrentDomain();

        if (!$domain) {
            return 1.0; // USD to USD = 1.0
        }

        // Cache key
        $cacheKey = "currency_rate_for_domain_{$domain}";

        return Cache::remember($cacheKey, 3600, function () use ($domain) {
            return 1.0; // USD
        });
    }

    /**
     * Lấy currency hiện tại (cached trong request)
     */
    public static function getCurrentCurrency(): string
    {
        if (self::$currentCurrency === null) {
            self::$currentCurrency = self::getCurrencyForDomain();
        }
        return self::$currentCurrency;
    }

    /**
     * Lấy currency rate hiện tại (cached trong request)
     */
    public static function getCurrentCurrencyRate(): ?float
    {
        if (self::$currentCurrencyRate === null) {
            self::$currentCurrencyRate = self::getCurrencyRateForDomain();
        }
        return self::$currentCurrencyRate;
    }

    /**
     * Convert giá từ USD sang currency hiện tại
     */
    public static function convertFromUSD(float $usdAmount, ?string $domain = null): float
    {
        $currency = self::getCurrencyForDomain($domain);

        // Nếu là USD, không cần convert
        if ($currency === 'USD') {
            return $usdAmount;
        }

        $rate = self::getCurrencyRateForDomain($domain);

        // Nếu không có rate, trả về giá gốc (USD)
        if (!$rate) {
            Log::warning('Currency conversion rate not found, returning USD amount', [
                'domain' => $domain ?? self::getCurrentDomain(),
                'currency' => $currency,
                'amount' => $usdAmount
            ]);
            return $usdAmount;
        }

        return $usdAmount * $rate;
    }

    /**
     * Convert giá từ USD sang currency với rate cụ thể
     */
    public static function convertFromUSDWithRate(float $usdAmount, string $currency, float $rate): float
    {
        // Nếu là USD, không cần convert
        if ($currency === 'USD') {
            return $usdAmount;
        }

        return $usdAmount * $rate;
    }

    /**
     * Format giá theo currency hiện tại
     */
    public static function formatPrice(float $amount, ?string $currency = null, ?string $domain = null): string
    {
        $currency = $currency ?? self::getCurrencyForDomain($domain);
        $amount = number_format($amount, 2, '.', '');

        // Currency symbols và formatting
        $formatters = [
            'USD' => fn($a) => '$' . number_format($a, 2),
            'GBP' => fn($a) => '£' . number_format($a, 2),
            'EUR' => fn($a) => '€' . number_format($a, 2),
            'VND' => fn($a) => number_format($a, 0) . ' ₫',
            'CAD' => fn($a) => 'C$' . number_format($a, 2),
            'AUD' => fn($a) => 'A$' . number_format($a, 2),
            'JPY' => fn($a) => '¥' . number_format($a, 0),
            'CNY' => fn($a) => '¥' . number_format($a, 2),
            'MXN' => fn($a) => 'MX$' . number_format($a, 2),
            'HKD' => fn($a) => 'HK$' . number_format($a, 2),
            'SGD' => fn($a) => 'S$' . number_format($a, 2),
        ];

        if (isset($formatters[$currency])) {
            return $formatters[$currency]((float) $amount);
        }

        // Fallback: currency code + amount
        return number_format((float) $amount, 2) . ' ' . $currency;
    }

    /**
     * Format giá từ USD amount (tự động convert và format)
     */
    public static function formatPriceFromUSD(float $usdAmount, ?string $domain = null): string
    {
        $convertedAmount = self::convertFromUSD($usdAmount, $domain);
        return self::formatPrice($convertedAmount, null, $domain);
    }

    /**
     * Lấy currency symbol
     */
    public static function getCurrencySymbol(?string $currency = null, ?string $domain = null): string
    {
        $currency = $currency ?? self::getCurrencyForDomain($domain);

        $symbols = [
            'USD' => '$',
            'GBP' => '£',
            'EUR' => '€',
            'VND' => '₫',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
            'CNY' => '¥',
            'MXN' => 'MX$',
            'HKD' => 'HK$',
            'SGD' => 'S$',
        ];

        return $symbols[$currency] ?? $currency;
    }

    /**
     * Clear cache cho domain
     */
    public static function clearCache(?string $domain = null): void
    {
        $domain = $domain ?? self::getCurrentDomain();

        if ($domain) {
            Cache::forget("currency_for_domain_{$domain}");
            Cache::forget("currency_rate_for_domain_{$domain}");
        }

        // Clear current request cache
        self::$currentCurrency = null;
        self::$currentCurrencyRate = null;
        self::$currentDomain = null;
    }

    /**
     * Set currency cho request hiện tại (dùng cho testing hoặc override)
     */
    public static function setCurrency(string $currency, ?float $rate = null): void
    {
        self::$currentCurrency = $currency;
        self::$currentCurrencyRate = $rate ?? 1.0;
    }
}
