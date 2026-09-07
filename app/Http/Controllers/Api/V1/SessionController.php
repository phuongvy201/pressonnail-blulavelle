<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentId = $request->user()->currentAccessToken()?->id;

        $sessions = $request->user()->tokens()->orderByDesc('last_used_at')->orderByDesc('id')->get()
            ->map(fn ($token) => [
                'sessionId' => (string) $token->id,
                'deviceName' => $token->name,
                'ipAddress' => $token->ip_address,
                'userAgent' => $token->user_agent,
                'lastUsedAt' => ApiResponse::iso($token->last_used_at),
                'createdAt' => ApiResponse::iso($token->created_at),
                'expiresAt' => ApiResponse::iso($token->expires_at),
                'isCurrent' => $token->id === $currentId,
            ])
            ->values()
            ->all();

        return ApiResponse::success($sessions, ['count' => count($sessions)]);
    }

    public function destroy(Request $request, string $sessionId): JsonResponse
    {
        $token = $request->user()->tokens()->where('id', $sessionId)->first();

        if (! $token) {
            return ApiResponse::error('NOT_FOUND', 'Session not found.', 404);
        }

        $token->delete();

        return ApiResponse::success(['revoked' => true, 'sessionId' => $sessionId]);
    }
}
