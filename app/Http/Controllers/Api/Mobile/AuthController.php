<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\RespondsWithMobileJson;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Mobile\GuestCartMerger;
use App\Services\RecaptchaVerifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use RespondsWithMobileJson;

    public function captcha(RecaptchaVerifier $recaptcha): JsonResponse
    {
        return $this->mobileSuccess($recaptcha->config());
    }

    public function register(Request $request, GuestCartMerger $guestCartMerger, RecaptchaVerifier $recaptcha): JsonResponse
    {
        $request->merge([
            'password_confirmation' => $request->input(
                'passwordConfirmation',
                $request->input('password_confirmation')
            ),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'deviceName' => ['nullable', 'string', 'max:120'],
            'recaptchaToken' => ['nullable', 'string'],
        ]);

        $recaptcha->verify($request);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        $this->mergeGuestDataIfPresent($request, $user, $guestCartMerger);

        return $this->issueTokenResponse($user, $request, 'Registration successful! Please check your email to verify your account.');
    }

    public function login(Request $request, GuestCartMerger $guestCartMerger): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'deviceName' => ['nullable', 'string', 'max:120'],
        ]);

        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        /** @var User $user */
        $user = Auth::user();
        Auth::logout();

        $this->mergeGuestDataIfPresent($request, $user, $guestCartMerger);

        return $this->issueTokenResponse($user, $request);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->mobileError('Unauthenticated.', 401);
        }

        return $this->mobileSuccess([
            'authenticated' => true,
            'user' => $this->formatMobileUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return $this->mobileSuccess([
            'authenticated' => false,
            'user' => null,
        ], 'Logged out successfully.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete();
        }

        return $this->mobileSuccess([
            'authenticated' => false,
            'user' => null,
        ], 'All sessions revoked.');
    }

    private function issueTokenResponse(User $user, Request $request, ?string $message = null): JsonResponse
    {
        $deviceName = $request->input('deviceName', 'Mobile App');
        $tokenName = config('mobile_api.token_name', 'mobile-app');

        $expiresAt = null;
        $expirationMinutes = config('mobile_api.token_expiration_minutes');
        if ($expirationMinutes) {
            $expiresAt = now()->addMinutes((int) $expirationMinutes);
        }

        $token = $user->createToken($tokenName, ['*'], $expiresAt);

        return $this->mobileSuccess([
            'authenticated' => true,
            'token' => $token->plainTextToken,
            'tokenType' => 'Bearer',
            'user' => $this->formatMobileUser($user),
        ], $message);
    }

    private function mergeGuestDataIfPresent(Request $request, User $user, GuestCartMerger $guestCartMerger): void
    {
        $header = config('mobile_api.guest_cart_header', 'X-Guest-Cart-Token');
        $guestSessionId = $request->header($header);

        if ($guestSessionId && preg_match('/^[a-zA-Z0-9_-]{32,64}$/', $guestSessionId)) {
            $guestCartMerger->merge($guestSessionId, $user);
        }
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
