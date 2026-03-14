@props([
    'products',
    'title' => 'Recently Viewed',
    'limit' => 5,
])
@if(isset($products) && $products->isNotEmpty())
<div class="pt-8 border-t border-slate-200">
    <h3 class="text-lg font-extrabold text-slate-900 mb-6 flex items-center gap-3">
        <span class="h-px bg-slate-200 flex-1"></span>
        <span class="uppercase tracking-widest text-xs">{{ $title }}</span>
        <span class="h-px bg-slate-200 flex-1"></span>
    </h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4">
        @foreach($products->take($limit) as $product)
            <x-product-card :product="$product" :show-quick-view="true" />
        @endforeach
    </div>
</div>
@endif
