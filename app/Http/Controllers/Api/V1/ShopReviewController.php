<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopReviewController extends Controller
{
    public function index(int $shopId, Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:48'],
            'excludeProductId' => ['sometimes', 'integer', 'min:1'],
        ]);

        $shop = Shop::query()->active()->findOrFail($shopId);
        $productIds = Product::query()->where('shop_id', $shop->id)->select('id');
        $query = Review::query()
            ->approved()
            ->whereIn('product_id', $productIds)
            ->with(['product:id,name,slug', 'user:id,name'])
            ->orderByDesc('created_at');

        if (! empty($filters['excludeProductId'])) {
            $query->where('product_id', '!=', (int) $filters['excludeProductId']);
        }

        $reviews = $query->paginate(
            (int) ($filters['perPage'] ?? 12),
            ['*'],
            'page',
            (int) ($filters['page'] ?? 1)
        );

        return response()->json([
            'success' => true,
            'meta' => [
                'page' => $reviews->currentPage(),
                'perPage' => $reviews->perPage(),
                'total' => $reviews->total(),
                'lastPage' => $reviews->lastPage(),
                'hasNextPage' => $reviews->hasMorePages(),
                'hasPreviousPage' => $reviews->currentPage() > 1,
            ],
            'items' => $reviews->getCollection()->map(fn (Review $review) => [
                'id' => $review->id,
                'customer_name' => $review->user?->name ?: $review->customer_name,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'review_text' => $review->review_text,
                'image_url' => $review->image_url,
                'is_verified_purchase' => (bool) $review->is_verified_purchase,
                'created_at' => $review->created_at?->toIso8601String(),
                'product_name' => $review->product?->name,
                'product_slug' => $review->product?->slug,
            ])->values(),
        ]);
    }
}
