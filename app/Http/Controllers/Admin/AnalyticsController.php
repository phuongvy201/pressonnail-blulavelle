<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    private AnalyticsService $analyticsService;
    private ?string $selectedDomain = null;

    public function __construct()
    {
        // Không khởi tạo service ở đây, sẽ khởi tạo trong ind  exex() dựa trên selected_domain
    }

    public function index(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $tab = $request->get('tab', 'acquisition');
        $filter = $request->get('filter', 'all'); // All, Organic Search, Paid Search, Direct, Social, Referrals, Display, Email, Other

        // Lấy danh sách domain đã cấu hình trong database
        $availableDomains = \App\Models\DomainAnalyticsConfig::getAllDomains();

        // Check if no domain is configured
        if (empty($availableDomains)) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'No domain is configured for analytics.');
        }

        // Get the domain selected from the request
        $selectedDomainParam = $request->get('selected_domain');

        // Only accept domain from database, not allow default
        if ($selectedDomainParam === 'default' || $selectedDomainParam === '' || $selectedDomainParam === null) {
            // If no domain is selected, use the first domain in the list
            $selectedDomain = $availableDomains[0];
        } else {
            // Check if the domain exists in the database
            if (!in_array($selectedDomainParam, $availableDomains)) {
                return redirect()->route('admin.analytics.index')
                    ->with('error', 'Invalid domain. Domain must be configured in the database.');
            }
            $selectedDomain = $selectedDomainParam;
        }

        $this->selectedDomain = $selectedDomain;
        $this->analyticsService = AnalyticsService::forDomain($selectedDomain);

        // Check if the service is not initialized (due to no config)
        if (!$this->analyticsService->isInitialized()) {
            return redirect()->route('admin.dashboard')
                ->with('error', "Domain '{$selectedDomain}' is not fully configured for analytics.");
        }

        $displayDomain = $selectedDomain;

        $data = [
            'days' => $days,
            'tab' => $tab,
            'filter' => $filter,
            'selectedDomain' => $selectedDomain,
            'displayDomain' => $displayDomain,
            'availableDomains' => $availableDomains,
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
