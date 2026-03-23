<?php

namespace App\Services;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunRealtimeReportRequest;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Support\Settings;
use Exception;

class AnalyticsService
{
    private ?BetaAnalyticsDataClient $client = null;
    private string $propertyId;
    private ?string $domain = null;

    public function __construct(?string $domain = null)
    {
        $this->domain = $domain;
        $credentialsPath = null;

        // Single-domain mode: lấy GA4 config từ Settings (UI), fallback về config/services.php (.env).
        $this->propertyId = (string) (Settings::get(
            'analytics.google_analytics_property_id',
            config('services.google.analytics.property_id')
        ) ?? '');

        $configuredCredentialsPath = Settings::get(
            'analytics.google_analytics_credentials_path',
            config('services.google.analytics.credentials_path')
        );

        if (!empty($configuredCredentialsPath)) {
            $credentialsPath = (string) $configuredCredentialsPath;
        }

        if (!$this->propertyId) {
            Log::error('Google Analytics Property ID chưa được cấu hình');
            return;
        }

        // Hỗ trợ cả đường dẫn tuyệt đối và đường dẫn tương đối trong storage/app.
        if ($credentialsPath && !file_exists($credentialsPath)) {
            if (Storage::exists($credentialsPath)) {
                $credentialsPath = Storage::path($credentialsPath);
            }
        }

        if (!$credentialsPath || !file_exists($credentialsPath)) {
            Log::error("File credentials Google Analytics không tồn tại: " . ($credentialsPath ?? 'null'));
            return;
        }

        try {
            $this->client = new BetaAnalyticsDataClient([
                'credentials' => $credentialsPath,
            ]);
        } catch (Exception $e) {
            Log::error('Lỗi khởi tạo Google Analytics client: ' . $e->getMessage());
        }
    }

    /**
     * Tạo instance AnalyticsService cho domain cụ thể
     */
    public static function forDomain(?string $domain): self
    {
        return new self($domain);
    }

    /**
     * Lấy domain từ request hiện tại
     */
    public static function getCurrentDomain(): ?string
    {
        $host = request()->getHost();
        // Loại bỏ port nếu có
        $host = explode(':', $host)[0];
        return $host;
    }

    /**
     * Kiểm tra xem service đã được khởi tạo đúng chưa
     */
    public function isInitialized(): bool
    {
        return $this->client !== null && !empty($this->propertyId);
    }

    /**
     * Lấy dữ liệu realtime - Người đang online và trang đang xem
     */
    public function getRealtimePages(): array
    {
        if (!$this->client) {
            return [];
        }

        return Cache::remember("analytics.realtime.pages.{$this->propertyId}", 60, function () {
            try {
                $request = new RunRealtimeReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'dimensions' => [
                        new Dimension(['name' => 'unifiedScreenName']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'screenPageViews']),
                    ],
                    'limit' => 10, // Top 10 trang
                ]);
                $response = $this->client->runRealtimeReport($request);

                // Log API response để debug
                Log::info('GA4 Realtime Pages API', [
                    'row_count' => $response->getRows()->count(),
                    'response' => json_decode($response->serializeToJsonString(), true)
                ]);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $data[] = [
                        'page' => $dimensionValues[0]->getValue(),
                        'users' => (int) $metricValues[0]->getValue(),
                        'views' => (int) $metricValues[1]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy realtime pages: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy dữ liệu realtime - Người đang online theo quốc gia/thành phố
     */
    public function getRealtimeLocations(): array
    {
        if (!$this->client) {
            return [];
        }

        return Cache::remember("analytics.realtime.locations.{$this->propertyId}", 60, function () {
            try {
                $request = new RunRealtimeReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'dimensions' => [
                        new Dimension(['name' => 'country']),
                        new Dimension(['name' => 'city']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'activeUsers']),
                    ],
                    'limit' => 20,
                ]);
                $response = $this->client->runRealtimeReport($request);

                // Log API response để debug
                Log::info('GA4 Realtime Locations API', [
                    'row_count' => $response->getRows()->count(),
                    'response' => json_decode($response->serializeToJsonString(), true)
                ]);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $data[] = [
                        'country' => $dimensionValues[0]->getValue(),
                        'city' => $dimensionValues[1]->getValue(),
                        'users' => (int) $metricValues[0]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy realtime locations: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy dữ liệu realtime - Nguồn truy cập
     * 
     * Lưu ý: Realtime API không hỗ trợ dimensions cho source/medium/channel.
     * Chỉ có thể lấy tổng số active users hoặc phân loại theo country/device.
     * Để lấy thông tin source/medium/channel, cần sử dụng Reporting API (không realtime).
     */
    public function getRealtimeSources(): array
    {
        if (!$this->client) {
            return [];
        }

        return Cache::remember("analytics.realtime.sources.{$this->propertyId}", 60, function () {
            try {
                // Realtime API không hỗ trợ source/medium/channel dimensions
                // Sử dụng country dimension để phân loại theo quốc gia thay thế
                $request = new RunRealtimeReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'dimensions' => [
                        new Dimension(['name' => 'country']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'activeUsers']),
                    ],
                    'limit' => 20,
                ]);
                $response = $this->client->runRealtimeReport($request);

                // Log API response để debug
                Log::info('GA4 Realtime Sources API', [
                    'row_count' => $response->getRows()->count(),
                    'response' => json_decode($response->serializeToJsonString(), true)
                ]);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $country = $dimensionValues[0]->getValue();
                    $data[] = [
                        'channel' => 'Realtime',
                        'source' => $country ? "From {$country}" : 'Unknown',
                        'medium' => 'Realtime',
                        'users' => (int) $metricValues[0]->getValue(),
                        'country' => $country,
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy realtime sources: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy dữ liệu realtime - Thiết bị
     */
    public function getRealtimeDevices(): array
    {
        if (!$this->client) {
            return [];
        }

        return Cache::remember("analytics.realtime.devices.{$this->propertyId}", 60, function () {
            try {
                $request = new RunRealtimeReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'dimensions' => [
                        new Dimension(['name' => 'deviceCategory']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'activeUsers']),
                    ],
                ]);
                $response = $this->client->runRealtimeReport($request);

                // Log API response để debug
                Log::info('GA4 Realtime Devices API', [
                    'row_count' => $response->getRows()->count(),
                    'response' => json_decode($response->serializeToJsonString(), true)
                ]);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $data[] = [
                        'device' => $dimensionValues[0]->getValue(),
                        'users' => (int) $metricValues[0]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy realtime devices: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy tổng số người đang online
     */
    public function getTotalActiveUsers(): int
    {
        if (!$this->client) {
            return 0;
        }

        return Cache::remember("analytics.realtime.total_users.{$this->propertyId}", 60, function () {
            try {
                $request = new RunRealtimeReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'metrics' => [
                        new Metric(['name' => 'activeUsers']),
                    ],
                ]);
                $response = $this->client->runRealtimeReport($request);

                // Log API response để debug
                Log::info('GA4 Total Active Users API', [
                    'row_count' => $response->getRows()->count(),
                    'response' => json_decode($response->serializeToJsonString(), true)
                ]);

                if ($response->getRows()->count() > 0) {
                    $row = $response->getRows()[0];
                    $total = (int) $row->getMetricValues()[0]->getValue();
                    return $total;
                }

                return 0;
            } catch (Exception $e) {
                Log::error('Lỗi lấy total active users: ' . $e->getMessage());
                return 0;
            }
        });
    }

    /**
     * Lấy báo cáo hành vi (không realtime) - 7 ngày qua
     */
    public function getBehaviorReport(int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        try {
            $request = new RunReportRequest([
                'property' => "properties/{$this->propertyId}",
                'date_ranges' => [
                    new DateRange([
                        'start_date' => "{$days}daysAgo",
                        'end_date' => 'today',
                    ]),
                ],
                'dimensions' => [
                    new Dimension(['name' => 'pagePath']),
                ],
                'metrics' => [
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'engagedSessions']),
                ],
                'limit' => 20,
                'order_bys' => [
                    new OrderBy([
                        'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                        'desc' => true,
                    ]),
                ],
            ]);
            $response = $this->client->runReport($request);

            $data = [];
            foreach ($response->getRows() as $row) {
                $dimensionValues = $row->getDimensionValues();
                $metricValues = $row->getMetricValues();

                $data[] = [
                    'page' => $dimensionValues[0]->getValue(),
                    'views' => (int) $metricValues[0]->getValue(),
                    'engaged_sessions' => (int) $metricValues[1]->getValue(),
                ];
            }

            return $data;
        } catch (Exception $e) {
            Log::error('Lỗi lấy behavior report: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy tất cả dữ liệu realtime
     */
    public function getAllRealtime(): array
    {
        return [
            'total_active_users' => $this->getTotalActiveUsers(),
            'pages' => $this->getRealtimePages(),
            'locations' => $this->getRealtimeLocations(),
            'sources' => $this->getRealtimeSources(),
            'devices' => $this->getRealtimeDevices(),
        ];
    }

    /**
     * Lấy dữ liệu acquisition - Sessions theo channel
     */
    public function getAcquisitionChannels(int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.acquisition.channels.{$this->propertyId}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800); // 5min, 10min, 30min

        return Cache::remember($cacheKey, $cacheTime, function () use ($days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'sessionDefaultChannelGroup']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'averageSessionDuration']),
                        new Metric(['name' => 'newUsers']),
                        new Metric(['name' => 'bounceRate']),
                        new Metric(['name' => 'screenPageViews']),
                    ],
                    'limit' => 20,
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'sessions']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                // Log API response để debug
                Log::info('GA4 Acquisition Channels API', [
                    'days' => $days,
                    'row_count' => $response->getRows()->count(),
                    'response' => json_decode($response->serializeToJsonString(), true)
                ]);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $data[] = [
                        'channel' => $dimensionValues[0]->getValue(),
                        'sessions' => (int) $metricValues[0]->getValue(),
                        'avg_session_duration' => $metricValues[1]->getValue(),
                        'new_users' => (int) $metricValues[2]->getValue(),
                        'bounce_rate' => (float) $metricValues[3]->getValue(),
                        'page_views' => (int) $metricValues[4]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy acquisition channels: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy chi tiết nguồn truy cập (TikTok, Facebook, Pinterest, etc.)
     */
    public function getTrafficSources(int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.traffic.sources.{$this->propertyId}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'sessionSource']),
                        new Dimension(['name' => 'sessionMedium']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'averageSessionDuration']),
                        new Metric(['name' => 'newUsers']),
                        new Metric(['name' => 'bounceRate']),
                        new Metric(['name' => 'screenPageViews']),
                    ],
                    'limit' => 50,
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'sessions']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                // Log API response để debug
                Log::info('GA4 Traffic Sources API', [
                    'days' => $days,
                    'row_count' => $response->getRows()->count(),
                    'response' => json_decode($response->serializeToJsonString(), true)
                ]);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $source = $dimensionValues[0]->getValue();
                    $medium = $dimensionValues[1]->getValue();

                    // Xác định loại nguồn
                    $sourceType = $this->categorizeSource($source, $medium);

                    $data[] = [
                        'source' => $source,
                        'medium' => $medium,
                        'source_type' => $sourceType,
                        'sessions' => (int) $metricValues[0]->getValue(),
                        'avg_session_duration' => $metricValues[1]->getValue(),
                        'new_users' => (int) $metricValues[2]->getValue(),
                        'bounce_rate' => (float) $metricValues[3]->getValue(),
                        'page_views' => (int) $metricValues[4]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy traffic sources: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Phân loại nguồn truy cập
     */
    private function categorizeSource(string $source, string $medium): string
    {
        $sourceLower = strtolower($source);
        $mediumLower = strtolower($medium);

        // Social Media - Kiểm tra exact match trước, sau đó mới kiểm tra contains
        if (
            $sourceLower === 'tiktok' ||
            $sourceLower === 'tiktok.com' ||
            str_ends_with($sourceLower, '.tiktok.com') ||
            str_ends_with($sourceLower, '@tiktok.com')
        ) {
            return 'TikTok';
        }
        if (str_contains($sourceLower, 'facebook') || str_contains($sourceLower, 'fb.com')) {
            return 'Facebook';
        }
        if (str_contains($sourceLower, 'pinterest') || str_contains($sourceLower, 'pinterest.com')) {
            return 'Pinterest';
        }
        if (str_contains($sourceLower, 'instagram') || str_contains($sourceLower, 'instagram.com')) {
            return 'Instagram';
        }
        if (str_contains($sourceLower, 'twitter') || str_contains($sourceLower, 'x.com') || str_contains($sourceLower, 'twitter.com')) {
            return 'Twitter/X';
        }
        if (str_contains($sourceLower, 'youtube') || str_contains($sourceLower, 'youtube.com')) {
            return 'YouTube';
        }
        if (str_contains($sourceLower, 'linkedin') || str_contains($sourceLower, 'linkedin.com')) {
            return 'LinkedIn';
        }

        // Search Engines
        if (str_contains($sourceLower, 'google')) {
            return 'Google';
        }
        if (str_contains($sourceLower, 'bing')) {
            return 'Bing';
        }

        // Direct
        if ($sourceLower === '(direct)' || $sourceLower === 'direct') {
            return 'Direct';
        }

        // Referral
        if ($mediumLower === 'referral') {
            return 'Referral';
        }

        // Email
        if ($mediumLower === 'email') {
            return 'Email';
        }

        // Organic Search
        if ($mediumLower === 'organic') {
            return 'Organic Search';
        }

        // Paid Search
        if ($mediumLower === 'cpc' || $mediumLower === 'paid') {
            return 'Paid Search';
        }

        return 'Other';
    }

    /**
     * Lấy dữ liệu sessions theo ngày (cho line chart)
     */
    public function getSessionsByDate(int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.sessions.by_date.{$this->propertyId}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'date']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'screenPageViews']),
                    ],
                    'order_bys' => [
                        new OrderBy([
                            'dimension' => new DimensionOrderBy([
                                'dimension_name' => 'date',
                            ]),
                            'desc' => false,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                // Log API response để debug
                Log::info('GA4 Sessions By Date API', [
                    'days' => $days,
                    'row_count' => $response->getRows()->count(),
                    'response' => json_decode($response->serializeToJsonString(), true)
                ]);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $date = $dimensionValues[0]->getValue();
                    $formattedDate = \Carbon\Carbon::createFromFormat('Ymd', $date)->format('M d');

                    $data[] = [
                        'date' => $formattedDate,
                        'sessions' => (int) $metricValues[0]->getValue(),
                        'page_views' => (int) $metricValues[1]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy sessions by date: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy tổng hợp metrics cho period
     */
    public function getSummaryMetrics(int $days = 7): array
    {
        if (!$this->client) {
            return [
                'sessions' => 0,
                'avg_session_duration' => 0,
                'new_sessions_percent' => 0,
                'bounce_rate' => 0,
                'goal_completions' => 0,
                'pages_per_session' => 0,
            ];
        }

        $cacheKey = "analytics.summary.metrics.{$this->propertyId}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'averageSessionDuration']),
                        new Metric(['name' => 'newUsers']),
                        new Metric(['name' => 'bounceRate']),
                        new Metric(['name' => 'screenPageViews']),
                    ],
                ]);
                $response = $this->client->runReport($request);

                // Log API response để debug
                Log::info('GA4 Summary Metrics API', [
                    'days' => $days,
                    'row_count' => $response->getRows()->count(),
                    'response' => json_decode($response->serializeToJsonString(), true)
                ]);

                if ($response->getRows()->count() > 0) {
                    $row = $response->getRows()[0];
                    $metricValues = $row->getMetricValues();

                    $sessions = (int) $metricValues[0]->getValue();
                    $newUsers = (int) $metricValues[2]->getValue();
                    $pageViews = (int) $metricValues[4]->getValue();

                    $result = [
                        'sessions' => $sessions,
                        'avg_session_duration' => $metricValues[1]->getValue(),
                        'new_sessions_percent' => $sessions > 0 ? round(($newUsers / $sessions) * 100, 2) : 0,
                        'bounce_rate' => (float) $metricValues[3]->getValue(),
                        'goal_completions' => 0, // Cần cấu hình goals trong GA4
                        'pages_per_session' => $sessions > 0 ? round($pageViews / $sessions, 2) : 0,
                    ];

                    return $result;
                }

                return [
                    'sessions' => 0,
                    'avg_session_duration' => 0,
                    'new_sessions_percent' => 0,
                    'bounce_rate' => 0,
                    'goal_completions' => 0,
                    'pages_per_session' => 0,
                ];
            } catch (Exception $e) {
                Log::error('Lỗi lấy summary metrics: ' . $e->getMessage());
                return [
                    'sessions' => 0,
                    'avg_session_duration' => 0,
                    'new_sessions_percent' => 0,
                    'bounce_rate' => 0,
                    'goal_completions' => 0,
                    'pages_per_session' => 0,
                ];
            }
        });
    }

    /**
     * Lấy dữ liệu Audience - Demographics
     */
    public function getAudienceDemographics(int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.audience.demographics.{$this->propertyId}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'country']),
                        new Dimension(['name' => 'city']),
                        new Dimension(['name' => 'sessionSource']),
                        new Dimension(['name' => 'sessionMedium']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'screenPageViews']),
                    ],
                    'limit' => 20,
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'activeUsers']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                // Log API response để debug
                Log::info('GA4 Audience Demographics API', [
                    'days' => $days,
                    'row_count' => $response->getRows()->count(),
                    'response' => json_decode($response->serializeToJsonString(), true)
                ]);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $data[] = [
                        'country' => $dimensionValues[0]->getValue(),
                        'city' => $dimensionValues[1]->getValue(),
                        'users' => (int) $metricValues[0]->getValue(),
                        'sessions' => (int) $metricValues[1]->getValue(),
                        'page_views' => (int) $metricValues[2]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy audience demographics: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy dữ liệu Audience - Devices
     */
    public function getAudienceDevices(int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.audience.devices.{$this->propertyId}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'deviceCategory']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'averageSessionDuration']),
                    ],
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'activeUsers']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $data[] = [
                        'device' => $dimensionValues[0]->getValue(),
                        'users' => (int) $metricValues[0]->getValue(),
                        'sessions' => (int) $metricValues[1]->getValue(),
                        'avg_duration' => $metricValues[2]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy audience devices: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy dữ liệu Conversions - Events
     */
    public function getConversions(int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.conversions.{$this->propertyId}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'eventName']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'eventCount']),
                        new Metric(['name' => 'totalUsers']),
                    ],
                    'limit' => 20,
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'eventCount']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $data[] = [
                        'event_name' => $dimensionValues[0]->getValue(),
                        'count' => (int) $metricValues[0]->getValue(),
                        'value' => (int) $metricValues[1]->getValue(), // Using totalUsers as value
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy conversions: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy dữ liệu Pages - Top pages
     */
    public function getTopPages(int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.pages.top.{$this->propertyId}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'pagePath']),
                        new Dimension(['name' => 'pageTitle']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'screenPageViews']),
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'averageSessionDuration']),
                        new Metric(['name' => 'bounceRate']),
                    ],
                    'limit' => 20,
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $data[] = [
                        'page_path' => $dimensionValues[0]->getValue(),
                        'page_title' => $dimensionValues[1]->getValue(),
                        'pageviews' => (int) $metricValues[0]->getValue(),
                        'unique_pageviews' => (int) $metricValues[1]->getValue(), // Using activeUsers as unique_pageviews
                        'avg_time_on_page' => (float) $metricValues[2]->getValue(), // averageSessionDuration in seconds
                        'bounce_rate' => (float) $metricValues[3]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy top pages: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy dữ liệu Events - All events
     */
    public function getAllEvents(int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.events.all.{$this->propertyId}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'eventName']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'eventCount']),
                        new Metric(['name' => 'totalUsers']),
                        new Metric(['name' => 'eventValue']),
                    ],
                    'limit' => 50,
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'eventCount']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $data[] = [
                        'event_name' => $dimensionValues[0]->getValue(),
                        'count' => (int) $metricValues[0]->getValue(),
                        'total_value' => (float) $metricValues[2]->getValue(), // eventValue
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy all events: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy dữ liệu Domain - Thống kê theo domain
     */
    public function getDomains(int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.domains.{$this->propertyId}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'hostName']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'screenPageViews']),
                        new Metric(['name' => 'averageSessionDuration']),
                        new Metric(['name' => 'bounceRate']),
                        new Metric(['name' => 'newUsers']),
                    ],
                    'limit' => 50,
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'sessions']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                // Log API response để debug
                Log::info('GA4 Domains API', [
                    'days' => $days,
                    'row_count' => $response->getRows()->count(),
                    'response' => json_decode($response->serializeToJsonString(), true)
                ]);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $data[] = [
                        'domain' => $dimensionValues[0]->getValue(),
                        'sessions' => (int) $metricValues[0]->getValue(),
                        'users' => (int) $metricValues[1]->getValue(),
                        'page_views' => (int) $metricValues[2]->getValue(),
                        'avg_session_duration' => $metricValues[3]->getValue(),
                        'bounce_rate' => (float) $metricValues[4]->getValue(),
                        'new_users' => (int) $metricValues[5]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error('Lỗi lấy domains: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy top pages theo domain cụ thể
     */
    public function getDomainPages(string $domain, int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.domain.pages.{$domain}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($domain, $days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'hostName']),
                        new Dimension(['name' => 'pagePath']),
                        new Dimension(['name' => 'pageTitle']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'screenPageViews']),
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'averageSessionDuration']),
                        new Metric(['name' => 'bounceRate']),
                    ],
                    'limit' => 50,
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $rowDomain = $dimensionValues[0]->getValue();
                    // Filter by domain
                    if ($rowDomain !== $domain) {
                        continue;
                    }

                    $data[] = [
                        'domain' => $rowDomain,
                        'page_path' => $dimensionValues[1]->getValue(),
                        'page_title' => $dimensionValues[2]->getValue(),
                        'page_views' => (int) $metricValues[0]->getValue(),
                        'users' => (int) $metricValues[1]->getValue(),
                        'avg_duration' => $metricValues[2]->getValue(),
                        'bounce_rate' => (float) $metricValues[3]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error("Lỗi lấy domain pages cho {$domain}: " . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy traffic sources theo domain cụ thể
     */
    public function getDomainTrafficSources(string $domain, int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.domain.sources.{$domain}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($domain, $days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'hostName']),
                        new Dimension(['name' => 'sessionSource']),
                        new Dimension(['name' => 'sessionMedium']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'averageSessionDuration']),
                        new Metric(['name' => 'bounceRate']),
                    ],
                    'limit' => 50,
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'sessions']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $rowDomain = $dimensionValues[0]->getValue();
                    // Filter by domain
                    if ($rowDomain !== $domain) {
                        continue;
                    }

                    $source = $dimensionValues[1]->getValue();
                    $medium = $dimensionValues[2]->getValue();
                    $sourceType = $this->categorizeSource($source, $medium);

                    $data[] = [
                        'domain' => $rowDomain,
                        'source' => $source,
                        'medium' => $medium,
                        'source_type' => $sourceType,
                        'sessions' => (int) $metricValues[0]->getValue(),
                        'users' => (int) $metricValues[1]->getValue(),
                        'avg_session_duration' => $metricValues[2]->getValue(),
                        'bounce_rate' => (float) $metricValues[3]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error("Lỗi lấy domain traffic sources cho {$domain}: " . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy demographics theo domain cụ thể
     */
    public function getDomainDemographics(string $domain, int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.domain.demographics.{$domain}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($domain, $days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'hostName']),
                        new Dimension(['name' => 'country']),
                        new Dimension(['name' => 'city']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'screenPageViews']),
                    ],
                    'limit' => 50,
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'activeUsers']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $rowDomain = $dimensionValues[0]->getValue();
                    // Filter by domain
                    if ($rowDomain !== $domain) {
                        continue;
                    }

                    $data[] = [
                        'domain' => $rowDomain,
                        'country' => $dimensionValues[1]->getValue(),
                        'city' => $dimensionValues[2]->getValue(),
                        'users' => (int) $metricValues[0]->getValue(),
                        'sessions' => (int) $metricValues[1]->getValue(),
                        'page_views' => (int) $metricValues[2]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error("Lỗi lấy domain demographics cho {$domain}: " . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy devices theo domain cụ thể
     */
    public function getDomainDevices(string $domain, int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.domain.devices.{$domain}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($domain, $days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'hostName']),
                        new Dimension(['name' => 'deviceCategory']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'averageSessionDuration']),
                        new Metric(['name' => 'bounceRate']),
                    ],
                    'order_bys' => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'activeUsers']),
                            'desc' => true,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $rowDomain = $dimensionValues[0]->getValue();
                    // Filter by domain
                    if ($rowDomain !== $domain) {
                        continue;
                    }

                    $data[] = [
                        'domain' => $rowDomain,
                        'device' => $dimensionValues[1]->getValue(),
                        'users' => (int) $metricValues[0]->getValue(),
                        'sessions' => (int) $metricValues[1]->getValue(),
                        'avg_duration' => $metricValues[2]->getValue(),
                        'bounce_rate' => (float) $metricValues[3]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error("Lỗi lấy domain devices cho {$domain}: " . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Lấy timeline (sessions theo ngày) theo domain cụ thể
     */
    public function getDomainTimeline(string $domain, int $days = 7): array
    {
        if (!$this->client) {
            return [];
        }

        $cacheKey = "analytics.domain.timeline.{$domain}.{$days}";
        $cacheTime = $days <= 7 ? 300 : ($days <= 30 ? 600 : 1800);

        return Cache::remember($cacheKey, $cacheTime, function () use ($domain, $days) {
            try {
                $request = new RunReportRequest([
                    'property' => "properties/{$this->propertyId}",
                    'date_ranges' => [
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ],
                    'dimensions' => [
                        new Dimension(['name' => 'hostName']),
                        new Dimension(['name' => 'date']),
                    ],
                    'metrics' => [
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'screenPageViews']),
                    ],
                    'order_bys' => [
                        new OrderBy([
                            'dimension' => new DimensionOrderBy([
                                'dimension_name' => 'date',
                            ]),
                            'desc' => false,
                        ]),
                    ],
                ]);
                $response = $this->client->runReport($request);

                $data = [];
                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $rowDomain = $dimensionValues[0]->getValue();
                    // Filter by domain
                    if ($rowDomain !== $domain) {
                        continue;
                    }

                    $date = $dimensionValues[1]->getValue();
                    $formattedDate = \Carbon\Carbon::createFromFormat('Ymd', $date)->format('M d');

                    $data[] = [
                        'domain' => $rowDomain,
                        'date' => $formattedDate,
                        'sessions' => (int) $metricValues[0]->getValue(),
                        'users' => (int) $metricValues[1]->getValue(),
                        'page_views' => (int) $metricValues[2]->getValue(),
                    ];
                }

                return $data;
            } catch (Exception $e) {
                Log::error("Lỗi lấy domain timeline cho {$domain}: " . $e->getMessage());
                return [];
            }
        });
    }
}
