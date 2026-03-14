<?php

namespace App\Services;

use App\Models\ShippingRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CustomerLocationService
{
    /** Cache TTL (seconds) cho kết quả geo theo IP. */
    public const GEO_IP_CACHE_TTL = 86400; // 24 giờ

    public function detectCountryCode(?Request $request = null, string $fallback = 'US'): string
    {
        $request = $request ?? (function_exists('request') ? request() : null);

        // 1) CDN / edge headers (Cloudflare, CloudFront, Vercel, etc.)
        if ($request) {
            $headerCandidates = [
                'CF-IPCountry',
                'CloudFront-Viewer-Country',
                'X-Vercel-IP-Country',
                'X-Country-Code',
                'X-App-Country',
            ];

            foreach ($headerCandidates as $header) {
                $value = strtoupper(trim((string) $request->header($header, '')));
                if ($this->isIso2($value)) {
                    return $value;
                }
            }
        }

        // 2) Lấy quốc gia từ IP khách (geolocation API, có cache)
        if ($request) {
            $countryFromIp = $this->getCountryCodeFromIp($request->ip());
            if ($this->isIso2($countryFromIp)) {
                return $countryFromIp;
            }
        }

        // 3) Fallback theo cấu hình DB (ShippingRate default -> ShippingZone countries)
        $fromRates = $this->detectCountryCodeFromShippingRates();
        if ($this->isIso2($fromRates)) {
            return $fromRates;
        }

        // 4) Session (nếu app có lưu lựa chọn deliver-to)
        try {
            $sessionCandidates = [
                'shipping_country',
                'delivery_country',
                'ship_country',
                'country',
                'country_code',
            ];

            foreach ($sessionCandidates as $key) {
                $value = strtoupper(trim((string) session($key, '')));
                if ($this->isIso2($value)) {
                    return $value;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 5) Fallback theo currency hiện tại (đang dùng trong CartController)
        try {
            $currency = CurrencyService::getCurrentCurrency();
            $currencyToCountry = [
                'USD' => 'US',
                'GBP' => 'GB',
                'CAD' => 'CA',
                'MXN' => 'MX',
                'VND' => 'VN',
                'EUR' => 'DE',
            ];
            if (!empty($currencyToCountry[$currency])) {
                return $currencyToCountry[$currency];
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 6) Fallback theo domain (đang dùng trong CartController)
        try {
            $domain = CurrencyService::getCurrentDomain();
            if ($domain) {
                $domainToCountry = [
                    'mx' => 'MX',
                    'mexico' => 'MX',
                    'us' => 'US',
                    'usa' => 'US',
                    'united-states' => 'US',
                    'gb' => 'GB',
                    'uk' => 'GB',
                    'united-kingdom' => 'GB',
                    'ca' => 'CA',
                    'canada' => 'CA',
                    'vn' => 'VN',
                    'vietnam' => 'VN',
                    'de' => 'DE',
                    'germany' => 'DE',
                    'eu' => 'DE',
                    'europe' => 'DE',
                ];

                $domainLower = strtolower($domain);
                foreach ($domainToCountry as $needle => $countryCode) {
                    if (strpos($domainLower, $needle) !== false) {
                        return $countryCode;
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return strtoupper($fallback ?: 'US');
    }

    /**
     * Lấy mã quốc gia (ISO 2) từ IP khách qua API geolocation.
     * Dùng cache theo IP để tránh gọi API liên tục.
     * Bỏ qua IP local/private (localhost, 127.x, 10.x, 192.168.x, ...).
     */
    public function getCountryCodeFromIp(?string $ip): ?string
    {
        if (! $ip || $ip === '') {
            return null;
        }
        if ($this->isPrivateOrLocalIp($ip)) {
            return null;
        }

        $cacheKey = 'geo_ip_' . md5($ip);

        $cached = Cache::get($cacheKey);
        if ($cached !== null && $this->isIso2($cached)) {
            return $cached;
        }

        try {
            $response = Http::timeout(2)->get("https://ipapi.co/{$ip}/json/");
            if (! $response->successful()) {
                return null;
            }
            $data = $response->json();
            $code = $data['country_code'] ?? $data['country'] ?? null;
            $code = is_string($code) ? strtoupper(trim($code)) : null;

            if ($this->isIso2($code ?? '')) {
                Cache::put($cacheKey, $code, self::GEO_IP_CACHE_TTL);

                return $code;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    private function isPrivateOrLocalIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        return true;
    }

    private function detectCountryCodeFromShippingRates(): ?string
    {
        try {
            $rate = ShippingRate::query()
                ->where('is_active', true)
                ->with(['shippingZone' => function ($q) {
                    $q->where('is_active', true);
                }])
                ->ordered()
                ->first();

            $countries = $rate?->shippingZone?->countries ?? null;
            if (!is_array($countries) || empty($countries)) {
                return null;
            }

            $first = strtoupper(trim((string) ($countries[0] ?? '')));
            return $this->isIso2($first) ? $first : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getCountryName(string $countryCode): string
    {
        $code = strtoupper(trim($countryCode));

        $map = [
            'US' => 'United States',
            'VN' => 'Vietnam',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'MX' => 'Mexico',
            'DE' => 'Germany',
            'AU' => 'Australia',
            'FR' => 'France',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'IN' => 'India',
            'SG' => 'Singapore',
            'TH' => 'Thailand',
            'MY' => 'Malaysia',
            'PH' => 'Philippines',
            'ID' => 'Indonesia',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'NL' => 'Netherlands',
            'BR' => 'Brazil',
            'PL' => 'Poland',
            'RU' => 'Russia',
            'CN' => 'China',
        ];

        return $map[$code] ?? $code;
    }

    private function isIso2(?string $value): bool
    {
        return $value !== null && $value !== '' && (bool) preg_match('/^[A-Z]{2}$/', $value);
    }
}

