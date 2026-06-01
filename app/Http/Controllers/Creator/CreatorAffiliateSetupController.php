<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use App\Support\AffiliateSetupStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CreatorAffiliateSetupController extends Controller
{
    public function index(): View
    {
        $affiliate = $this->affiliate();
        $this->maybeSyncProfileFromApplication($affiliate);

        $payoutMethods = config('creator.payout_methods', []);

        return view('creator.setup.index', [
            'affiliate' => $affiliate->fresh(),
            'setup' => AffiliateSetupStatus::for($affiliate->fresh()),
            'platforms' => config('creator.platforms', []),
            'followerRanges' => config('creator.follower_ranges', []),
            'payoutMethods' => $payoutMethods,
            'defaultPayoutMethod' => $this->resolveDefaultPayoutMethod($affiliate, $payoutMethods),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $affiliate = $this->affiliate();
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'primary_platform' => ['required', Rule::in(array_keys(config('creator.platforms', [])))],
            'follower_range' => ['nullable', Rule::in(array_keys(config('creator.follower_ranges', [])))],
            'content_niche' => 'required|string|max:255',
        ]);

        $affiliate->update($validated);

        return redirect()
            ->to(route('creator.setup.index').'#profile')
            ->with('success', 'Profile saved.');
    }

    public function updateSocial(Request $request): RedirectResponse
    {
        $affiliate = $this->affiliate();
        $validated = $request->validate([
            'social_links' => 'required|string|max:4000',
            'portfolio_links' => 'nullable|string|max:4000',
        ]);

        if (count(Affiliate::parseLinkLines($validated['social_links'])) === 0) {
            return back()
                ->withInput()
                ->withErrors(['social_links' => 'Add at least one valid URL (one per line).']);
        }

        $affiliate->update($validated);

        return redirect()
            ->to(route('creator.setup.index').'#social')
            ->with('success', 'Social links saved.');
    }

    public function updatePayout(Request $request): RedirectResponse
    {
        $affiliate = $this->affiliate();
        $methods = array_keys(config('creator.payout_methods', []));

        $validated = $request->validate([
            'payout_method' => ['required', Rule::in($methods)],
            'payout_legal_name' => 'required|string|max:255',
            'payout_paypal_email' => 'nullable|email|max:255|required_if:payout_method,paypal',
            'payout_bank_name' => 'nullable|string|max:255|required_if:payout_method,bank_transfer',
            'payout_account_holder' => 'nullable|string|max:255|required_if:payout_method,bank_transfer',
            'payout_routing_number' => 'nullable|string|regex:/^\d{9}$/|required_if:payout_method,bank_transfer',
            'payout_account_number' => 'nullable|string|regex:/^\d{4,17}$/|required_if:payout_method,bank_transfer',
        ]);

        $payload = [
            'payout_method' => $validated['payout_method'],
            'payout_legal_name' => $validated['payout_legal_name'],
            'payout_paypal_email' => $validated['payout_method'] === 'paypal' ? $validated['payout_paypal_email'] : null,
            'payout_venmo_handle' => null,
            'payout_bank_name' => null,
            'payout_account_holder' => null,
            'payout_routing_number' => null,
            'payout_account_number' => null,
            'payout_account_last4' => null,
            'payout_routing_last4' => null,
        ];

        if ($validated['payout_method'] === 'bank_transfer') {
            $accountNumber = $validated['payout_account_number'];
            $routingNumber = $validated['payout_routing_number'];
            $payload['payout_bank_name'] = $validated['payout_bank_name'];
            $payload['payout_account_holder'] = $validated['payout_account_holder'];
            $payload['payout_routing_number'] = $routingNumber;
            $payload['payout_account_number'] = $accountNumber;
            $payload['payout_account_last4'] = substr($accountNumber, -4);
            $payload['payout_routing_last4'] = substr($routingNumber, -4);
        }

        $affiliate->update($payload);

        return redirect()
            ->to(route('creator.setup.index').'#payout')
            ->with('success', $affiliate->fresh()->hasPayoutSetup()
                ? 'Payout information saved. You are eligible for payouts when commissions are processed.'
                : 'Payout information saved.');
    }

    /**
     * @param  array<string, string>  $payoutMethods
     */
    private function resolveDefaultPayoutMethod(Affiliate $affiliate, array $payoutMethods): string
    {
        $method = old('payout_method', $affiliate->payout_method ?? 'paypal');

        return array_key_exists($method, $payoutMethods)
            ? $method
            : (array_key_first($payoutMethods) ?: 'paypal');
    }

    private function affiliate(): Affiliate
    {
        $affiliate = auth()->user()?->affiliate;
        if (! $affiliate || ! $affiliate->is_active) {
            abort(403);
        }

        return $affiliate;
    }

    private function maybeSyncProfileFromApplication(Affiliate $affiliate): void
    {
        if ($affiliate->hasCompleteProfile() && $affiliate->hasSocialLinks()) {
            return;
        }

        if (! $affiliate->user_id) {
            return;
        }

        $application = AffiliateApplication::query()
            ->where('user_id', $affiliate->user_id)
            ->where('status', AffiliateApplication::STATUS_APPROVED)
            ->orderByDesc('processed_at')
            ->first();

        if (! $application) {
            return;
        }

        $affiliate->fillFromApplication($application);
        $affiliate->saveQuietly();
    }
}
