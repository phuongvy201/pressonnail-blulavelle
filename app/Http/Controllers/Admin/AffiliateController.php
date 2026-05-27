<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Support\AffiliateSettings;
use App\Support\AffiliateTier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AffiliateController extends Controller
{
    public function index()
    {
        $affiliates = Affiliate::query()->with('user')->orderBy('code')->paginate(25);

        return view('admin.affiliates.index', compact('affiliates'));
    }

    public function create()
    {
        $tierRates = AffiliateSettings::tierRates();

        return view('admin.affiliates.create', compact('tierRates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:64|unique:affiliates,code',
            'display_name' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'tier' => ['required', Rule::in(AffiliateTier::ALL)],
            'tier_locked' => 'boolean',
            'commission_rate_override' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $data['code'] = strtolower(trim($data['code']));
        $data['tier_locked'] = $request->boolean('tier_locked');
        $data['is_active'] = $request->boolean('is_active');
        if ($data['commission_rate_override'] === null || $data['commission_rate_override'] === '') {
            $data['commission_rate_override'] = null;
        }

        Affiliate::query()->create($data);

        return redirect()->route('admin.affiliates.index')
            ->with('success', 'Affiliate created.');
    }

    public function edit(Affiliate $affiliate)
    {
        $tierRates = AffiliateSettings::tierRates();

        return view('admin.affiliates.edit', compact('affiliate', 'tierRates'));
    }

    public function update(Request $request, Affiliate $affiliate)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', Rule::unique('affiliates', 'code')->ignore($affiliate->id)],
            'display_name' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'tier' => ['required', Rule::in(AffiliateTier::ALL)],
            'tier_locked' => 'boolean',
            'commission_rate_override' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $data['code'] = strtolower(trim($data['code']));
        $data['tier_locked'] = $request->boolean('tier_locked');
        $data['is_active'] = $request->boolean('is_active');
        if ($request->input('commission_rate_override') === null || $request->input('commission_rate_override') === '') {
            $data['commission_rate_override'] = null;
        }

        $affiliate->update($data);

        return redirect()->route('admin.affiliates.index')
            ->with('success', 'Affiliate updated.');
    }

    public function destroy(Affiliate $affiliate)
    {
        $affiliate->delete();

        return redirect()->route('admin.affiliates.index')
            ->with('success', 'Affiliate deleted.');
    }
}
