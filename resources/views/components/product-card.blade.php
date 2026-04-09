@props(['product', 'showQuickView' => true, 'itemListName' => null])

@php
    $media = $product->getEffectiveMedia();
    $imageUrl = null;
    $webpUrl = null;
    $imgAlt = $product->name;
    if ($media && count($media) > 0) {
        $imgAlt = $product->altForMediaItem($media[0], null, 0);
        if (is_string($media[0])) {
            $imageUrl = str_starts_with($media[0], 'http') ? $media[0] : asset('storage/' . $media[0]);
        } elseif (is_array($media[0])) {
            $u = $media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? null;
            $imageUrl = $u ? (str_starts_with($u, 'http') ? $u : asset('storage/' . $u)) : null;
            $w = $media[0]['webp'] ?? null;
            if ($w) {
                $webpUrl = str_starts_with($w, 'http') ? $w : asset('storage/' . $w);
            }
        }
    }
    $currentPriceUsd = (float) ($product->price ?? ($product->template->base_price ?? 0));
    $originalPriceUsd = (float) ($product->list_price ?? $product->template->list_price ?? $product->template->base_price ?? 0);
    $onSale = $originalPriceUsd > 0 && $originalPriceUsd > $currentPriceUsd;
    $discountPercent = $onSale ? round((($originalPriceUsd - $currentPriceUsd) / $originalPriceUsd) * 100) : 0;
    $avgRating = method_exists($product, 'getAverageRating') ? $product->getAverageRating() : 0;
    $reviewsCount = method_exists($product, 'getTotalReviews') ? $product->getTotalReviews() : 0;
    $productUrl = !empty($product->slug) ? route('products.show', ['slug' => $product->slug]) : null;
    $primaryCategory = optional(($product->categories ?? collect())->first())->name
        ?? optional(($product->collections ?? collect())->first())->name;
    $gaSelectItemPayload = [
        'item_id' => $product->sku ?? $product->id,
        'item_name' => $product->name,
        'item_category' => $primaryCategory,
        'price' => round((float) ($product->price ?? ($product->template->base_price ?? 0)), 2),
        'quantity' => 1,
    ];
@endphp

<div
    class="group bg-white rounded-xl overflow-hidden transition-all duration-300 shadow-md hover:shadow-2xl hover:shadow-gray-300/50 border border-gray-100 hover:border-gray-200"
    data-ga-select-item
    data-ga-item='@json($gaSelectItemPayload)'
    data-ga-list-name="{{ $itemListName ?: 'Product List' }}"
>
    <!-- Image wrapper: zoom + Quick View overlay -->
    <div class="relative aspect-square overflow-hidden bg-gray-100">
        @if($imageUrl)
            <img src="{{ $webpUrl ?: $imageUrl }}"
                 alt="{{ $imgAlt }}"
                 class="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-110">
        @else
            <div class="w-full h-full flex items-center justify-center bg-gray-200">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
        @endif

        {{-- Overlay: Quick View button (nền tối nhẹ, nút màu hồng) --}}
        @if($showQuickView && $productUrl)
            <a href="{{ $productUrl }}"
                data-ga-select-item-link
                class="absolute inset-0 flex items-center justify-center bg-black/20 opacity-0 group-hover:opacity-100 transition-all duration-300">
                    <span class="inline-flex items-center gap-2 px-5 py-3 bg-primary-dark text-white font-semibold rounded-full shadow-xl transform translate-y-3 group-hover:translate-y-0 transition-transform duration-300 hover:bg-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Quick View
                    </span>
                </a>
        @endif

        {{-- Wishlist --}}
        <div class="absolute top-2 left-2 sm:top-3 sm:left-3 z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
            <x-wishlist-button :product="$product" size="sm" />
        </div>

        {{-- Sale badge --}}
        @if($onSale)
            <div class="absolute top-2 right-2 sm:top-3 sm:right-3 z-10 bg-primary text-white px-2 py-1 rounded-full text-[10px] sm:text-xs font-bold shadow-md">
                -{{ $discountPercent }}%
            </div>
        @endif
    </div>

    {{-- Info --}}
    <div class="p-3 sm:p-4">
        <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-[#005366] transition-colors text-sm sm:text-base">
            @if($productUrl)
                <a href="{{ $productUrl }}" class="block" data-ga-select-item-link>
                    {{ Str::limit($product->name, 50) }}
                </a>
            @else
                <span class="block text-gray-700 cursor-default">{{ Str::limit($product->name, 50) }}</span>
            @endif
        </h3>
        <p class="text-xs sm:text-sm text-gray-500 mb-1 line-clamp-1">By {{ $product->shop->shop_name ?? $product->shop->name ?? 'Shop' }}</p>

        {{-- Rating --}}
        @if($reviewsCount > 0)
        <div class="flex items-center gap-1 text-amber-500 mb-2">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <span class="text-xs font-semibold text-gray-600">{{ number_format($avgRating, 1) }}</span>
            <span class="text-xs text-gray-600">({{ $reviewsCount }})</span>
        </div>
        @endif

        {{-- Giá: hiển thị giá sale (giá hiện tại + giá gốc gạch ngang) khi có list_price > price --}}
        <div class="flex flex-wrap items-baseline gap-2">
            @if($onSale)
                <span class="text-base sm:text-lg font-bold text-primary-fg">{{ format_price_usd($currentPriceUsd) }}</span>
                <span class="text-xs sm:text-sm text-gray-600 line-through">{{ format_price_usd($originalPriceUsd) }}</span>
            @else
                <span class="text-base sm:text-lg font-bold text-gray-900">{{ format_price_usd($currentPriceUsd) }}</span>
            @endif
        </div>
    </div>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-ga-select-item-link]');
        if (!link) return;

        var card = link.closest('[data-ga-select-item]');
        if (!card) return;

        if (card.dataset.gaTracked === '1') return;
        card.dataset.gaTracked = '1';

        if (typeof dataLayer === 'undefined') return;

        var item = {};
        try {
            item = JSON.parse(card.getAttribute('data-ga-item') || '{}');
        } catch (e) {
            item = {};
        }

        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            event: 'select_item',
            ecommerce: {
                item_list_name: card.getAttribute('data-ga-list-name') || 'Product List',
                items: [item]
            }
        });
    }, { passive: true });
});
</script>
@endonce
