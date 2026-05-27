<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliateDashboardAnalyticsService;
use App\Services\AffiliateSampleRequestService;
use App\Support\AffiliateReferralLink;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateAdminAnalyticsController extends Controller
{
    public function __construct(
        private readonly AffiliateDashboardAnalyticsService $analytics,
        private readonly AffiliateSampleRequestService $samples,
    ) {}

    public function index(Request $request): View
    {
        $period = $this->analytics->validatePeriod((string) $request->query('period', '30d'));

        return view('admin.affiliates.analytics.index', [
            'period' => $period,
            'periodLabel' => $this->analytics->periodLabel($period),
            'totals' => $this->analytics->programTotals($period),
            'rows' => $this->analytics->programAffiliateSummaries($period),
        ]);
    }

    public function show(Request $request, Affiliate $affiliate): View
    {
        $affiliate->load('user');
        $period = $this->analytics->validatePeriod((string) $request->query('period', '30d'));
        $tab = (string) $request->query('tab', 'overview');
        $allowedTabs = ['overview', 'links', 'traffic', 'products', 'samples', 'commissions'];
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'overview';
        }

        $data = [
            'affiliate' => $affiliate,
            'period' => $period,
            'periodLabel' => $this->analytics->periodLabel($period),
            'tab' => $tab,
            'shopHomeUrl' => AffiliateReferralLink::homeUrl($affiliate),
            'sampleSummary' => $this->samples->dashboardSummary($affiliate, 5),
        ];

        if ($tab === 'overview') {
            $data['analytics'] = $this->analytics->build($affiliate, $period);
        } elseif ($tab === 'links') {
            $data['links'] = $this->analytics->linkPerformanceList($affiliate, $period, 100);
        } elseif ($tab === 'traffic') {
            $data['sources'] = $this->analytics->trafficSourcesList($affiliate, $period, 50);
        } elseif ($tab === 'products') {
            $data['products'] = $this->analytics->topProductsList($affiliate, $period, 50);
        } elseif ($tab === 'samples') {
            $data['sampleRequests'] = $this->samples->listForAffiliate($affiliate, 100);
        } else {
            $data['commissionsData'] = $this->analytics->commissionsDetail($affiliate, $period);
        }

        return view('admin.affiliates.analytics.show', $data);
    }
}
