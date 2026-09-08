<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Mobile\GuestCartMerger;
use App\Services\RecaptchaVerifier;
use App\Support\ApiV1\ApiResponse;
use App\Support\ApiV1\TokenIssuer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function csrf(): JsonResponse
    {
        return ApiResponse::success([
            'authMode' => 'bearer',
            'csrfRequired' => false,
            'csrfToken' => null,
            'tokenType' => 'Bearer',
            'tokenTtlSeconds' => ((int) config('api_v1.token_expiration_minutes', 43200)) * 60,
            'notes' => 'iOS uses Authorization: Bearer. Do not scrape HTML for CSRF. Send X-Request-ID and Idempotency-Key on mutations that create orders or charges.',
        ]);
    }

    public function captcha(RecaptchaVerifier $recaptcha): JsonResponse
    {
        return ApiResponse::success($recaptcha->config());
    }

    public function register(Request $request, TokenIssuer $tokens, GuestCartMerger $merger, RecaptchaVerifier $recaptcha): JsonResponse
    {
        $request->merge([
            'password_confirmation' => $request->input('passwordConfirmation', $request->input('password_confirmation')),
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
            'password' => $validated['password'],
        ]);

        event(new Registered($user));
        $this->mergeGuest($request, $user, $merger);

        return ApiResponse::success([
            'token' => $tokens->issue($user, $request, $validated['deviceName'] ?? null),
            'user' => $this->userPayload($user),
        ], null, 201);
    }

    public function login(Request $request, TokenIssuer $tokens, GuestCartMerger $merger): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'deviceName' => ['nullable', 'string', 'max:120'],
        ]);

        $this->ensureLoginNotRateLimited($request);

        $user = User::where('email', strtolower($request->input('email')))->first();

        if (! $user || $user->anonymized_at || ! Hash::check($request->input('password'), (string) $user->password)) {
            RateLimiter::hit($this->loginKey($request));

            return ApiResponse::error('INVALID_CREDENTIALS', 'These credentials do not match our records.', 401);
        }

        RateLimiter::clear($this->loginKey($request));
        $this->mergeGuest($request, $user, $merger);

        return ApiResponse::success([
            'token' => $tokens->issue($user, $request, $request->input('deviceName')),
            'user' => $this->userPayload($user),
        ]);
    }

    public function refresh(Request $request, TokenIssuer $tokens): JsonResponse
    {
        return ApiResponse::success([
            'token' => $tokens->rotate($request->user(), $request),
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function revoke(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        $token?->delete();

        return ApiResponse::success(['revoked' => true]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return ApiResponse::success([
            'sent' => true,
            'message' => 'If an account exists for that email, a reset link has been sent.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->merge([
            'password_confirmation' => $request->input('passwordConfirmation', $request->input('password_confirmation')),
        ]);

        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => $request->password,
                    'remember_token' => Str::random(60),
                ])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error('RESET_FAILED', 'The reset token is invalid or expired.', 422, [
                'token' => [__($status)],
            ]);
        }

        return ApiResponse::success(['reset' => true]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->merge([
            'password_confirmation' => $request->input('passwordConfirmation', $request->input('password_confirmation')),
        ]);

        $validated = $request->validate([
            'currentPassword' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['currentPassword'], (string) $user->password)) {
            return ApiResponse::error('CURRENT_PASSWORD_INVALID', 'Current password is incorrect.', 422, [
                'currentPassword' => ['Current password is incorrect.'],
            ]);
        }

        $user->update(['password' => $validated['password']]);

        $currentId = $user->currentAccessToken()?->id;
        $user->tokens()->where('id', '!=', $currentId)->delete();

        return ApiResponse::success(['changed' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public static function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'emailVerified' => $user->email_verified_at !== null,
            'createdAt' => ApiResponse::iso($user->created_at),
            'updatedAt' => ApiResponse::iso($user->updated_at),
        ];
    }

    private function mergeGuest(Request $request, User $user, GuestCartMerger $merger): void
    {
        $header = config('api_v1.guest_cart_header', 'X-Guest-Cart-Token');
        $token = $request->header($header);
        if ($token && preg_match('/^[a-zA-Z0-9_-]{32,64}$/', $token)) {
            $merger->merge($token, $user);
        }
    }

    private function ensureLoginNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->loginKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->loginKey($request));

        throw ValidationException::withMessages([
            'email' => [trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])],
        ]);
    }

    private function loginKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|api-v1|'.$request->ip());
    }
}
