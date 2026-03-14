@extends('layouts.app')

@section('title', 'All Products')

@section('content')
@php
    $currentCurrency = currency();
    $currencySymbol = currency_symbol();
@endphp
@php
    $gtagItems = collect($products->items())->map(function ($product, $loopIndex) use ($products) {
        $primaryCategory = optional($product->category)->name ?? optional($product->template->category)->name ?? null;
        return [
            'item_id' => $product->sku ?? $product->id,
            'item_name' => $product->name,
            'item_list_name' => 'All Products',
            'item_category' => $primaryCategory,
            'price' => (float) ($product->price ?? $product->base_price ?? 0),
            'index' => ($products->perPage() * max($products->currentPage() - 1, 0)) + $loopIndex + 1,
        ];
    })->values()->toArray();
@endphp

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof fbq !== 'undefined') {
        fbq('track', 'ViewContent', { content_type: 'product_list', content_name: 'All Products' });
    }
    if (typeof dataLayer !== 'undefined') {
        dataLayer.push({ 'event': 'view_item_list', 'item_list_name': 'All Products', 'items': @json($gtagItems) });
    }
});
</script>

{{-- Hero --}}
<section class="bg-primary/5 py-12 lg:py-20 border-b border-primary/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-20 text-center">
        <h1 class="font-serif text-4xl sm:text-5xl lg:text-7xl text-slate-900 mb-4">All Press-On Nails</h1>
        <p class="text-slate-600 text-base sm:text-lg lg:text-xl max-w-3xl mx-auto leading-relaxed italic">
            Explore our complete collection of salon-quality, reusable nails. From classic French tips to bold chrome designs, find your perfect match.
        </p>
    </div>
</section>

{{-- Main: sidebar + grid --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-20 py-12">
    <div class="flex flex-col lg:flex-row gap-12">
        {{-- Mobile filter overlay --}}
        <div id="mobile-filter-overlay" class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden"></div>

        {{-- Sidebar filters --}}
        <aside id="filter-drawer" class="fixed inset-0 z-50 hidden lg:static lg:z-auto lg:block w-full lg:w-64 flex-shrink-0">
            <div class="h-full lg:h-auto flex items-end lg:items-stretch">
                <div class="w-full bg-white rounded-t-3xl lg:rounded-none shadow-2xl lg:shadow-none border-t border-slate-200 lg:border-0 max-h-[85vh] lg:max-h-none overflow-hidden flex flex-col lg:block">
                    {{-- Mobile header --}}
                    <div class="lg:hidden flex items-center justify-between px-5 py-4 border-b border-slate-200">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-1.447.894l-4-2A1 1 0 019 17V12.414L3.293 6.707A1 1 0 013 6V4z"/></svg>
                            <span class="text-base font-extrabold text-slate-900">Filters</span>
                        </div>
                        <button type="button" id="mobile-filter-close" class="p-2 rounded-xl hover:bg-slate-100 text-slate-700" aria-label="Close filters">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form method="GET" action="{{ route('products.index') }}" id="filter-form" class="p-5 lg:p-0 overflow-y-auto lg:overflow-visible">
                @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                {{-- Category --}}
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-widest text-slate-400 mb-3">Category</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 cursor-pointer rounded-xl px-3 py-2 hover:bg-slate-50 transition-colors">
                            <input type="radio" name="category" value="" class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4"
                                {{ !request()->filled('category') ? 'checked' : '' }}>
                            <span class="text-sm font-semibold text-slate-800">All</span>
                        </label>
                        @foreach($categories as $cat)
                        <label class="flex items-center gap-3 cursor-pointer rounded-xl px-3 py-2 hover:bg-slate-50 transition-colors">
                            <input type="radio" name="category" value="{{ $cat->id }}" class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4"
                                {{ request('category') == $cat->id ? 'checked' : '' }}>
                            <span class="text-sm font-semibold text-slate-800">{{ $cat->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                {{-- Shop --}}
                <div class="mt-7">
                    <h3 class="text-xs font-extrabold uppercase tracking-widest text-slate-400 mb-3">Shop</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 cursor-pointer rounded-xl px-3 py-2 hover:bg-slate-50 transition-colors">
                            <input type="radio" name="shop" value="" class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4"
                                {{ !request()->filled('shop') ? 'checked' : '' }}>
                            <span class="text-sm font-semibold text-slate-800">All Shops</span>
                        </label>
                        @foreach($shops as $shop)
                        <label class="flex items-center gap-3 cursor-pointer rounded-xl px-3 py-2 hover:bg-slate-50 transition-colors">
                            <input type="radio" name="shop" value="{{ $shop->id }}" class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4"
                                {{ request('shop') == $shop->id ? 'checked' : '' }}>
                            <span class="text-sm font-semibold text-slate-800">{{ $shop->shop_name ?? $shop->name ?? 'Shop' }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="sort" id="sort-input" value="{{ request('sort', 'newest') }}">
                <div class="mt-7 lg:mt-6 grid grid-cols-1 gap-2 lg:block">
                    <button type="submit" class="w-full px-4 py-3 bg-primary text-white rounded-xl font-extrabold hover:opacity-90 transition-opacity">Apply Filters</button>
                @if(request()->hasAny(['category', 'shop', 'sort']))
                    <a href="{{ route('products.index') }}" class="block text-center text-sm font-bold text-slate-600 hover:text-primary transition-colors py-2">Clear All</a>
                @endif
                </div>
            </form>
                </div>
            </div>
        </aside>

        <div class="flex-1">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <p class="text-sm text-slate-700 font-medium">
                    Showing {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} styles
                </p>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" id="mobile-filter-open" class="lg:hidden inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 font-extrabold text-sm shadow-sm hover:bg-slate-50">
                        <svg class="w-4.5 h-4.5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-1.447.894l-4-2A1 1 0 019 17V12.414L3.293 6.707A1 1 0 013 6V4z"/></svg>
                        Filters
                    </button>
                    <span class="text-sm text-slate-700 font-medium">Sort by:</span>
                    <select id="sort-select" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm font-bold text-slate-900 focus:ring-2 focus:ring-primary cursor-pointer">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Most Relevant</option>
                        <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name: A to Z</option>
                    </select>
                </div>
            </div>

            @if($products->isEmpty())
            <div class="text-center py-16">
                <p class="text-slate-600 mb-6">No products match your filters.</p>
                <a href="{{ route('products.index') }}" class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg font-bold hover:opacity-90 transition-opacity">View All</a>
            </div>
            @else
            <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-x-4 gap-y-8 sm:gap-x-8 sm:gap-y-12">
                @foreach($products as $product)
                    <x-product-card :product="$product" :show-quick-view="true" />
                @endforeach
            </div>

            {{-- Load more / Pagination --}}
            <div class="mt-20 flex flex-col items-center gap-6">
                @if($products->hasPages())
                <div class="flex gap-2 items-center justify-center flex-wrap">
                    {{ $products->links() }}
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

{{-- See it in action --}}
<x-see-it-in-action
    heading="See it in action"
    subheading="Watch how easily our press-on nails apply and see the premium finish in real life."
/>

{{-- Feedback / Testimonials --}}
<x-testimonials title="Over 50,000+ Happy Customers" rating="4.8/5" />

{{-- Dark promo section (Indulge in skin softness style) --}}
<section class="px-4 sm:px-6 lg:px-20 py-20 bg-[#2b2533] text-white overflow-hidden">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl lg:text-4xl font-black text-center mb-12">Why choose us</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div>
                <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-primary">local_shipping</span>
                </div>
                <h3 class="text-xl font-bold mb-2">Free Shipping</h3>
                <p class="text-white/80 text-sm">On orders over $50</p>
            </div>
            <div>
                <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-primary">verified</span>
                </div>
                <h3 class="text-xl font-bold mb-2">100% Satisfaction</h3>
                <p class="text-white/80 text-sm">Guaranteed quality</p>
            </div>
            <div>
                <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-primary">recycling</span>
                </div>
                <h3 class="text-xl font-bold mb-2">Reusable</h3>
                <p class="text-white/80 text-sm">Salon-quality, reusable nails</p>
            </div>
        </div>
    </div>
</section>
{{-- Recently Viewed (dùng chung) --}}
<div class="mt-10">
    <x-recently-viewed :products="$recentlyViewedProducts ?? null" :limit="5" />
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('filter-form');
    var sortInput = document.getElementById('sort-input');
    var sortSelect = document.getElementById('sort-select');

    // Mobile filter drawer
    var drawer = document.getElementById('filter-drawer');
    var overlay = document.getElementById('mobile-filter-overlay');
    var openBtn = document.getElementById('mobile-filter-open');
    var closeBtn = document.getElementById('mobile-filter-close');
    function openDrawer() {
        if (!drawer || !overlay) return;
        drawer.classList.remove('hidden');
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    function closeDrawer() {
        if (!drawer || !overlay) return;
        drawer.classList.add('hidden');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
    if (openBtn) openBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);

    if (sortSelect && sortInput && form) {
        sortSelect.addEventListener('change', function() {
            sortInput.value = this.value;
            form.submit();
        });
    }
    // Single category: use first checked only (GET can have one category)
    var categoryChecks = form && form.querySelectorAll('input[name="category"]');
    if (categoryChecks && categoryChecks.length) {
        categoryChecks.forEach(function(cb) {
            cb.addEventListener('change', function() {
                if (this.checked) {
                    categoryChecks.forEach(function(c) { if (c !== cb) c.checked = false; });
                }
                form.submit();
            });
        });
    }
    var shopRadios = form && form.querySelectorAll('input[name="shop"]');
    if (shopRadios && shopRadios.length) {
        shopRadios.forEach(function(r) {
            r.addEventListener('change', function() { form.submit(); });
        });
    }
});
</script>
@endsection
