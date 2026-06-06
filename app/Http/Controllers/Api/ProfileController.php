<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
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
            ],
            'stats' => [
                'totalOrders' => $user->orders()->count(),
                'totalSpent' => (float) $user->orders()->where('payment_status', 'paid')->sum('total_amount'),
                'wishlistItems' => $user->wishlists()->count(),
            ],
        ]);
    }
}
