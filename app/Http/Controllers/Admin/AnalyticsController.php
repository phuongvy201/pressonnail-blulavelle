<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    private AnalyticsService $analyticsService;

    public function __construct()
    {
        // Không khởi tạo service ở đây, sẽ khởi tạo trong index()
    }

    public function index(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $tab = $request->get('tab', 'acquisition');
        $filter = $request->get('filter', 'all'); // All, Organic Search, Paid Search, Direct, Social, Referrals, Display, Email, Other

        // Single-domain mode: dùng cấu hình GA4 chung, không chọn domain.
        $this->analyticsService = AnalyticsService::forDomain(null);

        // Check if the service is not initialized (due to no config)
        if (!$this->analyticsService->isInitialized()) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Google Analytics chưa được cấu hình đầy đủ (property_id / credentials).');
        }

        $data = [
            'days' => $days,
            'tab' => $tab,
            'filter' => $filter,
        ];

        // Common data for all tabs
        $data['summaryMetrics'] = $this->analyticsService->getSummaryMetrics($days);

        // Load data for all tabs to be able to switch tab without reloading
        // Acquisition data
        $data['sessionsByDate'] = $this->analyticsService->getSessionsByDate($days);
        $data['channels'] = $this->analyticsService->getAcquisitionChannels($days);
        $data['trafficSources'] = $this->analyticsService->getTrafficSources($days);

                // Filter data based on filter parameter (only applies when tab is acquisition)
        if ($tab === 'acquisition' && $filter !== 'all') {
            $data['trafficSources'] = $this->filterTrafficSources($data['trafficSources'], $filter);
            $data['channels'] = $this->filterChannels($data['channels'], $filter);
        }

        $data['totalSessions'] = array_sum(array_column($data['channels'], 'sessions'));

        // Audience data
        $data['demographics'] = $this->analyticsService->getAudienceDemographics($days);
        $data['devices'] = $this->analyticsService->getAudienceDevices($days);

        // Conversions data
        $data['conversions'] = $this->analyticsService->getConversions($days);

        // Pages data
        $data['topPages'] = $this->analyticsService->getTopPages($days);

        // Events data
        $data['events'] = $this->analyticsService->getAllEvents($days);

        // Domains data
        $domain = $request->get('domain');
        if ($domain) {
            // Hiển thị chi tiết domain
            $data['selectedDomain'] = $domain;
            $data['domainPages'] = $this->analyticsService->getDomainPages($domain, $days);
            $data['domainTrafficSources'] = $this->analyticsService->getDomainTrafficSources($domain, $days);
            $data['domainDemographics'] = $this->analyticsService->getDomainDemographics($domain, $days);
            $data['domainDevices'] = $this->analyticsService->getDomainDevices($domain, $days);
            $data['domainTimeline'] = $this->analyticsService->getDomainTimeline($domain, $days);
        } else {
            // Hiển thị danh sách domains
            $data['domains'] = $this->analyticsService->getDomains($days);
        }

        return view('admin.analytics.index', $data);
    }

    /**
     * Filter traffic sources by source type
     */
    private function filterTrafficSources(array $trafficSources, string $filter): array
    {
        $filterMap = [
            'organic-search' => ['Google', 'Bing', 'Organic Search'],
            'paid-search' => ['Paid Search'],
            'direct' => ['Direct'],
            'social' => ['Facebook', 'TikTok', 'Pinterest', 'Instagram', 'Twitter/X', 'YouTube', 'LinkedIn'],
            'referrals' => ['Referral'],
            'display' => ['Display'],
            'email' => ['Email'],
            'other' => ['Other'],
        ];

        $allowedTypes = $filterMap[strtolower(str_replace(' ', '-', $filter))] ?? [];

        if (empty($allowedTypes)) {
            return $trafficSources;
        }

        return array_filter($trafficSources, function ($source) use ($allowedTypes) {
            return in_array($source['source_type'] ?? '', $allowedTypes);
        });
    }

    /**
     * Filter channels by source type
     */
    private function filterChannels(array $channels, string $filter): array
    {
        $filterMap = [
            'organic-search' => ['Organic Search'],
            'paid-search' => ['Paid Search', 'Paid Social'],
            'direct' => ['Direct'],
            'social' => ['Paid Social', 'Social'],
            'referrals' => ['Referral'],
            'display' => ['Display'],
            'email' => ['Email'],
            'other' => ['Unassigned', 'Other'],
        ];

        $allowedChannels = $filterMap[strtolower(str_replace(' ', '-', $filter))] ?? [];

        if (empty($allowedChannels)) {
            return $channels;
        }

        return array_filter($channels, function ($channel) use ($allowedChannels) {
            return in_array($channel['channel'] ?? '', $allowedChannels);
        });
    }
}
