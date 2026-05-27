<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedAffiliate
{
    /**
     * Require an active affiliate profile (post-approval). Does not change the user's ecommerce role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()
                ->route('creator.login')
                ->with('error', 'Please sign in on the creator portal to access your dashboard.');
        }

        if (! $user->canAccessCreatorAffiliateFeatures()) {
            if ($request->expectsJson()) {
                abort(403, 'Creator affiliate access required.');
            }

            return redirect()
                ->route('creator.affiliate.status')
                ->with('error', 'Your creator account is not active yet. If you were approved, ensure you sign in here with the same email used when applying.');
        }

        return $next($request);
    }
}
