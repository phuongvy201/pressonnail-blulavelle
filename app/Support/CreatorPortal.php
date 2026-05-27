<?php

namespace App\Support;

class CreatorPortal
{
    public static function baseUrl(): string
    {
        $configured = trim((string) env('CREATOR_PORTAL_URL', ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $domain = trim((string) config('creator.domain', ''));

        return $domain !== '' ? $scheme.'://'.$domain : rtrim((string) config('app.url'), '/');
    }

    public static function url(string $path = '/'): string
    {
        $path = '/'.ltrim($path, '/');

        return self::baseUrl().$path;
    }

    public static function dashboardUrl(): string
    {
        return self::url('/dashboard');
    }

    public static function loginUrl(): string
    {
        return self::url('/login');
    }
}
