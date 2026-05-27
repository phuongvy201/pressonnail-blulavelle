@extends('layouts.creator')

@section('title', 'Link performance')

@section('content')
<div class="mx-auto max-w-5xl px-5 py-10 md:px-16 md:py-12">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('creator.dashboard', ['period' => $period]) }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>
            <h1 class="creator-font-headline mt-2 text-3xl font-bold text-[#0b1c30]">Link performance</h1>
            <p class="mt-1 text-sm text-[#707884]">Clicks per landing page · {{ $periodLabel }}</p>
        </div>
        @include('creator.partials.analytics-period-form', ['period' => $period, 'action' => route('creator.analytics.links')])
    </div>

    @include('creator.partials.analytics-nav', ['period' => $period])

    <p class="mt-6 text-sm text-[#404753]">
        <a href="{{ route('creator.product-links.index') }}" class="font-semibold text-primary hover:underline">Get product referral links →</a>
    </p>

    <div class="mt-6 rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
        @if (count($links) === 0)
            <p class="text-sm text-[#707884]">No clicks with <code class="rounded bg-[#e5eeff] px-1">?ref={{ $affiliate->code }}</code> in this period.</p>
        @else
            <ul class="divide-y divide-[#bfc7d5]/60">
                @foreach ($links as $link)
                    <li class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            @if (! empty($link['shop_url']))
                                <a href="{{ $link['shop_url'] }}" target="_blank" rel="noopener noreferrer" class="text-base font-semibold text-[#0b1c30] hover:text-primary hover:underline">{{ $link['label'] }}</a>
                            @else
                                <p class="text-base font-semibold text-[#0b1c30]">{{ $link['label'] }}</p>
                            @endif
                            @if (! empty($link['subtitle']))
                                <p class="mt-0.5 font-mono text-xs text-[#707884]">{{ $link['subtitle'] }}</p>
                            @endif
                            @if (! empty($link['referral_url']))
                                <p id="ref-link-{{ $loop->index }}" class="mt-2 break-all font-mono text-xs text-primary">{{ $link['referral_url'] }}</p>
                                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('ref-link-{{ $loop->index }}').textContent.trim())"
                                        class="mt-1 text-xs font-semibold text-primary hover:underline">Copy referral link</button>
                            @endif
                        </div>
                        <span class="shrink-0 text-lg font-bold tabular-nums text-[#0b1c30]">{{ number_format($link['clicks']) }} clicks</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
