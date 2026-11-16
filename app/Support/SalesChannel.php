<?php

namespace App\Support;

use Illuminate\Http\Request;

class SalesChannel
{
    public const WEBSITE = 'website';

    public const APP = 'app';

    public static function fromCheckoutRequest(Request $request): string
    {
        if ($request->is('api/*') || $request->input('payment_ui') === 'checkout_session') {
            return self::APP;
        }

        return self::WEBSITE;
    }

    public static function label(?string $channel): string
    {
        return $channel === self::APP ? 'App' : 'Website';
    }

    public static function placedPhrase(?string $channel): string
    {
        return $channel === self::APP
            ? 'Placed in the BluLavelle app'
            : 'Placed on the BluLavelle website';
    }
}
