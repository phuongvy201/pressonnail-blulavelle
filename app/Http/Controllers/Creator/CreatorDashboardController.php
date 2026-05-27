<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\AffiliateApplication;
use App\Services\AffiliateDashboardAnalyticsService;
use App\Services\AffiliateSampleRequestService;
use App\Support\AffiliateSetupStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatorDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        AffiliateDashboardAnalyticsService $analytics,
        AffiliateSampleRequestService $samples,
    ): View {
        $affiliate = auth()->user()->affiliate;
        $this->maybeSyncProfileFromApplication($affiliate);
        $affiliate = $affiliate->fresh();

        $period = $analytics->validatePeriod((string) $request->query('period', '30d'));

        return view('creator.dashboard', [
            'affiliate' => $affiliate,
            'setup' => AffiliateSetupStatus::for($affiliate),
            'shopUrl' => rtrim(config('creator.shop_url', config('app.url')), '/'),
            'period' => $period,
            'analytics' => $analytics->build($affiliate, $period),
            'sampleSummary' => $samples->dashboardSummary($affiliate, 3),
        ]);
    }

    private function maybeSyncProfileFromApplication($affiliate): void
    {
        if (! $affiliate || ($affiliate->hasCompleteProfile() && $affiliate->hasSocialLinks())) {
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
