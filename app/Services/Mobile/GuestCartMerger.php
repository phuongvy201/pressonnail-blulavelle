<?php

namespace App\Services\Mobile;

use App\Models\Cart;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Log;

class GuestCartMerger
{
    public function merge(string $guestSessionId, User $user): void
    {
        $this->mergeCart($guestSessionId, $user);
        $this->mergeWishlist($guestSessionId, $user);
    }

    private function mergeCart(string $guestSessionId, User $user): void
    {
        try {
            $sessionCartItems = Cart::where('session_id', $guestSessionId)->get();

            foreach ($sessionCartItems as $item) {
                $existingItem = Cart::where('user_id', $user->id)
                    ->where('product_id', $item->product_id)
                    ->where('selected_variant', $item->selected_variant)
                    ->where('customizations', $item->customizations)
                    ->first();

                if ($existingItem) {
                    $existingItem->increment('quantity', $item->quantity);
                    $item->delete();
                } else {
                    $item->update([
                        'user_id' => $user->id,
                        'session_id' => null,
                    ]);
                }
            }

            if ($sessionCartItems->isNotEmpty()) {
                Log::info('Mobile guest cart merged on auth', [
                    'user_id' => $user->id,
                    'guest_session_id' => $guestSessionId,
                    'items_moved' => $sessionCartItems->count(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Mobile guest cart merge failed', [
                'user_id' => $user->id,
                'guest_session_id' => $guestSessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function mergeWishlist(string $guestSessionId, User $user): void
    {
        try {
            Wishlist::transferSessionToUser($guestSessionId, $user->id);
        } catch (\Throwable $e) {
            Log::error('Mobile guest wishlist merge failed', [
                'user_id' => $user->id,
                'guest_session_id' => $guestSessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
