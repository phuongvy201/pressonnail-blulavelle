@extends('layouts.creator')

@section('title', 'Products sold')

@section('content')
<div class="mx-auto max-w-5xl px-5 py-10 md:px-16 md:py-12">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('creator.dashboard', ['period' => $period]) }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>
            <h1 class="creator-font-headline mt-2 text-3xl font-bold text-[#0b1c30]">Products sold</h1>
            <p class="mt-1 text-sm text-[#707884]">Attributed paid orders · {{ $periodLabel }}</p>
        </div>
        @include('creator.partials.analytics-period-form', ['period' => $period, 'action' => route('creator.analytics.products')])
    </div>

    @include('creator.partials.analytics-nav', ['period' => $period])

    <div class="mt-6 rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm overflow-x-auto">
        @if (count($products) === 0)
            <p class="text-sm text-[#707884]">No attributed product sales in this period.</p>
        @else
            <table class="w-full min-w-[480px] text-left text-sm">
                <thead class="text-xs uppercase text-[#707884]">
                    <tr>
                        <th class="pb-3">Product</th>
                        <th class="pb-3 text-right">Qty sold</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#bfc7d5]/60">
                    @foreach ($products as $p)
                        <tr>
                            <td class="py-3 font-medium text-[#0b1c30]">{{ $p['name'] }}</td>
                            <td class="py-3 text-right text-[#707884]">{{ number_format($p['quantity']) }}</td>
                            <td class="py-3 text-right">
                                @if (! empty($p['slug']))
                                    <a href="{{ route('products.show', $p['slug']) }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-primary hover:underline">View on shop</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
