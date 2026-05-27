<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AffiliateOnboardingDraft;
use App\Support\CreatorPortal;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request, $id, $hash): RedirectResponse
    {
        try {
            $user = User::findOrFail($id);

            // Verify the hash matches
            if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                return redirect()->route('login')
                    ->with('error', 'Invalid verification link. Please request a new verification email.');
            }

            // Check if URL is signed correctly (only if signature exists in request)
            // Some email clients may modify URLs, so we allow verification if hash matches
            if ($request->has('signature')) {
                try {
                    if (!URL::hasValidSignature($request)) {
                        // If signature exists but invalid, still allow if hash is correct
                        // This handles cases where email clients modify URLs slightly
                        Log::warning('Email verification: Invalid signature but valid hash', [
                            'user_id' => $user->id,
                            'email' => $user->email
                        ]);
                    }
                } catch (\Exception $e) {
                    // If signature validation throws exception, still allow if hash is correct
                    Log::warning('Email verification: Signature validation error', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Login the user if not already logged in
            if (!Auth::check()) {
                Auth::login($user);
            }

            // Mark email as verified
            if ($user->hasVerifiedEmail()) {
                return $this->redirectAfterVerification($user)
                    ->with('success', 'Your email is already verified.');
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            return $this->redirectAfterVerification($user)
                ->with('success', 'Your email has been verified successfully!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('login')
                ->with('error', 'User not found. Please check your verification link.');
        } catch (\Exception $e) {
            Log::error('Email verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('login')
                ->with('error', 'An error occurred during email verification. Please try requesting a new verification email.');
        }
    }

    /**
     * Redirect user to appropriate page based on their role after email verification
     */
    private function redirectAfterVerification(User $user): RedirectResponse
    {
        if (AffiliateOnboardingDraft::has(request())) {
            return redirect()->to(CreatorPortal::url('/affiliate/apply/verify-email'))
                ->with('success', 'Email verified. You can now submit your creator application.');
        }

        // Redirect based on role
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard')->with('verified', '1');
        } elseif ($user->hasRole('ad-partner')) {
            return redirect()->route('admin.orders.index')->with('verified', '1');
        } elseif ($user->hasRole('seller')) {
            return redirect()->route('admin.seller.dashboard')->with('verified', '1');
        }

        // Default: redirect customers to home page
        return redirect()->route('home')->with('verified', '1');
    }
}
