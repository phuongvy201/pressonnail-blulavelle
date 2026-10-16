<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExpoPushService;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PushTokenController extends Controller
{
    public function __construct(private readonly ExpoPushService $push)
    {
    }

    public function store(Request $request): JsonResponse
    {
        if (! Schema::hasTable('device_push_tokens')) {
            return ApiResponse::error('PUSH_UNAVAILABLE', 'Push registration is not ready on this server yet.', 503);
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:191', 'regex:/^ExponentPushToken\[.+\]$/'],
            'platform' => ['nullable', 'in:ios,android,web'],
            'appVersion' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $user = $request->user();
        $guestKey = $this->guestDeviceKey($request);
        if (! $user && $guestKey === null) {
            return ApiResponse::error('PUSH_IDENTITY', 'Sign in or open the app with a device key before registering push.', 422);
        }

        $email = $validated['email'] ?? $user?->email;
        $cartToken = $this->guestCartToken($request);

        $row = $this->push->register(
            $validated['token'],
            $user?->id,
            $user ? null : $guestKey,
            $validated['platform'] ?? null,
            $validated['appVersion'] ?? null,
            is_string($email) ? $email : null,
            $cartToken,
        );

        return ApiResponse::success([
            'registered' => true,
            'tokenId' => $row->id,
        ], null, 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        if (! Schema::hasTable('device_push_tokens')) {
            return ApiResponse::success(['registered' => false]);
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:191'],
        ]);

        $this->push->unregister(
            $validated['token'],
            $request->user()?->id,
            $this->guestDeviceKey($request),
        );

        return ApiResponse::success(['registered' => false]);
    }

    private function guestDeviceKey(Request $request): ?string
    {
        $token = $request->header('X-Live-Chat-Guest') ?? $request->header('X-Device-Id');
        if (! is_string($token) || preg_match('/^[A-Za-z0-9_-]{32,64}$/', $token) !== 1) {
            return null;
        }

        return $token;
    }

    private function guestCartToken(Request $request): ?string
    {
        $header = (string) config('api_v1.guest_cart_header', 'X-Guest-Cart-Token');
        $token = $request->header($header);
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        return trim($token);
    }
}
