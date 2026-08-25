<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bridges checkout session keys (promo, gift card, discount mode) between Laravel session
 * and cache for authenticated mobile users (no cookie persistence on native apps).
 */
class MobileCartSessionBridge
{
    private const STATE_KEYS = [
        'discount_mode',
        'applied_promo_code_id',
        'applied_promo_code',
        'applied_gift_card_code',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $cached = Cache::get($this->cacheKey($user->id), []);
            foreach ($cached as $key => $value) {
                session([$key => $value]);
            }
        }

        $response = $next($request);

        if ($user) {
            $state = [];
            foreach (self::STATE_KEYS as $key) {
                if (session()->has($key)) {
                    $state[$key] = session($key);
                }
            }

            $ttlDays = (int) config('mobile_api.checkout_state_ttl_days', 30);
            Cache::put($this->cacheKey($user->id), $state, now()->addDays($ttlDays));
        }

        return $response;
    }

    private function cacheKey(int $userId): string
    {
        return 'mobile_checkout_state_'.$userId;
    }
}
