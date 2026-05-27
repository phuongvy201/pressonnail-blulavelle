<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CreatorPortalAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to($this->postLoginUrl(Auth::user()));
        }

        return view('creator.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $this->syncAffiliateLinkForUser(Auth::user());

        return redirect()->to($this->postLoginUrl(Auth::user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('creator.home');
    }

    /**
     * If application is approved but affiliate row missed user_id, link by ref code.
     */
    private function syncAffiliateLinkForUser(\App\Models\User $user): void
    {
        if ($user->hasActiveAffiliate()) {
            return;
        }

        $application = $user->affiliateApplications()
            ->where('status', AffiliateApplication::STATUS_APPROVED)
            ->latest()
            ->first();

        if (! $application) {
            return;
        }

        $affiliate = Affiliate::query()
            ->where('code', strtolower($application->proposed_ref_code))
            ->first();

        if (! $affiliate) {
            return;
        }

        if ($affiliate->user_id !== null && (int) $affiliate->user_id !== (int) $user->id) {
            return;
        }

        $affiliate->update([
            'user_id' => $user->id,
            'is_active' => true,
            'display_name' => $affiliate->display_name ?: $application->full_name,
        ]);
    }

    private function postLoginUrl(\App\Models\User $user): string
    {
        if ($user->canAccessCreatorAffiliateFeatures()) {
            return route('creator.dashboard');
        }

        if ($user->affiliateApplications()->exists()) {
            return route('creator.affiliate.status');
        }

        return route('creator.home');
    }
}
