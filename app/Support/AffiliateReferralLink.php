<?php

namespace App\Support;

use App\Models\Affiliate;
use App\Models\Product;

class AffiliateReferralLink
{
    public static function shopBaseUrl(): string
    {
        return rtrim((string) config('creator.shop_url', config('app.url')), '/');
    }

    public static function refQueryParam(): string
    {
        return (string) config('affiliate.ref_query_param', 'ref');
    }

    public static function appendRef(string $url, Affiliate $affiliate): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.self::refQueryParam().'='.rawurlencode($affiliate->code);
    }

    public static function homeUrl(Affiliate $affiliate): string
    {
        return self::appendRef(self::shopBaseUrl(), $affiliate);
    }

    public static function productUrl(Product $product, Affiliate $affiliate): ?string
    {
        $slug = trim((string) $product->slug);
        if ($slug === '') {
            return null;
        }

        return self::appendRef(self::shopBaseUrl().'/products/'.$slug, $affiliate);
    }
}
