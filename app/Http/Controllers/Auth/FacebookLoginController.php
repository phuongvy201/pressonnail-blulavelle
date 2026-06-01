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

class FacebookLoginController extends Controller
{
    /**
     * Redirect user to Facebook OAuth provider
     */
    public function redirectToFacebook(): RedirectResponse
    {
        return Socialite::driver('facebook')->redirect();
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function handleFacebookCallback(Request $request): RedirectResponse
    {
        try {
            $facebookUser = Socialite::driver('facebook')->user();

            // Check if user already exists
            $user = User::where('email', $facebookUser->getEmail())
                ->orWhere('facebook_id', $facebookUser->getId())
                ->first();

            if ($user) {
                // User exists, update Facebook ID if not set
                if (!$user->facebook_id) {
                    $user->facebook_id = $facebookUser->getId();
                    $user->save();
                }

                // Update avatar if available and not set
                if ($facebookUser->getAvatar() && !$user->avatar) {
                    $user->avatar = $facebookUser->getAvatar();
                    $user->save();
                }

                // Update name if changed
                if ($facebookUser->getName() && $user->name !== $facebookUser->getName()) {
                    $user->name = $facebookUser->getName();
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
                            'content_name' => 'Facebook Login',
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
                // Note: Facebook may not always provide email, handle this case
                $email = $facebookUser->getEmail();
                if (!$email) {
                    return redirect()->route('register')
                        ->with('error', 'Facebook account does not have an email address. Please register with email.');
                }

                $user = User::create([
                    'name' => $facebookUser->getName(),
                    'email' => $email,
                    'facebook_id' => $facebookUser->getId(),
                    'avatar' => $facebookUser->getAvatar(),
                    'email_verified_at' => now(), // Facebook emails are usually verified
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
                            'content_name' => 'Facebook Registration',
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
            Log::error('Facebook OAuth error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('login')
                ->with('error', 'Unable to login with Facebook. Please try again.');
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
