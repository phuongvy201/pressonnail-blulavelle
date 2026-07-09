@php
    $products = $products ?? collect();
    $limit = $limit ?? 5;
    $hasProducts = $products->isNotEmpty();
@endphp
@if($hasProducts)
    <x-recently-viewed-carousel :products="$products" :limit="$limit" />
@else
<div class="rv-section rv-empty">
    <div class="mb-6">
        <p class="flex items-center gap-2 text-[11px] sm:text-xs font-bold uppercase tracking-[0.22em] text-[#0195FE] mb-3">
            <span class="w-4 h-px bg-[#0195FE]"></span>
            <span>For You</span>
        </p>
        <h2 class="text-3xl sm:text-4xl leading-tight">
            <span class="font-black text-slate-900">Recently</span>
            <span class="rv-heading-highlight font-normal italic">Viewed</span>
        </h2>
    </div>
    <p class="text-slate-500 text-sm text-center py-6">Your recently viewed items will appear here. <a href="{{ route('products.index') }}" class="text-primary font-semibold hover:underline">Continue shopping</a>.</p>
</div>
@endif
