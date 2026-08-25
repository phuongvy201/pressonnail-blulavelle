<?php

namespace App\Http\Controllers\Api\Mobile\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsWithMobileJson
{
    protected function mobileSuccess(mixed $data = [], ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if (is_array($data)) {
            $payload = array_merge($payload, $data);
        } else {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    protected function mobileError(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    protected function formatMobileUser(\App\Models\User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'address' => $user->address,
            'city' => $user->city,
            'state' => $user->state,
            'postalCode' => $user->postal_code,
            'country' => $user->country,
            'emailVerified' => $user->email_verified_at !== null,
            'roles' => $user->getRoleNames()->values()->all(),
        ];
    }
}
