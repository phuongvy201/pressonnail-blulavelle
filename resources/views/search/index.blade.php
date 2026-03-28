@extends('layouts.app')

@section('title', 'Search Results' . ($query ? ' for "' . $query . '"' : ''))

@section('content')
@php
    // Paginator::toArray() khi bọc bằng collect() sẽ tạo collection lẫn current_page (int) → lỗi "id on int"
    $productsForTracking = ($products ?? null) instanceof \Illuminate\Contracts\Pagination\Paginator
        ? collect($products->items())
        : collect($products ?? []);
    $tiktokSearchContents = $productsForTracking
        ->take(5)
        ->map(function ($product) {
            return [
                'content_id' => (string) ($product->id ?? $product->slug ?? ''),
                'content_type' => 'product',
                'content_name' => $product->name ?? '',
            ];
        })
        ->filter(fn($item) => !empty($item['content_id']) && !empty($item['content_name']))
        ->values();
    $searchGtagItems = $productsForTracking
        ->take(24)
        ->values()
        ->map(function ($product, $index) {
            $categoryName = optional(($product->categories ?? collect())->first())->name
                ?? optional(($product->collections ?? collect())->first())->name;

            return [
                'item_id' => $product->sku ?? $product->id,
                'item_name' => $product->name,
                'item_category' => $categoryName,
                'price' => round((float) ($product->price ?? $product->base_price ?? 0), 2),
                'quantity' => 1,
                'index' => $index + 1,
            ];
        })
        ->all();
    $popularSearches = ['Almond', 'French Tip', 'Matte', 'Glitter', 'Coffin', 'Short Nails'];
@endphp
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof fbq !== 'undefined') {
        @if($query)
        fbq('track', 'Search', { search_string: '{{ addslashes($query) }}', content_category: 'product' });
        @endif
        fbq('track', 'ViewContent', { content_name: 'Search Results', content_type: 'search' });
    }
    if (typeof window !== 'undefined' && window.ttq) {
        var tiktokPayload = { contents: {!! $tiktokSearchContents->isEmpty() ? '[]' : $tiktokSearchContents->toJson(JSON_UNESCAPED_UNICODE) !!}, value: 0, currency: 'USD' };
        @if($query) tiktokPayload.search_string = {!! json_encode($query) !!}; @endif
        window.ttq.track('Search', tiktokPayload);
    }
    if (typeof dataLayer !== 'undefined') {
        @if($query)
        dataLayer.push({ event: 'search', search_term: @json($query) });
        @endif
        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            event: 'view_item_list',
            ecommerce: {
                item_list_name: 'Search Results',
                items: @json($searchGtagItems)
            }
        });
    }
});
</script>

<main class="search-page min-h-screen bg-[#f8f6f6] flex flex-col flex-1 px-4 md:px-20 lg:px-40 py-8 max-w-[1440px] mx-auto w-full pb-16" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    {{-- Search bar section (giống code.html) --}}
    <div class="flex flex-col gap-6 mb-10">
        <div class="relative w-full max-w-2xl mx-auto">
            <form action="{{ route('search') }}" method="GET" class="block">
                <label class="flex flex-col w-full h-14">
                    <div class="flex w-full flex-1 items-stretch rounded-xl h-full shadow-sm overflow-hidden bg-white border border-primary/10">
                        <span class="text-primary flex items-center justify-center pl-5 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" name="q" value="{{ $query }}"
                               placeholder="Search for nails, collections..."
                               class="flex-1 min-w-0 border-0 rounded-r-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 px-4 text-base md:text-lg placeholder:text-slate-400">
                    </div>
                </label>
            </form>
        </div>
        <div class="flex flex-col items-center gap-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Popular Searches</span>
            <div class="flex gap-2 flex-wrap justify-center">
                @foreach($popularSearches as $term)
                <a href="{{ route('search', ['q' => $term]) }}" class="px-4 py-1.5 rounded-full text-sm font-medium transition-all {{ ($query ?? '') === $term ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white text-slate-600 border border-primary/10 hover:border-primary' }}">
                    {{ $term }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    @if($query)
        @if($totalResults > 0)
            {{-- Results header: title + count (count nhỏ hơn, xuống dòng trên mobile) --}}
            <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-2 mb-6 border-b border-primary/5 pb-4">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900">Search Results for <span class="text-primary">"{{ $query }}"</span></h1>
                <p class="text-xs sm:text-sm text-slate-500 shrink-0">{{ number_format($totalResults) }} result{{ $totalResults !== 1 ? 's' : '' }} found</p>
            </div>

            {{-- Filter pills --}}
            <div class="flex flex-wrap gap-2 mb-6">
                <a href="{{ route('search', ['q' => $query]) }}" class="px-4 py-2 rounded-full text-sm font-medium transition-all {{ (!$type || $type === 'all') ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white text-slate-600 border border-primary/10 hover:border-primary' }}">All ({{ $totalResults }})</a>
                <a href="{{ route('search', array_merge(['q' => $query, 'type' => 'products'], $filters ?? [])) }}" class="px-4 py-2 rounded-full text-sm font-medium transition-all {{ ($type ?? '') === 'products' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white text-slate-600 border border-primary/10 hover:border-primary' }}">Products ({{ $counts['products'] ?? 0 }})</a>
                <a href="{{ route('search', ['q' => $query, 'type' => 'collections']) }}" class="px-4 py-2 rounded-full text-sm font-medium transition-all {{ ($type ?? '') === 'collections' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white text-slate-600 border border-primary/10 hover:border-primary' }}">Collections ({{ $counts['collections'] ?? 0 }})</a>
                <a href="{{ route('search', ['q' => $query, 'type' => 'shops']) }}" class="px-4 py-2 rounded-full text-sm font-medium transition-all {{ ($type ?? '') === 'shops' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white text-slate-600 border border-primary/10 hover:border-primary' }}">Shops ({{ $counts['shops'] ?? 0 }})</a>
            </div>

            @php
                $filterOptions = $filterOptions ?? ['colors' => [], 'shapes' => [], 'sizes' => []];
                $filters = $filters ?? [];
                $filterParams = array_merge(['q' => $query, 'type' => 'products'], array_filter($filters));
            @endphp

            {{-- Attribute filters (Color, Shape, Price) - bỏ Size --}}
            @if(($type === 'all' || $type === 'products') && (count($filterOptions['colors']) > 0 || count($filterOptions['shapes']) > 0))
            <div class="mb-8 p-4 bg-white rounded-xl border border-primary/10 shadow-sm">
                <form method="GET" action="{{ route('search') }}" id="search-filters-form" class="space-y-4">
                    <input type="hidden" name="q" value="{{ $query }}">
                    <input type="hidden" name="type" value="products">
                    <div class="flex flex-wrap items-end gap-4">
                        @if(count($filterOptions['colors']) > 0)
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-1.5">Color</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($filterOptions['colors'] as $color)
                                <a href="{{ route('search', array_merge($filterParams, ['color' => $color])) }}" class="px-3 py-1.5 rounded-full text-sm font-medium transition-all {{ ($filters['color'] ?? '') === $color ? 'bg-primary text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-primary/10' }}">{{ $color }}</a>
                                @endforeach
                                @if(!empty($filters['color']))<a href="{{ route('search', array_merge(['q' => $query, 'type' => 'products'], array_diff_key($filters, ['color' => 1]))) }}" class="px-2 py-1 text-xs text-slate-500 hover:text-primary">Clear</a>@endif
                            </div>
                        </div>
                        @endif
                        @if(count($filterOptions['shapes']) > 0)
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-1.5">Shape</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($filterOptions['shapes'] as $shape)
                                <a href="{{ route('search', array_merge($filterParams, ['shape' => $shape])) }}" class="px-3 py-1.5 rounded-full text-sm font-medium transition-all {{ ($filters['shape'] ?? '') === $shape ? 'bg-primary text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-primary/10' }}">{{ $shape }}</a>
                                @endforeach
                                @if(!empty($filters['shape']))<a href="{{ route('search', array_merge(['q' => $query, 'type' => 'products'], array_diff_key($filters, ['shape' => 1]))) }}" class="px-2 py-1 text-xs text-slate-500 hover:text-primary">Clear</a>@endif
                            </div>
                        </div>
                        @endif
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-1.5">Price</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <input type="number" name="price_min" value="{{ $filters['price_min'] ?? '' }}" placeholder="Min" min="0" step="0.01" class="w-20 px-2 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20">
                                <span class="text-slate-400">–</span>
                                <input type="number" name="price_max" value="{{ $filters['price_max'] ?? '' }}" placeholder="Max" min="0" step="0.01" class="w-20 px-2 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20">
                                @if(!empty($filters['color']))<input type="hidden" name="color" value="{{ $filters['color'] }}">@endif
                                @if(!empty($filters['shape']))<input type="hidden" name="shape" value="{{ $filters['shape'] }}">@endif
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-primary text-white hover:opacity-90">Apply</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            @elseif(($type === 'all' || $type === 'products') && $query)
            {{-- Chỉ có ô Price khi không có Color/Shape/Size --}}
            <div class="mb-8 p-4 bg-white rounded-xl border border-primary/10 shadow-sm">
                <form method="GET" action="{{ route('search') }}">
                    <input type="hidden" name="q" value="{{ $query }}">
                    <input type="hidden" name="type" value="products">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Price</span>
                        <input type="number" name="price_min" value="{{ $filters['price_min'] ?? '' }}" placeholder="Min" min="0" step="0.01" class="w-20 px-2 py-1.5 text-sm border border-slate-200 rounded-lg">
                        <span class="text-slate-400">–</span>
                        <input type="number" name="price_max" value="{{ $filters['price_max'] ?? '' }}" placeholder="Max" min="0" step="0.01" class="w-20 px-2 py-1.5 text-sm border border-slate-200 rounded-lg">
                        <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-primary text-white hover:opacity-90">Apply</button>
                    </div>
                </form>
            </div>
            @endif

            <div class="space-y-12">
                {{-- Products --}}
                @if(($type === 'all' || $type === 'products') && isset($products) && $products->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-16">
                    @foreach($products as $product)
                        <x-product-card :product="$product" :show-quick-view="true" />
                    @endforeach
                </div>
                @if(($type ?? '') === 'products' && method_exists($products, 'links'))
                <div class="mt-8 flex justify-center">
                    {{ $products->links() }}
                </div>
                @elseif(isset($counts['products']) && $counts['products'] > $products->count())
                <div class="text-center">
                    <a href="{{ route('search', array_merge(['q' => $query, 'type' => 'products'], array_filter($filters ?? []))) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-lg shadow-md shadow-primary/20 hover:opacity-90 transition-opacity">
                        View All Products ({{ $counts['products'] }})
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
                @endif
                @endif

                {{-- Collections --}}
                @if(($type === 'all' || $type === 'collections') && isset($collections) && $collections->count() > 0)
                <div>
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Collections</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        @foreach($collections as $collection)
                        <a href="{{ route('collections.show', $collection->slug) }}" class="group flex flex-col bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border border-primary/5">
                            <div class="aspect-[4/3] bg-slate-100 overflow-hidden">
                                @if($collection->image ?? null)
                                    <img src="{{ $collection->image }}" alt="{{ $collection->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-primary/10 text-primary">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-slate-900 line-clamp-2 group-hover:text-primary transition-colors">{{ $collection->name }}</h3>
                                <p class="text-xs text-slate-500 mt-1">{{ $collection->active_products_count ?? 0 }} products</p>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    @if(isset($counts['collections']) && $counts['collections'] > $collections->count())
                    <div class="text-center mt-6">
                        <a href="{{ route('search', ['q' => $query, 'type' => 'collections']) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-lg shadow-md shadow-primary/20 hover:opacity-90">View All Collections</a>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Shops --}}
                @if(($type === 'all' || $type === 'shops') && isset($shops) && $shops->count() > 0)
                <div>
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Shops</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        @foreach($shops as $shop)
                        <a href="{{ route('shops.show', $shop->shop_slug ?? $shop->id) }}" class="group flex flex-col bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border border-primary/5 p-4">
                            <div class="flex items-center gap-3">
                                @if($shop->shop_logo ?? null)
                                    <img src="{{ $shop->shop_logo }}" alt="" class="w-14 h-14 rounded-full object-cover ring-2 ring-white shadow">
                                @else
                                    <span class="w-14 h-14 rounded-full bg-primary text-white flex items-center justify-center font-bold text-lg">{{ strtoupper(substr($shop->shop_name, 0, 1)) }}</span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-slate-900 truncate group-hover:text-primary transition-colors">{{ $shop->shop_name }}</h3>
                                    <p class="text-xs text-slate-500">{{ $shop->products_count ?? 0 }} products</p>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    @if(isset($counts['shops']) && $counts['shops'] > $shops->count())
                    <div class="text-center mt-6">
                        <a href="{{ route('search', ['q' => $query, 'type' => 'shops']) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-lg shadow-md shadow-primary/20 hover:opacity-90">View All Shops</a>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        @else
            {{-- No results --}}
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="size-24 bg-primary/10 text-primary rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">No results found</h2>
                <p class="text-slate-500 mb-8 max-w-md">We couldn't find anything for "{{ $query }}". Try adjusting your search or browse below.</p>
                <div class="flex gap-4 flex-wrap justify-center">
                    <a href="{{ route('search') }}" class="px-6 py-2.5 bg-primary text-white rounded-lg font-bold shadow-md shadow-primary/20 hover:opacity-90">Clear Search</a>
                    <a href="{{ route('products.index') }}" class="px-6 py-2.5 border border-primary text-primary rounded-lg font-bold hover:bg-primary/10">View All Nails</a>
                </div>
            </div>
        @endif
    @else
        {{-- Empty search state --}}
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="size-24 bg-primary/10 text-primary rounded-full flex items-center justify-center mb-6">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-2">Search Products & Collections</h2>
            <p class="text-slate-500 mb-8 max-w-md">Enter keywords to search for nails, collections, or shops. Use the search bar above or try a popular search.</p>
            <div class="flex gap-4 flex-wrap justify-center">
                <a href="{{ route('products.index') }}" class="px-6 py-2.5 bg-primary text-white rounded-lg font-bold shadow-md shadow-primary/20 hover:opacity-90">View All Nails</a>
                <a href="{{ route('collections.index') }}" class="px-6 py-2.5 border border-primary text-primary rounded-lg font-bold hover:bg-primary/10">Collections</a>
            </div>
        </div>
    @endif
</main>
@endsection
