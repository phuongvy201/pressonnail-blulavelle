@props([
    'shown',
    'total',
    'detailRoute' => null,
    'period' => '30d',
    'detailLabel' => 'View all →',
])

@if ($total > $shown && $detailRoute)
    <p class="mt-3 border-t border-[#bfc7d5]/50 pt-3 text-xs text-[#707884]">
        Showing top {{ number_format($shown) }} of {{ number_format($total) }}.
        <a href="{{ route($detailRoute, ['period' => $period]) }}" class="font-semibold text-primary hover:underline">{{ $detailLabel }}</a>
    </p>
@endif
