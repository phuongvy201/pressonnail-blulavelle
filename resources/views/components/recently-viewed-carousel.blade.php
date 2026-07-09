@props([
    'products',
    'limit' => 10,
    'eyebrow' => 'For You',
    'heading' => 'Recently',
    'headingHighlight' => 'Viewed',
])

@if(isset($products) && $products->isNotEmpty())
@php
    $items = $products->take($limit)->values();
    $carouselId = 'rv-carousel-' . \Illuminate\Support\Str::random(10);
@endphp
<div class="rv-section" data-rv-root="{{ $carouselId }}">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5 sm:gap-6 mb-8 sm:mb-10">
        <div>
            <p class="flex items-center gap-2 text-[11px] sm:text-xs font-bold uppercase tracking-[0.22em] text-[#0195FE] mb-3">
                <span class="w-4 h-px bg-[#0195FE]"></span>
                <span>{{ $eyebrow }}</span>
            </p>
            <h2 class="text-3xl sm:text-4xl lg:text-[2.75rem] leading-tight">
                <span class="font-black text-slate-900">{{ $heading }}</span>
                <span class="rv-heading-highlight font-normal italic">{{ $headingHighlight }}</span>
            </h2>
        </div>
        @if($items->count() > 1)
        <div class="rv-nav shrink-0 flex items-center gap-2.5 self-start sm:self-auto">
            <button type="button" data-rv-prev="{{ $carouselId }}" class="rv-nav-btn" aria-label="Previous products" disabled>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" data-rv-next="{{ $carouselId }}" class="rv-nav-btn" aria-label="Next products">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
        @endif
    </div>

    <div class="rv-viewport overflow-hidden" data-rv-viewport="{{ $carouselId }}">
        <div id="{{ $carouselId }}" class="rv-track flex will-change-transform" data-rv-track>
            @foreach($items as $product)
                <div class="rv-slide shrink-0" data-rv-slide>
                    <x-product-card :product="$product" :show-quick-view="true" />
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
