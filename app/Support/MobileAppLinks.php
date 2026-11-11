<?php

namespace App\Support;

class MobileAppLinks
{
    public static function appleAppId(): string
    {
        return trim((string) Settings::get('app.apple_app_id', config('mobile_app.apple_app_id', '6826066333')));
    }

    public static function iosBaseUrl(): string
    {
        $configured = trim((string) Settings::get('app.ios_store_url', config('mobile_app.ios_store_url', '')));
        if ($configured !== '') {
            return $configured;
        }

        $appId = self::appleAppId();

        return $appId !== '' ? 'https://apps.apple.com/app/id'.$appId : '';
    }

    public static function androidEnabled(): bool
    {
        return filter_var(
            Settings::get('app.android_enabled', config('mobile_app.android_enabled', false)),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public static function androidBaseUrl(): string
    {
        if (! self::androidEnabled()) {
            return '';
        }

        return trim((string) Settings::get('app.android_store_url', config('mobile_app.android_store_url', '')));
    }

    /**
     * Campaign-tracked App Store URL for Analytics → Sources.
     * ct examples: website_header, website_footer, website_hero_qr, website_tryon, order_confirmation
     */
    public static function iosUrl(string $campaign = 'website'): string
    {
        $base = self::iosBaseUrl();
        if ($base === '') {
            return '';
        }

        $parts = parse_url($base) ?: [];
        $query = [];
        if (! empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }

        $providerToken = trim((string) config('mobile_app.campaign_provider_token', ''));
        if ($providerToken !== '') {
            $query['pt'] = $providerToken;
        }
        $query['ct'] = $campaign;
        $query['mt'] = '8';

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? 'apps.apple.com';
        $path = $parts['path'] ?? '/app/id'.self::appleAppId();
        $built = $scheme.'://'.$host.$path;
        if ($query !== []) {
            $built .= '?'.http_build_query($query);
        }

        return $built;
    }

    public static function androidUrl(string $campaign = 'website'): string
    {
        $base = self::androidBaseUrl();
        if ($base === '') {
            return '';
        }

        $sep = str_contains($base, '?') ? '&' : '?';

        return $base.$sep.'utm_source=website&utm_campaign='.rawurlencode($campaign);
    }

    public static function qrImageUrl(string $campaign = 'website_hero_qr', int $size = 160): string
    {
        $target = self::iosUrl($campaign);
        if ($target === '') {
            return '';
        }

        return 'https://api.qrserver.com/v1/create-qr-code/?size='.$size.'x'.$size.'&margin=8&data='.rawurlencode($target);
    }

    public static function itunesSmartBannerContent(): ?string
    {
        $appId = self::appleAppId();
        if ($appId === '') {
            return null;
        }

        return 'app-id='.$appId;
    }
}
