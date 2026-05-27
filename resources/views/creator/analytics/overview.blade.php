@extends('layouts.creator')

@section('title', 'Analytics overview')

@push('styles')
<style>
    .creator-kpi-card { transition: box-shadow 0.2s ease; }
    .creator-kpi-card:hover { box-shadow: 0 8px 24px rgba(0, 96, 167, 0.1); }
</style>
@endpush

@section('content')
@php $k = $data['kpis']; $tier = $data['tier_progress']; @endphp
<div class="mx-auto max-w-7xl px-5 py-10 md:px-16 md:py-12">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('creator.dashboard', ['period' => $period]) }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>
            <h1 class="creator-font-headline mt-2 text-3xl font-bold text-[#0b1c30]">Analytics overview</h1>
            <p class="mt-1 text-sm text-[#707884]">{{ $periodLabel }}</p>
        </div>
        @include('creator.partials.analytics-period-form', ['period' => $period, 'action' => route('creator.analytics.overview')])
    </div>

    @include('creator.partials.analytics-nav', ['period' => $period])

    <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 lg:gap-4">
        @foreach ([
            ['label' => 'Total clicks', 'value' => number_format($k['total_clicks'])],
            ['label' => 'Orders', 'value' => number_format($k['total_orders'])],
            ['label' => 'Conversion', 'value' => $k['conversion_rate'].'%'],
            ['label' => 'Revenue', 'value' => '$'.number_format($k['total_revenue'], 2)],
            ['label' => 'Commission', 'value' => '$'.number_format($k['total_commission'], 2)],
            ['label' => 'Available payout', 'value' => '$'.number_format($k['available_payout'], 2), 'highlight' => true],
        ] as $card)
            <div class="creator-kpi-card rounded-xl border {{ !empty($card['highlight']) ? 'border-primary/40 bg-[#e5eeff]/50' : 'border-[#bfc7d5] bg-white' }} p-4 shadow-sm">
                <p class="creator-font-label text-[10px] font-semibold uppercase tracking-wide text-[#707884] sm:text-xs">{{ $card['label'] }}</p>
                <p class="mt-1 text-lg font-bold text-[#0b1c30] sm:text-xl">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Revenue &amp; commission</h2>
            <div class="mt-4 h-72"><canvas id="creatorRevenueChart"></canvas></div>
        </div>
        <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
            <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Clicks &amp; orders</h2>
            <div class="mt-4 h-60"><canvas id="creatorTrafficChart"></canvas></div>
        </div>
        <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
            <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Tier progress</h2>
            <p class="mt-3 text-sm font-semibold text-[#0b1c30]">{{ $tier['current_tier_label'] ?? ucfirst($tier['current_tier']) }} · {{ $tier['commission_percent'] }}%</p>
            @if ($tier['next_tier'] && ($tier['next_threshold_orders'] ?? null))
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-[#eff4ff]">
                    <div class="h-full rounded-full bg-primary" style="width: {{ $tier['progress_percent'] }}%"></div>
                </div>
                <p class="mt-2 text-xs text-[#707884]">{{ number_format($tier['rolling_orders'] ?? 0) }} / {{ number_format($tier['next_threshold_orders']) }} orders ({{ $tier['rolling_days'] }}d)</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const series = @json($data['series']);
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
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, grid: { color: grid } }, x: { grid: { display: false } } } },
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
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, grid: { color: grid } }, x: { grid: { display: false } } } },
        });
    }
})();
</script>
@endpush
