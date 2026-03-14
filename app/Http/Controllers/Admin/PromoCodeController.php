<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function index(Request $request)
    {
        $query = PromoCode::query();

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

        $promoCodes = $query->orderBy('code')->paginate(20);

        return view('admin.promo-codes.index', compact('promoCodes'));
    }

    public function create()
    {
        return view('admin.promo-codes.create');
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
            'send_on_trigger' => 'nullable|in:thank_you,wishlist,add_to_cart',
        ];
        if ($request->type === 'percentage') {
            $rules['value'] = 'required|numeric|min:0|max:100';
        }
        $data = $request->validate($rules);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');

        if (isset($data['value']) && $data['type'] === 'percentage' && $data['value'] > 100) {
            $data['value'] = 100;
        }

        PromoCode::create($data);

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Promo code đã được tạo.');
    }

    public function edit(PromoCode $promoCode)
    {
        return view('admin.promo-codes.edit', compact('promoCode'));
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
            'send_on_trigger' => 'nullable|in:thank_you,wishlist,add_to_cart',
        ];
        if ($request->type === 'percentage') {
            $rules['value'] = 'required|numeric|min:0|max:100';
        }
        $data = $request->validate($rules);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');

        if (isset($data['value']) && $data['type'] === 'percentage' && $data['value'] > 100) {
            $data['value'] = 100;
        }

        $promoCode->update($data);

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Promo code đã được cập nhật.');
    }

    public function destroy(PromoCode $promoCode)
    {
        $promoCode->delete();
        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Promo code đã được xóa.');
    }
}
