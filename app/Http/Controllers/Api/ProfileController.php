<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Services\CustomerAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct(private readonly CustomerAddressService $addresses)
    {
    }

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

    /**
     * Update personal profile only (name, email, phone, avatar).
     * Shipping addresses use PUT /api/profile/address or /api/v1/addresses.
     */
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
        ]);

        $this->applyAvatarUpload($request, $user, $validated);

        $user->update([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $user->phone,
            'avatar' => $validated['avatar'] ?? $user->avatar,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    /**
     * Create/update the default shipping address in the address book.
     */
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
            'recipientName' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'line1' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'stateProvince' => ['nullable', 'string', 'max:100'],
            'state_province' => ['nullable', 'string', 'max:100'],
            'postalCode' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'countryCode' => ['nullable', 'string', 'max:2'],
            'country_code' => ['nullable', 'string', 'max:2'],
            'label' => ['nullable', 'string', 'max:40'],
        ]);

        $line1 = $validated['line1'] ?? $validated['address'] ?? null;
        $state = $validated['stateProvince'] ?? $validated['state_province'] ?? $validated['state'] ?? null;
        $postal = $validated['postalCode'] ?? $validated['postal_code'] ?? null;
        $countryRaw = $validated['countryCode'] ?? $validated['country_code'] ?? $validated['country'] ?? null;
        $countryCode = $this->normalizeCountryCode($countryRaw);

        $existing = $this->addresses->defaultShipping($user);

        $payload = array_filter([
            'recipient_name' => $validated['recipientName'] ?? $validated['recipient_name'] ?? $existing?->recipient_name ?? $user->name,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : ($existing?->phone ?? $user->phone),
            'line1' => $line1 ?? $existing?->line1,
            'line2' => array_key_exists('line2', $validated) ? $validated['line2'] : $existing?->line2,
            'city' => array_key_exists('city', $validated) ? $validated['city'] : $existing?->city,
            'state_province' => $state ?? $existing?->state_province,
            'postal_code' => $postal ?? $existing?->postal_code,
            'country_code' => $countryCode ?? $existing?->country_code,
            'label' => array_key_exists('label', $validated) ? $validated['label'] : ($existing?->label ?? 'Default'),
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ], static fn ($value) => $value !== null && $value !== '');

        if (empty($payload['line1']) || empty($payload['city']) || empty($payload['postal_code']) || empty($payload['country_code'])) {
            return response()->json([
                'success' => false,
                'message' => 'Address requires line1/address, city, postalCode, and countryCode (or country).',
                'errors' => [
                    'address' => ['Provide street, city, postal code, and country.'],
                ],
            ], 422);
        }

        if ($existing) {
            $address = $this->addresses->update($user, $existing, $payload);
        } else {
            $address = $this->addresses->create($user, $payload);
        }

        $this->syncLegacyUserAddress($user, $address);

        return response()->json([
            'success' => true,
            'message' => 'Default shipping address updated.',
            'user' => $this->formatUser($user->fresh()),
            'defaultAddress' => $this->formatAddress($address),
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
        $default = $this->addresses->defaultShipping($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            // Flat fields mirror default address (backward compatible for older clients).
            'address' => $default?->line1 ?? $user->address,
            'city' => $default?->city ?? $user->city,
            'state' => $default?->state_province ?? $user->state,
            'postalCode' => $default?->postal_code ?? $user->postal_code,
            'country' => $default?->country_code ?? $user->country,
            'defaultAddress' => $default ? $this->formatAddress($default) : null,
            'emailVerified' => $user->email_verified_at !== null,
            'roles' => $user->getRoleNames()->values()->all(),
        ];
    }

    private function formatAddress(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'recipientName' => $address->recipient_name,
            'phone' => $address->phone,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'city' => $address->city,
            'stateProvince' => $address->state_province,
            'postalCode' => $address->postal_code,
            'countryCode' => $address->country_code,
            'label' => $address->label,
            'isDefaultShipping' => $address->is_default_shipping,
            'isDefaultBilling' => $address->is_default_billing,
        ];
    }

    private function normalizeCountryCode(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $value = trim((string) $raw);
        if (strlen($value) === 2) {
            return strtoupper($value);
        }

        $map = [
            'united states' => 'US',
            'usa' => 'US',
            'united kingdom' => 'GB',
            'uk' => 'GB',
            'canada' => 'CA',
            'australia' => 'AU',
            'vietnam' => 'VN',
            'viet nam' => 'VN',
        ];

        return $map[strtolower($value)] ?? strtoupper(substr($value, 0, 2));
    }

    private function syncLegacyUserAddress($user, CustomerAddress $address): void
    {
        $user->forceFill([
            'address' => $address->line1,
            'city' => $address->city,
            'state' => $address->state_province,
            'postal_code' => $address->postal_code,
            'country' => $address->country_code,
        ])->save();
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
