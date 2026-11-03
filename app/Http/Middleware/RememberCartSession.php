<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the session id from before Auth::login() regenerates it.
 * Guest cart rows are stored under that id.
 */
class RememberCartSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && ! $request->attributes->has('cart_session_id')) {
            $request->attributes->set('cart_session_id', $request->session()->getId());
        }

        return $next($request);
    }
}
