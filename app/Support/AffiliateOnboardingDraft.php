<?php

namespace App\Support;

use Illuminate\Http\Request;

class AffiliateOnboardingDraft
{
    public const SESSION_KEY = 'affiliate_onboarding_draft';

    /**
     * @return array<string, mixed>|null
     */
    public static function get(Request $request): ?array
    {
        $draft = $request->session()->get(self::SESSION_KEY);

        return is_array($draft) ? $draft : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function put(Request $request, array $data): void
    {
        $request->session()->put(self::SESSION_KEY, $data);
    }

    public static function forget(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public static function has(Request $request): bool
    {
        return $request->session()->has(self::SESSION_KEY);
    }
}
