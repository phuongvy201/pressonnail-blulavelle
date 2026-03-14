@extends('layouts.app')

@section('title', 'Collections - Bluprinter')
@section('meta_description', 'Browse our curated collections of custom products')

@section('content')
<main class="flex-1">
    {{-- Breadcrumb --}}
    <nav class="max-w-7xl mx-auto px-6 lg:px-20 py-4 flex items-center gap-2 text-xs font-medium text-slate-400 uppercase tracking-widest">
        <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-slate-600">Collections</span>
    </nav>

    {{-- Hero --}}
    <section class="bg-primary/5 py-12 sm:py-16 lg:py-24 mb-10">
        <div class="max-w-7xl mx-auto px-6 lg:px-20 text-center">
            <h1 class="font-serif text-4xl sm:text-5xl lg:text-7xl text-slate-900 mb-4">Our Collections</h1>
            <p class="text-slate-600 text-base sm:text-lg lg:text-xl max-w-3xl mx-auto leading-relaxed italic">
                Hand-crafted stories for your fingertips. Discover curated series of salon-quality press-ons designed to reflect every mood, aesthetic, and occasion.
            </p>

            {{-- Search + Sort --}}
            <form method="GET" class="mt-8 sm:mt-10 max-w-3xl mx-auto">
                <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 items-stretch">
                    <label class="relative flex-1">
                        <span class="sr-only">Search collections</span>
                        <svg class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search collections..."
                            class="w-full pl-12 pr-4 py-3 rounded-full border border-slate-200 bg-white shadow-sm focus:ring-2 focus:ring-primary focus:border-transparent text-sm sm:text-base"
                        />
                    </label>

                    <label class="sm:w-56">
                        <span class="sr-only">Sort by</span>
                        <select
                            name="sort"
                            class="w-full py-3 px-4 rounded-full border border-slate-200 bg-white shadow-sm focus:ring-2 focus:ring-primary focus:border-transparent text-sm sm:text-base font-semibold text-slate-900"
                        >
                            <option value="featured" {{ request('sort') == 'featured' ? 'selected' : '' }}>Featured</option>
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name</option>
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest</option>
                            <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                            <option value="products" {{ request('sort') == 'products' ? 'selected' : '' }}>Most Products</option>
                        </select>
                    </label>

                    <button type="submit" class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-primary text-white font-extrabold shadow-sm hover:opacity-95 transition">
                        Apply
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- Category chips --}}
    <section class="max-w-7xl mx-auto px-6 lg:px-20 mb-12">
        <div class="flex flex-wrap justify-center gap-3 sm:gap-4 py-6 border-y border-primary/10">
            @php
                $chipCollections = $featuredCollections->take(4);
            @endphp

            @foreach($chipCollections as $chip)
                <a href="{{ route('collections.show', $chip->slug) }}" class="group inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-primary/10 shadow-sm hover:bg-primary hover:text-white transition">
                    <span class="text-xs sm:text-sm font-extrabold uppercase tracking-widest">{{ $chip->name }}</span>
                    <span class="text-[11px] sm:text-xs font-bold opacity-70 group-hover:opacity-90">({{ $chip->active_products_count }})</span>
                </a>
            @endforeach

            <a href="{{ route('collections.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/5 border border-primary/10 text-slate-900 hover:bg-primary hover:text-white transition">
                <span class="text-xs sm:text-sm font-extrabold uppercase tracking-widest">All</span>
            </a>
        </div>
    </section>

    {{-- Collections grid --}}
    <section class="max-w-7xl mx-auto px-6 lg:px-20 pb-24">
        @if($collections->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10">
                @foreach($collections as $collection)
                    <a href="{{ route('collections.show', $collection->slug) }}" class="group block">
                        <div class="relative overflow-hidden rounded-2xl aspect-[3/4] mb-5 shadow-sm bg-slate-100">
                            @if($collection->image)
                                <img
                                    src="{{ $collection->image }}"
                                    alt="{{ $collection->name }}"
                                    class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110"
                                    loading="lazy"
                                />
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-primary/30 to-primary/5"></div>
                            @endif

                            <div class="absolute inset-0 bg-gradient-to-t from-black/45 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                            <div class="absolute inset-x-0 bottom-0 p-6 translate-y-4 group-hover:translate-y-0 opacity-0 group-hover:opacity-100 transition-all duration-500">
                                <div class="w-full py-3.5 bg-white text-slate-900 font-extrabold rounded-xl shadow-2xl hover:bg-primary hover:text-white transition-colors text-center">
                                    Shop the Collection
                                </div>
                            </div>

                            <div class="absolute top-4 left-4 flex items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-white/90 backdrop-blur text-slate-900 text-xs font-extrabold">
                                    {{ $collection->active_products_count }} items
                                </span>
                                @if($collection->featured)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-primary text-white text-xs font-extrabold">
                                        Featured
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-center px-4">
                            <h3 class="font-serif text-2xl sm:text-3xl text-slate-900 mb-2 group-hover:text-primary transition-colors line-clamp-2">
                                {{ $collection->name }}
                            </h3>

                            @if($collection->description)
                                <p class="text-slate-500 text-sm italic mb-3 line-clamp-2">{{ $collection->description }}</p>
                            @else
                                <p class="text-slate-500 text-sm italic mb-3">Curated sets designed to match your mood.</p>
                            @endif

                            <span class="inline-block text-xs font-bold uppercase tracking-[0.2em] text-primary border-b border-primary/30 pb-1 group-hover:border-primary transition-all lg:hidden">
                                View Series
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $collections->links() }}
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
                <h3 class="text-xl font-extrabold text-slate-900 mb-2">No Collections Found</h3>
                <p class="text-slate-600">Try adjusting your search or filters.</p>
            </div>
        @endif
    </section>

    <x-see-it-in-action />

    <section class="pb-24">
        <x-recently-viewed wrapperClass="max-w-7xl mx-auto px-6 lg:px-20" />
    </section>
</main>
@endsection
