<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TikTokEventsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    /**
     * Redirect user to Google OAuth provider
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Check if user already exists
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // User exists, check if they have Google ID
                if (!$user->google_id) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                }

                // Update avatar if available
                if ($googleUser->getAvatar() && !$user->avatar) {
                    $user->avatar = $googleUser->getAvatar();
                    $user->save();
                }

                Auth::login($user, true); // Remember user
                $request->session()->regenerate();

                // Track TikTok event if enabled
                $tikTok = app(TikTokEventsService::class);
                if ($tikTok->enabled()) {
                    $tikTok->track(
                        'CompleteRegistration',
                        [
                            'value' => 0,
                            'currency' => 'USD',
                            'content_type' => 'user',
                            'content_id' => (string) $user->id,
                            'content_name' => 'Google Login',
                        ],
                        $request,
                        [
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'external_id' => $user->id,
                        ]
                    );
                }

                return $this->redirectAfterLogin($user);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(), // Google emails are already verified
                    'password' => Hash::make(uniqid('', true)), // Random password since using OAuth
                ]);

                // Assign customer role by default
                if (!$user->hasAnyRole(['admin', 'seller', 'ad-partner'])) {
                    $user->assignRole('customer');
                }

                Auth::login($user, true);
                $request->session()->regenerate();

                // Track TikTok event
                $tikTok = app(TikTokEventsService::class);
                if ($tikTok->enabled()) {
                    $tikTok->track(
                        'CompleteRegistration',
                        [
                            'value' => 0,
                            'currency' => 'USD',
                            'content_type' => 'user',
                            'content_id' => (string) $user->id,
                            'content_name' => 'Google Registration',
                        ],
                        $request,
                        [
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'external_id' => $user->id,
                        ]
                    );
                }

                return $this->redirectAfterLogin($user)
                    ->with('success', 'Registration successful! Welcome to Bluprinter.');
            }
        } catch (\Exception $e) {
            Log::error('Google OAuth error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('login')
                ->with('error', 'Unable to login with Google. Please try again.');
        }
    }

    /**
     * Redirect user to appropriate page based on their role
     */
    private function redirectAfterLogin(User $user): RedirectResponse
    {
        // Redirect based on role
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('ad-partner')) {
            return redirect()->route('admin.orders.index');
        } elseif ($user->hasRole('seller')) {
            return redirect()->route('admin.seller.dashboard');
        }

        // Default: redirect customers to home page
        return redirect()->route('home');
    }
}
