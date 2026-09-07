<?php

namespace App\Support\ApiV1;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\NewAccessToken;

class TokenIssuer
{
    public function issue(User $user, Request $request, ?string $deviceName = null): array
    {
        $name = $deviceName ?: $request->input('deviceName', config('api_v1.token_name', 'ios-app'));
        $minutes = config('api_v1.token_expiration_minutes');
        $expiresAt = $minutes ? now()->addMinutes((int) $minutes) : null;

        $token = $user->createToken((string) $name, ['*'], $expiresAt);
        $this->stamp($token, $request);

        return [
            'token' => $token->plainTextToken,
            'tokenType' => 'Bearer',
            'expiresAt' => ApiResponse::iso($expiresAt),
            'sessionId' => (string) $token->accessToken->id,
            'deviceName' => (string) $name,
        ];
    }

    public function rotate(User $user, Request $request): array
    {
        $current = $user->currentAccessToken();
        $deviceName = $current->name ?? $request->input('deviceName');
        $payload = $this->issue($user, $request, $deviceName);

        if ($current) {
            $current->delete();
        }

        return $payload;
    }

    private function stamp(NewAccessToken $token, Request $request): void
    {
        $token->accessToken->forceFill([
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
        ])->save();
    }
}
