<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Collect UTM from query; infer utm_source / utm_medium from referrer or in-app browser UA when missing.
 */
class AffiliateUtmCapture
{
    /**
     * @return array<string, string>
     */
    public static function fromRequest(Request $request): array
    {
        $params = config('affiliate.utm_params', []);
        if (! is_array($params)) {
            $params = [];
        }

        $captured = [];
        foreach ($params as $param) {
            if (! is_string($param) || ! $request->query->has($param)) {
                continue;
            }
            $v = $request->query($param);
            if ($v !== null && $v !== '') {
                $captured[$param] = substr((string) $v, 0, 128);
            }
        }

        if (! config('affiliate.infer_utm_when_missing', true)) {
            return $captured;
        }

        $inferred = self::inferFromReferrer($request->headers->get('referer'));
        if ($inferred === []) {
            $inferred = self::inferFromUserAgent($request->userAgent());
        }

        foreach ($inferred as $key => $value) {
            if ($value === '' || array_key_exists($key, $captured)) {
                continue;
            }
            $captured[$key] = $value;
        }

        return $captured;
    }

    /**
     * @return array{utm_source?: string, utm_medium?: string}
     */
    public static function inferFromReferrer(?string $referrer): array
    {
        if (! is_string($referrer) || trim($referrer) === '') {
            return [];
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return [];
        }

        $host = strtolower($host);
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $source = match (true) {
            str_contains($host, 'instagram') => 'instagram',
            str_contains($host, 'tiktok') => 'tiktok',
            str_contains($host, 'facebook') || $host === 'fb.com' || str_ends_with($host, '.fb.com') => 'facebook',
            str_contains($host, 'pinterest') => 'pinterest',
            $host === 't.co' || str_contains($host, 'twitter') || $host === 'x.com' => 'twitter',
            str_contains($host, 'youtube') || $host === 'youtu.be' => 'youtube',
            str_contains($host, 'linkedin') => 'linkedin',
            default => null,
        };

        if ($source === null) {
            return [];
        }

        return [
            'utm_source' => $source,
            'utm_medium' => 'social',
        ];
    }

    /**
     * In-app browsers often strip Referer; UA still identifies the app.
     *
     * @return array{utm_source?: string, utm_medium?: string}
     */
    public static function inferFromUserAgent(?string $userAgent): array
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return [];
        }

        $ua = strtolower($userAgent);

        $source = match (true) {
            str_contains($ua, 'instagram') => 'instagram',
            str_contains($ua, 'tiktok') => 'tiktok',
            str_contains($ua, 'fbav') || str_contains($ua, 'fban') || str_contains($ua, 'facebook') => 'facebook',
            str_contains($ua, 'pinterest') => 'pinterest',
            default => null,
        };

        if ($source === null) {
            return [];
        }

        return [
            'utm_source' => $source,
            'utm_medium' => 'social',
        ];
    }

    /**
     * @param  array<string, string>  $existing
     * @param  array<string, string>  $incoming  Query params win over session; inferred never overwrites explicit keys.
     */
    public static function merge(array $existing, array $incoming): array
    {
        $merged = $existing;

        foreach ($incoming as $key => $value) {
            if ($value === '') {
                continue;
            }
            if (! array_key_exists($key, $merged) || $merged[$key] === '') {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
