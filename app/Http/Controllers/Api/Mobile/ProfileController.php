<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\RespondsWithMobileJson;
use App\Http\Controllers\Api\ProfileController as StorefrontProfileController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    use RespondsWithMobileJson;

    public function show(Request $request, StorefrontProfileController $controller): JsonResponse
    {
        return $controller->show($request);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postalCode' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

        if ($request->hasFile('avatar')) {
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

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'address' => $validated['address'] ?? $user->address,
            'city' => $validated['city'] ?? $user->city,
            'state' => $validated['state'] ?? $user->state,
            'postal_code' => $validated['postalCode'] ?? $validated['postal_code'] ?? $user->postal_code,
            'country' => $validated['country'] ?? $user->country,
            'avatar' => $validated['avatar'] ?? $user->avatar,
        ]);

        return $this->mobileSuccess([
            'user' => $this->formatMobileUser($user->fresh()),
        ], 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'currentPassword' => ['required_without:current_password', 'string'],
            'current_password' => ['required_without:currentPassword', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $current = $validated['currentPassword'] ?? $validated['current_password'];

        if (! Hash::check($current, $user->password)) {
            return $this->mobileError('Current password is incorrect.', 422, [
                'currentPassword' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return $this->mobileSuccess(message: 'Password updated successfully.');
    }
}
