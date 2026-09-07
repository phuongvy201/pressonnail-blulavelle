<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CheckoutAttempt;
use App\Services\CheckoutAttemptService;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutAttemptController extends Controller
{
    public function store(Request $request, CheckoutAttemptService $service): JsonResponse
    {
        return $service->createOrResume($request);
    }

    public function show(Request $request, string $attemptId, CheckoutAttemptService $service): JsonResponse
    {
        $attempt = $this->owned($request, $attemptId);

        return $service->show($attempt);
    }

    public function confirm(Request $request, string $attemptId, CheckoutAttemptService $service): JsonResponse
    {
        $attempt = $this->owned($request, $attemptId);

        return $service->confirm($request, $attempt);
    }

    public function retry(Request $request, string $attemptId, CheckoutAttemptService $service): JsonResponse
    {
        $attempt = $this->owned($request, $attemptId);

        return $service->retry($request, $attempt);
    }

    private function owned(Request $request, string $attemptId): CheckoutAttempt
    {
        $attempt = CheckoutAttempt::query()->where('id', $attemptId)->first();

        if (! $attempt) {
            abort(404, 'Checkout attempt not found.');
        }

        $userId = $request->user()?->id;
        $guest = $request->header(config('api_v1.guest_cart_header', 'X-Guest-Cart-Token'));

        $owns = ($userId && (int) $attempt->user_id === (int) $userId)
            || ($guest && $attempt->guest_token && hash_equals($attempt->guest_token, $guest));

        if (! $owns) {
            abort(403, 'You cannot access this checkout attempt.');
        }

        return $attempt;
    }
}
