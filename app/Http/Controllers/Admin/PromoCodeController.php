<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\Affiliate;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function index(Request $request)
    {
        $query = PromoCode::query()->with('affiliate:id,code,display_name');

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('trigger')) {
            $query->where('send_on_trigger', $request->trigger);
        }

        if ($request->filled('affiliate')) {
            if ($request->affiliate === 'creator') {
                $query->whereNotNull('affiliate_id');
            } elseif ($request->affiliate === 'shop') {
                $query->whereNull('affiliate_id');
            }
        }

        $promoCodes = $query->orderBy('code')->paginate(20);

        return view('admin.promo-codes.index', compact('promoCodes'));
    }

    public function create(Request $request)
    {
        $affiliates = Affiliate::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'display_name']);

        $suggestedCode = null;
        if ($request->boolean('generate')) {
            try {
                $suggestedCode = PromoCode::generateUniqueCode();
            } catch (\RuntimeException) {
                return redirect()
                    ->route('admin.promo-codes.create')
                    ->withErrors(['code' => 'Could not generate a unique code. Please try again or enter manually.']);
            }
        }

        $selectedAffiliateId = $request->filled('affiliate_id')
            ? (int) $request->input('affiliate_id')
            : null;

        return view('admin.promo-codes.create', compact('affiliates', 'suggestedCode', 'selectedAffiliateId'));
    }

    public function suggestCode()
    {
        try {
            return response()->json(['code' => PromoCode::generateUniqueCode()]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => 'Could not generate a unique code.'], 503);
        }
    }

    public function store(Request $request)
    {
        $rules = [
            'code' => 'required|string|max:64|unique:promo_codes,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'send_on_trigger' => 'nullable|in:thank_you,wishlist,add_to_cart,checkout_fail',
            'affiliate_id' => 'nullable|exists:affiliates,id',
        ];
        if ($request->type === 'percentage') {
            $rules['value'] = 'required|numeric|min:0|max:100';
        }
        $data = $request->validate($rules);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');
        $data['affiliate_id'] = $request->filled('affiliate_id') ? (int) $data['affiliate_id'] : null;

        if (isset($data['value']) && $data['type'] === 'percentage' && $data['value'] > 100) {
            $data['value'] = 100;
        }

        PromoCode::create($data);

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Promo code created.');
    }

    public function edit(PromoCode $promoCode)
    {
        $affiliates = Affiliate::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'display_name']);

        return view('admin.promo-codes.edit', compact('promoCode', 'affiliates'));
    }

    public function update(Request $request, PromoCode $promoCode)
    {
        $rules = [
            'code' => 'required|string|max:64|unique:promo_codes,code,' . $promoCode->id,
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
            'send_on_trigger' => 'nullable|in:thank_you,wishlist,add_to_cart,checkout_fail',
            'affiliate_id' => 'nullable|exists:affiliates,id',
        ];
        if ($request->type === 'percentage') {
            $rules['value'] = 'required|numeric|min:0|max:100';
        }
        $data = $request->validate($rules);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');
        $data['affiliate_id'] = $request->filled('affiliate_id') ? (int) $data['affiliate_id'] : null;

        if (isset($data['value']) && $data['type'] === 'percentage' && $data['value'] > 100) {
            $data['value'] = 100;
        }

        $promoCode->update($data);

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Promo code updated.');
    }

    public function destroy(PromoCode $promoCode)
    {
        $promoCode->delete();
        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Promo code deleted.');
    }
}
