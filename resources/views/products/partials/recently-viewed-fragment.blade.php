@php
    $products = $products ?? collect();
    $limit = $limit ?? 5;
    $hasProducts = $products->isNotEmpty();
@endphp
@if($hasProducts)
    <x-related-products :products="$products" title="Recently Viewed" :limit="$limit" />
@else
<div class="pt-8 border-t border-slate-200 recently-viewed-fragment">
    <h3 class="text-lg font-extrabold text-slate-900 mb-6 flex items-center gap-3">
        <span class="h-px bg-slate-200 flex-1"></span>
        <span class="uppercase tracking-widest text-xs">Recently Viewed</span>
        <span class="h-px bg-slate-200 flex-1"></span>
    </h3>
    <p class="text-slate-500 text-sm text-center py-6">Your recently viewed items will appear here. <a href="{{ route('products.index') }}" class="text-primary font-semibold hover:underline">Continue shopping</a>.</p>
</div>
@endif
