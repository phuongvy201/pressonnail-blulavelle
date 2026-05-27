<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\AffiliateApplication;
use App\Services\AffiliateApplicationSubmissionService;
use App\Support\AffiliateOnboardingDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AffiliateOnboardingController extends Controller
{
    public function __construct(
        private readonly AffiliateApplicationSubmissionService $submissionService,
    ) {}

    public function step1(): View
    {
        $draft = AffiliateOnboardingDraft::get(request()) ?? [];

        return view('creator.affiliate.apply.step1', [
            'draft' => $draft,
            'platforms' => $this->platforms(),
            'followerRanges' => $this->followerRanges(),
        ]);
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            $this->step1Rules(),
            $this->step1Messages()
        );

        $code = strtolower($validated['proposed_ref_code']);
        $this->submissionService->validateRefCode($code);

        if (Auth::check() && Auth::user()->hasPendingAffiliateApplication()) {
            return redirect()
                ->route('creator.affiliate.status')
                ->with('error', 'You already have a pending application.');
        }

        $validated['proposed_ref_code'] = $code;
        $validated['accepted_program_terms'] = true;

        AffiliateOnboardingDraft::put($request, $validated);

        return redirect()->route('creator.affiliate.apply.account');
    }

    public function step2(Request $request): View|RedirectResponse
    {
        if (! AffiliateOnboardingDraft::has($request)) {
            return redirect()
                ->route('creator.affiliate.apply')
                ->with('error', 'Please complete your creator application first.');
        }

        if (Auth::check()) {
            $user = Auth::user();

            if ($user->hasPendingAffiliateApplication()) {
                return redirect()->route('creator.affiliate.status');
            }

            if ($user->hasActiveAffiliate()) {
                return redirect()->route('creator.dashboard');
            }

            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('creator.affiliate.apply.verify-email');
            }
        }

        $draft = AffiliateOnboardingDraft::get($request) ?? [];

        return view('creator.affiliate.apply.step2', [
            'draft' => $draft,
            'isLoggedIn' => Auth::check(),
        ]);
    }

    public function step3VerifyEmail(Request $request): View|RedirectResponse
    {
        if (! AffiliateOnboardingDraft::has($request)) {
            return redirect()
                ->route('creator.affiliate.apply')
                ->with('error', 'Please complete your creator application first.');
        }

        if (! Auth::check()) {
            return redirect()
                ->route('creator.affiliate.apply.account')
                ->with('error', 'Sign in or create an account to continue.');
        }

        $user = Auth::user();

        if ($user->hasPendingAffiliateApplication()) {
            return redirect()->route('creator.affiliate.status');
        }

        if ($user->hasActiveAffiliate()) {
            return redirect()->route('creator.dashboard');
        }

        $draft = AffiliateOnboardingDraft::get($request) ?? [];

        return view('creator.affiliate.apply.step3-verify-email', [
            'draft' => $draft,
            'emailVerified' => $user->hasVerifiedEmail(),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        if (! AffiliateOnboardingDraft::has($request)) {
            return redirect()->route('creator.affiliate.apply');
        }

        if (! Auth::check()) {
            return redirect()
                ->route('creator.affiliate.apply.account')
                ->with('error', 'Sign in or create an account to submit your application.');
        }

        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()
                ->route('creator.affiliate.apply.verify-email')
                ->with('error', 'Please verify your email before submitting your application.');
        }

        if ($user->hasActiveAffiliate()) {
            AffiliateOnboardingDraft::forget($request);

            return redirect()->route('creator.dashboard');
        }

        if ($user->hasPendingAffiliateApplication()) {
            AffiliateOnboardingDraft::forget($request);

            return redirect()->route('creator.affiliate.status');
        }

        $draft = AffiliateOnboardingDraft::get($request) ?? [];
        $this->submissionService->submit($user, $draft);
        AffiliateOnboardingDraft::forget($request);

        return redirect()
            ->route('creator.affiliate.status')
            ->with('success', 'Your creator application was submitted and is pending review.');
    }

    public function status(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('creator.login');
        }

        $user = Auth::user();

        $this->syncApprovedAffiliateForUser($user);

        if ($user->canAccessCreatorAffiliateFeatures()) {
            return redirect()->route('creator.dashboard');
        }

        $application = $user->affiliateApplicationForStatus();

        if ($application && $application->user_id === null) {
            $application->update(['user_id' => $user->id]);
        }

        return view('creator.affiliate.status', [
            'application' => $application,
            'affiliateMissing' => $application?->status === AffiliateApplication::STATUS_APPROVED
                && ! $user->hasPendingAffiliateApplication(),
        ]);
    }

    /**
     * Link affiliate profile to user when admin approved but user_id was not set on affiliates row.
     */
    private function syncApprovedAffiliateForUser(\App\Models\User $user): void
    {
        if ($user->hasActiveAffiliate()) {
            return;
        }

        $application = $user->affiliateApplications()
            ->where('status', AffiliateApplication::STATUS_APPROVED)
            ->latest()
            ->first();

        if (! $application) {
            return;
        }

        $affiliate = \App\Models\Affiliate::query()
            ->where('code', strtolower($application->proposed_ref_code))
            ->first();

        if (! $affiliate) {
            return;
        }

        if ($affiliate->user_id !== null && (int) $affiliate->user_id !== (int) $user->id) {
            return;
        }

        $affiliate->update([
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function thanks(): View
    {
        return view('creator.affiliate.thanks');
    }

    /**
     * @return array<string, mixed>
     */
    private function step1Rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:32',
            'primary_platform' => ['required', Rule::in(array_keys($this->platforms()))],
            'follower_range' => ['required', Rule::in(array_keys($this->followerRanges()))],
            'follower_count' => 'nullable|integer|min:0|max:999999999',
            'content_niche' => 'required|string|max:255',
            'social_links' => 'required|string|max:4000',
            'portfolio_links' => 'nullable|string|max:4000',
            'message' => 'nullable|string|max:2000',
            'proposed_ref_code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*$/',
            ],
            'accepted_program_terms' => 'accepted',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function step1Messages(): array
    {
        return [
            'proposed_ref_code.regex' => 'Ref code may only contain letters, numbers, and hyphens (no spaces).',
            'accepted_program_terms.accepted' => 'You must agree to the Affiliate Program Terms.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function platforms(): array
    {
        return [
            'tiktok' => 'TikTok',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
            'facebook' => 'Facebook',
            'pinterest' => 'Pinterest',
            'other' => 'Other',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function followerRanges(): array
    {
        return [
            'under_1k' => 'Under 1K',
            '1k_10k' => '1K – 10K',
            '10k_50k' => '10K – 50K',
            '50k_100k' => '50K – 100K',
            '100k_500k' => '100K – 500K',
            '500k_plus' => '500K+',
        ];
    }
}
