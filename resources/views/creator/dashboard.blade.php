@extends('layouts.creator')

@section('title', 'Creator dashboard')

@push('styles')
<style>
    .creator-kpi-card { transition: box-shadow 0.2s ease; }
    .creator-kpi-card:hover { box-shadow: 0 8px 24px rgba(0, 96, 167, 0.1); }
</style>
@endpush

@section('content')
@php
    $a = $analytics;
    $k = $a['kpis'];
    $tier = $a['tier_progress'];
    $comm = $a['commissions'];
@endphp
    <div class="mx-auto max-w-7xl px-5 py-10 md:px-16 md:py-12">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="creator-font-headline text-3xl font-bold text-[#0b1c30]">Affiliate dashboard</h1>
                <p class="mt-2 text-[#404753]">Welcome, {{ $affiliate->display_name ?? auth()->user()->name }} · <span class="font-mono text-primary">{{ $affiliate->code }}</span></p>
                @include('creator.partials.setup-note', ['setup' => $setup ?? null])
            </div>
            <form method="get" action="{{ route('creator.dashboard') }}" class="flex items-center gap-2">
                <label for="period" class="creator-font-label text-xs font-semibold uppercase tracking-wide text-[#707884]">Period</label>
                <select id="period" name="period" onchange="this.form.submit()"
                        class="rounded-lg border border-[#bfc7d5] bg-white px-3 py-2 text-sm font-medium text-[#0b1c30] focus:border-primary focus:ring-1 focus:ring-primary">
                    @foreach (['7d' => 'Last 7 days', '30d' => 'Last 30 days', '90d' => 'Last 90 days', 'all' => 'All time'] as $val => $label)
                        <option value="{{ $val }}" @selected($period === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        {{-- KPI row --}}
        <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 lg:gap-4">
            @foreach ([
                ['label' => 'Total clicks', 'value' => number_format($k['total_clicks']), 'icon' => 'ads_click'],
                ['label' => 'Orders', 'value' => number_format($k['total_orders']), 'icon' => 'shopping_bag'],
                ['label' => 'Conversion', 'value' => $k['conversion_rate'].'%', 'icon' => 'percent'],
                ['label' => 'Revenue', 'value' => '$'.number_format($k['total_revenue'], 2), 'icon' => 'payments'],
                ['label' => 'Commission', 'value' => '$'.number_format($k['total_commission'], 2), 'icon' => 'savings'],
                ['label' => 'Available payout', 'value' => '$'.number_format($k['available_payout'], 2), 'icon' => 'account_balance_wallet', 'highlight' => true],
            ] as $card)
                <div class="creator-kpi-card rounded-xl border {{ !empty($card['highlight']) ? 'border-primary/40 bg-[#e5eeff]/50' : 'border-[#bfc7d5] bg-white' }} p-4 shadow-sm">
                    <span class="material-symbols-outlined text-[22px] text-primary">{{ $card['icon'] }}</span>
                    <p class="creator-font-label mt-2 text-[10px] font-semibold uppercase tracking-wide text-[#707884] sm:text-xs">{{ $card['label'] }}</p>
                    <p class="mt-1 text-lg font-bold text-[#0b1c30] sm:text-xl">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-[#707884]">Metrics for {{ $a['period_label'] }}. Available payout is all pending commissions (current balance).</p>

        {{-- Charts --}}
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Revenue &amp; commission over time</h2>
                    @include('creator.partials.dashboard-section-link', ['route' => 'creator.analytics.overview', 'period' => $period])
                </div>
                <div class="mt-4 h-64">
                    <canvas id="creatorRevenueChart"></canvas>
                </div>
            </div>
            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Clicks &amp; orders</h2>
                    @include('creator.partials.dashboard-section-link', ['route' => 'creator.analytics.overview', 'period' => $period, 'label' => 'Full charts →'])
                </div>
                <div class="mt-4 h-52">
                    <canvas id="creatorTrafficChart"></canvas>
                </div>
            </div>
            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Tier progress</h2>
                    @include('creator.partials.dashboard-section-link', ['route' => 'creator.setup.index', 'period' => $period, 'label' => 'Account setup →'])
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-[#0b1c30]">{{ $tier['current_tier_label'] ?? ucfirst($tier['current_tier']) }} · {{ $tier['commission_percent'] }}%</span>
                        @if ($tier['next_tier'])
                            <span class="text-[#707884]">Next: {{ ucfirst($tier['next_tier']) }}</span>
                        @else
                            <span class="text-emerald-700">Top tier</span>
                        @endif
                    </div>
                    @if ($tier['next_tier'] && ($tier['next_threshold_orders'] ?? null))
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-[#eff4ff]">
                            <div class="h-full rounded-full bg-primary transition-all" style="width: {{ $tier['progress_percent'] }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-[#707884]">
                            {{ number_format($tier['rolling_orders'] ?? 0) }} / {{ number_format($tier['next_threshold_orders']) }} attributed orders (last {{ $tier['rolling_days'] }} days)
                        </p>
                    @else
                        <p class="mt-2 text-xs text-[#707884]">{{ number_format($tier['rolling_orders'] ?? 0) }} attributed orders in rolling window.</p>
                    @endif
                    @if (!empty($tier['inactivity_warning']))
                        <p class="mt-2 text-xs text-amber-800">No attributed orders in {{ $tier['inactivity_days'] }}+ days — tier may drop one level on next refresh.</p>
                    @endif
                    @if ($tier['tier_locked'])
                        <p class="mt-2 text-xs text-amber-800">Tier locked by admin — auto tier changes paused.</p>
                    @endif
                </div>
                <div class="mt-6 rounded-lg border border-[#bfc7d5] bg-[#f8f9ff] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#707884]">Storefront link</p>
                    <p class="mt-1 break-all font-mono text-sm text-primary">{{ $a['shop_home_url'] }}</p>
                    <p class="creator-font-label mt-3 flex flex-wrap gap-4 text-sm font-semibold">
                        <a href="{{ route('creator.product-links.index') }}" class="text-primary hover:underline">Product links →</a>
                        <a href="{{ route('creator.promo-codes.index') }}" class="text-primary hover:underline">Assigned coupons →</a>
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            {{-- Top products --}}
            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Top performing products</h2>
                    @include('creator.partials.dashboard-section-link', ['route' => 'creator.analytics.products', 'period' => $period])
                </div>
                @if (count($a['top_products']) === 0)
                    <p class="mt-4 text-sm text-[#707884]">No attributed orders in this period yet.</p>
                @else
                    <ul class="mt-4 divide-y divide-[#bfc7d5]/60">
                        @foreach ($a['top_products'] as $p)
                            <li class="flex items-center justify-between gap-3 py-3 text-sm">
                                <span class="min-w-0 flex-1 font-medium text-[#0b1c30] truncate">{{ $p['name'] }}</span>
                                <span class="shrink-0 text-[#707884]">{{ $p['quantity'] }} sold</span>
                            </li>
                        @endforeach
                    </ul>
                    @include('creator.partials.dashboard-preview-footer', [
                        'shown' => count($a['top_products']),
                        'total' => $a['top_products_total'] ?? 0,
                        'detailRoute' => 'creator.analytics.products',
                        'period' => $period,
                    ])
                @endif
            </div>

            {{-- Link performance --}}
            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Link performance</h2>
                    @include('creator.partials.dashboard-section-link', ['route' => 'creator.analytics.links', 'period' => $period])
                </div>
                @if (count($a['link_performance']) === 0)
                    <p class="mt-4 text-sm text-[#707884]">No referral clicks recorded yet. Share links with <code class="rounded bg-[#e5eeff] px-1">?ref={{ $affiliate->code }}</code>.</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($a['link_performance'] as $link)
                            <li class="flex items-start justify-between gap-3 rounded-lg bg-[#f8f9ff] px-3 py-2.5 text-sm">
                                <div class="min-w-0 flex-1">
                                    @if (! empty($link['shop_url']))
                                        <a href="{{ $link['shop_url'] }}" target="_blank" rel="noopener noreferrer"
                                           class="font-semibold text-[#0b1c30] hover:text-primary hover:underline line-clamp-2"
                                           title="{{ $link['label'] }}">{{ $link['label'] }}</a>
                                    @else
                                        <span class="font-semibold text-[#0b1c30] line-clamp-2">{{ $link['label'] }}</span>
                                    @endif
                                    @if (! empty($link['subtitle']))
                                        <p class="mt-0.5 truncate font-mono text-xs text-[#707884]" title="{{ $link['path'] }}">{{ $link['subtitle'] }}</p>
                                    @endif
                                    @if (($link['type'] ?? '') === 'product')
                                        <span class="mt-1 inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary">Product</span>
                                    @elseif (($link['type'] ?? '') === 'home')
                                        <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">Home</span>
                                    @endif
                                </div>
                                <span class="shrink-0 pt-0.5 font-semibold tabular-nums text-[#0b1c30]">{{ number_format($link['clicks']) }} clicks</span>
                            </li>
                        @endforeach
                    </ul>
                    @include('creator.partials.dashboard-preview-footer', [
                        'shown' => count($a['link_performance']),
                        'total' => $a['link_performance_total'] ?? 0,
                        'detailRoute' => 'creator.analytics.links',
                        'period' => $period,
                    ])
                @endif
            </div>

            {{-- Traffic sources --}}
            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Traffic sources</h2>
                    @include('creator.partials.dashboard-section-link', ['route' => 'creator.analytics.traffic', 'period' => $period])
                </div>
                @if (count($a['traffic_sources']) === 0)
                    <p class="mt-4 text-sm text-[#707884]">UTM and referrer data will appear as visitors use your links.</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($a['traffic_sources'] as $src)
                            <li class="flex justify-between text-sm">
                                <span class="capitalize text-[#404753]">{{ $src['source'] }}</span>
                                <span class="font-semibold text-[#0b1c30]">{{ number_format($src['count']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @include('creator.partials.dashboard-preview-footer', [
                        'shown' => count($a['traffic_sources']),
                        'total' => $a['traffic_sources_total'] ?? 0,
                        'detailRoute' => 'creator.analytics.traffic',
                        'period' => $period,
                    ])
                @endif
            </div>

            {{-- Coupon usage --}}
            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Coupon usage</h2>
                    @include('creator.partials.dashboard-section-link', ['route' => 'creator.promo-codes.index', 'period' => $period, 'label' => 'All coupons →'])
                </div>
                @if (count($a['coupon_usage']) === 0)
                    <p class="mt-4 text-sm text-[#707884]">No affiliate promo codes linked yet.</p>
                @else
                    <ul class="mt-4 divide-y divide-[#bfc7d5]/60">
                        @foreach ($a['coupon_usage'] as $c)
                            <li class="flex justify-between py-2 text-sm">
                                <span class="font-mono font-semibold text-[#0b1c30]">{{ $c['code'] }}</span>
                                <span class="text-[#707884]">{{ $c['uses'] }} uses · ${{ number_format($c['revenue'], 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @include('creator.partials.dashboard-preview-footer', [
                        'shown' => count($a['coupon_usage']),
                        'total' => $a['coupon_usage_total'] ?? 0,
                        'detailRoute' => 'creator.promo-codes.index',
                        'period' => $period,
                        'detailLabel' => 'All coupons →',
                    ])
                @endif
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            @include('creator.partials.dashboard-section-link', ['route' => 'creator.analytics.commissions', 'period' => $period, 'label' => 'Commissions & payouts →'])
        </div>

        {{-- Commissions --}}
        <div class="mt-2 grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-5">
                <p class="creator-font-label text-xs font-semibold uppercase text-amber-900">Pending</p>
                <p class="mt-1 text-2xl font-bold text-[#0b1c30]">${{ number_format($comm['pending'], 2) }}</p>
                <p class="text-xs text-[#707884]">{{ $comm['counts']['pending'] }} commission(s)</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-5">
                <p class="creator-font-label text-xs font-semibold uppercase text-emerald-900">Paid</p>
                <p class="mt-1 text-2xl font-bold text-[#0b1c30]">${{ number_format($comm['paid'], 2) }}</p>
                <p class="text-xs text-[#707884]">{{ $comm['counts']['paid'] }} commission(s)</p>
            </div>
            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5">
                <p class="creator-font-label text-xs font-semibold uppercase text-[#707884]">Void / adjusted</p>
                <p class="mt-1 text-2xl font-bold text-[#0b1c30]">${{ number_format($comm['void'], 2) }}</p>
                <p class="text-xs text-[#707884]">{{ $comm['counts']['void'] }} voided</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm overflow-x-auto">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Recent commissions</h2>
                    @include('creator.partials.dashboard-section-link', ['route' => 'creator.analytics.commissions', 'period' => $period, 'label' => 'View all →'])
                </div>
                <table class="mt-4 w-full min-w-[320px] text-left text-sm">
                    <thead class="text-xs uppercase text-[#707884]">
                        <tr><th class="pb-2">Order</th><th class="pb-2">Amount</th><th class="pb-2">Status</th><th class="pb-2">Date</th></tr>
                    </thead>
                    <tbody class="divide-y divide-[#bfc7d5]/50">
                        @forelse ($a['recent_commissions'] as $row)
                            <tr>
                                <td class="py-2 font-mono text-xs">{{ $row['order_number'] ?? '—' }}</td>
                                <td class="py-2 font-semibold">${{ number_format($row['amount'], 2) }}</td>
                                <td class="py-2 capitalize">{{ $row['status'] }}</td>
                                <td class="py-2 text-[#707884]">{{ $row['created_at'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-[#707884]">No commissions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @include('creator.partials.dashboard-preview-footer', [
                    'shown' => count($a['recent_commissions']),
                    'total' => $a['recent_commissions_total'] ?? 0,
                    'detailRoute' => 'creator.analytics.commissions',
                    'period' => $period,
                ])
            </div>
            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm overflow-x-auto">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Payout history</h2>
                    @include('creator.partials.dashboard-section-link', ['route' => 'creator.analytics.commissions', 'period' => $period, 'label' => 'View all →'])
                </div>
                <table class="mt-4 w-full min-w-[280px] text-left text-sm">
                    <thead class="text-xs uppercase text-[#707884]">
                        <tr><th class="pb-2">Date</th><th class="pb-2">Amount</th><th class="pb-2">Type</th></tr>
                    </thead>
                    <tbody class="divide-y divide-[#bfc7d5]/50">
                        @forelse ($a['payout_history'] as $row)
                            <tr>
                                <td class="py-2 text-[#707884]">{{ $row['date'] }}</td>
                                <td class="py-2 font-semibold {{ $row['amount'] < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                    {{ $row['amount'] < 0 ? '-' : '' }}${{ number_format(abs($row['amount']), 2) }}
                                </td>
                                <td class="py-2 capitalize text-[#404753]">{{ str_replace('_', ' ', $row['type']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-[#707884]">No payouts recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @include('creator.partials.dashboard-preview-footer', [
                    'shown' => count($a['payout_history']),
                    'total' => $a['payout_history_total'] ?? 0,
                    'detailRoute' => 'creator.analytics.commissions',
                    'period' => $period,
                ])
            </div>
        </div>

        {{-- Sample requests summary --}}
        @php $sq = $sampleSummary; $sampleQuota = $sq['quota']; $recentSamples = $sq['recent']; @endphp
        <div class="mt-8">
            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">inventory_2</span>
                        <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Sample requests</h2>
                    </div>
                    <a href="{{ route('creator.sample-requests.index') }}" class="text-xs font-semibold text-primary hover:underline">View all →</a>
                </div>
                <p class="mt-2 text-sm text-[#707884]">
                    Quota: {{ $sampleQuota['used'] }}/{{ $sampleQuota['max_requests'] }} ({{ $sampleQuota['period_days'] }}d) ·
                    <span class="font-semibold capitalize">{{ $sampleQuota['tier'] }}</span> tier
                    @if ($sampleQuota['remaining'] > 0)
                        · <span class="text-emerald-700">{{ $sampleQuota['remaining'] }} remaining</span>
                    @endif
                </p>
                @if ($recentSamples->isEmpty())
                    <p class="mt-3 text-sm text-[#707884]">No requests yet.</p>
                    @if ($sampleQuota['remaining'] > 0)
                        <a href="{{ route('creator.sample-requests.create') }}" class="mt-3 inline-flex text-sm font-semibold text-primary hover:underline">Request a sample →</a>
                    @endif
                @else
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($recentSamples as $sr)
                            <li class="flex justify-between gap-2">
                                <a href="{{ route('creator.sample-requests.show', $sr) }}" class="truncate text-[#0b1c30] hover:text-primary hover:underline">{{ $sr->product?->name }}</a>
                                @include('creator.sample-requests.partials.status-badge', ['status' => $sr->status])
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const series = @json($a['series']);
    const grid = 'rgba(191, 199, 213, 0.35)';
    const primary = '#0195fe';
    const rose = '#f43f5e';

    if (document.getElementById('creatorRevenueChart')) {
        new Chart(document.getElementById('creatorRevenueChart'), {
            type: 'line',
            data: {
                labels: series.labels,
                datasets: [
                    { label: 'Revenue', data: series.revenue, borderColor: primary, backgroundColor: 'rgba(1, 149, 254, 0.08)', fill: true, tension: 0.35 },
                    { label: 'Commission', data: series.commission, borderColor: rose, backgroundColor: 'rgba(244, 63, 94, 0.06)', fill: true, tension: 0.35 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, grid: { color: grid } }, x: { grid: { display: false } } },
            },
        });
    }

    if (document.getElementById('creatorTrafficChart')) {
        new Chart(document.getElementById('creatorTrafficChart'), {
            type: 'bar',
            data: {
                labels: series.labels,
                datasets: [
                    { label: 'Clicks', data: series.clicks, backgroundColor: 'rgba(1, 149, 254, 0.65)', borderRadius: 4 },
                    { label: 'Orders', data: series.orders, backgroundColor: 'rgba(0, 96, 167, 0.85)', borderRadius: 4 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, grid: { color: grid } }, x: { grid: { display: false } } },
            },
        });
    }
})();
</script>
@endpush
