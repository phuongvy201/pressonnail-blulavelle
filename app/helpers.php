<?php

use App\Services\CurrencyService;

if (! function_exists('currency')) {
    /**
     * Lấy currency code hiện tại
     */
    function currency(?string $domain = null): string
    {
        return CurrencyService::getCurrencyForDomain($domain);
    }
}

if (! function_exists('currency_rate')) {
    /**
     * Lấy currency rate hiện tại
     */
    function currency_rate(?string $domain = null): ?float
    {
        return CurrencyService::getCurrencyRateForDomain($domain);
    }
}

if (! function_exists('currency_symbol')) {
    /**
     * Lấy currency symbol
     */
    function currency_symbol(?string $currency = null, ?string $domain = null): string
    {
        return CurrencyService::getCurrencySymbol($currency, $domain);
    }
}

if (! function_exists('format_price')) {
    /**
     * Format giá theo currency hiện tại
     */
    function format_price(float $amount, ?string $currency = null, ?string $domain = null): string
    {
        return CurrencyService::formatPrice($amount, $currency, $domain);
    }
}

if (! function_exists('format_price_usd')) {
    /**
     * Format giá từ USD (tự động convert và format)
     */
    function format_price_usd(float $usdAmount, ?string $domain = null): string
    {
        return CurrencyService::formatPriceFromUSD($usdAmount, $domain);
    }
}

if (! function_exists('convert_currency')) {
    /**
     * Convert giá từ USD sang currency hiện tại
     */
    function convert_currency(float $usdAmount, ?string $domain = null): float
    {
        return CurrencyService::convertFromUSD($usdAmount, $domain);
    }
}

if (! function_exists('content_block')) {
    /**
     * Lấy nội dung block (trang chủ, v.v.) đã merge với default.
     * Dùng cho inline editing: admin chỉnh trên frontend, lưu vào DB.
     */
    function content_block(string $blockKey, array $default = []): array
    {
        return \App\Models\ContentBlock::getContent($blockKey, $default);
    }
}

if (! function_exists('footer_faq_block_defaults')) {
    /**
     * Nội dung mặc định block FAQ chân trang (layout.footer_faq).
     */
    function footer_faq_block_defaults(): array
    {
        return [
            'section_heading' => 'Your Questions, Answered',
            'q1' => 'Where can I wear my BluLavelle nails?',
            'a1' => 'BluLavelle nails are designed for every chapter of your life. From professional meetings and elegant galas to everyday errands and vacations by the sea. Our sets are water-resistant and resilient, allowing you to go about your routine with confidence and grace.',
            'q2' => 'How long do BluLavelle nails last?',
            'a2' => 'With proper application, your BluLavelle manicure can last over 2 weeks using our professional nail glue, or 3-7 days using our gentle adhesive tabs—perfect for weekend events. Best of all, they are reusable, allowing you to enjoy your favorite designs again and again.',
            'q3' => 'How do I apply and remove them safely?',
            'a3' => 'Application is an effortless 10-minute ritual: simply prep, size, and press. To remove, soak your hands in warm soapy water with a touch of oil for a few minutes. This gentle process ensures your natural nails remain healthy and damage-free, preserved for your next BluLavelle set.',
            'q4' => 'Do you offer returns or refunds?',
            'a4' => 'Due to hygiene and safety standards, we generally do not accept returns. However, your satisfaction is our priority. If your order arrives damaged or incorrect, please reach out to our concierge team within 7 days, and we will ensure a seamless solution for you.',
        ];
    }
}

if (! function_exists('footer_faq_block_schema')) {
    /**
     * Schema cho inline edit (modal) — khớp key với data-content-field trong layout.
     */
    function footer_faq_block_schema(): array
    {
        return [
            ['key' => 'section_heading', 'label' => 'Tiêu đề section FAQ', 'type' => 'text'],
            ['key' => 'q1', 'label' => 'Câu hỏi 1', 'type' => 'text'],
            ['key' => 'a1', 'label' => 'Trả lời 1', 'type' => 'textarea'],
            ['key' => 'q2', 'label' => 'Câu hỏi 2', 'type' => 'text'],
            ['key' => 'a2', 'label' => 'Trả lời 2', 'type' => 'textarea'],
            ['key' => 'q3', 'label' => 'Câu hỏi 3', 'type' => 'text'],
            ['key' => 'a3', 'label' => 'Trả lời 3', 'type' => 'textarea'],
            ['key' => 'q4', 'label' => 'Câu hỏi 4', 'type' => 'text'],
            ['key' => 'a4', 'label' => 'Trả lời 4', 'type' => 'textarea'],
        ];
    }
}

if (! function_exists('product_media_image_urls')) {
    /**
     * Trích URL ảnh gốc và WebP (nếu có) từ phần tử media — chuỗi URL hoặc mảng {url, webp}.
     * Bỏ qua video.
     *
     * @return array{original: ?string, webp: ?string}
     */
    function product_media_image_urls(string|array|null $item): array
    {
        if ($item === null || $item === '') {
            return ['original' => null, 'webp' => null];
        }
        if (is_string($item)) {
            return ['original' => $item, 'webp' => null];
        }
        if (! is_array($item)) {
            return ['original' => null, 'webp' => null];
        }
        if (($item['type'] ?? null) === 'video') {
            return ['original' => null, 'webp' => null];
        }
        $original = $item['url'] ?? $item['path'] ?? null;
        $webp = $item['webp'] ?? null;

        return [
            'original' => is_string($original) ? $original : null,
            'webp' => is_string($webp) ? $webp : null,
        ];
    }
}
