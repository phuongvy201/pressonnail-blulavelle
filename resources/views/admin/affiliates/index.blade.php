@extends('layouts.admin')

@section('title', 'Affiliates')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Affiliates / KOL</h1>
            <p class="mt-1 text-sm text-gray-600"><code class="text-xs bg-gray-100 px-1 rounded">ref</code> links (30-day last-click cookie), tiers &amp; promo assignment.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.promo-codes.create', ['generate' => 1]) }}"
               class="inline-flex items-center px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700">
                + Tạo promo code
            </a>
            <a href="{{ route('admin.promo-codes.index') }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                Promo codes
            </a>
            <a href="{{ route('admin.affiliates.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                Add affiliate
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Code (ref)</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tier</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">% override</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tier locked</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Active</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($affiliates as $a)
                        <tr>
                            <td class="px-4 py-3 font-mono text-sm font-semibold text-gray-900">{{ $a->code }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $a->display_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $a->tier }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $a->commission_rate_override !== null ? $a->commission_rate_override.'%' : '—' }}</td>
                            <td class="px-4 py-3">{{ $a->tier_locked ? 'Yes' : 'No' }}</td>
                            <td class="px-4 py-3">{{ $a->is_active ? 'Yes' : 'No' }}</td>
                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                @if (Route::has('admin.affiliates.analytics.show'))
                                    <a href="{{ route('admin.affiliates.analytics.show', $a) }}" class="text-violet-600 hover:underline text-sm font-medium">Analytics</a>
                                @endif
                                <a href="{{ route('admin.promo-codes.create', ['affiliate_id' => $a->id, 'generate' => 1]) }}"
                                   class="text-violet-600 hover:underline text-sm font-medium"
                                   title="Tạo coupon cho {{ $a->code }}">Coupon</a>
                                <a href="{{ route('admin.affiliates.edit', $a) }}" class="text-blue-600 hover:underline text-sm">Edit</a>
                                <form action="{{ route('admin.affiliates.destroy', $a) }}" method="POST" class="inline" onsubmit="return confirm('Delete this affiliate?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">No affiliates yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($affiliates->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $affiliates->links() }}</div>
        @endif
    </div>
</div>
@endsection
