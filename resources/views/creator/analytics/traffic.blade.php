@extends('layouts.creator')

@section('title', 'Traffic sources')

@section('content')
<div class="mx-auto max-w-5xl px-5 py-10 md:px-16 md:py-12">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('creator.dashboard', ['period' => $period]) }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>
            <h1 class="creator-font-headline mt-2 text-3xl font-bold text-[#0b1c30]">Traffic sources</h1>
            <p class="mt-1 text-sm text-[#707884]">From UTM tags and referrers on clicks with your ref code · {{ $periodLabel }}</p>
        </div>
        @include('creator.partials.analytics-period-form', ['period' => $period, 'action' => route('creator.analytics.traffic')])
    </div>

    @include('creator.partials.analytics-nav', ['period' => $period])

    <div class="mt-6 rounded-lg border border-[#bfc7d5] bg-[#f8f9ff] p-4 text-sm text-[#404753]">
        <p>Links from Instagram/TikTok without UTM are often detected automatically (referrer or in-app browser). For clearer reports, add UTM when you can:</p>
        <code class="mt-2 block break-all rounded bg-white px-2 py-1 font-mono text-xs text-primary">?ref={{ $affiliate->code }}&amp;utm_source=instagram&amp;utm_medium=bio</code>
    </div>

    <div class="mt-6 rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
        @if (count($sources) === 0)
            <p class="text-sm text-[#707884]">No source data yet.</p>
        @else
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-[#707884]">
                    <tr><th class="pb-3">Source</th><th class="pb-3 text-right">Clicks / events</th></tr>
                </thead>
                <tbody class="divide-y divide-[#bfc7d5]/60">
                    @foreach ($sources as $src)
                        <tr>
                            <td class="py-3 capitalize font-medium text-[#0b1c30]">{{ $src['source'] }}</td>
                            <td class="py-3 text-right font-semibold tabular-nums">{{ number_format($src['count']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
