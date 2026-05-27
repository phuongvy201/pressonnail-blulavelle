@extends('layouts.creator')

@section('title', 'Sample requests')

@section('content')
<div class="mx-auto max-w-5xl px-5 py-10 md:px-16 md:py-12">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('creator.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>
            <h1 class="creator-font-headline mt-2 text-3xl font-bold text-[#0b1c30]">Sample requests</h1>
            <p class="mt-2 text-sm text-[#404753]">
                Tier <span class="font-semibold capitalize">{{ $quota['tier'] }}</span> —
                {{ $quota['used'] }}/{{ $quota['max_requests'] }} used (last {{ $quota['period_days'] }} days)
                @if ($quota['remaining'] > 0)
                    · <span class="text-emerald-700 font-semibold">{{ $quota['remaining'] }} remaining</span>
                @endif
            </p>
        </div>
        @if ($quota['remaining'] > 0)
            <a href="{{ route('creator.sample-requests.create') }}"
               class="creator-btn-primary creator-font-label inline-flex shrink-0 items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold tracking-wide">
                Request sample
            </a>
        @endif
    </div>

    @include('creator.partials.setup-note', ['setup' => $setup ?? null])

    @if (session('success'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="mt-6 rounded-xl border border-[#bfc7d5] bg-[#f8f9ff] p-4 text-sm text-[#404753]">
        Samples are for affiliate-eligible products in stock. Each request is reviewed manually. Include size/variant and a complete US shipping address.
    </div>

    <div class="mt-8 overflow-hidden rounded-xl border border-[#bfc7d5] bg-white shadow-sm">
        @if ($requests->isEmpty())
            <p class="p-8 text-center text-sm text-[#707884]">No sample requests yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-[#f8f9ff] text-xs uppercase text-[#707884]">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Variant / size</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#bfc7d5]/60">
                        @foreach ($requests as $req)
                            <tr>
                                <td class="px-4 py-3 text-[#707884]">{{ $req->created_at?->format('M j, Y') }}</td>
                                <td class="px-4 py-3 font-medium text-[#0b1c30]">{{ $req->product?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-[#404753]">
                                    @if ($req->selected_variant)
                                        {{ $req->selected_variant['variant_name'] ?? 'Variant' }}
                                    @elseif ($req->size_preset)
                                        Size {{ $req->size_preset }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @include('creator.sample-requests.partials.status-badge', ['status' => $req->status])
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('creator.sample-requests.show', $req) }}" class="font-semibold text-primary hover:underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($requests->hasPages())
                <div class="border-t border-[#bfc7d5] px-4 py-3">{{ $requests->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
