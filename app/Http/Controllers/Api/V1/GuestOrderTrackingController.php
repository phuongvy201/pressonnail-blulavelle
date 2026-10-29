<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Orders\GuestOrderAccess;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestOrderTrackingController extends Controller
{
    public function __construct(private GuestOrderAccess $access) {}

    /**
     * Legacy entry: order number + email alone no longer returns the order.
     * Clients must request and verify an email OTP, or use a lookup token.
     */
    public function track(Request $request): JsonResponse
    {
        return ApiResponse::error(
            'OTP_REQUIRED',
            'Guest order lookup now requires email verification. Request a code with POST /api/v1/orders/track/otp, then confirm with POST /api/v1/orders/track/verify.',
            422
        );
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orderNumber' => ['required_without:order_number', 'string', 'max:64'],
            'order_number' => ['required_without:orderNumber', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $orderNumber = $validated['orderNumber'] ?? $validated['order_number'];
        $found = $this->access->findByNumberAndEmail($orderNumber, $validated['email']);
        if (isset($found['error'])) {
            return ApiResponse::error('ORDER_NOT_FOUND', $found['error'], $found['status']);
        }

        $sent = $this->access->sendTrackOtp($found['order']);
        if (isset($sent['error'])) {
            return ApiResponse::error('OTP_SEND_FAILED', $sent['error'], $sent['status']);
        }

        return ApiResponse::success([
            'sent' => true,
            'message' => 'If that order exists, a verification code was sent to the email on the order.',
            'expiresInSeconds' => GuestOrderAccess::OTP_TTL_SECONDS,
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orderNumber' => ['required_without:order_number', 'string', 'max:64'],
            'order_number' => ['required_without:orderNumber', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'size:6'],
            'code' => ['nullable', 'string', 'size:6'],
        ]);

        $orderNumber = $validated['orderNumber'] ?? $validated['order_number'];
        $otp = $validated['otp'] ?? $validated['code'] ?? '';
        $result = $this->access->verifyTrackOtp($orderNumber, $validated['email'], $otp);
        if (isset($result['error'])) {
            $code = match ($result['status']) {
                404 => 'ORDER_NOT_FOUND',
                429 => 'OTP_LOCKED',
                default => 'OTP_INVALID',
            };

            return ApiResponse::error($code, $result['error'], $result['status']);
        }

        return ApiResponse::success([
            'order' => $this->access->publicPayload($result['order']),
        ]);
    }

    public function lookup(string $token): JsonResponse
    {
        $order = $this->access->findByLookupToken($token);
        if (! $order) {
            return ApiResponse::error('NOT_FOUND', 'This order lookup link is invalid or expired.', 404);
        }

        return ApiResponse::success([
            'order' => $this->access->publicPayload($order),
        ]);
    }

    public function claim(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error('UNAUTHENTICATED', 'Sign in to claim guest orders.', 401);
        }

        if (! $user->hasVerifiedEmail()) {
            return ApiResponse::error(
                'EMAIL_NOT_VERIFIED',
                'Verify your email before guest orders can be added to this account.',
                403
            );
        }

        $claimed = $this->access->claimForVerifiedUser($user);

        return ApiResponse::success([
            'claimed' => $claimed,
            'message' => $claimed > 0
                ? ($claimed === 1 ? '1 guest order was added to your account.' : "{$claimed} guest orders were added to your account.")
                : 'No guest orders matched your verified email.',
        ]);
    }
}
