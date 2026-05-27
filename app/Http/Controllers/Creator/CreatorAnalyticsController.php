<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Services\AffiliateDashboardAnalyticsService;
use App\Support\AffiliateSetupStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatorAnalyticsController extends Controller
{
    public function __construct(
        private readonly AffiliateDashboardAnalyticsService $analytics,
    ) {}

    public function overview(Request $request): View
    {
        return $this->render($request, 'creator.analytics.overview', function ($affiliate, $period) {
            return ['data' => $this->analytics->overviewDetail($affiliate, $period)];
        });
    }

    public function links(Request $request): View
    {
        return $this->render($request, 'creator.analytics.links', function ($affiliate, $period) {
            return ['links' => $this->analytics->linkPerformanceList($affiliate, $period)];
        });
    }

    public function traffic(Request $request): View
    {
        return $this->render($request, 'creator.analytics.traffic', function ($affiliate, $period) {
            return ['sources' => $this->analytics->trafficSourcesList($affiliate, $period)];
        });
    }

    public function products(Request $request): View
    {
        return $this->render($request, 'creator.analytics.products', function ($affiliate, $period) {
            return ['products' => $this->analytics->topProductsList($affiliate, $period)];
        });
    }

    public function commissions(Request $request): View
    {
        return $this->render($request, 'creator.analytics.commissions', function ($affiliate, $period) {
            return ['data' => $this->analytics->commissionsDetail($affiliate, $period)];
        });
    }

    /**
     * @param  callable(\App\Models\Affiliate, string): array<string, mixed>  $dataResolver
     */
    private function render(Request $request, string $view, callable $dataResolver): View
    {
        $affiliate = auth()->user()->affiliate;
        $period = $this->analytics->validatePeriod((string) $request->query('period', '30d'));

        return view($view, array_merge([
            'affiliate' => $affiliate,
            'setup' => AffiliateSetupStatus::for($affiliate),
            'period' => $period,
            'periodLabel' => $this->analytics->periodLabel($period),
        ], $dataResolver($affiliate, $period)));
    }
}
