@extends('layouts.admin')

@section('title', 'Sample request #'.$sampleRequest->id)

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <a href="{{ route('admin.sample-requests.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Sample requests</a>

    @if (session('success'))
        <div class="mt-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Request #{{ $sampleRequest->id }}</h1>
        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize bg-gray-100">{{ $sampleRequest->status }}</span>
    </div>

    <dl class="mt-8 bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
        <div class="px-4 py-3 grid sm:grid-cols-3 gap-2">
            <dt class="text-sm text-gray-500">Affiliate</dt>
            <dd class="sm:col-span-2 text-sm">{{ $sampleRequest->affiliate?->display_name }} <span class="font-mono text-gray-500">({{ $sampleRequest->affiliate?->code }})</span> · tier {{ $sampleRequest->tier_at_request }}</dd>
        </div>
        <div class="px-4 py-3 grid sm:grid-cols-3 gap-2">
            <dt class="text-sm text-gray-500">Product</dt>
            <dd class="sm:col-span-2 text-sm">{{ $sampleRequest->product?->name }}
                @if ($sampleRequest->productVariant)
                    <br><span class="text-gray-600">Variant: {{ $sampleRequest->productVariant->variant_name }}</span>
                @elseif ($sampleRequest->size_preset)
                    <br><span class="text-gray-600">Size: {{ $sampleRequest->size_preset }}</span>
                @endif
                · Qty {{ $sampleRequest->quantity }}
            </dd>
        </div>
        <div class="px-4 py-3 grid sm:grid-cols-3 gap-2">
            <dt class="text-sm text-gray-500">Ship to</dt>
            <dd class="sm:col-span-2 text-sm whitespace-pre-line">{{ $sampleRequest->shipping_name }}
{{ $sampleRequest->shipping_address }}@if($sampleRequest->shipping_address_line2)
{{ $sampleRequest->shipping_address_line2 }}@endif
{{ $sampleRequest->shipping_city }}, {{ $sampleRequest->shipping_state }} {{ $sampleRequest->shipping_postal_code }}
{{ $sampleRequest->shipping_country }}
@if($sampleRequest->shipping_phone){{ $sampleRequest->shipping_phone }}@endif</dd>
        </div>
        @if ($sampleRequest->creator_notes)
            <div class="px-4 py-3 grid sm:grid-cols-3 gap-2">
                <dt class="text-sm text-gray-500">Creator notes</dt>
                <dd class="sm:col-span-2 text-sm whitespace-pre-wrap">{{ $sampleRequest->creator_notes }}</dd>
            </div>
        @endif
        @if ($sampleRequest->order)
            <div class="px-4 py-3 grid sm:grid-cols-3 gap-2">
                <dt class="text-sm text-gray-500">Order</dt>
                <dd class="sm:col-span-2 text-sm font-mono">
                    <a href="{{ route('admin.orders.show', $sampleRequest->order) }}" class="text-blue-600 hover:underline">{{ $sampleRequest->order->order_number }}</a>
                </dd>
            </div>
        @endif
        @if ($sampleRequest->tracking_number)
            <div class="px-4 py-3 grid sm:grid-cols-3 gap-2">
                <dt class="text-sm text-gray-500">Tracking</dt>
                <dd class="sm:col-span-2 text-sm font-mono">{{ $sampleRequest->tracking_number }}</dd>
            </div>
        @endif
    </dl>

    @if ($sampleRequest->isPending())
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <form method="post" action="{{ route('admin.sample-requests.approve', $sampleRequest) }}" class="rounded-xl border border-green-200 bg-green-50/30 p-5 space-y-3">
                @csrf
                <h2 class="font-semibold text-green-900">Approve</h2>
                <p class="text-xs text-gray-600">Creates a $0 internal order and reserves stock.</p>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="create_order" value="1" checked class="rounded">
                    Create fulfillment order
                </label>
                <textarea name="admin_notes" rows="2" class="w-full rounded border border-gray-300 text-sm" placeholder="Admin notes (optional)">{{ old('admin_notes') }}</textarea>
                <button type="submit" class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">Approve</button>
            </form>
            <form method="post" action="{{ route('admin.sample-requests.reject', $sampleRequest) }}" class="rounded-xl border border-red-200 bg-red-50/30 p-5 space-y-3">
                @csrf
                <h2 class="font-semibold text-red-900">Reject</h2>
                <textarea name="rejection_reason" rows="3" required class="w-full rounded border border-gray-300 text-sm" placeholder="Reason for creator *">{{ old('rejection_reason') }}</textarea>
                <textarea name="admin_notes" rows="2" class="w-full rounded border border-gray-300 text-sm" placeholder="Internal notes">{{ old('admin_notes') }}</textarea>
                <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Reject</button>
            </form>
        </div>
    @endif

    @if ($sampleRequest->status === \App\Models\AffiliateSampleRequest::STATUS_APPROVED)
        <form method="post" action="{{ route('admin.sample-requests.ship', $sampleRequest) }}" class="mt-8 rounded-xl border border-gray-200 bg-white p-5 space-y-3">
            @csrf
            <h2 class="font-semibold text-gray-900">Mark shipped</h2>
            <input type="text" name="tracking_number" value="{{ old('tracking_number', $sampleRequest->tracking_number) }}" placeholder="Tracking number" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Mark shipped</button>
        </form>
    @endif

    @if ($sampleRequest->status === \App\Models\AffiliateSampleRequest::STATUS_SHIPPED)
        <form method="post" action="{{ route('admin.sample-requests.deliver', $sampleRequest) }}" class="mt-8">
            @csrf
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Mark delivered</button>
        </form>
    @endif
</div>
@endsection
