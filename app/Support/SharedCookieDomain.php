<?php

namespace App\Support;

/**
 * Resolve a cookie domain only when it is explicitly configured.
 *
 * We intentionally avoid inferring from app/creator hosts so separate Laravel apps
 * do not accidentally share session cookies across subdomains.
 */
class SharedCookieDomain
{
    public static function resolve(): ?string
    {
        $explicit = env('SESSION_DOMAIN');
        if (is_string($explicit) && $explicit !== '' && strtolower($explicit) !== 'null') {
            return self::normalize($explicit);
        }

        return null;
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

}
