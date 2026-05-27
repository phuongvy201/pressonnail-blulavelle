@extends('layouts.creator')

@section('title', 'Commissions')

@section('content')
@php $comm = $data['breakdown']; @endphp
<div class="mx-auto max-w-6xl px-5 py-10 md:px-16 md:py-12">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('creator.dashboard', ['period' => $period]) }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>
            <h1 class="creator-font-headline mt-2 text-3xl font-bold text-[#0b1c30]">Commissions &amp; payouts</h1>
            <p class="mt-1 text-sm text-[#707884]">{{ $data['period_label'] }}</p>
        </div>
        @include('creator.partials.analytics-period-form', ['period' => $period, 'action' => route('creator.analytics.commissions')])
    </div>

    @include('creator.partials.analytics-nav', ['period' => $period])

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-5">
            <p class="text-xs font-semibold uppercase text-amber-900">Pending</p>
            <p class="mt-1 text-2xl font-bold">${{ number_format($comm['pending'], 2) }}</p>
            <p class="text-xs text-[#707884]">{{ $comm['counts']['pending'] }} row(s)</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-5">
            <p class="text-xs font-semibold uppercase text-emerald-900">Paid</p>
            <p class="mt-1 text-2xl font-bold">${{ number_format($comm['paid'], 2) }}</p>
            <p class="text-xs text-[#707884]">{{ $comm['counts']['paid'] }} row(s)</p>
        </div>
        <div class="rounded-xl border border-[#bfc7d5] bg-white p-5">
            <p class="text-xs font-semibold uppercase text-[#707884]">Void</p>
            <p class="mt-1 text-2xl font-bold">${{ number_format($comm['void'], 2) }}</p>
            <p class="text-xs text-[#707884]">{{ $comm['counts']['void'] }} row(s)</p>
        </div>
    </div>

    <div class="mt-8 rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm overflow-x-auto">
        <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Attributed orders</h2>
        <p class="mt-1 text-xs text-[#707884]">Paid orders linked to your ref. If no commission was created, the reason is shown below.</p>
        <table class="mt-4 w-full min-w-[560px] text-left text-sm">
            <thead class="text-xs uppercase text-[#707884]">
                <tr>
                    <th class="pb-2">Order</th>
                    <th class="pb-2">Date</th>
                    <th class="pb-2 text-right">Total</th>
                    <th class="pb-2">Commission</th>
                    <th class="pb-2">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#bfc7d5]/50">
                @forelse ($data['attributed_orders'] ?? [] as $row)
                    <tr>
                        <td class="py-2 font-mono text-xs">{{ $row['order_number'] }}</td>
                        <td class="py-2 text-[#707884]">{{ $row['date'] }}</td>
                        <td class="py-2 text-right tabular-nums">${{ number_format($row['total'], 2) }}</td>
                        <td class="py-2 tabular-nums">
                            @if ($row['commission_amount'] !== null)
                                <span class="font-semibold text-primary">${{ number_format($row['commission_amount'], 2) }}</span>
                                @if ($row['commission_status'])
                                    <span class="block text-xs capitalize text-[#707884]">{{ $row['commission_status'] }}</span>
                                @endif
                            @else
                                <span class="text-[#707884]">—</span>
                            @endif
                        </td>
                        <td class="py-2">
                            @if ($row['eligibility'] === 'eligible')
                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Eligible</span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900">No commission</span>
                                @if (! empty($row['note_label']))
                                    <p class="mt-1 max-w-xs text-xs text-[#707884] leading-snug">{{ $row['note_label'] }}</p>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-4 text-[#707884]">No attributed paid orders in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm overflow-x-auto">
            <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">All commissions</h2>
            <table class="mt-4 w-full min-w-[400px] text-left text-sm">
                <thead class="text-xs uppercase text-[#707884]">
                    <tr>
                        <th class="pb-2">Order</th>
                        <th class="pb-2">Base</th>
                        <th class="pb-2">Rate</th>
                        <th class="pb-2">Amount</th>
                        <th class="pb-2">Status</th>
                        <th class="pb-2">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#bfc7d5]/50">
                    @forelse ($data['recent'] as $row)
                        <tr>
                            <td class="py-2 font-mono text-xs">{{ $row['order_number'] ?? '—' }}</td>
                            <td class="py-2 text-[#707884]">${{ number_format($row['base'], 2) }}</td>
                            <td class="py-2 text-[#707884]">{{ rtrim(rtrim(number_format($row['rate'], 2), '0'), '.') }}%</td>
                            <td class="py-2 font-semibold">${{ number_format($row['amount'], 2) }}</td>
                            <td class="py-2 capitalize">{{ $row['status'] }}</td>
                            <td class="py-2 text-[#707884]">{{ $row['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-[#707884]">No commissions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm overflow-x-auto">
            <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Payout history</h2>
            <table class="mt-4 w-full min-w-[320px] text-left text-sm">
                <thead class="text-xs uppercase text-[#707884]">
                    <tr><th class="pb-2">Date</th><th class="pb-2">Amount</th><th class="pb-2">Type</th><th class="pb-2">Note</th></tr>
                </thead>
                <tbody class="divide-y divide-[#bfc7d5]/50">
                    @forelse ($data['payout_history'] as $row)
                        <tr>
                            <td class="py-2 text-[#707884]">{{ $row['date'] }}</td>
                            <td class="py-2 font-semibold {{ $row['amount'] < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                {{ $row['amount'] < 0 ? '-' : '' }}${{ number_format(abs($row['amount']), 2) }}
                            </td>
                            <td class="py-2 capitalize">{{ str_replace('_', ' ', $row['type']) }}</td>
                            <td class="py-2 text-xs text-[#707884]">{{ $row['note'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-[#707884]">No payouts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
