<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WishlistController as StorefrontWishlistController;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        $items = Wishlist::getWishlistItems($userId, $sessionId, 50);

        return response()->json([
            'success' => true,
            'items' => $items->map(function ($wishlistItem) {
                $product = $wishlistItem->product;

                return [
                    'id' => $wishlistItem->id,
                    'productId' => $wishlistItem->product_id,
                    'addedAt' => $wishlistItem->created_at?->toIso8601String(),
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => (float) $product->getEffectivePrice(),
                        'primaryImage' => $this->resolveProductImage($product),
                        'inStock' => $product->hasStock(),
                    ] : null,
                ];
            })->values()->all(),
            'count' => $items->count(),
        ]);
    }

    public function add(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->add($request);
    }

    public function remove(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->remove($request);
    }

    public function toggle(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->toggle($request);
    }

    public function count(StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->count();
    }

    public function check(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->check($request);
    }

    public function clear(StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->clear();
    }

    private function resolveProductImage(Product $product): ?string
    {
        $image = $product->primary_image;

        if (is_string($image)) {
            return $image;
        }

        if (is_array($image)) {
            return $image['url'] ?? $image['src'] ?? null;
        }

        return null;
    }
}
