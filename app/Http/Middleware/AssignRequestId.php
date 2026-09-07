<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->header('X-Request-ID');
        $id = is_string($incoming) && preg_match('/^[A-Za-z0-9._-]{8,128}$/', $incoming)
            ? $incoming
            : (string) Str::uuid();

        $request->headers->set('X-Request-ID', $id);
        $request->attributes->set('request_id', $id);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $id);

        return $response;
    }
}
