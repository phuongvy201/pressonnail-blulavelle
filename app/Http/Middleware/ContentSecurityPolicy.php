<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        if (! config('csp.enabled', true)) {
            return $response;
        }

        if ($this->shouldSkip($request, $response)) {
            return $response;
        }

        $header = config('csp.report_only')
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $policy = $this->buildPolicy($nonce);
        if ($policy !== '') {
            $response->headers->set($header, $policy, false);
        }

        if (! $response->headers->has('X-Content-Type-Options')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        if (! $response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        return $response;
    }

    private function shouldSkip(Request $request, Response $response): bool
    {
        if ($request->is('api/*') || $request->is('webhooks/*') || $request->is('payment/stripe/webhook')) {
            return true;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html') && ! str_contains($contentType, 'application/xhtml')) {
            if (! str_starts_with($contentType, 'text/') && $contentType !== '') {
                return true;
            }
        }

        return false;
    }

    private function buildPolicy(string $nonce): string
    {
        $directives = config('csp.directives', []);
        $extra = config('csp.extra', []);
        $useNonces = (bool) config('csp.use_nonces', false);
        $parts = [];

        foreach ($directives as $name => $values) {
            if (! is_array($values)) {
                continue;
            }

            $merged = $values;
            if (isset($extra[$name]) && is_array($extra[$name])) {
                $merged = array_merge($merged, $extra[$name]);
            }

            // Nonce mode: allow listed scripts + this request's nonce.
            // Browsers ignore 'unsafe-inline' when a nonce/hash is present.
            if ($useNonces && in_array($name, ['script-src', 'style-src'], true)) {
                $merged = array_values(array_filter(
                    $merged,
                    static fn ($v) => $v !== "'unsafe-inline'"
                ));
                $merged[] = "'nonce-{$nonce}'";
                // Prefer modern hosts that understand nonces for remaining inline.
                if ($name === 'script-src') {
                    $merged[] = "'strict-dynamic'";
                }
            }

            $merged = array_values(array_unique(array_filter($merged, static fn ($v) => is_string($v) && $v !== '')));

            if ($name === 'upgrade-insecure-requests') {
                if ($request = request()) {
                    if ($request->isSecure() || app()->environment('production')) {
                        $parts[] = 'upgrade-insecure-requests';
                    }
                }
                continue;
            }

            if ($merged === []) {
                $parts[] = $name;
                continue;
            }

            $parts[] = $name.' '.implode(' ', $merged);
        }

        $reportUri = config('csp.report_uri');
        if (is_string($reportUri) && $reportUri !== '') {
            $parts[] = 'report-uri '.$reportUri;
        }

        return implode('; ', $parts);
    }
}
