<?php

namespace App\Http\Middleware;

use App\Services\AffiliateAttributionService;
use App\Services\AffiliateClickTrackingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateReferral
{
    public function __construct(
        private readonly AffiliateAttributionService $attributionService,
        private readonly AffiliateClickTrackingService $clickTracking,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $param = (string) config('affiliate.ref_query_param', 'ref');
        $raw = $request->query($param);
        if (!is_string($raw) || trim($raw) === '') {
            return $next($request);
        }

        $code = trim($raw);
        $affiliate = $this->attributionService->findActiveAffiliateByCode($code);
        if (!$affiliate) {
            return $next($request);
        }

        $name = (string) config('affiliate.cookie_name', 'pon_affiliate_ref');
        $minutes = (int) config('affiliate.cookie_ttl_minutes', 43200);
        $normalized = strtolower($affiliate->code);

        $cookieDomain = config('affiliate.cookie_domain');
        $cookieDomain = is_string($cookieDomain) && $cookieDomain !== '' ? $cookieDomain : null;

        Cookie::queue(cookie(
            $name,
            $normalized,
            $minutes,
            '/',
            $cookieDomain,
            (bool) config('session.secure', false),
            false,
            false,
            config('session.same_site') ?? 'lax'
        ));

        $this->clickTracking->record($affiliate, $request, $code);

        return $next($request);
    }
}
