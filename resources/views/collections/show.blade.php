@extends('layouts.app')

@section('title', $collection->meta_title ?? $collection->name)
@section('meta_description', $collection->meta_description ?? $collection->description)

@section('content')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof fbq !== 'undefined') {
        fbq('track', 'ViewContent', {
            content_name: '{{ addslashes($collection->name) }}',
            content_type: 'product_group'
        });
    }
});
</script>

<main class="flex-1">
    {{-- Breadcrumb --}}
    <nav class="max-w-7xl mx-auto px-6 lg:px-20 py-4 flex items-center gap-2 text-xs font-medium text-slate-400 uppercase tracking-widest">
        <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <a class="hover:text-primary transition-colors" href="{{ route('collections.index') }}">Collections</a>
        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-slate-600">{{ $collection->name }}</span>
    </nav>

    {{-- Hero: collection name + description + image --}}
    <section class="bg-primary/5 py-12 sm:py-16 lg:py-24 mb-10">
        <div class="max-w-7xl mx-auto px-6 lg:px-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
                <div class="order-2 lg:order-1 text-center lg:text-left">
                    @if($collection->featured)
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-primary text-white text-xs font-extrabold mb-4">Featured</span>
                    @endif
                    <h1 class="font-serif text-4xl sm:text-5xl lg:text-6xl text-slate-900 mb-4">{{ $collection->name }}</h1>
                    @if($collection->description)
                        <p class="text-slate-600 text-base sm:text-lg max-w-xl leading-relaxed italic">
                            {{ $collection->description }}
                        </p>
                    @endif
                    <p class="mt-4 text-slate-500 text-sm font-semibold">{{ $products->total() }} products</p>
                </div>
                <div class="order-1 lg:order-2 relative rounded-2xl overflow-hidden aspect-[4/3] lg:aspect-square bg-slate-100 shadow-lg">
                    @if($collection->image)
                        <img src="{{ $collection->image }}" alt="{{ $collection->name }}" class="w-full h-full object-cover" loading="eager">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-primary/30 to-primary/5 flex items-center justify-center">
                            <svg class="w-20 h-20 text-primary/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Filter + Sort --}}
    <section class="max-w-7xl mx-auto px-6 lg:px-20 mb-10">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 sm:gap-4 items-stretch sm:items-center flex-wrap">
            <div class="flex flex-wrap items-center gap-3">
                <input type="number" name="min_price" placeholder="Min price" value="{{ request('min_price') }}"
                       class="w-28 px-4 py-2.5 rounded-full border border-slate-200 bg-white shadow-sm focus:ring-2 focus:ring-primary focus:border-transparent text-sm">
                <span class="text-slate-400">–</span>
                <input type="number" name="max_price" placeholder="Max price" value="{{ request('max_price') }}"
                       class="w-28 px-4 py-2.5 rounded-full border border-slate-200 bg-white shadow-sm focus:ring-2 focus:ring-primary focus:border-transparent text-sm">
            </div>
            <label class="sm:w-48">
                <span class="sr-only">Sort by</span>
                <select name="sort" class="w-full py-2.5 px-4 rounded-full border border-slate-200 bg-white shadow-sm focus:ring-2 focus:ring-primary focus:border-transparent text-sm font-semibold text-slate-900">
                    <option value="default" {{ request('sort') == 'default' ? 'selected' : '' }}>Default</option>
                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name A–Z</option>
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest</option>
                </select>
            </label>
            <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 rounded-full bg-primary text-white font-extrabold shadow-sm hover:opacity-95 transition text-sm">
                Apply
            </button>
            @if(request()->hasAny(['min_price', 'max_price', 'sort']))
                <a href="{{ route('collections.show', $collection->slug) }}" class="text-sm font-semibold text-slate-500 hover:text-primary transition-colors">
                    Clear filters
                </a>
            @endif
        </form>
    </section>

    {{-- Products grid (product-card component) --}}
    <section class="max-w-7xl mx-auto px-6 lg:px-20 pb-16">
        @if($products->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                @foreach($products as $product)
                    <x-product-card :product="$product" :show-quick-view="true" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $products->links() }}
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
                <h3 class="text-xl font-extrabold text-slate-900 mb-2">No products in this collection</h3>
                <p class="text-slate-600 mb-6">Try adjusting filters or come back later.</p>
                @if(request()->hasAny(['min_price', 'max_price', 'sort']))
                    <a href="{{ route('collections.show', $collection->slug) }}" class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-primary text-white font-extrabold shadow-sm hover:opacity-95 transition">
                        Clear filters
                    </a>
                @endif
            </div>
        @endif
    </section>

    {{-- Related collections (chips như trang index) --}}
    @if(isset($relatedCollections) && $relatedCollections->count() > 0)
        <section class="max-w-7xl mx-auto px-6 lg:px-20 mb-16">
            <div class="flex flex-wrap justify-center gap-3 sm:gap-4 py-6 border-y border-primary/10">
                <span class="text-xs sm:text-sm font-extrabold uppercase tracking-widest text-slate-500 self-center mr-2">Related:</span>
                @foreach($relatedCollections as $related)
                    <a href="{{ route('collections.show', $related->slug) }}" class="group inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-primary/10 shadow-sm hover:bg-primary hover:text-white transition">
                        <span class="text-xs sm:text-sm font-extrabold uppercase tracking-widest">{{ $related->name }}</span>
                        <span class="text-[11px] sm:text-xs font-bold opacity-70 group-hover:opacity-90">({{ $related->active_products_count }})</span>
                    </a>
                @endforeach
                <a href="{{ route('collections.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/5 border border-primary/10 text-slate-900 hover:bg-primary hover:text-white transition">
                    <span class="text-xs sm:text-sm font-extrabold uppercase tracking-widest">All collections</span>
                </a>
            </div>
        </section>
    @endif

    <x-see-it-in-action />

    <section class="pb-24">
        <x-recently-viewed wrapperClass="max-w-7xl mx-auto px-6 lg:px-20" />
    </section>
</main>
@endsection
