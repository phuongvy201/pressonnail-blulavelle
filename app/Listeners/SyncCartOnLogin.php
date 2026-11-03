<?php

namespace App\Listeners;

use App\Models\Cart;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SyncCartOnLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        try {
            $user = $event->user;
            // Login regenerates the session id before this listener runs.
            // RememberCartSession stored the id that still owns the guest cart.
            $remembered = request()->attributes->get('cart_session_id');
            $sessionId = is_string($remembered) && $remembered !== ''
                ? $remembered
                : session()->getId();

            // Move session-based cart items to user-based
            $sessionCartItems = Cart::where('session_id', $sessionId)->get();

            foreach ($sessionCartItems as $item) {
                // Check if user already has this item
                $existingItem = Cart::where('user_id', $user->id)
                    ->where('product_id', $item->product_id)
                    ->where('selected_variant', $item->selected_variant)
                    ->where('customizations', $item->customizations)
                    ->first();

                if ($existingItem) {
                    // Update quantity
                    $existingItem->increment('quantity', $item->quantity);
                    $item->delete(); // Remove session item
                } else {
                    // Transfer to user
                    $item->update([
                        'user_id' => $user->id,
                        'session_id' => null
                    ]);
                }
            }

            Log::info('Cart synced on login', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'items_moved' => $sessionCartItems->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncing cart on login', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id ?? null
            ]);
        }
    }
}
