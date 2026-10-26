<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => AuthController::userPayload($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'stylePreferences' => ['sometimes', 'array'],
            'stylePreferences.styles' => ['sometimes', 'array'],
            'stylePreferences.styles.*' => ['string', Rule::in(config('style_preferences.styles', []))],
            'stylePreferences.shapes' => ['sometimes', 'array'],
            'stylePreferences.shapes.*' => ['string', Rule::in(config('style_preferences.shapes', []))],
        ]);

        if (array_key_exists('stylePreferences', $validated)) {
            $user->style_preferences = [
                'styles' => array_values($validated['stylePreferences']['styles'] ?? []),
                'shapes' => array_values($validated['stylePreferences']['shapes'] ?? []),
            ];
            unset($validated['stylePreferences']);
        }

        $user->fill($validated);
        $user->save();

        return ApiResponse::success([
            'user' => AuthController::userPayload($user->fresh()),
        ]);
    }
}
