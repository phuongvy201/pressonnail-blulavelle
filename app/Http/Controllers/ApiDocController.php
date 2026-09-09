<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Services\RecaptchaVerifier;
use Illuminate\Http\Request;

class ApiDocController extends Controller
{
    /**
     * Show API token dashboard (requires admin authentication)
     */
    public function tokenDashboard()
    {
        // Get active API tokens
        $tokens = ApiToken::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $recaptcha = app(RecaptchaVerifier::class)->config();

        return view('api.token-dashboard', compact('tokens', 'recaptcha'));
    }

    /**
     * Show API documentation (requires admin authentication)
     */
    public function documentation()
    {
        return view('api.documentation');
    }
}
