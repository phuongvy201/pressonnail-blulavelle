@extends('layouts.admin')

@section('title', 'Promo Codes')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Promo Codes</h1>
            <p class="mt-1 text-sm text-gray-600">Manage discount codes: %, validity, minimum order, email triggers</p>
        </div>
        <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
            <a href="{{ route('admin.promo-codes.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="around" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add code
            </a>
            <a href="{{ route('admin.promo-codes.create', ['generate' => 1]) }}"
               class="inline-flex items-center px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 transition-colors"
               title="Open create form with a random unique code prefilled">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Random code
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form action="{{ route('admin.promo-codes.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label for="trigger" class="block text-sm font-medium text-gray-700 mb-2">Email trigger</label>
                <select name="trigger" id="trigger" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="thank_you" {{ request('trigger') === 'thank_you' ? 'selected' : '' }}>Thank you (after order)</option>
                    <option value="wishlist" {{ request('trigger') === 'wishlist' ? 'selected' : '' }}>Wishlist</option>
                    <option value="add_to_cart" {{ request('trigger') === 'add_to_cart' ? 'selected' : '' }}>Add to cart</option>
                    <option value="checkout_fail" {{ request('trigger') === 'checkout_fail' ? 'selected' : '' }}>Checkout failed</option>
                </select>
            </div>
            <div>
                <label for="affiliate" class="block text-sm font-medium text-gray-700 mb-2">Gán cho</label>
                <select name="affiliate" id="affiliate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="creator" {{ request('affiliate') === 'creator' ? 'selected' : '' }}>Creator / KOC</option>
                    <option value="shop" {{ request('affiliate') === 'shop' ? 'selected' : '' }}>Shop only (không creator)</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Creator / KOC</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type / value</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Min order</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Expiry</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Uses</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($promoCodes as $promo)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold text-gray-900">{{ $promo->code }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($promo->affiliate)
                                <div class="flex flex-col gap-0.5">
                                    <a href="{{ route('admin.affiliates.edit', $promo->affiliate) }}"
                                       class="font-mono font-semibold text-violet-700 hover:underline"
                                       title="Ref: {{ $promo->affiliate->code }}">
                                        {{ $promo->affiliate->code }}
                                    </a>
                                    @if($promo->affiliate->display_name)
                                        <span class="text-gray-600 text-xs">{{ $promo->affiliate->display_name }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($promo->type === 'percentage')
                                <span class="text-sm">{{ (int) $promo->value }}%</span>
                            @else
                                <span class="text-sm">${{ number_format((float) $promo->value, 2) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $promo->min_order_value !== null ? '$' . number_format((float) $promo->min_order_value, 2) : '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            @if($promo->expires_at)
                                {{ $promo->expires_at->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            {{ $promo->used_count }}{{ $promo->max_uses !== null ? ' / ' . $promo->max_uses : '' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($promo->send_on_trigger)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    {{ $promo->send_on_trigger === 'thank_you' ? 'Thank you' : ($promo->send_on_trigger === 'wishlist' ? 'Wishlist' : ($promo->send_on_trigger === 'checkout_fail' ? 'Checkout fail' : 'Add to cart')) }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($promo->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">On</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Off</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.promo-codes.edit', $promo) }}"
                               class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Edit
                            </a>
                            <form action="{{ route('admin.promo-codes.destroy', $promo) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('Delete this promo code?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-red-200 rounded-lg text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">No promo codes yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($promoCodes->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $promoCodes->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
