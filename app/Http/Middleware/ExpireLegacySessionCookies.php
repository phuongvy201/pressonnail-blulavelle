<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Expire stale session cookies left over from older SESSION_DOMAIN / SESSION_COOKIE settings.
 *
 * Browsers may keep both host-only and parent-domain cookies with the same name, which breaks login.
 */
class ExpireLegacySessionCookies
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldRun($request)) {
            return $response;
        }

        foreach ($this->cookiesToExpire($request) as $cookie) {
            $response->headers->setCookie($cookie);
        }

        return $response;
    }

    private function shouldRun(Request $request): bool
    {
        if ($request->isMethod('OPTIONS')) {
            return false;
        }

        return $request->is('login*')
            || $request->is('logout*')
            || $request->is('auth/*')
            || $request->is('dashboard*')
            || $request->is('admin*')
            || Auth::check()
            || $this->requestHasDuplicateAuthCookies($request);
    }

    private function requestHasDuplicateAuthCookies(Request $request): bool
    {
        $cookieHeader = (string) $request->headers->get('Cookie', '');

        if ($cookieHeader === '') {
            return false;
        }

        $names = array_unique(array_filter(array_merge(
            config('session.legacy_cookie_names', []),
            [config('session.cookie'), 'XSRF-TOKEN']
        )));

        foreach ($names as $name) {
            if ($name === '' || $name === null) {
                continue;
            }

            if (substr_count($cookieHeader, $name.'=') > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, Cookie>
     */
    private function cookiesToExpire(Request $request): array
    {
        $secure = (bool) config('session.secure', $request->isSecure());
        $sameSite = config('session.same_site', 'lax');
        $path = config('session.path', '/');
        $currentSessionCookie = (string) config('session.cookie');
        $currentDomain = config('session.domain');

        $cookies = [];
        $legacyDomains = $this->legacyCookieDomains($currentDomain);
        $legacyNames = array_values(array_unique(array_filter(
            config('session.legacy_cookie_names', []),
            static fn ($name) => is_string($name) && $name !== '' && $name !== $currentSessionCookie
        )));

        $authCookieNames = array_values(array_unique(array_filter([
            $currentSessionCookie,
            'XSRF-TOKEN',
            $this->rememberCookieName(),
        ])));

        foreach ($legacyDomains as $domain) {
            foreach ($authCookieNames as $name) {
                $cookies[] = $this->makeExpiredCookie($name, $path, $domain, $secure, $sameSite);
            }
        }

        foreach ($legacyNames as $name) {
            $cookies[] = $this->makeExpiredCookie($name, $path, null, $secure, $sameSite);

            foreach ($legacyDomains as $domain) {
                $cookies[] = $this->makeExpiredCookie($name, $path, $domain, $secure, $sameSite);
            }
        }

        return $cookies;
    }

    /**
     * @return array<int, string|null>
     */
    private function legacyCookieDomains(?string $currentDomain): array
    {
        $configured = array_values(array_filter(array_map(
            static fn ($domain) => is_string($domain) && $domain !== '' ? $domain : null,
            config('session.legacy_cookie_domains', [])
        )));

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($host) && $host !== '' && $host !== 'localhost') {
            $parentDomain = '.'.strtolower($host);
            if ($parentDomain !== $currentDomain) {
                $configured[] = $parentDomain;
            }
        }

        return array_values(array_unique($configured));
    }

    private function rememberCookieName(): ?string
    {
        try {
            return Auth::guard('web')->getRecallerName();
        } catch (\Throwable) {
            return null;
        }
    }

    private function makeExpiredCookie(
        string $name,
        string $path,
        ?string $domain,
        bool $secure,
        ?string $sameSite
    ): Cookie {
        return Cookie::create(
            $name,
            null,
            1,
            $path,
            $domain,
            $secure,
            true,
            false,
            is_string($sameSite) ? $sameSite : Cookie::SAMESITE_LAX
        );
    }
}
