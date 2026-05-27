<?php

namespace App\Support;

/**
 * Parent cookie domain so login/session is shared between the storefront and creator subdomain.
 *
 * Example: shop pressonnail.test + creator creator.pressonnail.test → .pressonnail.test
 */
class SharedCookieDomain
{
    public static function resolve(): ?string
    {
        $explicit = env('SESSION_DOMAIN');
        if (is_string($explicit) && $explicit !== '' && strtolower($explicit) !== 'null') {
            return self::normalize($explicit);
        }

        return self::inferFromHosts();
    }

    private static function normalize(string $domain): string
    {
        $domain = trim($domain);

        // Browsers require a leading dot for subdomain sharing (except localhost).
        if ($domain !== 'localhost' && ! str_starts_with($domain, '.')) {
            return '.'.$domain;
        }

        return $domain;
    }

    private static function inferFromHosts(): ?string
    {
        $shopHost = self::hostFromUrl((string) config('app.url'));
        $creatorHost = trim((string) config('creator.domain', ''));

        if ($shopHost === '' || $creatorHost === '' || $shopHost === $creatorHost) {
            return null;
        }

        // creator.pressonnail.test is a subdomain of pressonnail.test
        if (str_ends_with($creatorHost, '.'.$shopHost)) {
            return self::normalize($shopHost);
        }

        return null;
    }

    private static function hostFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }
}
