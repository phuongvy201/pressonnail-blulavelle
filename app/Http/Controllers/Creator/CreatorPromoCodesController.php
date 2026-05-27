<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatorPromoCodesController extends Controller
{
    /**
     * Read-only list of promo codes assigned to this affiliate by admin.
     */
    public function index(Request $request): View
    {
        $affiliate = $this->affiliate();

        $query = PromoCode::query()->where('affiliate_id', $affiliate->id);

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $promoCodes = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('creator.promo-codes.index', [
            'affiliate' => $affiliate,
            'promoCodes' => $promoCodes,
        ]);
    }

    private function affiliate(): Affiliate
    {
        $affiliate = auth()->user()?->affiliate;
        if (! $affiliate || ! $affiliate->is_active) {
            abort(403);
        }

        return $affiliate;
    }
}
