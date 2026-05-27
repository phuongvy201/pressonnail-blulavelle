<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AffiliateSettings;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateProgramSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.affiliate-program', [
            'rates' => AffiliateSettings::tierRates(),
            'tierEvaluationDays' => AffiliateSettings::tierEvaluationDays(),
            'tierInactivityDays' => AffiliateSettings::tierInactivityDays(),
            'tierOrderThresholds' => AffiliateSettings::tierOrderThresholds(),
            'commissionNewCustomersOnly' => AffiliateSettings::commissionNewCustomersOnly(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'commission_new_customers_only' => ['nullable', 'boolean'],
            'tier_rate_basic' => ['required', 'numeric', 'min:0', 'max:100'],
            'tier_rate_silver' => ['required', 'numeric', 'min:0', 'max:100'],
            'tier_rate_gold' => ['required', 'numeric', 'min:0', 'max:100'],
            'tier_rate_diamond' => ['required', 'numeric', 'min:0', 'max:100'],
            'tier_evaluation_days' => ['required', 'integer', 'min:1', 'max:365'],
            'tier_inactivity_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'tier_threshold_silver' => ['required', 'integer', 'min:1'],
            'tier_threshold_gold' => ['required', 'integer', 'min:1', 'gte:tier_threshold_silver'],
            'tier_threshold_diamond' => ['required', 'integer', 'min:1', 'gte:tier_threshold_gold'],
        ]);

        Settings::set(
            'affiliate.commission_new_customers_only',
            $request->boolean('commission_new_customers_only') ? '1' : '0'
        );

        Settings::set('affiliate.tier_rates', json_encode([
            'basic' => round((float) $validated['tier_rate_basic'], 4),
            'silver' => round((float) $validated['tier_rate_silver'], 4),
            'gold' => round((float) $validated['tier_rate_gold'], 4),
            'diamond' => round((float) $validated['tier_rate_diamond'], 4),
        ], JSON_THROW_ON_ERROR));

        Settings::set('affiliate.tier_evaluation_days', (string) (int) $validated['tier_evaluation_days']);
        Settings::set('affiliate.tier_inactivity_days', (string) (int) $validated['tier_inactivity_days']);
        Settings::set('affiliate.tier_order_thresholds', json_encode([
            'silver' => (int) $validated['tier_threshold_silver'],
            'gold' => (int) $validated['tier_threshold_gold'],
            'diamond' => (int) $validated['tier_threshold_diamond'],
        ], JSON_THROW_ON_ERROR));

        return redirect()
            ->route('admin.settings.affiliate-program.edit')
            ->with('success', 'Đã cập nhật cấu hình chương trình affiliate.');
    }
}
