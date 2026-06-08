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
    <div class="flex items-center justify-between gap-4 mb-5 sm:mb-6">
        <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-3 flex-1 min-w-0">
            <span class="h-px bg-slate-200 flex-1"></span>
            <span class="uppercase tracking-widest text-xs text-center">{{ $title }}</span>
            <span class="h-px bg-slate-200 flex-1"></span>
        </h3>
        @if($items->count() > 1)
        <div class="shrink-0 hidden sm:flex items-center gap-2">
            <button type="button" data-slider-prev="{{ $sliderId }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100" aria-label="Previous products">
                <span aria-hidden="true">&larr;</span>
            </button>
            <button type="button" data-slider-next="{{ $sliderId }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100" aria-label="Next products">
                <span aria-hidden="true">&rarr;</span>
            </button>
        </div>
        @endif
    </div>
    <div class="w-full max-w-[100vw] sm:max-w-none overflow-hidden sm:overflow-visible">
        <div id="{{ $sliderId }}" class="related-products-scroll flex gap-3 sm:gap-4 w-full overflow-x-auto sm:overflow-x-hidden overscroll-x-contain snap-x snap-mandatory sm:snap-none scroll-smooth touch-pan-x sm:touch-auto pb-1 sm:pb-0">
            @foreach($items as $product)
                <div class="related-product-slide snap-start shrink-0 min-w-0 sm:!w-[45%] lg:!w-[23%] sm:!flex-[0_0_45%] lg:!flex-[0_0_23%]">
                    <x-product-card :product="$product" :show-quick-view="true" />
                </div>
            @endforeach
        </div>
    </div>
</div>
@once
<style>
.related-products-scroll { scrollbar-width: none; -ms-overflow-style: none; }
.related-products-scroll::-webkit-scrollbar { display: none; }
@media (max-width: 639px) {
    .related-product-slide {
        flex: 0 0 calc(50% - 0.375rem);
        width: calc(50% - 0.375rem);
        max-width: calc(50% - 0.375rem);
    }
}
</style>
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
