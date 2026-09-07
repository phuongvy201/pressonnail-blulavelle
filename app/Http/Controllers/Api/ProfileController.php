<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

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
            'user' => $this->formatUser($user),
            'stats' => [
                'totalOrders' => $user->orders()->count(),
                'totalSpent' => (float) $user->orders()->where('payment_status', 'paid')->sum('total_amount'),
                'wishlistItems' => $user->wishlists()->count(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postalCode' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

        $this->applyAvatarUpload($request, $user, $validated);
        $user->update($this->profileAttributes($user, $validated));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    public function updateAddress(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postalCode' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

        $user->update([
            'address' => array_key_exists('address', $validated) ? $validated['address'] : $user->address,
            'city' => array_key_exists('city', $validated) ? $validated['city'] : $user->city,
            'state' => array_key_exists('state', $validated) ? $validated['state'] : $user->state,
            'postal_code' => $validated['postalCode'] ?? $validated['postal_code'] ?? $user->postal_code,
            'country' => array_key_exists('country', $validated) ? $validated['country'] : $user->country,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->merge([
            'password_confirmation' => $request->input(
                'passwordConfirmation',
                $request->input('password_confirmation')
            ),
        ]);

        $validated = $request->validate([
            'currentPassword' => ['required_without:current_password', 'string'],
            'current_password' => ['required_without:currentPassword', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'passwordConfirmation' => ['nullable', 'string'],
        ]);

        $current = $validated['currentPassword'] ?? $validated['current_password'];

        if (! Hash::check($current, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
                'errors' => [
                    'currentPassword' => ['The provided password does not match your current password.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    private function formatUser($user): array
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

    private function profileAttributes($user, array $validated): array
    {
        return [
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $user->phone,
            'address' => array_key_exists('address', $validated) ? $validated['address'] : $user->address,
            'city' => array_key_exists('city', $validated) ? $validated['city'] : $user->city,
            'state' => array_key_exists('state', $validated) ? $validated['state'] : $user->state,
            'postal_code' => $validated['postalCode'] ?? $validated['postal_code'] ?? $user->postal_code,
            'country' => array_key_exists('country', $validated) ? $validated['country'] : $user->country,
            'avatar' => $validated['avatar'] ?? $user->avatar,
        ];
    }

    private function applyAvatarUpload(Request $request, $user, array &$validated): void
    {
        if (! $request->hasFile('avatar')) {
            return;
        }

        $avatar = $request->file('avatar');

        if ($user->avatar) {
            try {
                $oldFileName = basename(parse_url($user->avatar, PHP_URL_PATH));
                Storage::disk('s3')->delete('avatars/'.$oldFileName);
            } catch (\Throwable) {
                // ignore
            }
        }

        $fileName = time().'_'.Str::random(10).'.'.$avatar->getClientOriginalExtension();
        $path = Storage::disk('s3')->putFileAs('avatars', $avatar, $fileName, 'public');
        $validated['avatar'] = Storage::disk('s3')->url($path);
    }
}
