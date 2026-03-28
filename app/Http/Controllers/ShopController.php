<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Product;
use App\Models\Category;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShopController extends Controller
{
    public function show(Shop $shop)
    {
        // Load relationships
        $shop->load(['user', 'products' => function ($query) {
            $query->where('status', 'active')
                ->with(['template', 'variants'])
                ->orderBy('created_at', 'desc');
        }]);

        // Get shop statistics
        $stats = [
            'total_products' => $shop->products()->where('status', 'active')->count(),
            'followers' => $shop->followers()->count(),
            'favorited' => $shop->favorites()->count(),
        ];

        // Get product categories for this shop
        $categories = Category::whereHas('templates.products', function ($query) use ($shop) {
            $query->where('shop_id', $shop->id)->where('status', 'active');
        })->with(['templates.products' => function ($query) use ($shop) {
            $query->where('shop_id', $shop->id)->where('status', 'active')->limit(1);
        }])->get();

        // Get hot products (most viewed/favorited)
        $hotProducts = $shop->products()
            ->where('status', 'active')
            ->with(['template', 'variants'])
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();

        // Get all products for the shop
        $allProducts = $shop->products()
            ->where('status', 'active')
            ->with(['template', 'variants'])
            ->orderBy('created_at', 'desc')
            ->paginate(24);

        // Check if current user follows this shop
        $isFollowing = false;
        if (Auth::check()) {
            $isFollowing = $shop->followers()->where('user_id', Auth::id())->exists();
        }

        $shopProductIds = Product::query()->where('shop_id', $shop->id)->pluck('id');
        $shopReviewsCount = 0;
        $shopReviewsAvg = null;
        if ($shopProductIds->isNotEmpty()) {
            $revAgg = Review::query()
                ->approved()
                ->whereIn('product_id', $shopProductIds)
                ->selectRaw('COUNT(*) as c, AVG(rating) as a')
                ->first();
            $shopReviewsCount = (int) ($revAgg->c ?? 0);
            $shopReviewsAvg = $shopReviewsCount > 0 ? round((float) ($revAgg->a ?? 0), 1) : null;
        }

        return view('shops.show', compact(
            'shop',
            'stats',
            'categories',
            'hotProducts',
            'allProducts',
            'isFollowing',
            'shopReviewsCount',
            'shopReviewsAvg'
        ));
    }

    /**
     * Trang xem tất cả đánh giá đã duyệt của các sản phẩm thuộc shop (giao diện tổng hợp + lọc/sắp xếp).
     */
    public function reviews(Request $request, Shop $shop)
    {
        $productIds = Product::query()->where('shop_id', $shop->id)->pluck('id');

        $totalReviews = 0;
        $avgRating = 0.0;
        $distribution = [];
        if ($productIds->isNotEmpty()) {
            $agg = Review::query()
                ->approved()
                ->whereIn('product_id', $productIds)
                ->selectRaw('COUNT(*) as total, AVG(rating) as avg_rating')
                ->first();
            $totalReviews = (int) ($agg->total ?? 0);
            $avgRating = $totalReviews > 0 ? round((float) ($agg->avg_rating ?? 0), 1) : 0.0;

            $countsByStar = Review::query()
                ->approved()
                ->whereIn('product_id', $productIds)
                ->selectRaw('rating, COUNT(*) as c')
                ->groupBy('rating')
                ->pluck('c', 'rating');

            for ($star = 5; $star >= 1; $star--) {
                $c = (int) ($countsByStar[$star] ?? 0);
                $distribution[$star] = [
                    'count' => $c,
                    'percent' => $totalReviews > 0 ? (int) round(($c / $totalReviews) * 100) : 0,
                ];
            }
        } else {
            for ($star = 5; $star >= 1; $star--) {
                $distribution[$star] = ['count' => 0, 'percent' => 0];
            }
        }

        $sort = $request->query('sort', 'suggested');
        if (! in_array($sort, ['suggested', 'newest', 'oldest', 'highest', 'lowest'], true)) {
            $sort = 'suggested';
        }

        $ratingFilter = $request->query('rating');
        if ($ratingFilter !== null && $ratingFilter !== '' && ! in_array((int) $ratingFilter, [1, 2, 3, 4, 5], true)) {
            $ratingFilter = null;
        }

        $listQuery = Review::query()
            ->approved()
            ->whereIn('product_id', $productIds->isEmpty() ? [0] : $productIds)
            ->with(['product' => function ($q) {
                $q->select('id', 'name', 'slug', 'shop_id');
            }, 'user:id,name,avatar']);

        if ($ratingFilter !== null && $ratingFilter !== '') {
            $listQuery->where('rating', (int) $ratingFilter);
        }

        switch ($sort) {
            case 'newest':
                $listQuery->orderByDesc('created_at');
                break;
            case 'oldest':
                $listQuery->orderBy('created_at');
                break;
            case 'highest':
                $listQuery->orderByDesc('rating')->orderByDesc('created_at');
                break;
            case 'lowest':
                $listQuery->orderBy('rating')->orderByDesc('created_at');
                break;
            case 'suggested':
            default:
                $listQuery->orderByDesc('show_on_home')->orderByDesc('rating')->orderByDesc('created_at');
                break;
        }

        $reviews = $listQuery->paginate(12)->withQueryString();

        $photoReviews = collect();
        if ($productIds->isNotEmpty()) {
            $photoReviews = Review::query()
                ->approved()
                ->whereIn('product_id', $productIds)
                ->whereNotNull('image_url')
                ->where('image_url', '!=', '')
                ->orderByDesc('created_at')
                ->limit(28)
                ->get();
        }

        return view('shops.reviews', compact(
            'shop',
            'totalReviews',
            'avgRating',
            'distribution',
            'reviews',
            'photoReviews',
            'sort',
            'ratingFilter'
        ));
    }

    public function follow(Request $request, Shop $shop)
    {
        try {
            // Check if shop exists
            if (!$shop || !$shop->exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shop not found'
                ], 404);
            }

            $request->validate([
                'action' => 'required|in:follow,unfollow'
            ]);

            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You need to login to follow this shop'
                ], 401);
            }

            $user = Auth::user();
            $action = $request->input('action');

            if ($action === 'follow') {
                if (!$shop->followers()->where('user_id', $user->id)->exists()) {
                    $shop->followers()->attach($user->id);
                    $message = 'Successfully followed this shop!';
                } else {
                    $message = 'You are already following this shop!';
                }
            } else {
                $shop->followers()->detach($user->id);
                $message = 'Successfully unfollowed this shop!';
            }

            $followersCount = $shop->followers()->count();

            return response()->json([
                'success' => true,
                'message' => $message,
                'followers_count' => $followersCount,
                'is_following' => $action === 'follow'
            ]);
        } catch (\Exception $e) {
            Log::error('Follow shop error: ' . $e->getMessage(), [
                'shop_id' => $shop->id ?? null,
                'user_id' => Auth::id(),
                'action' => $request->input('action')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }

    public function contact(Request $request, Shop $shop)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'subject' => 'required|string|max:255'
        ]);

        // Here you would typically send an email to the shop owner
        // For now, we'll just return a success message

        return response()->json([
            'success' => true,
            'message' => 'Message has been sent to the shop successfully!'
        ]);
    }
}
