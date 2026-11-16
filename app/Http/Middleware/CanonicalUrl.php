<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $target = Seo::redirectTarget($request);
        if ($target === null) {
            return $next($request);
        }

        return redirect()->away($target, 301);
    }
}
