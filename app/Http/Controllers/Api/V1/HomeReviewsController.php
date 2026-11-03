<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ProductController;
use App\Models\Review;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;

class HomeReviewsController
{
    public function show(ProductController $products): JsonResponse
    {
        $reviews = Review::query()
            ->approved()
            ->pinnedToHome()
            ->with(['product.shop', 'product.template.category', 'product.variants', 'user:id,name'])
            ->whereHas('product', fn ($query) => $query->availableForDisplay())
            ->orderByDesc('created_at')
            ->limit(9)
            ->get()
            ->filter(fn (Review $review) => $review->product !== null)
            ->values();

        $count = $reviews->count();

        return ApiResponse::success([
            'summary' => $count > 0 ? [
                'average' => round((float) $reviews->avg('rating'), 1),
                'count' => $count,
            ] : null,
            'reviews' => $reviews->map(fn (Review $review) => [
                'id' => $review->id,
                'customer_name' => $review->user?->name ?: $review->customer_name,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'review_text' => (string) $review->review_text,
                'image_url' => $review->image_url_for_display,
                'is_verified_purchase' => (bool) $review->is_verified_purchase,
                'created_at' => $review->created_at?->toIso8601String(),
                'product' => $products->toListItem($review->product),
            ])->values(),
        ]);
    }
}
