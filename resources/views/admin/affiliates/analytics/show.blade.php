@extends('layouts.admin')

@section('title', 'Analytics — '.$affiliate->code)

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('admin.affiliates.analytics.index', ['period' => $period]) }}" class="text-sm text-blue-600 hover:underline">← All creators</a>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-gray-900">
                <span class="font-mono text-violet-700">{{ $affiliate->code }}</span>
                @if ($affiliate->display_name)
                    <span class="text-gray-600 font-normal">· {{ $affiliate->display_name }}</span>
                @endif
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                Tier <span class="capitalize font-medium">{{ $affiliate->tier }}</span>
                · {{ $affiliate->is_active ? 'Active' : 'Inactive' }}
                @if ($affiliate->user?->email)
                    · {{ $affiliate->user->email }}
                @endif
            </p>
            <p class="mt-1 text-xs text-gray-500 break-all">Home link: <a href="{{ $shopHomeUrl }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ $shopHomeUrl }}</a></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @include('admin.affiliates.partials.analytics-period-form', [
                'action' => route('admin.affiliates.analytics.show', $affiliate),
                'period' => $period,
            ])
            <a href="{{ route('admin.affiliates.edit', $affiliate) }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Edit affiliate</a>
        </div>
    </div>

    @include('admin.affiliates.partials.analytics-nav', ['affiliate' => $affiliate, 'period' => $period, 'tab' => $tab])

    @if ($tab === 'overview')
        @php $a = $analytics; $k = $a['kpis']; $tier = $a['tier_progress']; @endphp
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ([
                ['label' => 'Clicks', 'value' => number_format($k['total_clicks'])],
                ['label' => 'Orders', 'value' => number_format($k['total_orders'])],
                ['label' => 'Conversion', 'value' => $k['conversion_rate'].'%'],
                ['label' => 'Revenue', 'value' => '$'.number_format($k['total_revenue'], 2)],
                ['label' => 'Commission', 'value' => '$'.number_format($k['total_commission'], 2)],
                ['label' => 'Available payout', 'value' => '$'.number_format($k['available_payout'], 2)],
            ] as $card)
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase text-gray-500">{{ $card['label'] }}</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-500">{{ $a['period_label'] }}</p>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                <h2 class="text-sm font-semibold uppercase text-gray-600">Revenue &amp; commission</h2>
                <div class="mt-4 h-72"><canvas id="adminRevenueChart"></canvas></div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase text-gray-600">Clicks &amp; orders</h2>
                <div class="mt-4 h-60"><canvas id="adminTrafficChart"></canvas></div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase text-gray-600">Tier progress</h2>
                <p class="mt-3 text-sm font-semibold">{{ $tier['current_tier_label'] ?? ucfirst($tier['current_tier']) }} · {{ $tier['commission_percent'] }}%</p>
                @if ($tier['next_tier'] && ($tier['next_threshold_orders'] ?? null))
                    <div class="mt-3 h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full" style="width: {{ $tier['progress_percent'] }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ number_format($tier['rolling_orders'] ?? 0) }} / {{ number_format($tier['next_threshold_orders']) }} orders ({{ $tier['rolling_days'] }}d)</p>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase text-gray-600">Top traffic sources</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    @forelse ($a['traffic_sources'] as $src)
                        <li class="flex justify-between"><span class="capitalize">{{ $src['source'] }}</span><span class="font-semibold tabular-nums">{{ number_format($src['count']) }}</span></li>
                    @empty
                        <li class="text-gray-500">No data yet.</li>
                    @endforelse
                </ul>
                <a href="{{ route('admin.affiliates.analytics.show', ['affiliate' => $affiliate, 'period' => $period, 'tab' => 'traffic']) }}" class="mt-3 inline-block text-sm text-violet-600 hover:underline">View all →</a>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase text-gray-600">Top products</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    @forelse ($a['top_products'] as $p)
                        <li class="flex justify-between gap-2">
                            <span class="truncate">{{ $p['name'] }}</span>
                            <span class="shrink-0 font-semibold tabular-nums">{{ number_format($p['quantity']) }} · ${{ number_format($p['revenue'], 0) }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500">No sales yet.</li>
                    @endforelse
                </ul>
            </div>
            @php $sq = $sampleSummary; $sqQuota = $sq['quota']; @endphp
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold uppercase text-gray-600">Sample requests</h2>
                    <a href="{{ route('admin.affiliates.analytics.show', ['affiliate' => $affiliate, 'period' => $period, 'tab' => 'samples']) }}" class="text-sm text-violet-600 hover:underline">View all →</a>
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    Quota: {{ $sqQuota['used'] }}/{{ $sqQuota['max_requests'] }} ({{ $sqQuota['period_days'] }}d)
                    @if ($sq['pending_count'] > 0)
                        · <span class="font-semibold text-amber-700">{{ $sq['pending_count'] }} pending</span>
                    @endif
                </p>
                <ul class="mt-4 space-y-2 text-sm">
                    @forelse ($sq['recent'] as $sr)
                        <li class="flex justify-between gap-2">
                            <span class="truncate">{{ $sr->product?->name ?? '—' }}</span>
                            <span class="shrink-0 capitalize text-gray-600">{{ $sr->status }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500">No sample requests yet.</li>
                    @endforelse
                </ul>
                @if ($sq['pending_count'] > 0)
                    <a href="{{ route('admin.sample-requests.index', ['status' => 'pending']) }}" class="mt-3 inline-block text-sm text-amber-700 hover:underline">Review pending in queue →</a>
                @endif
            </div>
        </div>
    @elseif ($tab === 'links')
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            @if (count($links) === 0)
                <p class="text-sm text-gray-500">No clicks with <code class="bg-gray-100 px-1 rounded">?ref={{ $affiliate->code }}</code> in this period.</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($links as $link)
                        <li class="flex flex-col gap-2 py-4 sm:flex-row sm:justify-between sm:items-start">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900">{{ $link['label'] }}</p>
                                @if (!empty($link['subtitle']))
                                    <p class="text-xs font-mono text-gray-500">{{ $link['subtitle'] }}</p>
                                @endif
                                @if (!empty($link['referral_url']))
                                    <p class="mt-1 text-xs break-all text-blue-600">{{ $link['referral_url'] }}</p>
                                @endif
                            </div>
                            <span class="text-lg font-bold tabular-nums shrink-0">{{ number_format($link['clicks']) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @elseif ($tab === 'traffic')
        <div class="rounded-lg border border-amber-50 bg-amber-50/50 border p-4 text-sm text-amber-900 mb-4">
            Sources from UTM on <code class="bg-white px-1 rounded">?ref=</code> clicks, referrer host, or inferred in-app (Instagram, TikTok, …).
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            @if (count($sources) === 0)
                <p class="text-sm text-gray-500">No source data yet.</p>
            @else
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500"><tr><th class="pb-2">Source</th><th class="pb-2 text-right">Clicks</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($sources as $src)
                            <tr>
                                <td class="py-2 capitalize font-medium">{{ $src['source'] }}</td>
                                <td class="py-2 text-right font-semibold tabular-nums">{{ number_format($src['count']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @elseif ($tab === 'samples')
        @php $sq = $sampleSummary; $sqQuota = $sq['quota']; @endphp
        <div class="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Quota</p>
                <p class="mt-1 text-xl font-bold">{{ $sqQuota['used'] }}/{{ $sqQuota['max_requests'] }}</p>
                <p class="text-xs text-gray-500">{{ $sqQuota['period_days'] }}d · {{ ucfirst($sqQuota['tier']) }}</p>
            </div>
            @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'rejected' => 'Rejected'] as $statusKey => $statusLabel)
                <div class="rounded-lg border {{ $statusKey === 'pending' && ($sq['status_counts'][$statusKey] ?? 0) > 0 ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-white' }} p-4">
                    <p class="text-xs font-semibold uppercase {{ $statusKey === 'pending' ? 'text-amber-900' : 'text-gray-500' }}">{{ $statusLabel }}</p>
                    <p class="mt-1 text-xl font-bold">{{ $sq['status_counts'][$statusKey] ?? 0 }}</p>
                </div>
            @endforeach
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm overflow-x-auto">
            @if ($sampleRequests->isEmpty())
                <p class="text-sm text-gray-500">No sample requests for this creator.</p>
            @else
                <table class="w-full min-w-[560px] text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500">
                        <tr>
                            <th class="pb-2">ID</th>
                            <th class="pb-2">Product</th>
                            <th class="pb-2">Qty</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Submitted</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($sampleRequests as $req)
                            <tr>
                                <td class="py-2 font-mono text-xs">#{{ $req->id }}</td>
                                <td class="py-2">{{ $req->product?->name ?? '—' }}</td>
                                <td class="py-2 tabular-nums">{{ $req->quantity }}</td>
                                <td class="py-2 capitalize">{{ $req->status }}</td>
                                <td class="py-2 text-gray-500">{{ $req->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="py-2 text-right">
                                    <a href="{{ route('admin.sample-requests.show', $req) }}" class="text-violet-600 hover:underline font-medium">Review</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @elseif ($tab === 'products')
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm overflow-x-auto">
            @if (count($products) === 0)
                <p class="text-sm text-gray-500">No attributed product sales in this period.</p>
            @else
                <table class="w-full min-w-[480px] text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500">
                        <tr><th class="pb-2">Product</th><th class="pb-2 text-right">Units</th><th class="pb-2 text-right">Revenue</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($products as $p)
                            <tr>
                                <td class="py-2">{{ $p['name'] }}</td>
                                <td class="py-2 text-right tabular-nums">{{ number_format($p['quantity']) }}</td>
                                <td class="py-2 text-right tabular-nums">${{ number_format($p['revenue'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @else
        @php $comm = $commissionsData['breakdown']; @endphp
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase text-amber-900">Pending</p>
                <p class="mt-1 text-2xl font-bold">${{ number_format($comm['pending'], 2) }}</p>
            </div>
            <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                <p class="text-xs font-semibold uppercase text-green-900">Paid</p>
                <p class="mt-1 text-2xl font-bold">${{ number_format($comm['paid'], 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Void</p>
                <p class="mt-1 text-2xl font-bold">${{ number_format($comm['void'], 2) }}</p>
            </div>
        </div>
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm overflow-x-auto">
                <h2 class="text-sm font-semibold uppercase text-gray-600">Commissions</h2>
                <table class="mt-4 w-full min-w-[400px] text-sm">
                    <thead class="text-xs uppercase text-gray-500">
                        <tr><th class="pb-2">Order</th><th class="pb-2">Amount</th><th class="pb-2">Status</th><th class="pb-2">Date</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($commissionsData['recent'] as $row)
                            <tr>
                                <td class="py-2 font-mono text-xs">{{ $row['order_number'] ?? '—' }}</td>
                                <td class="py-2 font-semibold">${{ number_format($row['amount'], 2) }}</td>
                                <td class="py-2 capitalize">{{ $row['status'] }}</td>
                                <td class="py-2 text-gray-500">{{ $row['created_at'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-gray-500">None</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm overflow-x-auto">
                <h2 class="text-sm font-semibold uppercase text-gray-600">Payout history</h2>
                <table class="mt-4 w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500">
                        <tr><th class="pb-2">Date</th><th class="pb-2">Amount</th><th class="pb-2">Type</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($commissionsData['payout_history'] as $row)
                            <tr>
                                <td class="py-2 text-gray-500">{{ $row['date'] }}</td>
                                <td class="py-2 font-semibold">${{ number_format(abs($row['amount']), 2) }}</td>
                                <td class="py-2 capitalize">{{ str_replace('_', ' ', $row['type']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-gray-500">None</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@if ($tab === 'overview')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const series = @json($analytics['series']);
    if (document.getElementById('adminRevenueChart')) {
        new Chart(document.getElementById('adminRevenueChart'), {
            type: 'line',
            data: {
                labels: series.labels,
                datasets: [
                    { label: 'Revenue', data: series.revenue, borderColor: '#2563eb', fill: true, tension: 0.35 },
                    { label: 'Commission', data: series.commission, borderColor: '#e11d48', fill: true, tension: 0.35 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
        });
    }
    if (document.getElementById('adminTrafficChart')) {
        new Chart(document.getElementById('adminTrafficChart'), {
            type: 'bar',
            data: {
                labels: series.labels,
                datasets: [
                    { label: 'Clicks', data: series.clicks, backgroundColor: 'rgba(37, 99, 235, 0.7)' },
                    { label: 'Orders', data: series.orders, backgroundColor: 'rgba(30, 64, 175, 0.85)' },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
        });
    }
})();
</script>
@endpush
@endif
