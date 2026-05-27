@extends('layouts.admin')

@section('title', 'Creator analytics')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Creator analytics</h1>
            <p class="mt-1 text-sm text-gray-600">Clicks, orders, revenue &amp; commission per affiliate — same metrics as the creator dashboard.</p>
        </div>
        @include('admin.affiliates.partials.analytics-period-form', [
            'action' => route('admin.affiliates.analytics.index'),
            'period' => $period,
        ])
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['label' => 'Total clicks', 'value' => number_format($totals['clicks'])],
            ['label' => 'Attributed orders', 'value' => number_format($totals['orders'])],
            ['label' => 'Revenue', 'value' => '$'.number_format($totals['revenue'], 2)],
            ['label' => 'Commission', 'value' => '$'.number_format($totals['commission'], 2)],
        ] as $kpi)
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-500">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-bold text-gray-900">{{ $kpi['value'] }}</p>
            </div>
        @endforeach
    </div>
    <p class="text-xs text-gray-500">Program totals for {{ $periodLabel }}.</p>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Creator</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tier</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Clicks</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Orders</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Conv.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Revenue</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Commission</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        @php $a = $row['affiliate']; @endphp
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm font-semibold text-gray-900">{{ $a->code }}</span>
                                @if ($a->display_name)
                                    <span class="block text-xs text-gray-500">{{ $a->display_name }}</span>
                                @endif
                                @if ($a->user?->email)
                                    <span class="block text-xs text-gray-400">{{ $a->user->email }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm capitalize text-gray-700">{{ $a->tier }}</td>
                            <td class="px-4 py-3 text-right text-sm tabular-nums">{{ number_format($row['clicks']) }}</td>
                            <td class="px-4 py-3 text-right text-sm tabular-nums">{{ number_format($row['orders']) }}</td>
                            <td class="px-4 py-3 text-right text-sm tabular-nums">{{ $row['conversion_rate'] }}%</td>
                            <td class="px-4 py-3 text-right text-sm tabular-nums">${{ number_format($row['revenue'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm tabular-nums">${{ number_format($row['commission'], 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.affiliates.analytics.show', ['affiliate' => $a, 'period' => $period]) }}"
                                   class="text-sm font-medium text-violet-600 hover:underline">Dashboard →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">No affiliates yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
