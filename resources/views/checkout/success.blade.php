@extends('layouts.app')

@section('title', 'Order Success')

@section('content')
@php
    $primary = '#059669';
    $orderDate = \Carbon\Carbon::parse($order->created_at);
    $estimatedStart = $orderDate->copy()->addDays(3);
    $estimatedEnd = $orderDate->copy()->addDays(5);
    $itemsSum = (float) ($order->items?->sum('total_price') ?? 0);
    $orderSubtotal = (float) ($order->subtotal ?? 0);
    $bulkDiscount = max(0, $itemsSum - $orderSubtotal);
    $gaItems = $order->items->map(function($item, $index) {
        return [
            'item_id' => (string) $item->product_id,
            'item_name' => $item->product_name,
            'price' => (float) $item->unit_price,
            'quantity' => (int) $item->quantity,
            'index' => $index + 1
        ];
    })->values()->toArray();
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof fbq !== 'undefined') {
        const productIds = [
            @foreach($order->items as $item)
                '{{ $item->product_id }}'{{ !$loop->last ? ',' : '' }}
            @endforeach
        ];
        fbq('track', 'Purchase', {
            content_ids: productIds,
            content_type: 'product',
            value: '{{ $order->total_amount }}',
            currency: '{{ $currency ?? "USD" }}',
            transaction_id: '{{ $order->order_number }}',
            num_items: {{ $order->items->count() }}
        });
        localStorage.removeItem('cart');
        window.dispatchEvent(new CustomEvent('cartUpdated'));
    }
    if (typeof dataLayer !== 'undefined') {
        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            event: 'purchase',
            ecommerce: {
                currency: '{{ $currency ?? "USD" }}',
                transaction_id: '{{ $order->order_number }}',
                value: Number('{{ $order->total_amount }}') || 0,
                tax: Number('{{ $order->tax_amount }}') || 0,
                shipping: Number('{{ $order->shipping_cost }}') || 0,
                items: @json($gaItems)
            }
        });
    }
    if (typeof window !== 'undefined' && window.ttq) {
        const tiktokOrderContents = {!! $order->items->map(fn($item) => [
            'content_id' => (string) $item->product_id,
            'content_type' => 'product',
            'content_name' => $item->product_name,
            'quantity' => (int) $item->quantity,
            'price' => (float) $item->unit_price,
        ])->values()->toJson(JSON_UNESCAPED_UNICODE) !!};
        try {
            window.ttq.track('PlaceAnOrder', { contents: tiktokOrderContents, value: Number('{{ $order->total_amount }}') || 0, currency: '{{ $currency ?? "USD" }}', order_id: '{{ $order->order_number }}' });
            window.ttq.track('Purchase', { contents: tiktokOrderContents, value: Number('{{ $order->total_amount }}') || 0, currency: '{{ $currency ?? "USD" }}', order_id: '{{ $order->order_number }}' });
        } catch (e) {}
    }
});
</script>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<div class="min-h-screen bg-emerald-50/80 text-slate-900" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    <main class="flex-1 px-4 md:px-10 lg:px-16 py-8 max-w-[1200px] mx-auto w-full">
        {{-- Success header (giống code.html) --}}
        <div class="flex flex-col items-center text-center mb-10">
            <div class="w-20 h-20 rounded-full flex items-center justify-center mb-6 bg-emerald-100">
                <svg class="w-12 h-12" style="color: {{ $primary }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-slate-900 tracking-tight text-3xl md:text-4xl font-extrabold leading-tight mb-2">Thank You for Your Order!</h1>
            <p class="text-slate-500 text-lg">Your order <span class="font-bold" style="color: {{ $primary }};">#{{ $order->order_number }}</span> has been placed successfully.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left: Order Summary + What Happens Next --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Order Summary --}}
                <section class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2" style="color: {{ $primary }};">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        Order Summary
                    </h3>
                    <div class="space-y-4">
                        @foreach($order->items as $item)
                            @php
                                $product = $item->product;
                                $imageUrl = null;
                                $successLineImgAlt = $item->product_name;
                                if ($product) {
                                    $media = $product->getEffectiveMedia();
                                    if ($media && count($media) > 0) {
                                        $successLineImgAlt = $product->altForMediaItem($media[0], $item->product_name, 0);
                                        $imageUrl = is_string($media[0]) ? $media[0] : ($media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? null);
                                    }
                                }
                            @endphp
                            <div class="flex items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                                <div class="aspect-square size-16 shrink-0 rounded-lg overflow-hidden bg-[#f8f6f6] border border-slate-100">
                                    @if($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $successLineImgAlt }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-slate-300">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-slate-900 font-semibold truncate">{{ $item->product_name }}</p>
                                    <p class="text-slate-500 text-sm">
                                        @if(!empty($item->product_options) && is_array($item->product_options))
                                            @php
                                                $parts = [];
                                                $humanize = function ($key) {
                                                    if (is_numeric($key)) return null;
                                                    return ucwords(str_replace(['_', '-'], ' ', (string) $key));
                                                };
                                                foreach ($item->product_options as $k => $v) {
                                                    if (is_array($v)) {
                                                        if (isset($v['value']) && (string)$v['value'] !== '' && !isset($v['Size']) && !isset($v['Nail Shape'])) {
                                                            $parts[] = 'Custom: ' . $v['value'];
                                                        } else {
                                                            foreach ($v as $subK => $subV) {
                                                                if ($subK === 'price' || (is_scalar($subV) && (string)$subV === '')) continue;
                                                                if ($subK === 'value' && count($v) > 1) continue;
                                                                $label = $humanize($subK);
                                                                if ($label && is_scalar($subV)) $parts[] = $label . ': ' . $subV;
                                                            }
                                                        }
                                                    } elseif (is_scalar($v)) {
                                                        $label = $humanize($k);
                                                        if ($label && (string)$v !== '') $parts[] = $label . ': ' . $v;
                                                    }
                                                }
                                            @endphp
                                            {{ count($parts) ? implode(', ', $parts) : '—' }}
                                        @else
                                            —
                                        @endif
                                    </p>
                                    <p class="text-slate-400 text-xs">Qty: {{ $item->quantity }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-bold text-slate-900">{{ \App\Services\CurrencyService::formatPrice($item->total_price ?? ($item->unit_price * $item->quantity), $currency ?? 'USD') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if(($currency ?? 'USD') !== 'USD' && isset($currencyRate))
                        <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-lg border border-slate-100 mt-4">
                            <div class="flex justify-between"><span>Exchange Rate:</span><span class="font-medium">1 USD = {{ number_format($currencyRate, 4) }} {{ $currency }}</span></div>
                        </div>
                    @endif

                    <div class="mt-8 pt-6 border-t border-slate-100 space-y-2">
                        <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>{{ \App\Services\CurrencyService::formatPrice($convertedSubtotal ?? $order->subtotal, $currency ?? 'USD') }}</span></div>
                        @if($bulkDiscount > 0)
                        <div class="flex justify-between text-emerald-600 font-medium"><span>Volume discount</span><span>-{{ \App\Services\CurrencyService::formatPrice($bulkDiscount, $currency ?? 'USD') }}</span></div>
                        @endif
                        @if($order->promo_code && (float)($order->discount_amount ?? 0) > 0)
                        <div class="flex justify-between text-emerald-600"><span>Promo ({{ $order->promo_code }})</span><span>-{{ \App\Services\CurrencyService::formatPrice($order->discount_amount, $currency ?? 'USD') }}</span></div>
                        @endif
                        <div class="flex justify-between text-slate-500"><span>Shipping</span><span>{{ \App\Services\CurrencyService::formatPrice($convertedShipping ?? $order->shipping_cost, $currency ?? 'USD') }}</span></div>
                        <div class="flex justify-between text-slate-500"><span>Tax</span><span>{{ \App\Services\CurrencyService::formatPrice($convertedTax ?? $order->tax_amount, $currency ?? 'USD') }}</span></div>
                        @if($order->tip_amount > 0)
                            <div class="flex justify-between text-slate-500"><span>Tips</span><span>{{ \App\Services\CurrencyService::formatPrice($convertedTip ?? $order->tip_amount, $currency ?? 'USD') }}</span></div>
                        @endif
                        <div class="flex justify-between text-slate-900 font-bold text-lg pt-2">
                            <span>Total Amount Paid</span>
                            <span style="color: {{ $primary }};">{{ \App\Services\CurrencyService::formatPrice($convertedTotal ?? $order->total_amount, $currency ?? 'USD') }}</span>
                        </div>
                    </div>
                </section>

                {{-- What Happens Next? (timeline giống code.html) --}}
                <section class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2" style="color: {{ $primary }};">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        What Happens Next?
                    </h3>
                    <div class="relative space-y-8 pl-8 before:content-[''] before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-emerald-400/40">
                        <div class="relative">
                            <div class="absolute -left-8 top-0.5 w-6 h-6 rounded-full flex items-center justify-center border-4 border-white shadow-sm" style="background: {{ $primary }};">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="font-bold text-slate-900">Order Confirmed</p>
                            <p class="text-sm text-slate-500">We've received your order and are getting it ready for processing.</p>
                        </div>
                        <div class="relative">
                            <div class="absolute -left-8 top-0.5 w-6 h-6 rounded-full border-4 border-white bg-slate-200"></div>
                            <p class="font-bold text-slate-900">Processing & Packing</p>
                            <p class="text-sm text-slate-500">Your items are being hand-picked and beautifully packed. (1-2 business days)</p>
                        </div>
                        <div class="relative">
                            <div class="absolute -left-8 top-0.5 w-6 h-6 rounded-full border-4 border-white bg-slate-200"></div>
                            <p class="font-bold text-slate-900">Shipping Notification</p>
                            <p class="text-sm text-slate-500">You'll receive an email with your tracking number as soon as your package ships.</p>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Right: Shipping + Estimated Delivery + Actions --}}
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: {{ $primary }};">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Shipping Address
                    </h3>
                    <div class="text-slate-600 leading-relaxed">
                        <p class="font-bold text-slate-900">{{ $order->customer_name }}</p>
                        <p>{{ $order->shipping_address }}</p>
                        <p>{{ $order->city }}, {{ $order->state }} {{ $order->postal_code }}</p>
                        <p>{{ $order->country }}</p>
                    </div>
                </div>

                <div class="p-6 rounded-xl border border-emerald-200 shadow-sm bg-emerald-50/80">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: {{ $primary }};">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Estimated Delivery
                    </h3>
                    <p class="text-2xl font-extrabold text-slate-900">{{ $estimatedStart->format('M j') }} – {{ $estimatedEnd->format('M j') }}</p>
                    <p class="text-sm text-slate-500 mt-2">Standard Shipping (3-5 Business Days)</p>
                </div>

                <div class="space-y-3">
                    <a href="{{ route('products.index') }}" class="w-full flex items-center justify-center gap-2 font-bold py-4 px-6 rounded-xl transition-all shadow-lg text-white" style="background: {{ $primary }};">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        Continue Shopping
                    </a>
                    <a href="{{ route('checkout.receipt', $order->order_number) }}" class="w-full flex items-center justify-center gap-2 bg-white border border-slate-200 hover:border-slate-300 text-slate-700 font-bold py-4 px-6 rounded-xl transition-all">
                        <svg class="w-5 h-5" style="color: {{ $primary }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2h2z"/></svg>
                        Print Receipt
                    </a>
                </div>

                <div class="bg-white p-6 rounded-xl border border-dashed border-slate-300 text-center">
                    <p class="text-sm font-medium text-slate-600 mb-3">Need help with your order?</p>
                    <a href="{{ route('customer.orders.show', $order->order_number) }}" class="font-bold hover:underline flex items-center justify-center gap-1" style="color: {{ $primary }};">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        View Order & Contact Support
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>
@endsection
