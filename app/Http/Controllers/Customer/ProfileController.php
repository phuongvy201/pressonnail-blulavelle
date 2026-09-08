<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CustomerAddressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct(private readonly CustomerAddressService $addresses)
    {
    }

    /**
     * Show customer profile
     */
    public function index()
    {
        $user = auth()->user();

        // Get customer statistics
        $stats = [
            'total_orders' => $user->orders()->count(),
            'total_spent' => $user->orders()->where('payment_status', 'paid')->sum('total_amount'),
            'wishlist_items' => $user->wishlists()->count(),
        ];

        return view('customer.profile.index', compact('user', 'stats'));
    }

    /**
     * Show edit profile form
     */
    public function edit()
    {
        $user = auth()->user();
        $addresses = $this->addresses->listFor($user);

        return view('customer.profile.edit', compact('user', 'addresses'));
    }

    /**
     * Update customer profile
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        // Handle avatar upload to AWS S3 (optimized)
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');

            try {
                // Validate file
                if ($avatar->isValid()) {
                    // Delete old avatar from S3 asynchronously (don't wait for it)
                    if ($user->avatar) {
                        try {
                            $oldFileName = basename(parse_url($user->avatar, PHP_URL_PATH));
                            Storage::disk('s3')->delete('avatars/' . $oldFileName);
                        } catch (\Exception $e) {
                            // Ignore deletion errors
                        }
                    }

                    // Generate unique filename
                    $fileName = time() . '_' . Str::random(10) . '.' . $avatar->getClientOriginalExtension();

                    // Upload to AWS S3 with optimized settings
                    $filePath = Storage::disk('s3')->putFileAs(
                        'avatars',
                        $avatar,
                        $fileName,
                        [
                            'visibility' => 'public',
                            'CacheControl' => 'max-age=31536000',
                        ]
                    );

                    if ($filePath) {
                        // Create the correct S3 URL format (giống ProductController)
                        $validated['avatar'] = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $filePath;
                    }
                }
            } catch (\Exception $e) {
                // If S3 upload fails, return error
                return back()->withErrors(['avatar' => 'Failed to upload avatar: ' . $e->getMessage()]);
            }
        }

        $user->update($validated);

        return redirect()->route('customer.profile.index')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = auth()->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.'
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return redirect()->route('customer.profile.index')
            ->with('success', 'Password updated successfully!');
    }

    /**
     * Delete account
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = auth()->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Password is incorrect.'
            ]);
        }

        // Delete avatar from S3 if exists
        if ($user->avatar) {
            try {
                // Extract filename from URL
                $oldFileName = basename(parse_url($user->avatar, PHP_URL_PATH));
                Storage::disk('s3')->delete('avatars/' . $oldFileName);
            } catch (\Exception $e) {
                // Continue even if deletion fails
            }
        }

        // Logout and delete account
        auth()->logout();
        $user->delete();

        return redirect()->route('home')
            ->with('success', 'Your account has been deleted.');
    }
}
