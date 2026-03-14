<?php

use App\Services\CurrencyService;

if (!function_exists('currency')) {
    /**
     * Lấy currency code hiện tại
     */
    function currency(?string $domain = null): string
    {
        return CurrencyService::getCurrencyForDomain($domain);
    }
}

if (!function_exists('currency_rate')) {
    /**
     * Lấy currency rate hiện tại
     */
    function currency_rate(?string $domain = null): ?float
    {
        return CurrencyService::getCurrencyRateForDomain($domain);
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Lấy currency symbol
     */
    function currency_symbol(?string $currency = null, ?string $domain = null): string
    {
        return CurrencyService::getCurrencySymbol($currency, $domain);
    }
}

if (!function_exists('format_price')) {
    /**
     * Format giá theo currency hiện tại
     */
    function format_price(float $amount, ?string $currency = null, ?string $domain = null): string
    {
        return CurrencyService::formatPrice($amount, $currency, $domain);
    }
}

if (!function_exists('format_price_usd')) {
    /**
     * Format giá từ USD (tự động convert và format)
     */
    function format_price_usd(float $usdAmount, ?string $domain = null): string
    {
        return CurrencyService::formatPriceFromUSD($usdAmount, $domain);
    }
}

if (!function_exists('convert_currency')) {
    /**
     * Convert giá từ USD sang currency hiện tại
     */
    function convert_currency(float $usdAmount, ?string $domain = null): float
    {
        return CurrencyService::convertFromUSD($usdAmount, $domain);
    }
}

if (!function_exists('content_block')) {
    /**
     * Lấy nội dung block (trang chủ, v.v.) đã merge với default.
     * Dùng cho inline editing: admin chỉnh trên frontend, lưu vào DB.
     */
    function content_block(string $blockKey, array $default = []): array
    {
        return \App\Models\ContentBlock::getContent($blockKey, $default);
    }
}









































