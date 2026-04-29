@props([
    'products',
    'title' => 'Recently Viewed',
    'limit' => 10,
])
@if(isset($products) && $products->isNotEmpty())
@php
    $items = $products->take($limit)->values();
    $sliderId = 'related-slider-' . md5($title . '-' . $items->pluck('id')->join('-'));
@endphp
<div class="pt-8 border-t border-slate-200">
    <div class="flex items-center justify-between gap-4 mb-6">
        <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-3 flex-1">
            <span class="h-px bg-slate-200 flex-1"></span>
            <span class="uppercase tracking-widest text-xs">{{ $title }}</span>
            <span class="h-px bg-slate-200 flex-1"></span>
        </h3>
        @if($items->count() > 1)
        <div class="shrink-0 flex items-center gap-2">
            <button type="button" data-slider-prev="{{ $sliderId }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100" aria-label="Previous products">
                <span aria-hidden="true">&larr;</span>
            </button>
            <button type="button" data-slider-next="{{ $sliderId }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100" aria-label="Next products">
                <span aria-hidden="true">&rarr;</span>
            </button>
        </div>
        @endif
    </div>
    <div id="{{ $sliderId }}" class="flex gap-4 overflow-x-hidden scroll-smooth">
        @foreach($items as $product)
            <div class="min-w-0 shrink-0 basis-[85%] sm:basis-[45%] lg:basis-[23%]">
                <x-product-card :product="$product" :show-quick-view="true" />
            </div>
        @endforeach
    </div>
</div>
@once
<script>
document.addEventListener('click', function (event) {
    var prevBtn = event.target.closest('[data-slider-prev]');
    var nextBtn = event.target.closest('[data-slider-next]');
    var id = prevBtn ? prevBtn.getAttribute('data-slider-prev') : (nextBtn ? nextBtn.getAttribute('data-slider-next') : null);
    if (!id) return;

    var slider = document.getElementById(id);
    if (!slider) return;

    var amount = Math.max(280, Math.floor(slider.clientWidth * 0.9));
    slider.scrollBy({
        left: prevBtn ? -amount : amount,
        behavior: 'smooth'
    });
});
</script>
@endonce
@endif
