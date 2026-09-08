@extends('layouts.customer-account')

@section('title', __('My Orders'))

@section('account-content')
@php
    $primary = '#0195FE';
@endphp

                <div class="flex flex-col gap-6">
                    <div class="flex flex-col gap-1">
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-900">Order History</h1>
                        <p class="text-slate-500 text-sm">Track, manage and reorder your favorite nail essentials.</p>
                    </div>

                    {{-- Quick stats as filters (giữ logic, đổi style theo theme) --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                        <a href="{{ route('customer.orders.index') }}"
                           class="bg-white rounded-xl border border-black/5 p-4 hover:shadow-md transition-all {{ !request('status') ? 'ring-2 ring-black/5' : '' }}">
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Total</div>
                            <div class="text-xl font-bold mt-1" style="color: {{ $primary }};">{{ $stats['total'] }}</div>
                        </a>
                        <a href="{{ route('customer.orders.index', ['status' => 'pending']) }}"
                           class="bg-white rounded-xl border border-black/5 p-4 hover:shadow-md transition-all {{ request('status') == 'pending' ? 'ring-2 ring-yellow-300' : '' }}">
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Pending</div>
                            <div class="text-xl font-bold mt-1 text-yellow-600">{{ $stats['pending'] }}</div>
                        </a>
                        <a href="{{ route('customer.orders.index', ['status' => 'processing']) }}"
                           class="bg-white rounded-xl border border-black/5 p-4 hover:shadow-md transition-all {{ request('status') == 'processing' ? 'ring-2 ring-blue-300' : '' }}">
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Processing</div>
                            <div class="text-xl font-bold mt-1 text-blue-600">{{ $stats['processing'] }}</div>
                        </a>
                        <a href="{{ route('customer.orders.index', ['status' => 'completed']) }}"
                           class="bg-white rounded-xl border border-black/5 p-4 hover:shadow-md transition-all {{ request('status') == 'completed' ? 'ring-2 ring-green-300' : '' }}">
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Completed</div>
                            <div class="text-xl font-bold mt-1 text-green-600">{{ $stats['completed'] }}</div>
                        </a>
                        <a href="{{ route('customer.orders.index', ['status' => 'cancelled']) }}"
                           class="bg-white rounded-xl border border-black/5 p-4 hover:shadow-md transition-all {{ request('status') == 'cancelled' ? 'ring-2 ring-red-300' : '' }}">
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Cancelled</div>
                            <div class="text-xl font-bold mt-1 text-red-600">{{ $stats['cancelled'] }}</div>
                        </a>
                    </div>

                    {{-- Search & Filter --}}
                    <div class="bg-white rounded-xl shadow-sm border border-black/5 p-4 md:p-6">
                        <div class="flex items-center justify-between gap-4 mb-4">
                            <h3 class="text-base font-semibold text-slate-900">Search & Filter</h3>
                            @if($search || $status)
                                <a href="{{ route('customer.orders.index') }}"
                                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-600 bg-black/5 rounded-lg hover:bg-black/10 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Clear
                                </a>
                            @endif
                        </div>

                        <form method="GET" action="{{ route('customer.orders.index') }}" class="flex flex-col md:flex-row gap-3">
                            <div class="flex-1 relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <input type="text"
                                       name="search"
                                       value="{{ $search ?? '' }}"
                                       placeholder="Search by order number, customer name, or email..."
                                       class="w-full pl-10 pr-3 py-3 border border-black/10 rounded-xl focus:ring-2 focus:ring-black/10 focus:border-transparent transition-all bg-black/5 focus:bg-white text-sm">
                            </div>

                            <button type="submit"
                                    class="inline-flex items-center justify-center px-6 py-3 text-white font-semibold rounded-xl transition-all"
                                    style="background: {{ $primary }};">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                Search
                            </button>
                        </form>

                        @if($search || $status)
                            <div class="mt-4 flex flex-wrap gap-2">
                                @if($search)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-black/5 text-slate-700">
                                        <svg class="w-4 h-4 mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        "{{ $search }}"
                                    </span>
                                @endif
                                @if($status)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-black/5 text-slate-700">
                                        <svg class="w-4 h-4 mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/></svg>
                                        {{ ucfirst($status) }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Orders list --}}
                    @if($orders->count() > 0)
                        <div class="space-y-6">
                            @foreach($orders as $order)
                                @php
                                    $onlyGiftCardLines = $order->items->isNotEmpty()
                                        && $order->items->every(fn ($it) => (bool) optional($it->product)->is_gift_card);
                                    $storedShipping = (float) ($order->shipping_cost ?? 0);
                                    $displayOrderTotal = $onlyGiftCardLines
                                        ? max(0, (float) $order->total_amount - $storedShipping)
                                        : (float) $order->total_amount;
                                @endphp
                                <div class="bg-white border border-black/5 rounded-xl overflow-hidden shadow-sm">
                                    <div class="p-4 md:p-6 border-b border-black/5 flex flex-wrap justify-between items-center gap-4" style="background: rgba(1,149,254,.06);">
                                        <div class="flex flex-wrap gap-6">
                                            <div class="flex flex-col">
                                                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Order ID</span>
                                                <span class="font-bold text-slate-900">#{{ $order->order_number }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Placed On</span>
                                                <span class="text-sm font-medium">{{ $order->created_at->format('M d, Y') }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Total</span>
                                                <span class="text-sm font-bold" style="color: {{ $primary }};">${{ number_format($displayOrderTotal, 2) }}</span>
                                            </div>
                                        </div>

                                        <span class="px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1
                                            @if($order->status == 'pending') bg-yellow-100 text-yellow-700
                                            @elseif($order->status == 'processing') bg-blue-100 text-blue-700
                                            @elseif($order->status == 'completed' || $order->status == 'delivered') bg-green-100 text-green-700
                                            @elseif($order->status == 'cancelled') bg-red-100 text-red-700
                                            @else bg-slate-100 text-slate-700
                                            @endif">
                                            <span class="w-2 h-2 rounded-full
                                                @if($order->status == 'pending') bg-yellow-500
                                                @elseif($order->status == 'processing') bg-blue-500
                                                @elseif($order->status == 'completed' || $order->status == 'delivered') bg-green-500
                                                @elseif($order->status == 'cancelled') bg-red-500
                                                @else bg-slate-500
                                                @endif"></span>
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </div>

                                    <div class="p-4 md:p-6 flex flex-col gap-6">
                                        {{-- Items preview (giống code.html: list dọc) --}}
                                        @if($order->items->count() > 0)
                                            <div class="flex flex-col gap-4">
                                                @foreach($order->items->take(3) as $item)
                                                    <div class="flex items-center gap-4">
                                                        @php
                                                            $productMedia = $item->product ? $item->product->getEffectiveMedia() : [];
                                                            $productImageUrl = null;
                                                            $productImageAlt = $item->product_name;
                                                            if (!empty($productMedia) && $item->product) {
                                                                $productImageAlt = $item->product->altForMediaItem($productMedia[0], $item->product_name, 0);
                                                                if (is_string($productMedia[0])) {
                                                                    $productImageUrl = $productMedia[0];
                                                                } elseif (is_array($productMedia[0])) {
                                                                    $productImageUrl = $productMedia[0]['url'] ?? $productMedia[0]['path'] ?? reset($productMedia[0]) ?? null;
                                                                }
                                                            }
                                                            $itemPrice = $item->price ?? $item->unit_price ?? $item->subtotal ?? null;
                                                        @endphp
                                                        <div class="size-16 rounded-lg bg-[#f8f6f6] overflow-hidden border border-black/5">
                                                            @if($productImageUrl)
                                                                <img src="{{ $productImageUrl }}" alt="{{ $productImageAlt }}" class="w-full h-full object-cover">
                                                            @endif
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <h4 class="text-sm font-bold text-slate-900 truncate">{{ $item->product_name }}</h4>
                                                            <p class="text-xs text-slate-500">
                                                                Qty: {{ $item->quantity }}
                                                                @if(is_numeric($itemPrice)) • ${{ number_format((float) $itemPrice, 2) }} @endif
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                                @if($order->items->count() > 3)
                                                    <div class="text-xs text-slate-500">+{{ $order->items->count() - 3 }} more item(s)</div>
                                                @endif
                                            </div>
                                        @endif

                                        @php
                                            $canReviewOrder = in_array($order->status, ['completed', 'delivered']);
                                            $reviewableItems = $order->items->filter(function ($it) {
                                                return $it->product_id
                                                    && $it->product
                                                    && !($it->product->is_gift_card ?? false);
                                            })->unique('product_id');
                                            $pendingReviewItems = $canReviewOrder
                                                ? $reviewableItems->filter(fn ($it) => !isset($userReviewedProductIds[$it->product_id]))
                                                : collect();
                                        @endphp

                                        <div class="pt-4 border-t border-black/5 space-y-3">
                                            <div class="rounded-lg border border-black/5 bg-[#f8f6f6] px-3 py-2.5">
                                                @if($canReviewOrder)
                                                    @if($pendingReviewItems->isNotEmpty())
                                                        <p class="text-xs font-semibold text-slate-700">
                                                            Your order has been delivered. Review your products:
                                                        </p>
                                                        <div class="mt-2 flex flex-wrap gap-2">
                                                            @foreach($pendingReviewItems->take(3) as $reviewItem)
                                                                <a href="{{ route('customer.orders.show', $order->order_number) }}#order-reviews"
                                                                   class="inline-flex items-center px-3 py-1.5 rounded-full text-[11px] font-bold border border-[#0195FE]/30 bg-white text-[#0195FE] hover:bg-[#0195FE]/5 transition-colors">
                                                                    Review {{ \Illuminate\Support\Str::limit($reviewItem->product_name, 24) }}
                                                                </a>
                                                            @endforeach
                                                            @if($pendingReviewItems->count() > 3)
                                                                <a href="{{ route('customer.orders.show', $order->order_number) }}#order-reviews"
                                                                   class="inline-flex items-center px-3 py-1.5 rounded-full text-[11px] font-bold border border-black/10 bg-white text-slate-700 hover:bg-black/5 transition-colors">
                                                                    +{{ $pendingReviewItems->count() - 3 }} more
                                                                </a>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <p class="text-xs font-semibold text-green-700">
                                                            Thank you! You have reviewed all products in this order.
                                                        </p>
                                                    @endif
                                                @else
                                                    <p class="text-xs font-semibold text-slate-600">
                                                        Review this order here after it is marked as delivered.
                                                    </p>
                                                @endif
                                            </div>

                                            <div class="flex flex-wrap gap-3">
                                            <a href="{{ route('customer.orders.show', $order->order_number) }}"
                                               class="px-4 py-2 rounded-lg text-white text-xs font-bold transition-all"
                                               style="background: {{ $primary }}; box-shadow: 0 12px 30px rgba(1,149,254,.18);">
                                                View Details
                                            </a>
                                            <a href="{{ route('products.index') }}"
                                               class="px-4 py-2 rounded-lg text-xs font-bold transition-all"
                                               style="background: rgba(1,149,254,.10); color: {{ $primary }};">
                                                Continue Shopping
                                            </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $orders->links() }}
                        </div>
                    @else
                        <div class="bg-white rounded-xl shadow-sm border border-dashed border-black/10 p-12 text-center">
                            <div class="size-20 mx-auto mb-6 rounded-full flex items-center justify-center" style="background: rgba(1,149,254,.10); color: {{ $primary }};">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-2">
                                @if($search || $status)
                                    No Orders Found
                                @else
                                    No orders yet
                                @endif
                            </h3>
                            <p class="text-slate-500 mb-8 max-w-md mx-auto text-sm">
                                @if($search || $status)
                                    No orders match your search criteria. Try adjusting your filters or search terms.
                                @else
                                    It looks like you haven't placed any orders yet. Start your journey to perfect nails today!
                                @endif
                            </p>
                            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                @if($search || $status)
                                    <a href="{{ route('customer.orders.index') }}"
                                       class="px-6 py-3 rounded-xl bg-black/5 text-slate-700 font-bold hover:bg-black/10 transition-colors">
                                        Clear Filters
                                    </a>
                                @endif
                                <a href="{{ route('products.index') }}"
                                   class="px-8 py-3 rounded-xl text-white font-bold transition-all"
                                   style="background: {{ $primary }}; box-shadow: 0 12px 30px rgba(1,149,254,.18);">
                                    Continue Shopping
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
@endsection
