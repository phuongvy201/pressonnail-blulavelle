@extends('layouts.admin')

@section('title', 'Virtual Try-On History')

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
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Virtual Try-On History</h1>
            <p class="mt-1 text-sm text-gray-600">All customer nail preview generations</p>
        </div>
        <a href="{{ route('admin.settings.virtual-nail.edit') }}"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Settings
        </a>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase">Total</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase">Completed</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['completed']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase">In progress</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['in_progress']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase">Failed</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($stats['failed']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm col-span-2 lg:col-span-1">
            <p class="text-xs font-semibold text-gray-500 uppercase">Success rate</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['success_rate'] }}%</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <form method="GET" action="{{ route('admin.virtual-nail-trials.index') }}" class="p-4 flex flex-wrap items-center gap-3 border-b border-gray-100">
            <div class="w-full sm:w-56">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="UUID, email, product…"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="w-40">
                <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All statuses</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-28">
                <select name="per_page" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    @foreach([20, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', $perPage) === $size ? 'selected' : '' }}>{{ $size }}/page</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Filter</button>
                @if(request()->anyFilled(['search', 'status', 'per_page']))
                    <a href="{{ route('admin.virtual-nail-trials.index') }}" class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Clear</a>
                @endif
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Preview</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Shape / Length</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Provider</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($trials as $trial)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            @if($trial->result_image_path)
                                <img src="{{ route('admin.virtual-nail-trials.result', $trial) }}"
                                     alt="Result" class="w-12 h-12 rounded-lg object-cover border border-gray-200 bg-gray-50">
                            @elseif($trial->hand_image_path)
                                <img src="{{ route('admin.virtual-nail-trials.hand', $trial) }}"
                                     alt="Hand" class="w-12 h-12 rounded-lg object-cover border border-gray-200 bg-gray-50 opacity-70">
                            @else
                                <span class="inline-flex w-12 h-12 items-center justify-center rounded-lg bg-gray-100 text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900 max-w-[10rem] truncate">
                                {{ $trial->product?->name ?? '—' }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if($trial->user)
                                <div class="font-medium text-gray-900">{{ $trial->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $trial->user->email }}</div>
                            @else
                                <div class="text-gray-500">Guest</div>
                                <div class="text-xs text-gray-400 font-mono">{{ Str::limit($trial->session_id, 12) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $trial->nail_shape }}<br>
                            <span class="text-gray-400">{{ $trial->nail_length }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold {{ $statusColors[$trial->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($trial->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $trial->provider ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                            {{ $trial->created_at->format('M j, Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.virtual-nail-trials.show', $trial) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">No try-on generations yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($trials->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $trials->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
