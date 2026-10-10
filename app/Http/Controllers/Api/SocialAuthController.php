<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SocialIdentityVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    public function store(Request $request, SocialIdentityVerifier $identities): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'in:google,facebook'],
            'idToken' => ['nullable', 'string', 'max:4096'],
            'accessToken' => ['nullable', 'string', 'max:4096'],
        ]);

        try {
            $user = $identities->findOrCreate($identities->profile(
                $validated['provider'],
                $validated['idToken'] ?? '',
                $validated['accessToken'] ?? '',
            ));
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'authenticated' => false,
                'message' => $exception->getMessage(),
                'user' => null,
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('Social sign-in failed', [
                'provider' => $validated['provider'],
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'authenticated' => false,
                'message' => 'Unable to sign in with this account. Please try again.',
                'user' => null,
            ], 502);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'emailVerified' => $user->email_verified_at !== null,
                'roles' => $user->getRoleNames()->values()->all(),
            ],
        ]);
    }
}
