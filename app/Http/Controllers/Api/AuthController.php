<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\TikTokEventsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->merge([
            'password_confirmation' => $request->input(
                'passwordConfirmation',
                $request->input('password_confirmation')
            ),
            'g-recaptcha-response' => $request->input(
                'recaptchaToken',
                $request->input('g-recaptcha-response')
            ),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $this->validateRecaptcha($request);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        $tikTok = app(TikTokEventsService::class);
        if ($tikTok->enabled()) {
            $tikTok->track(
                'CompleteRegistration',
                [
                    'value' => 0,
                    'currency' => 'USD',
                    'content_type' => 'user',
                    'content_id' => (string) $user->id,
                    'content_name' => 'Account Registration',
                ],
                $request,
                [
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'external_id' => $user->id,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'message' => 'Registration successful! Please check your email to verify your account.',
            'user' => $this->formatUser($user),
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'user' => $this->formatUser(Auth::user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'authenticated' => false,
            'user' => null,
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => true,
                'authenticated' => false,
                'user' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'user' => $this->formatUser($user),
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'emailVerified' => $user->email_verified_at !== null,
            'roles' => $user->getRoleNames()->values()->all(),
        ];
    }

    private function validateRecaptcha(Request $request): void
    {
        $secretKey = config('services.recaptcha.secret_key');

        if (! $secretKey) {
            return;
        }

        $token = $request->input('g-recaptcha-response');
        if (! $token) {
            throw ValidationException::withMessages([
                'recaptchaToken' => [__('Please complete the security check.')],
            ]);
        }

        try {
            $response = Http::asForm()->post(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]
            );

            $data = $response->json();

            if (! ($data['success'] ?? false)) {
                throw ValidationException::withMessages([
                    'recaptchaToken' => [__('Security verification failed, please try again.')],
                ]);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'recaptchaToken' => [__('Unable to verify security, please try again.')],
            ]);
        }
    }
}
