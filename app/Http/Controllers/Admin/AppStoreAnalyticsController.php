<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppleAnalyticsImport;
use App\Models\AppleAppDownload;
use App\Services\Apple\AppStoreAnalyticsSync;
use App\Support\Apple\AppStoreConnectToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class AppStoreAnalyticsController extends Controller
{
    public function index(): View
    {
        $since = now()->subDays(30)->toDateString();
        $base = AppleAppDownload::query()->where('stat_date', '>=', $since);

        return view('admin.app-store.index', [
            'configured' => AppStoreConnectToken::configured(),
            'since' => $since,
            'byType' => (clone $base)
                ->selectRaw('download_type, SUM(counts) as total')
                ->groupBy('download_type')
                ->orderByDesc('total')
                ->get(),
            'byCampaign' => (clone $base)
                ->where('campaign', '!=', '')
                ->selectRaw('campaign, SUM(counts) as total')
                ->groupBy('campaign')
                ->orderByDesc('total')
                ->get(),
            'bySource' => (clone $base)
                ->selectRaw('source_type, SUM(counts) as total')
                ->groupBy('source_type')
                ->orderByDesc('total')
                ->limit(12)
                ->get(),
            'byDate' => (clone $base)
                ->selectRaw('stat_date, SUM(counts) as total')
                ->groupBy('stat_date')
                ->orderBy('stat_date')
                ->get(),
            'lastImport' => AppleAnalyticsImport::query()->latest('imported_at')->first(),
        ]);
    }

    public function sync(AppStoreAnalyticsSync $sync): RedirectResponse
    {
        if (! AppStoreConnectToken::configured()) {
            return back()->with('error', 'Chưa có APPLE_CONNECT_ISSUER_ID, APPLE_CONNECT_KEY_ID và file khóa .p8.');
        }

        set_time_limit(120);

        try {
            $result = $sync->sync(7);
        } catch (Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $result['report'].': '.$result['instances'].' báo cáo mới, '.$result['rows'].' dòng.');
    }
}
