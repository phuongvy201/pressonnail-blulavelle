<?php

namespace App\Services\Apple;

use App\Models\AppleAnalyticsImport;
use App\Models\AppleAppDownload;
use App\Support\Apple\AppStoreConnectToken;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AppStoreAnalyticsSync
{
    public function __construct(private AppStoreConnectClient $client) {}

    /**
     * @return array{report: string, instances: int, rows: int}
     */
    public function sync(int $days = 14): array
    {
        $days = max(1, min($days, 30));
        $requestId = $this->ongoingRequestId();
        $report = $this->downloadsReport($requestId);
        $instances = 0;
        $rows = 0;

        for ($ago = $days; $ago >= 1; $ago--) {
            $processingDate = now()->subDays($ago)->toDateString();
            $payload = $this->client->get('/v1/analyticsReports/'.$report['id'].'/instances', [
                'filter[granularity]' => 'DAILY',
                'filter[processingDate]' => $processingDate,
                'limit' => 20,
            ]);
            foreach ($payload['data'] ?? [] as $instance) {
                if (! is_array($instance)) {
                    continue;
                }
                $imported = $this->importInstance($instance, $report['name']);
                $instances += $imported['imported'];
                $rows += $imported['rows'];
            }
        }

        return [
            'report' => $report['name'],
            'instances' => $instances,
            'rows' => $rows,
        ];
    }

    private function ongoingRequestId(): string
    {
        $appId = AppStoreConnectToken::appId();
        $existing = $this->client->get('/v1/apps/'.$appId.'/analyticsReportRequests', [
            'filter[accessType]' => 'ONGOING',
            'limit' => 5,
        ]);
        $id = (string) ($existing['data'][0]['id'] ?? '');
        if ($id !== '') {
            return $id;
        }

        try {
            $created = $this->client->post('/v1/analyticsReportRequests', [
                'data' => [
                    'type' => 'analyticsReportRequests',
                    'attributes' => ['accessType' => 'ONGOING'],
                    'relationships' => [
                        'app' => ['data' => ['type' => 'apps', 'id' => $appId]],
                    ],
                ],
            ]);
        } catch (RuntimeException $exception) {
            if (! str_contains($exception->getMessage(), '409')) {
                throw $exception;
            }
            $created = $this->client->get('/v1/apps/'.$appId.'/analyticsReportRequests', [
                'filter[accessType]' => 'ONGOING',
                'limit' => 5,
            ]);
        }

        $id = (string) ($created['data']['id'] ?? $created['data'][0]['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('App Store Connect did not return an analytics report request.');
        }

        return $id;
    }

    /**
     * @return array{id: string, name: string}
     */
    private function downloadsReport(string $requestId): array
    {
        $payload = $this->client->get('/v1/analyticsReportRequests/'.$requestId.'/reports', [
            'limit' => 200,
        ]);
        $detailed = null;
        $standard = null;
        foreach ($payload['data'] ?? [] as $report) {
            if (! is_array($report)) {
                continue;
            }
            $name = (string) ($report['attributes']['name'] ?? '');
            $category = (string) ($report['attributes']['category'] ?? '');
            if ($category !== 'APP_STORE_ENGAGEMENT') {
                continue;
            }
            if ($name === 'App Downloads Detailed') {
                $detailed = $report;
            }
            if ($name === 'App Downloads Standard') {
                $standard = $report;
            }
        }

        $chosen = $detailed ?? $standard;
        if ($chosen === null) {
            throw new RuntimeException('The App Downloads report is not ready yet. A new ongoing request can take 24 to 48 hours.');
        }

        return [
            'id' => (string) $chosen['id'],
            'name' => (string) ($chosen['attributes']['name'] ?? 'App Downloads'),
        ];
    }

    /**
     * @param  array<string, mixed>  $instance
     * @return array{imported: int, rows: int}
     */
    private function importInstance(array $instance, string $reportName): array
    {
        $instanceId = (string) ($instance['id'] ?? '');
        if ($instanceId === '') {
            return ['imported' => 0, 'rows' => 0];
        }

        $segments = $this->client->get('/v1/analyticsReportInstances/'.$instanceId.'/segments', [
            'limit' => 50,
        ]);
        $checksums = [];
        $bodies = [];
        foreach ($segments['data'] ?? [] as $segment) {
            if (! is_array($segment)) {
                continue;
            }
            $url = (string) ($segment['attributes']['url'] ?? '');
            if ($url === '') {
                continue;
            }
            $checksums[] = (string) ($segment['attributes']['checksum'] ?? '');
            $bodies[] = $this->client->download($url);
        }
        if ($bodies === []) {
            return ['imported' => 0, 'rows' => 0];
        }

        $checksum = hash('sha256', implode('|', $checksums));
        $saved = AppleAnalyticsImport::query()->where('instance_id', $instanceId)->first();
        if ($saved && $saved->checksum === $checksum) {
            return ['imported' => 0, 'rows' => 0];
        }

        $parsed = [];
        foreach ($bodies as $body) {
            $parsed = array_merge($parsed, AppStoreReportParser::rows($body));
        }
        $aggregated = $this->aggregate($parsed);

        DB::transaction(function () use ($aggregated, $instanceId, $reportName, $instance, $checksum): void {
            $dates = array_values(array_unique(array_column($aggregated, 'stat_date')));
            if ($dates !== []) {
                AppleAppDownload::query()->whereIn('stat_date', $dates)->delete();
            }
            foreach (array_chunk($aggregated, 500) as $chunk) {
                AppleAppDownload::query()->insert($chunk);
            }
            AppleAnalyticsImport::query()->updateOrCreate(
                ['instance_id' => $instanceId],
                [
                    'report_name' => $reportName,
                    'processing_date' => $instance['attributes']['processingDate'] ?? null,
                    'checksum' => $checksum,
                    'imported_at' => now(),
                ]
            );
        });

        return ['imported' => 1, 'rows' => count($aggregated)];
    }

    /**
     * @param  list<array{stat_date: string, download_type: string, source_type: string, campaign: string, territory: string, device: string, counts: int}>  $rows
     * @return list<array{stat_date: string, download_type: string, source_type: string, campaign: string, territory: string, device: string, counts: int, created_at: mixed, updated_at: mixed}>
     */
    private function aggregate(array $rows): array
    {
        $bucket = [];
        $now = now();
        foreach ($rows as $row) {
            $key = implode('|', [
                $row['stat_date'],
                $row['download_type'],
                $row['source_type'],
                $row['campaign'],
                $row['territory'],
                $row['device'],
            ]);
            if (! isset($bucket[$key])) {
                $bucket[$key] = $row + ['created_at' => $now, 'updated_at' => $now];
                continue;
            }
            $bucket[$key]['counts'] += $row['counts'];
        }

        return array_values($bucket);
    }
}
