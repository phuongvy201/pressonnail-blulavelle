@extends('layouts.admin')

@section('title', 'Try-On #' . Str::limit($trial->uuid, 8, ''))

@section('content')
@php
    $statusColors = [
        'pending' => 'bg-amber-100 text-amber-800',
        'processing' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-800',
    ];
@endphp
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.virtual-nail-trials.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">&larr; Back to history</a>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">Try-On Detail</h1>
            <p class="mt-1 text-sm text-gray-500 font-mono">{{ $trial->uuid }}</p>
        </div>
        <span class="inline-flex self-start px-3 py-1 rounded-full text-sm font-bold {{ $statusColors[$trial->status] ?? 'bg-gray-100 text-gray-700' }}">
            {{ ucfirst($trial->status) }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Details</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Product</dt>
                        <dd class="font-medium text-gray-900">
                            @if($trial->product)
                                <a href="{{ route('products.show', $trial->product->slug) }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">
                                    {{ $trial->product->name }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Shape / Length</dt>
                        <dd class="font-medium text-gray-900">{{ $trial->nail_shape }} · {{ $trial->nail_length }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Customer</dt>
                        <dd class="font-medium text-gray-900">
                            @if($trial->user)
                                {{ $trial->user->name }}<br>
                                <span class="text-gray-500 font-normal">{{ $trial->user->email }}</span>
                            @else
                                Guest session<br>
                                <span class="text-xs font-mono text-gray-400">{{ $trial->session_id }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Provider</dt>
                        <dd class="font-medium text-gray-900">{{ $trial->provider ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Created</dt>
                        <dd class="font-medium text-gray-900">{{ $trial->created_at->format('M j, Y H:i:s') }}</dd>
                    </div>
                    @if($trial->completed_at)
                    <div>
                        <dt class="text-gray-500">Completed</dt>
                        <dd class="font-medium text-gray-900">{{ $trial->completed_at->format('M j, Y H:i:s') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            @if($trial->error_message)
            <div class="bg-red-50 rounded-xl border border-red-200 p-5">
                <h2 class="text-sm font-bold text-red-800 mb-2">Error</h2>
                <p class="text-sm text-red-700 break-words">{{ $trial->error_message }}</p>
            </div>
            @endif
        </div>

        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <h2 class="text-sm font-bold text-gray-700 mb-3">Hand photo</h2>
                @if($trial->hand_image_path)
                    <a href="{{ route('admin.virtual-nail-trials.hand', $trial) }}" target="_blank" rel="noopener">
                        <img src="{{ route('admin.virtual-nail-trials.hand', $trial) }}"
                             alt="Hand photo" class="w-full rounded-lg border border-gray-200 bg-gray-50 object-contain max-h-96">
                    </a>
                @else
                    <p class="text-sm text-gray-500 py-8 text-center">No hand image</p>
                @endif
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <h2 class="text-sm font-bold text-gray-700 mb-3">AI result</h2>
                @if($trial->result_image_path)
                    <a href="{{ route('admin.virtual-nail-trials.result', $trial) }}" target="_blank" rel="noopener">
                        <img src="{{ route('admin.virtual-nail-trials.result', $trial) }}"
                             alt="Result" class="w-full rounded-lg border border-gray-200 bg-gray-50 object-contain max-h-96">
                    </a>
                @else
                    <div class="py-8 text-center">
                        @if(in_array($trial->status, ['pending', 'processing'], true))
                            <p class="text-sm text-blue-600 font-medium">Still generating…</p>
                        @else
                            <p class="text-sm text-gray-500">No result image</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
