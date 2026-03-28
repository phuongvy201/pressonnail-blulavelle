<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

/**
 * Trang đánh giá công khai: tất cả review đã duyệt trên nền tảng.
 */
class ReviewListingController extends Controller
{
    public function index(Request $request)
    {
        $agg = Review::query()
            ->approved()
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg_rating')
            ->first();

        $totalReviews = (int) ($agg->total ?? 0);
        $avgRating = $totalReviews > 0 ? round((float) ($agg->avg_rating ?? 0), 1) : 0.0;

        $distribution = [];
        if ($totalReviews > 0) {
            $countsByStar = Review::query()
                ->approved()
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
            ->with(['product' => function ($q) {
                $q->select('id', 'name', 'slug', 'shop_id');
            }, 'product.shop:id,shop_name,shop_slug', 'user:id,name,avatar']);

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

        $photoReviews = Review::query()
            ->approved()
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->orderByDesc('created_at')
            ->limit(28)
            ->get();

        return view('reviews.index', compact(
            'totalReviews',
            'avgRating',
            'distribution',
            'reviews',
            'photoReviews',
            'sort',
            'ratingFilter'
        ));
    }
}
