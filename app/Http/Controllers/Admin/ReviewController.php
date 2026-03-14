<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** Query reviews với quyền (Admin: tất cả, Seller: của sản phẩm mình). */
    protected function baseQuery()
    {
        $user = auth()->user();
        $query = Review::with('product:id,name,slug,user_id');
        if (!$user->hasRole('admin')) {
            $query->whereHas('product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
        return $query;
    }

    /** Lấy các review id thuộc quyền user (để kiểm tra khi xóa/ghim). */
    protected function allowedReviewIds(array $ids): array
    {
        return $this->baseQuery()->whereIn('id', $ids)->pluck('id')->all();
    }

    /**
     * Danh sách review (Admin: tất cả, Seller: chỉ review của sản phẩm mình).
     */
    public function index(Request $request)
    {
        $query = $this->baseQuery();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('review_text', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('approved')) {
            if ($request->approved === '1') {
                $query->where('is_approved', true);
            } elseif ($request->approved === '0') {
                $query->where('is_approved', false);
            }
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('show_on_home')) {
            if ($request->show_on_home === '1') {
                $query->where('show_on_home', true);
            } elseif ($request->show_on_home === '0') {
                $query->where('show_on_home', false);
            }
        }

        $reviews = $query->orderByDesc('created_at')->paginate((int) $request->get('per_page', 20))->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    /** Xóa một review. */
    public function destroy(Review $review)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            if (!$review->product || $review->product->user_id !== $user->id) {
                abort(403);
            }
        }
        $review->delete();
        return redirect()->route('admin.reviews.index')->with('success', 'Đã xóa review.');
    }

    /** Xóa hàng loạt. */
    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $ids = $this->allowedReviewIds($request->ids);
        $count = Review::whereIn('id', $ids)->delete();
        return redirect()->route('admin.reviews.index')->with('success', "Đã xóa {$count} review.");
    }

    /** Bật/tắt ghim trang chủ một review. */
    public function togglePin(Review $review)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            if (!$review->product || $review->product->user_id !== $user->id) {
                abort(403);
            }
        }
        $review->update(['show_on_home' => !$review->show_on_home]);
        $label = $review->show_on_home ? 'Ghim' : 'Bỏ ghim';
        return redirect()->route('admin.reviews.index')->with('success', "Đã {$label} review.");
    }

    /** Ghim trang chủ hàng loạt. */
    public function bulkPin(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $ids = $this->allowedReviewIds($request->ids);
        Review::whereIn('id', $ids)->update(['show_on_home' => true]);
        return redirect()->route('admin.reviews.index')->with('success', 'Đã ghim ' . count($ids) . ' review lên trang chủ.');
    }

    /** Bỏ ghim hàng loạt. */
    public function bulkUnpin(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $ids = $this->allowedReviewIds($request->ids);
        Review::whereIn('id', $ids)->update(['show_on_home' => false]);
        return redirect()->route('admin.reviews.index')->with('success', 'Đã bỏ ghim ' . count($ids) . ' review.');
    }
}
