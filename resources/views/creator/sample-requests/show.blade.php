@extends('layouts.creator')

@section('title', 'Sample request #'.$sampleRequest->id)

@section('content')
<div class="mx-auto max-w-3xl px-5 py-10 md:px-16 md:py-12">
    <a href="{{ route('creator.sample-requests.index') }}" class="text-sm font-semibold text-primary hover:underline">← Sample requests</a>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="creator-font-headline text-2xl font-bold text-[#0b1c30]">Request #{{ $sampleRequest->id }}</h1>
        @include('creator.sample-requests.partials.status-badge', ['status' => $sampleRequest->status])
    </div>
    <p class="mt-1 text-sm text-[#707884]">Submitted {{ $sampleRequest->created_at?->format('M j, Y g:i A') }}</p>

    <dl class="mt-8 divide-y divide-[#bfc7d5]/60 rounded-xl border border-[#bfc7d5] bg-white shadow-sm">
        <div class="px-5 py-4">
            <dt class="text-xs font-semibold uppercase text-[#707884]">Product</dt>
            <dd class="mt-1 font-medium text-[#0b1c30]">{{ $sampleRequest->product?->name }}</dd>
            @if ($sampleRequest->selected_variant)
                <dd class="mt-1 text-sm text-[#404753]">Variant: {{ $sampleRequest->selected_variant['variant_name'] ?? '—' }}</dd>
            @elseif ($sampleRequest->size_preset)
                <dd class="mt-1 text-sm text-[#404753]">Size: {{ $sampleRequest->size_preset }}</dd>
            @endif
            <dd class="mt-1 text-sm text-[#707884]">Qty: {{ $sampleRequest->quantity }}</dd>
        </div>
        <div class="px-5 py-4">
            <dt class="text-xs font-semibold uppercase text-[#707884]">Ship to</dt>
            <dd class="mt-1 text-sm text-[#0b1c30]">
                {{ $sampleRequest->shipping_name }}<br>
                {{ $sampleRequest->shipping_address }}@if($sampleRequest->shipping_address_line2), {{ $sampleRequest->shipping_address_line2 }}@endif<br>
                {{ $sampleRequest->shipping_city }}@if($sampleRequest->shipping_state), {{ $sampleRequest->shipping_state }}@endif {{ $sampleRequest->shipping_postal_code }}<br>
                {{ $sampleRequest->shipping_country }}
                @if ($sampleRequest->shipping_phone)<br>{{ $sampleRequest->shipping_phone }}@endif
            </dd>
        </div>
        @if ($sampleRequest->creator_notes)
            <div class="px-5 py-4">
                <dt class="text-xs font-semibold uppercase text-[#707884]">Your notes</dt>
                <dd class="mt-1 text-sm text-[#404753] whitespace-pre-wrap">{{ $sampleRequest->creator_notes }}</dd>
            </div>
        @endif
        @if ($sampleRequest->rejection_reason)
            <div class="px-5 py-4 bg-red-50/50">
                <dt class="text-xs font-semibold uppercase text-red-800">Rejection reason</dt>
                <dd class="mt-1 text-sm text-red-900 whitespace-pre-wrap">{{ $sampleRequest->rejection_reason }}</dd>
            </div>
        @endif
        @if ($sampleRequest->tracking_number)
            <div class="px-5 py-4">
                <dt class="text-xs font-semibold uppercase text-[#707884]">Tracking</dt>
                <dd class="mt-1 font-mono text-sm text-primary">{{ $sampleRequest->tracking_number }}</dd>
            </div>
        @endif
        @if ($sampleRequest->order)
            <div class="px-5 py-4">
                <dt class="text-xs font-semibold uppercase text-[#707884]">Fulfillment order</dt>
                <dd class="mt-1 text-sm font-mono text-[#0b1c30]">{{ $sampleRequest->order->order_number }}</dd>
            </div>
        @endif
    </dl>
</div>
@endsection
