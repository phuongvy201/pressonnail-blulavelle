<?php

namespace App\Http\Middleware;

use App\Support\AffiliateUtmCapture;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateUtm
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = (string) config('affiliate.utm_session_key', 'affiliate_utm_last');
        $params = config('affiliate.utm_params', []);
        if (! is_array($params)) {
            $params = [];
        }

        $explicitFromQuery = [];
        foreach ($params as $param) {
            if (! is_string($param) || ! $request->query->has($param)) {
                continue;
            }
            $v = $request->query($param);
            if ($v !== null && $v !== '') {
                $explicitFromQuery[$param] = substr((string) $v, 0, 128);
            }
        }

        $incoming = AffiliateUtmCapture::fromRequest($request);

        if ($explicitFromQuery !== [] || $incoming !== []) {
            $existing = $request->session()->get($sessionKey, []);
            if (! is_array($existing)) {
                $existing = [];
            }
            // Explicit ?utm_* on URL always overrides session.
            $existing = array_merge($existing, $explicitFromQuery);
            // Inferred source (e.g. Instagram referer / in-app UA) only fills empty keys.
            $existing = AffiliateUtmCapture::merge($existing, $incoming);
            $request->session()->put($sessionKey, $existing);
        }

        return $next($request);
    }
}
