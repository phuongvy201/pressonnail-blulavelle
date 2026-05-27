<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\AffiliateOnboardingDraft;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CreatorOnboardingAccountController extends Controller
{
    public function loginForm(Request $request): View|RedirectResponse
    {
        if (! AffiliateOnboardingDraft::has($request)) {
            return redirect()->route('creator.affiliate.apply');
        }

        if (Auth::check()) {
            return redirect()->route('creator.affiliate.apply.account');
        }

        $draft = AffiliateOnboardingDraft::get($request) ?? [];

        return view('creator.affiliate.apply.login', [
            'draft' => $draft,
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! AffiliateOnboardingDraft::has($request)) {
            return redirect()->route('creator.affiliate.apply');
        }

        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('creator.affiliate.apply.verify-email');
        }

        return redirect()->route('creator.affiliate.apply.account');
    }

    public function registerForm(Request $request): View|RedirectResponse
    {
        if (! AffiliateOnboardingDraft::has($request)) {
            return redirect()->route('creator.affiliate.apply');
        }

        if (Auth::check()) {
            return redirect()->route('creator.affiliate.apply.account');
        }

        $draft = AffiliateOnboardingDraft::get($request) ?? [];

        return view('creator.affiliate.apply.register-account', [
            'draft' => $draft,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        if (! AffiliateOnboardingDraft::has($request)) {
            return redirect()->route('creator.affiliate.apply');
        }

        $draft = AffiliateOnboardingDraft::get($request) ?? [];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        // Keep draft; account email is canonical for the application record.
        $draft['full_name'] = $draft['full_name'] ?? $validated['name'];
        AffiliateOnboardingDraft::put($request, $draft);

        $draft['registered_via_onboarding'] = true;
        AffiliateOnboardingDraft::put($request, $draft);

        return redirect()
            ->route('creator.affiliate.apply.verify-email')
            ->with('success', 'Account created. Please verify your email to submit your application.');
    }

    public function resendVerificationEmail(Request $request): RedirectResponse
    {
        if (! AffiliateOnboardingDraft::has($request)) {
            return redirect()->route('creator.affiliate.apply');
        }

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()
                ->route('creator.affiliate.apply.verify-email')
                ->with('success', 'Your email is already verified.');
        }

        $user->sendEmailVerificationNotification();

        return redirect()
            ->route('creator.affiliate.apply.verify-email')
            ->with('status', 'verification-link-sent');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('creator.affiliate.apply.account');
    }
}
