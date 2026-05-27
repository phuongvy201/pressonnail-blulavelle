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

if (! function_exists('content_block_section_bg_schema')) {
    /**
     * Schema field màu nền section (inline edit).
     */
    function content_block_section_bg_schema(): array
    {
        return [
            'key' => 'bg_color',
            'label' => 'Màu nền section (HEX, vd: #f8f9ff hoặc #eff4ff) — để trống dùng màu mặc định',
            'type' => 'text',
        ];
    }
}

if (! function_exists('content_block_section_bg_style')) {
    /**
     * Inline style an toàn cho background section từ content block.
     */
    function content_block_section_bg_style(?string $color): string
    {
        $color = trim((string) $color);
        if ($color === '') {
            return '';
        }

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color)) {
            return 'background-color: '.$color.';';
        }

        if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+(?:\s*,\s*[\d.]+\s*)?\)$/i', $color)) {
            return 'background-color: '.$color.';';
        }

        return '';
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

if (! function_exists('creator_layout_footer_block_defaults')) {
    function creator_layout_footer_block_defaults(): array
    {
        $portal = config('creator.portal_name', config('app.name'));

        return [
            'tagline' => $portal.' — Elevating creator partnerships with clear commissions and a single storefront checkout.',
            'bg_color' => '#eff4ff',
        ];
    }
}

if (! function_exists('creator_layout_footer_block_schema')) {
    function creator_layout_footer_block_schema(): array
    {
        return [
            ['key' => 'tagline', 'label' => 'Mô tả footer portal', 'type' => 'textarea'],
            content_block_section_bg_schema(),
        ];
    }
}

if (! function_exists('creator_home_hero_block_defaults')) {
    function creator_home_hero_block_defaults(): array
    {
        return [
            'badge' => 'Exclusive Opportunity',
            'heading_brand' => config('app.name'),
            'heading_highlight' => 'Creator Program',
            'subheading' => 'Turn your passion for beauty into professional rewards. Earn premium commissions and access exclusive luxury releases while growing your digital presence.',
            'cta_primary_label' => 'Become a Creator',
            'cta_secondary_label' => 'Explore Benefits',
            'hero_image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCm2K2Hu6djcrEZp_mO6c6MgTb2LiWtULO9aOgclmuWsplOW5YstRPJca9lL5MoAYccbDgW-zMeU7cLtsqsT2ThM0S63_n2cYgCnKFa7WbVjSS6pbTDaWTEDlMf8zfmDpdm9cNd7HbVxn9sjYSVxWZ06Ngqj2NywCHFyQmwIvvJ_DSHoZx-oOY_w410gtlhY11QkKcqhnpybDBFqmEGA0pPMC-gIrgq30qkTLY-LWEcqYVh0QpSCnS_qtZ3X-qrOfZp7kh995ke0vZQ',
            'stat_clicks_value' => '12.4k',
            'stat_clicks_change' => '+14% this week',
            'stat_commission_value' => '$2,840.50',
            'stat_commission_bar' => '75',
            'bg_color' => '',
        ];
    }
}

if (! function_exists('creator_home_hero_block_schema')) {
    function creator_home_hero_block_schema(): array
    {
        return [
            content_block_section_bg_schema(),
            ['key' => 'badge', 'label' => 'Badge', 'type' => 'text'],
            ['key' => 'heading_brand', 'label' => 'Tiêu đề — tên thương hiệu', 'type' => 'text'],
            ['key' => 'heading_highlight', 'label' => 'Tiêu đề — dòng nổi bật (italic)', 'type' => 'text'],
            ['key' => 'subheading', 'label' => 'Mô tả', 'type' => 'textarea'],
            ['key' => 'cta_primary_label', 'label' => 'Nút chính', 'type' => 'text'],
            ['key' => 'cta_secondary_label', 'label' => 'Nút phụ', 'type' => 'text'],
            ['key' => 'hero_image', 'label' => 'Ảnh hero', 'type' => 'image'],
            ['key' => 'stat_clicks_value', 'label' => 'Thẻ Clicks — số liệu', 'type' => 'text'],
            ['key' => 'stat_clicks_change', 'label' => 'Thẻ Clicks — dòng phụ (vd: +14% this week)', 'type' => 'text'],
            ['key' => 'stat_commission_value', 'label' => 'Thẻ Commission — số tiền', 'type' => 'text'],
            ['key' => 'stat_commission_bar', 'label' => 'Thẻ Commission — thanh % (0–100)', 'type' => 'text'],
        ];
    }
}

if (! function_exists('creator_home_faq_block_defaults')) {
    function creator_home_faq_block_defaults(): array
    {
        return [
            'section_heading' => 'Frequently Asked',
            'q1' => 'How are commissions calculated?',
            'a1' => 'Commissions are calculated as a percentage of the final order value (excluding taxes and shipping) for all purchases made through your link.',
            'q2' => 'How long do cookies last?',
            'a2' => 'Cookie duration follows your program terms. Check your affiliate dashboard after approval for the exact attribution window.',
            'q3' => 'When do I get my payouts?',
            'a3' => 'Payouts are processed on a regular schedule once your balance reaches the minimum threshold. You will receive details by email when your account is activated.',
            'q4' => 'How often can I request free samples?',
            'a4' => 'Sample eligibility depends on your tier and campaign availability. Our team will outline sample policies when you are onboarded.',
            'bg_color' => '',
        ];
    }
}

if (! function_exists('creator_home_faq_block_schema')) {
    function creator_home_faq_block_schema(): array
    {
        return [
            content_block_section_bg_schema(),
            ['key' => 'section_heading', 'label' => 'Tiêu đề FAQ', 'type' => 'text'],
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

if (! function_exists('content_block_asset_url')) {
    function content_block_asset_url(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }
}

if (! function_exists('creator_home_page_block')) {
    function creator_home_page_block(string $blockKey): array
    {
        return content_block($blockKey, \App\Support\CreatorHomePageBlocks::defaults($blockKey));
    }
}

if (! function_exists('creator_home_tiers_block')) {
    function creator_home_tiers_block(): array
    {
        $data = content_block('creator.home.tiers', \App\Support\CreatorHomePageBlocks::homeTiersDefaults());

        return \App\Support\CreatorHomePageBlocks::normalizeTiersContent($data);
    }
}

if (! function_exists('creator_home_sample_request_url')) {
    function creator_home_sample_request_url(): string
    {
        $user = auth()->user();

        if ($user && $user->canAccessCreatorAffiliateFeatures()) {
            return route('creator.sample-requests.create');
        }

        return route('creator.affiliate.apply');
    }
}

if (! function_exists('creator_home_page_schema')) {
    function creator_home_page_schema(string $blockKey): array
    {
        return \App\Support\CreatorHomePageBlocks::schema($blockKey);
    }
}

if (! function_exists('creator_home_features_list')) {
    /**
     * @return list<string>
     */
    function creator_home_features_list(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text))));
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

if (! function_exists('storage_public_path_from_url')) {
    /**
     * Lấy đường dẫn tương đối (storage/app/public hoặc public/) từ URL đầy đủ hoặc path có /storage/ hoặc /images/.
     */
    function storage_public_path_from_url(?string $src): ?string
    {
        if ($src === null || $src === '') {
            return null;
        }

        $pathPart = $src;
        if (str_contains($src, '://')) {
            $parsed = parse_url($src);
            $pathPart = $parsed['path'] ?? '';
        }

        if (preg_match('#/storage/(.+)$#i', $pathPart, $m)) {
            return rawurldecode($m[1]);
        }

        if (preg_match('#/(images/.+)$#i', $pathPart, $m)) {
            return rawurldecode($m[1]);
        }

        return null;
    }
}

if (! function_exists('storage_image_resize_url')) {
    /**
     * URL resize có chữ ký (WebP/JPEG theo Accept) — chỉ file local public hoặc disk public.
     */
    function storage_image_resize_url(?string $src, int $maxWidth = 720): ?string
    {
        $path = storage_public_path_from_url($src);
        if ($path === null || str_contains($path, '..')) {
            return null;
        }

        if (! preg_match('/\.(jpe?g|png|gif|webp)$/i', $path)) {
            return null;
        }

        $w = max(80, min(1920, $maxWidth));

        return \Illuminate\Support\Facades\URL::signedRoute('media.resize', [
            'p' => $path,
            'w' => $w,
        ], absolute: true);
    }
}

if (! function_exists('optimized_local_img')) {
    /**
     * Dùng URL resize nếu được; ngược lại trả về URL gốc.
     */
    function optimized_local_img(?string $src, int $maxWidth = 720): string
    {
        if ($src === null || $src === '') {
            return '';
        }

        return storage_image_resize_url($src, $maxWidth) ?? $src;
    }
}
