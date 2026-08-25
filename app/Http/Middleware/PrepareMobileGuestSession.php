<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs BEFORE StartSession. Assigns a stable session id for guest mobile clients
 * so cart / wishlist logic (session_id column) works without cookies.
 */
class PrepareMobileGuestSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        $header = config('mobile_api.guest_cart_header', 'X-Guest-Cart-Token');
        $token = $request->header($header);

        if (! $this->isValidGuestToken($token)) {
            $token = Str::random(40);
        }

        Session::driver()->setId($token);
        $request->attributes->set('mobile_guest_cart_token', $token);

        $response = $next($request);

        if ($token) {
            $response->headers->set($header, $token);
        }

        return $response;
    }

    private function isValidGuestToken(?string $token): bool
    {
        return is_string($token) && preg_match('/^[a-zA-Z0-9_-]{32,64}$/', $token) === 1;
    }
}
