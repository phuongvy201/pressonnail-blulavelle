<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function export(Request $request): JsonResponse
    {
        $user = $request->user()->load(['orders.items', 'wishlists', 'addresses']);

        $data = [
            'exportedAt' => ApiResponse::iso(now()),
            'user' => AuthController::userPayload($user),
            'addresses' => $user->addresses->map(fn ($a) => AddressController::toPayload($a))->values()->all(),
            'orders' => $user->orders->map(fn ($order) => [
                'orderNumber' => $order->order_number,
                'status' => $order->status,
                'paymentStatus' => $order->payment_status,
                'totalAmount' => ApiResponse::toMinor($order->total_amount),
                'currency' => $order->currency,
                'createdAt' => ApiResponse::iso($order->created_at),
            ])->values()->all(),
            'wishlistProductIds' => $user->wishlists->pluck('product_id')->values()->all(),
            'retention' => [
                'ordersKeptForLegal' => true,
                'note' => 'Order records are retained for accounting and legal obligations even after account deletion.',
            ],
        ];

        return ApiResponse::success($data);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'accepted'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['password'], (string) $user->password)) {
            return ApiResponse::error('CURRENT_PASSWORD_INVALID', 'Password is incorrect.', 422, [
                'password' => ['Password is incorrect.'],
            ]);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->addresses()->delete();
            $user->wishlists()->delete();
            \App\Models\Cart::where('user_id', $user->id)->delete();

            $user->forceFill([
                'name' => 'Deleted User',
                'email' => 'deleted+'.$user->id.'@invalid.local',
                'phone' => null,
                'avatar' => null,
                'address' => null,
                'city' => null,
                'state' => null,
                'postal_code' => null,
                'country' => null,
                'google_id' => null,
                'facebook_id' => null,
                'password' => Str::random(40),
                'anonymized_at' => now(),
            ])->save();

            $user->delete();
        });

        return ApiResponse::success([
            'deleted' => true,
            'status' => 'completed',
            'retained' => [
                'orders' => 'Kept for accounting and legal records. Customer PII on the account is anonymized.',
                'payments' => 'Gateway transaction IDs remain on orders.',
            ],
            'removed' => [
                'profile',
                'addresses',
                'wishlist',
                'cart',
                'sessions',
            ],
        ]);
    }
}
