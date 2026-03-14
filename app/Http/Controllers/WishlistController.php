<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use App\Models\RecentProductView;
use App\Services\PromoCodeSendService;
use App\Services\TikTokEventsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class WishlistController extends Controller
{
    /**
     * Display the wishlist page.
     */
    public function index()
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        $wishlistItems = Wishlist::getWishlistItems($userId, $sessionId, 12);

        // Recently viewed (component có thể load AJAX nếu guest hoặc rỗng)
        $recentlyViewedProducts = collect();
        if (Auth::id()) {
            $recentIds = RecentProductView::getRecentProductIds(Auth::id(), 10, null);
            if ($recentIds->isNotEmpty()) {
                $recentlyViewedProducts = Product::whereIn('id', $recentIds->all())
                    ->availableForDisplay()
                    ->with(['shop', 'template'])
                    ->get()
                    ->sortBy(fn ($p) => array_search($p->id, $recentIds->toArray()));
            }
        }

        return view('wishlist.index', compact('wishlistItems', 'recentlyViewedProducts'));
    }

    /**
     * Add product to wishlist (AJAX).
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $productId = $request->product_id;
        $userId = Auth::id();
        $sessionId = session()->getId();

        // Check if product exists and is active
        $product = Product::where('id', $productId)
            ->where('status', 'active')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or not available.',
            ], 404);
        }

        // Check if already in wishlist
        if (Wishlist::isInWishlist($productId, $userId, $sessionId)) {
            return response()->json([
                'success' => false,
                'message' => 'Product is already in your wishlist.',
            ], 400);
        }

        // Add to wishlist
        $wishlist = Wishlist::addToWishlist($productId, $userId, $sessionId);

        if ($wishlist) {
            $this->trackTikTokWishlistAdd($request, $product);

            // Gửi email promo cho user đã đăng nhập (throttle 24h)
            if ($userId) {
                $user = Auth::user();
                if ($user && $user->email) {
                    app(PromoCodeSendService::class)->sendForTrigger(
                        $user->email,
                        PromoCodeSendService::TRIGGER_WISHLIST,
                        $userId,
                        true
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Product added to wishlist successfully.',
                'wishlist_count' => $this->getWishlistCount($userId, $sessionId),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to add product to wishlist.',
        ], 500);
    }

    /**
     * Remove product from wishlist (AJAX).
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $productId = $request->product_id;
        $userId = Auth::id();
        $sessionId = session()->getId();

        $removed = Wishlist::removeFromWishlist($productId, $userId, $sessionId);

        if ($removed) {
            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist successfully.',
                'wishlist_count' => $this->getWishlistCount($userId, $sessionId),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found in wishlist.',
        ], 404);
    }

    /**
     * Toggle product in wishlist (AJAX).
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $productId = $request->product_id;
        $userId = Auth::id();
        $sessionId = session()->getId();

        // Check if product exists and is active
        $product = Product::where('id', $productId)
            ->where('status', 'active')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or not available.',
            ], 404);
        }

        $isInWishlist = Wishlist::isInWishlist($productId, $userId, $sessionId);

        if ($isInWishlist) {
            // Remove from wishlist
            $removed = Wishlist::removeFromWishlist($productId, $userId, $sessionId);
            $action = 'removed';
            $message = 'Product removed from wishlist successfully.';
        } else {
            // Add to wishlist
            $added = Wishlist::addToWishlist($productId, $userId, $sessionId);
            $action = 'added';
            $message = 'Product added to wishlist successfully.';
            if ($added) {
                $this->trackTikTokWishlistAdd($request, $product);
            }
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'message' => $message,
            'wishlist_count' => $this->getWishlistCount($userId, $sessionId),
        ]);
    }

    /**
     * Get wishlist count (AJAX).
     */
    public function count(): JsonResponse
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        return response()->json([
            'success' => true,
            'count' => $this->getWishlistCount($userId, $sessionId),
        ]);
    }

    /**
     * Check if products are in wishlist (AJAX).
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $userId = Auth::id();
        $sessionId = session()->getId();
        $productIds = $request->product_ids;

        $query = Wishlist::whereIn('product_id', $productIds);

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $wishlistItems = $query->pluck('product_id')->toArray();

        return response()->json([
            'success' => true,
            'wishlist_items' => $wishlistItems,
        ]);
    }

    /**
     * Clear entire wishlist.
     */
    public function clear(): JsonResponse
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        $query = Wishlist::query();

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $deleted = $query->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wishlist cleared successfully.',
            'wishlist_count' => 0,
        ]);
    }

    /**
     * Get wishlist count for user or session.
     */
    private function getWishlistCount($userId = null, $sessionId = null): int
    {
        $query = Wishlist::query();

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        return $query->count();
    }

    private function trackTikTokWishlistAdd(Request $request, Product $product): void
    {
        /** @var TikTokEventsService $tikTok */
        $tikTok = app(TikTokEventsService::class);

        if (!$tikTok->enabled()) {
            return;
        }

        $user = Auth::user();

        $tikTok->track(
            'AddToWishlist',
            [
                'value' => round($product->price ?? $product->base_price ?? 0, 2),
                'currency' => 'USD',
                'content_type' => 'product',
                'content_id' => (string) $product->id,
                'content_name' => $product->name,
                'contents' => [[
                    'content_id' => (string) $product->id,
                    'content_type' => 'product',
                    'content_name' => $product->name,
                    'price' => round($product->price ?? $product->base_price ?? 0, 2),
                    'quantity' => 1,
                ]],
            ],
            $request,
            [
                'email' => $user?->email,
                'phone' => $user?->phone,
                'external_id' => $user?->id,
            ]
        );
    }

    /**
     * Transfer session wishlist to user when they log in.
     */
    public function transferSessionToUser()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $sessionId = session()->getId();

            Wishlist::transferSessionToUser($sessionId, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Wishlist transferred successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not authenticated.',
        ], 401);
    }
}
