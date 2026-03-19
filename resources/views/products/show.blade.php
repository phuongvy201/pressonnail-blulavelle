@extends('layouts.app')

@section('title', $product->name)

@section('content')
@php
    $currentCurrency = currency();
    $currencySymbol = currency_symbol();
    // Gallery = ảnh product trước, sau đó + media từ template (ảnh + video)
    $galleryItems = [];
    $normalizeUrl = function ($u) {
        if (!$u) return null;
        return (str_starts_with($u, 'http://') || str_starts_with($u, 'https://')) ? $u : asset('storage/' . $u);
    };
    // 1) Media của product
    $media = $product->media ?? [];
    if ($media && count($media) > 0) {
        foreach ($media as $m) {
            if (is_string($m)) {
                $url = $normalizeUrl($m);
                if ($url) $galleryItems[] = ['type' => 'image', 'url' => $url];
            } elseif (is_array($m)) {
                $u = $m['url'] ?? $m['path'] ?? reset($m);
                if (!$u) continue;
                if (isset($m['type']) && ($m['type'] ?? '') === 'video') {
                    $poster = isset($m['poster']) ? $normalizeUrl($m['poster']) : null;
                    $galleryItems[] = ['type' => 'video', 'url' => $normalizeUrl($u), 'poster' => $poster];
                } else {
                    $galleryItems[] = ['type' => 'image', 'url' => $normalizeUrl($u)];
                }
            }
        }
    }
    // 2) Nếu product không có media, dùng ảnh từ getEffectiveMedia (template)
    if (empty($galleryItems)) {
        $media = $product->getEffectiveMedia();
        if ($media && count($media) > 0) {
            foreach ($media as $m) {
                if (is_string($m)) {
                    $url = $normalizeUrl($m);
                    if ($url && !preg_match('/\.(mp4|webm|ogg|mov|avi)$/i', $m)) $galleryItems[] = ['type' => 'image', 'url' => $url];
                } elseif (is_array($m)) {
                    if (isset($m['type']) && ($m['type'] ?? '') === 'video') {
                        $u = $m['url'] ?? $m['path'] ?? null;
                        if ($u) {
                            $poster = isset($m['poster']) ? $normalizeUrl($m['poster']) : null;
                            $galleryItems[] = ['type' => 'video', 'url' => $normalizeUrl($u), 'poster' => $poster];
                        }
                    } else {
                        $u = $m['url'] ?? $m['path'] ?? reset($m);
                        if ($u) $galleryItems[] = ['type' => 'image', 'url' => $normalizeUrl($u)];
                    }
                }
            }
        }
    }
    // 3) Thêm media từ template vào cuối (ảnh + video, có thể trùng một phần nếu product không có media)
    $templateMedia = ($product->template && $product->template->media && count($product->template->media) > 0) ? $product->template->media : [];
    $existingUrls = collect($galleryItems)->pluck('url')->flip()->all();
    foreach ($templateMedia as $m) {
        if (is_string($m)) {
            $url = $normalizeUrl($m);
            if (!$url) continue;
            if (preg_match('/\.(mp4|webm|ogg|mov|avi)$/i', $m)) {
                if (empty($existingUrls[$url] ?? null)) { $galleryItems[] = ['type' => 'video', 'url' => $url, 'poster' => $galleryItems[0]['url'] ?? null]; $existingUrls[$url] = true; }
            } else {
                if (empty($existingUrls[$url] ?? null)) { $galleryItems[] = ['type' => 'image', 'url' => $url]; $existingUrls[$url] = true; }
            }
        } elseif (is_array($m)) {
            if (isset($m['type']) && ($m['type'] ?? '') === 'video') {
                $u = $m['url'] ?? $m['path'] ?? null;
                if ($u) {
                    $url = $normalizeUrl($u);
                    if (empty($existingUrls[$url] ?? null)) {
                        $poster = isset($m['poster']) ? $normalizeUrl($m['poster']) : ($galleryItems[0]['url'] ?? null);
                        $galleryItems[] = ['type' => 'video', 'url' => $url, 'poster' => $poster];
                        $existingUrls[$url] = true;
                    }
                }
            } else {
                $u = $m['url'] ?? $m['path'] ?? reset($m);
                if ($u) { $url = $normalizeUrl($u); if (empty($existingUrls[$url] ?? null)) { $galleryItems[] = ['type' => 'image', 'url' => $url]; $existingUrls[$url] = true; } }
            }
        }
    }
    $primaryImageUrl = isset($galleryItems[0])
        ? ($galleryItems[0]['type'] === 'image'
            ? $galleryItems[0]['url']
            : ($galleryItems[0]['poster'] ?? null))
        : null;
    // Fallback poster cho video (đặc biệt trên EC2 khi FFmpeg/poster không tạo được)
    $fallbackPosterUrl = collect($galleryItems)->firstWhere('type', 'image')['url'] ?? null;
    $fallbackPosterUrl = $fallbackPosterUrl ?: (is_string($primaryImageUrl) && !preg_match('/\.(mp4|webm|ogg|mov|avi)$/i', $primaryImageUrl) ? $primaryImageUrl : null);
    // Inline SVG placeholder (không phụ thuộc file trong public/)
    $fallbackPosterUrl = $fallbackPosterUrl ?: "data:image/svg+xml;charset=UTF-8," . rawurlencode('<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"800\" height=\"800\" viewBox=\"0 0 800 800\"><rect width=\"800\" height=\"800\" fill=\"#f1f5f9\"/><path d=\"M250 520l90-110 80 100 70-80 130 160H250z\" fill=\"#cbd5e1\"/><circle cx=\"320\" cy=\"310\" r=\"44\" fill=\"#cbd5e1\"/><text x=\"50%\" y=\"92%\" text-anchor=\"middle\" font-family=\"Arial\" font-size=\"28\" fill=\"#94a3b8\">No preview</text></svg>');
    $firstMediaIsVideo = isset($galleryItems[0]) && $galleryItems[0]['type'] === 'video';
    $reviewsCount = $product->getTotalReviews();
    $averageRating = $product->getAverageRating();
    $sizes = $product->variants->pluck('attributes')->filter()->map(function ($a) { return $a['Size'] ?? $a['size'] ?? null; })->filter()->unique()->values();
    if ($sizes->isEmpty()) { $sizes = collect(['XS', 'S', 'M', 'L']); }
    $nailShapeKey = 'Nail Shape';
    $shapes = $product->variants->pluck('attributes')->filter()->map(function ($a) use ($nailShapeKey) {
        return $a[$nailShapeKey] ?? $a['Shape'] ?? $a['shape'] ?? null;
    })->filter()->unique()->values();
    if ($shapes->isEmpty() && $product->template && $product->template->relationLoaded('variants')) {
        $shapes = $product->template->variants->pluck('attributes')->filter()->map(function ($a) use ($nailShapeKey) {
            return $a[$nailShapeKey] ?? $a['Shape'] ?? $a['shape'] ?? null;
        })->filter()->unique()->values();
    }
    $shapeIconMap = [
        'Short - Square' => 'rectangle', 'Medium - Square' => 'rectangle', 'Long - Square' => 'rectangle',
        'Short - Oval' => 'circle', 'Medium - Oval' => 'circle', 'Long - Oval' => 'circle',
        'Short - Almond' => 'water_drop', 'Medium - Almond' => 'water_drop', 'Long - Almond' => 'water_drop',
        'Short - Coffin' => 'straighten', 'Medium - Coffin' => 'straighten', 'Long - Coffin' => 'straighten',
        'Short - Stiletto' => 'straighten', 'Medium - Stiletto' => 'straighten', 'Long - Stiletto' => 'straighten',
    ];
    $shapeIcons = $shapes->mapWithKeys(function ($name) use ($shapeIconMap) {
        return [$name => $shapeIconMap[$name] ?? 'rectangle'];
    });
    if ($shapes->isEmpty()) {
        $shapes = collect(['Short - Square', 'Short - Oval', 'Short - Almond', 'Medium - Coffin']);
        $shapeIcons = collect($shapes->mapWithKeys(fn($n) => [$n => $shapeIconMap[$n] ?? 'rectangle']));
    }
    $sizeMm = ['XS' => '14-10-11-10-8mm', 'S' => '15-11-12-11-9mm', 'M' => '16-12-13-12-10mm', 'L' => '18-13-14-13-12mm'];
    $productPrice = (float) ($product->price ?? $product->base_price ?? 0);
    $productListPrice = (float) ($product->list_price ?? $product->template->list_price ?? 0);
    $showProductListPrice = $productListPrice > 0 && $productListPrice > $productPrice;
    $variantsForJs = $product->variants->map(function ($v) {
        return [
            'id' => $v->id,
            'price' => $v->price !== null ? (float) $v->price : null,
            'list_price' => $v->list_price !== null ? (float) $v->list_price : null,
            'attributes' => $v->attributes ?? [],
        ];
    })->values();

    // Shipping snippet (UI giống mẫu). Country lấy theo tool detect (header/session/currency/domain).
    $location = app(\App\Services\CustomerLocationService::class);
    $shipCountryCode = $location->detectCountryCode(request(), 'VN');
    $shipCountryName = $location->getCountryName($shipCountryCode);
    $shipsFrom = 'United States';
    $deliveryStart = now()->addDays(11);
    $deliveryEnd = now()->addDays(20);
    $deliveryRangeText = $deliveryStart->format('M j') . '–' . ($deliveryStart->format('M') === $deliveryEnd->format('M') ? $deliveryEnd->format('j') : $deliveryEnd->format('M j'));
    $shippingCostUsd = null;
    try {
        $shippingCalc = app(\App\Services\ShippingCalculator::class);
        $shippingQuote = $shippingCalc->calculateShipping(collect([
            ['product_id' => $product->id, 'quantity' => 1, 'price' => $productPrice],
        ]), $shipCountryCode);
        if (!empty($shippingQuote['success'])) {
            $shippingCostUsd = (float) ($shippingQuote['total_shipping'] ?? 0);
        }
    } catch (\Throwable $e) {
        $shippingCostUsd = null;
    }
    // Số viewing & in carts ngẫu nhiên theo product (seed bằng id để mỗi sản phẩm cố định)
    mt_srand(crc32((string) $product->id));
    $productViewingCount = random_int(8, 120);
    $productCartsCount = random_int(3, 65);
    mt_srand();
@endphp

<div class="min-h-screen bg-[#f8f6f6] font-display">
    <div class="max-w-[1200px] mx-auto w-full px-4 sm:px-6 md:px-10 py-8 md:py-12">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-10">
            {{-- Left: Gallery — thu gọn, ~40% trên desktop --}}
            <div class="w-full lg:w-[45%] xl:w-[43%] shrink-0">
                <div class="lg:sticky lg:top-10">
                    <div class="space-y-3">
                        <div class="aspect-square max-h-[460px] lg:max-h-[440px] w-full rounded-2xl overflow-hidden bg-white shadow-md border border-slate-100 relative mx-auto" id="product-main-media-wrap">
                            @if(count($galleryItems) > 0)
                                @if($firstMediaIsVideo)
                                    @php
                                        $mainPoster = $galleryItems[0]['poster'] ?? null;
                                        if ($mainPoster && preg_match('/\.(mp4|webm|ogg|mov|avi)$/i', $mainPoster)) { $mainPoster = null; }
                                        $mainPoster = $mainPoster ?: $fallbackPosterUrl;
                                    @endphp
                                    <video id="product-main-video" class="w-full h-full object-cover" controls playsinline poster="{{ $mainPoster }}" src="{{ $galleryItems[0]['url'] }}"></video>
                                    <img alt="{{ $product->name }}" class="w-full h-full object-cover hidden" id="product-main-image" src="">
                                @else
                                    <video id="product-main-video" class="w-full h-full object-cover hidden" controls playsinline></video>
                                    <img alt="{{ $product->name }}" class="w-full h-full object-cover" id="product-main-image" src="{{ $galleryItems[0]['url'] }}">
                                @endif
                            @else
                                <img alt="{{ $product->name }}" class="w-full h-full object-cover" id="product-main-image" src="{{ $primaryImageUrl ?? '' }}">
                                <video id="product-main-video" class="w-full h-full object-cover hidden" controls playsinline></video>
                            @endif
                            @if(count($galleryItems) === 0)
                                <div class="absolute inset-0 flex items-center justify-center bg-slate-100">
                                    <span class="material-symbols-outlined text-6xl text-slate-300">image</span>
                                </div>
                            @endif
                            @if(count($galleryItems) > 1)
                            <button type="button" id="main-media-prev" class="absolute left-2 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white/90 backdrop-blur border border-slate-200 shadow-md hover:bg-white transition flex items-center justify-center" aria-label="Ảnh/Video trước">
                                <span class="material-symbols-outlined text-slate-700">chevron_left</span>
                            </button>
                            <button type="button" id="main-media-next" class="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white/90 backdrop-blur border border-slate-200 shadow-md hover:bg-white transition flex items-center justify-center" aria-label="Ảnh/Video sau">
                                <span class="material-symbols-outlined text-slate-700">chevron_right</span>
                            </button>
                            @endif
                        </div>
                        @if(count($galleryItems) > 1)
                        <div class="relative" id="gallery-thumbnails">
                            <button type="button" id="gallery-prev"
                                    class="absolute -left-2 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white/90 backdrop-blur border border-slate-200 shadow-md hover:bg-white transition hidden md:inline-flex items-center justify-center"
                                    aria-label="Previous media">
                                <span class="material-symbols-outlined text-slate-700">chevron_left</span>
                            </button>

                            <div id="gallery-viewport" class="overflow-hidden">
                                <div id="gallery-track" class="flex gap-3 overflow-x-auto scroll-smooth no-scrollbar py-1">
                                    @foreach($galleryItems as $index => $item)
                                    <button type="button"
                                            class="gallery-thumb shrink-0 w-[88px] sm:w-[96px] md:w-[104px] aspect-square rounded-xl overflow-hidden border-2 {{ $index === 0 ? 'border-[#0297FE]' : 'border-slate-200' }} bg-white focus:outline-none focus:ring-2 focus:ring-[#0297FE]/40 transition-all relative group"
                                            data-index="{{ $index }}"
                                            data-type="{{ $item['type'] }}"
                                            data-url="{{ $item['url'] }}"
                                            data-poster="{{ $item['poster'] ?? '' }}"
                                            aria-label="{{ $item['type'] === 'video' ? 'Xem video ' : 'Xem ảnh ' }}{{ $index + 1 }}">
                                        @if($item['type'] === 'video')
                                            @php
                                                $thumbPoster = $item['poster'] ?? null;
                                                if ($thumbPoster && preg_match('/\.(mp4|webm|ogg|mov|avi)$/i', $thumbPoster)) { $thumbPoster = null; }
                                                $thumbPoster = $thumbPoster ?: $fallbackPosterUrl;
                                            @endphp
                                            <img class="w-full h-full object-cover" src="{{ $thumbPoster }}" alt="">
                                            <span class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-black/40 transition-colors">
                                                <span class="material-symbols-outlined text-white text-3xl drop-shadow">play_circle</span>
                                            </span>
                                        @else
                                            <img class="w-full h-full object-cover" src="{{ $item['url'] }}" alt="">
                                        @endif
                                    </button>
                                    @endforeach
                                </div>
                            </div>

                            <button type="button" id="gallery-next"
                                    class="absolute -right-2 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white/90 backdrop-blur border border-slate-200 shadow-md hover:bg-white transition hidden md:inline-flex items-center justify-center"
                                    aria-label="Next media">
                                <span class="material-symbols-outlined text-slate-700">chevron_right</span>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right: Nội dung + form — chiếm nhiều diện tích hơn --}}
            <div class="w-full lg:flex-1 min-w-0">
                {{-- Title + tag --}}
                <div class="mb-6">
                    @if($reviewsCount > 0)
                    <div class="flex items-center gap-2 mb-2">
                        <div class="flex text-amber-400">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($averageRating))
                                    <span class="material-symbols-outlined text-lg fill-current">star</span>
                                @elseif($i - 0.5 <= $averageRating)
                                    <span class="material-symbols-outlined text-lg fill-current">star_half</span>
                                @else
                                    <span class="material-symbols-outlined text-lg text-slate-200">star</span>
                                @endif
                            @endfor
                        </div>
                        <span class="text-sm font-medium text-slate-500">{{ $reviewsCount }} {{ $reviewsCount === 1 ? 'Review' : 'Reviews' }}</span>
                    </div>
                    @endif
                    <h1 class="text-2xl md:text-3xl text-heading font-extrabold text-slate-900 tracking-tight leading-tight">{{ $product->name }}</h1>
                    @if($product->shop)
                    <p class="mt-2 text-sm text-slate-600">
                        Sold by <a href="{{ route('shops.show', $product->shop->shop_slug) }}" class="font-semibold text-slate-800 hover:text-[#0297FE] transition-colors underline underline-offset-2">{{ $product->shop->shop_name ?? $product->shop->name ?? 'Shop' }}</a>
                    </p>
                    @endif
                    @if($product->collections->isNotEmpty())
                    <div class="mt-3 flex items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-[#0297FE]/10 text-[#0297FE] border border-[#0297FE]/20 uppercase tracking-wide">
                            {{ $product->collections->first()->name }}
                        </span>
                    </div>
                    @endif

                    {{-- In Stock, Viewing, In Cart (fake), Share --}}
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                            <span class="material-symbols-outlined text-base">check_circle</span>
                            In Stock
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            <span class="material-symbols-outlined text-base">visibility</span>
                            <span id="product-viewers">{{ $productViewingCount }}</span> viewing
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-[#0297FE]/10 text-[#0297FE] border border-[#0297FE]/15">
                            <span class="material-symbols-outlined text-base">shopping_cart</span>
                            In <span id="product-carts">{{ $productCartsCount }}+</span> carts
                        </span>
                        <button type="button" class="ml-auto p-1.5 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors" aria-label="Share product" id="product-share-btn">
                            <span class="material-symbols-outlined text-xl">share</span>
                        </button>
                    </div>

                    <div class="mt-4 inline-flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-widest bg-[#0297FE]/10 text-[#0297FE] border border-[#0297FE]/15">
                            Total
                        </span>
                        <span id="product-price" data-base-price="{{ $productPrice }}" data-list-price="{{ $productListPrice }}"
                              class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight drop-shadow-[0_1px_0_rgba(255,255,255,0.8)]">
                            {{ format_price($productPrice) }}
                        </span>
                        @if($showProductListPrice)
                            <span id="product-list-price" class="text-lg sm:text-xl text-slate-400 line-through font-semibold">{{ format_price($productListPrice) }}</span>
                        @else
                            <span id="product-list-price" class="text-lg sm:text-xl text-slate-400 line-through font-semibold hidden"></span>
                        @endif
                        <span id="product-price-note" class="text-sm font-bold text-[#0297FE] hidden"></span>
                    </div>
                </div>

                {{-- Toast thông báo (thay alert) --}}
                <div id="product-toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] max-w-md w-full mx-4 hidden" role="alert" aria-live="polite">
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border bg-white border-slate-200">
                        <span id="product-toast-icon" class="material-symbols-outlined text-2xl text-[#0297FE]">error</span>
                        <p id="product-toast-message" class="flex-1 text-sm font-medium text-slate-800"></p>
                        <button type="button" id="product-toast-close" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors" aria-label="Đóng">
                            <span class="material-symbols-outlined text-xl">close</span>
                        </button>
                    </div>
                </div>

                {{-- Form + Add to Cart + Shipping + Product Details --}}
                <div class="product-sticky-sidebar flex flex-col gap-6 rounded-2xl bg-white/80 backdrop-blur-sm p-6 shadow-sm border border-slate-100">
                
                    {{-- Customization (từ ProductTemplate) — nằm trên Select Size --}}
                    @if($product->template && $product->template->hasCustomization())
                    @php $customizationTypes = $product->template->getCustomizationTypes(); @endphp
                    <div class="space-y-4" id="product-customizations">
                        <label class="text-xs font-semibold text-slate-700 uppercase tracking-wide">Customization</label>
                        @foreach($customizationTypes as $index => $custom)
                        @php
                            $type = $custom['type'] ?? 'text';
                            $label = $custom['label'] ?? ('Option ' . ($index + 1));
                            $placeholder = $custom['placeholder'] ?? '';
                            $price = (float) ($custom['price'] ?? 0);
                            $required = !empty($custom['required']);
                            $options = isset($custom['options']) ? preg_split('/\r\n|\r|\n/', trim($custom['options']), -1, PREG_SPLIT_NO_EMPTY) : [];
                        @endphp
                        <div class="space-y-1.5 customization-row" data-customization-index="{{ $index }}" data-label="{{ e($label) }}" data-price="{{ $price }}">
                            <label class="text-sm font-medium text-slate-800">
                                {{ $label }}
                                @if($price > 0)<span class="text-slate-500 font-normal">(+{{ format_price($price) }})</span>@endif
                                @if($required)<span class="text-red-500">*</span>@endif
                            </label>
                            @if($type === 'text')
                                <input type="text" class="customization-field w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE]" name="customization[{{ $index }}]" placeholder="{{ $placeholder }}" @if($required) data-required="1" @endif>
                            @elseif($type === 'number')
                                <input type="number" class="customization-field w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE]" name="customization[{{ $index }}]" placeholder="{{ $placeholder }}" @if($required) data-required="1" @endif>
                            @elseif($type === 'textarea')
                                <textarea class="customization-field w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE] resize-none" name="customization[{{ $index }}]" rows="2" placeholder="{{ $placeholder }}" @if($required) data-required="1" @endif></textarea>
                            @elseif($type === 'select')
                                <select class="customization-field w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE]" name="customization[{{ $index }}]" @if($required) data-required="1" @endif>
                                    <option value="">— Select —</option>
                                    @foreach($options as $opt)
                                    <option value="{{ e(trim($opt)) }}">{{ e(trim($opt)) }}</option>
                                    @endforeach
                                </select>
                            @elseif($type === 'checkbox')
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" class="customization-field w-4 h-4 rounded border-slate-300 text-[#0297FE] focus:ring-[#0297FE]" name="customization[{{ $index }}]" value="1" @if($required) data-required="1" @endif>
                                    <span class="text-sm text-slate-600">{{ $placeholder ?: 'Yes' }}</span>
                                </label>
                            @else
                                <input type="text" class="customization-field w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE]" name="customization[{{ $index }}]" placeholder="{{ $placeholder }}" @if($required) data-required="1" @endif>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Select Size --}}
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-slate-700 uppercase tracking-wide">Select Size</label>
                        <div class="flex flex-wrap gap-2" id="size-options">
                            @foreach($sizes as $size)
                            <div class="size-badge" data-size="{{ $size }}" role="button" tabindex="0">
                                <span class="size-label">{{ $size }}</span>
                                <span class="size-mm">{{ $sizeMm[$size] ?? '' }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Select Shape (Nail Shape) - chỉ chữ, không icon --}}
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-slate-700 uppercase tracking-wide">Nail Shape</label>
                        <div class="flex flex-wrap gap-2" id="shape-options">
                            @foreach($shapes as $shape)
                            <div class="shape-button shape-button-text" data-shape="{{ $shape }}" role="button" tabindex="0">
                                <span class="text-xs font-medium text-slate-900">{{ $shape }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Quantity --}}
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-slate-700 uppercase tracking-wide" for="product-quantity">Quantity</label>
                        <div class="flex items-center gap-2 w-fit">
                            <button type="button" id="qty-minus" class="w-10 h-10 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Giảm số lượng">
                                <span class="material-symbols-outlined text-xl">remove</span>
                            </button>
                            <input type="number" id="product-quantity" name="quantity" min="1" max="99" value="1" class="w-16 text-center py-2 text-sm font-medium border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE]">
                            <button type="button" id="qty-plus" class="w-10 h-10 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Tăng số lượng">
                                <span class="material-symbols-outlined text-xl">add</span>
                            </button>
                        </div>
                    </div>

                    {{-- Add to Cart + Favorite --}}
                    <div class="flex gap-3 pt-1">
                        <button type="button" id="add-to-cart-btn" class="add-to-cart-btn flex-1 text-white font-bold py-4 px-6 rounded-xl transition-all flex items-center justify-center gap-2 min-h-[52px] touch-manipulation">
                            <svg class="add-to-cart-icon w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                            </svg>
                            <span>Add to Cart</span>
                        </button>
                        <button type="button"
                                data-wishlist-toggle
                                data-product-id="{{ $product->id }}"
                                data-product-name="{{ $product->name }}"
                                data-product-price="{{ $product->base_price ?? $product->price ?? 0 }}"
                                class="wishlist-btn shrink-0 w-14 h-14 rounded-xl border-2 border-slate-200 hover:border-[#0297FE]/50 hover:bg-[#0297FE]/5 transition-all inline-flex items-center justify-center not-in-wishlist text-slate-600"
                                aria-label="Yêu thích / Wishlist">
                            <svg class="wishlist-icon w-7 h-7 transition-colors" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Shipping info --}}
                    <div class="flex items-center justify-between pt-4 border-t border-slate-200 gap-4 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-green-600 text-xl">verified</span>
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Ships within 24 hours</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-600 text-xl">local_shipping</span>
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Free US Shipping</span>
                        </div>
                    </div>

                    {{-- Product Details (trong sticky sidebar) --}}
                    <div class="pt-6 border-t border-slate-200">
                        <h3 class="text-lg font-extrabold text-slate-900 mb-3">Product Details</h3>
                        <div class="text-slate-600 text-sm leading-relaxed space-y-2 product-detail-description">
                            {!! nl2br(e(strip_tags($product->getEffectiveDescription()))) !!}
                        </div>
                    </div>

                    {{-- Shipping and return policies (nằm dưới Product Details, vẫn trong right content) --}}
                    <div class="pt-6 border-t border-slate-200">
                        <h3 class="text-base font-extrabold text-slate-900 mb-4">Shipping and return policies</h3>

                        <div class="space-y-3 text-sm text-slate-700">
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-slate-600 text-xl leading-none">calendar_month</span>
                                <div class="leading-snug">
                                    <span class="text-slate-600">Order today to get by</span>
                                    <span class="font-bold underline underline-offset-2 decoration-[#0297FE] ml-1">{{ $deliveryRangeText }}</span>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-slate-600 text-xl leading-none">assignment_return</span>
                                <div class="relative leading-snug">
                                    <span class="group inline-flex items-center gap-1 font-bold underline underline-offset-2 decoration-[#0297FE] cursor-help">
                                        Returns &amp; exchanges accepted
                                        <span class="material-symbols-outlined text-base text-slate-500">info</span>

                                        <span class="pointer-events-none absolute left-0 top-full mt-2 z-20 hidden w-[320px] max-w-[85vw] rounded-xl border border-slate-200 bg-white p-3 text-xs font-medium text-slate-700 shadow-xl group-hover:block">
                                            <span class="block font-extrabold text-slate-900 mb-1">Return policy</span>
                                            <span class="block leading-relaxed">
                                                - Return within 30 days of delivery.<br>
                                                - Item must be unused and in original packaging.<br>
                                                - Custom/Personalized items are not eligible unless defective.<br>
                                                - Buyer pays return shipping unless the item is damaged/incorrect.
                                            </span>
                                        </span>
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-slate-600 text-xl leading-none">local_shipping</span>
                                <div class="leading-snug">
                                    <span class="text-slate-600">Cost to ship:</span>
                                    <span class="font-bold ml-1">
                                        @if(is_numeric($shippingCostUsd))
                                            {{ format_price_usd((float) $shippingCostUsd) }}
                                        @else
                                            Calculated at checkout
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-slate-600 text-xl leading-none">public</span>
                                <div class="leading-snug">
                                    <span class="text-slate-600">Ships from:</span>
                                    <span class="font-bold ml-1">{{ $shipsFrom }}</span>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="button" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-slate-900">
                                    Deliver to {{ $shipCountryName }}
                                    <span class="material-symbols-outlined text-base">edit</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Full width: Video, Recently Viewed --}}
        <div class="mt-12 space-y-12">
            @if($product->template && $product->template->media && count($product->template->media) > 0)
            @php
                $videoUrl = null;
                foreach ($product->template->media as $m) {
                    if (is_array($m) && isset($m['type']) && ($m['type'] ?? '') === 'video') { $videoUrl = $m['url'] ?? $m['path'] ?? null; break; }
                    if (is_string($m) && preg_match('/\.(mp4|webm|ogg)$/i', $m)) { $videoUrl = asset('storage/'.$m); break; }
                }
            @endphp
            @if($videoUrl)
            <div>
                <h3 class="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#0297FE]">play_circle</span>
                    See it in Action
                </h3>
                <div class="aspect-video w-full max-w-3xl rounded-2xl overflow-hidden bg-slate-200">
                    <video class="w-full h-full object-cover" src="{{ $videoUrl }}" controls poster="{{ $primaryImageUrl }}"></video>
                </div>
            </div>
            @endif
            @endif

            <x-related-products :products="$youMayAlsoProducts ?? collect()" title="You may also like" :limit="5" />

            <x-see-it-in-action />

            <x-testimonials />

            {{-- Recently Viewed (dùng chung) --}}
            <x-recently-viewed
                :products="$recentlyViewedProducts ?? null"
                :exclude-id="$product->id"
                :limit="5"
                wrapper-class=""
            />
        </div>
    </div>
</div>

<style>
/* Hide scrollbar but keep scroll (gallery thumbnails) */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
/* Wishlist button SVG: outline mặc định, filled khi trong wishlist */
button.wishlist-btn .wishlist-icon { fill: none; stroke: currentColor; }
button.wishlist-btn.in-wishlist .wishlist-icon { fill: currentColor; stroke: none; }
button.wishlist-btn.in-wishlist { color: #0297FE; }
/* Add to Cart button */
.add-to-cart-btn {
    background-color: #0297FE;
    box-shadow: 0 10px 15px -3px rgba(2, 151, 254, 0.25), 0 4px 6px -2px rgba(2, 151, 254, 0.15);
}
.add-to-cart-btn:hover {
    opacity: 0.9;
    box-shadow: 0 20px 25px -5px rgba(2, 151, 254, 0.2), 0 10px 10px -5px rgba(2, 151, 254, 0.1);
}
.add-to-cart-btn:active {
    transform: scale(0.98);
}
.add-to-cart-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
.add-to-cart-btn .add-to-cart-icon {
    stroke: currentColor;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var mainImg = document.getElementById('product-main-image');
    var mainVideo = document.getElementById('product-main-video');
    var thumbs = document.querySelectorAll('.gallery-thumb');
    var totalItems = thumbs.length;
    var currentMainIndex = 0;

    function showMainMediaIndex(index) {
        if (index < 0 || index >= totalItems) return;
        currentMainIndex = index;
        var btn = thumbs[index];
        if (!btn) return;
        var type = btn.getAttribute('data-type');
        var url = btn.getAttribute('data-url');
        var poster = btn.getAttribute('data-poster') || '';
        if (type === 'video' && url) {
            if (mainVideo) {
                mainVideo.src = url;
                mainVideo.poster = poster || (mainImg ? mainImg.src : '');
                mainVideo.classList.remove('hidden');
                mainVideo.currentTime = 0;
                mainVideo.play().catch(function(){});
            }
            if (mainImg) mainImg.classList.add('hidden');
        } else if (url) {
            if (mainVideo) { mainVideo.pause(); mainVideo.classList.add('hidden'); mainVideo.removeAttribute('src'); }
            if (mainImg) { mainImg.src = url; mainImg.classList.remove('hidden'); }
        }
        thumbs.forEach(function(b) { b.classList.remove('border-[#0297FE]'); b.classList.add('border-slate-200'); });
        btn.classList.add('border-[#0297FE]'); btn.classList.remove('border-slate-200');
        btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    }

    thumbs.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var index = parseInt(this.getAttribute('data-index'), 10);
            showMainMediaIndex(index);
        });
    });

    var mainPrev = document.getElementById('main-media-prev');
    var mainNext = document.getElementById('main-media-next');
    if (mainPrev) mainPrev.addEventListener('click', function() {
        showMainMediaIndex((currentMainIndex - 1 + totalItems) % totalItems);
    });
    if (mainNext) mainNext.addEventListener('click', function() {
        showMainMediaIndex((currentMainIndex + 1) % totalItems);
    });

    // Thumbnails carousel (Prev/Next)
    var track = document.getElementById('gallery-track');
    var prevBtn = document.getElementById('gallery-prev');
    var nextBtn = document.getElementById('gallery-next');
    function updateGalleryNav() {
        if (!track || !prevBtn || !nextBtn) return;
        var maxScrollLeft = track.scrollWidth - track.clientWidth;
        var left = track.scrollLeft;
        prevBtn.classList.toggle('opacity-30', left <= 2);
        prevBtn.classList.toggle('pointer-events-none', left <= 2);
        nextBtn.classList.toggle('opacity-30', left >= maxScrollLeft - 2);
        nextBtn.classList.toggle('pointer-events-none', left >= maxScrollLeft - 2);
    }
    function scrollGallery(dir) {
        if (!track) return;
        var delta = Math.max(240, Math.floor(track.clientWidth * 0.85));
        track.scrollBy({ left: dir * delta, behavior: 'smooth' });
    }
    if (track && prevBtn && nextBtn) {
        prevBtn.addEventListener('click', function() { scrollGallery(-1); });
        nextBtn.addEventListener('click', function() { scrollGallery(1); });
        track.addEventListener('scroll', function() { updateGalleryNav(); }, { passive: true });
        window.addEventListener('resize', function() { updateGalleryNav(); });
        updateGalleryNav();
    }

    document.querySelectorAll('.size-badge').forEach(function(el) {
        el.addEventListener('click', function() {
            document.querySelectorAll('.size-badge').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            updateDisplayedPrice();
        });
    });
    document.querySelectorAll('.shape-button').forEach(function(el) {
        el.addEventListener('click', function() {
            document.querySelectorAll('.shape-button').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            updateDisplayedPrice();
        });
    });
    if (document.querySelector('.size-badge')) document.querySelector('.size-badge').classList.add('active');
    if (document.querySelector('.shape-button')) document.querySelector('.shape-button').classList.add('active');

    // Variants data for pricing (variant.price overrides base)
    var PRODUCT_VARIANTS = @json($variantsForJs);
    var CURRENCY_CODE = @json($currentCurrency);
    var CURRENCY_SYMBOL = @json($currencySymbol);

    var toastTimeout = null;
    function showToast(message, type) {
        type = type || 'error';
        var toast = document.getElementById('product-toast');
        var icon = document.getElementById('product-toast-icon');
        var msgEl = document.getElementById('product-toast-message');
        if (!toast || !msgEl) return;
        if (toastTimeout) clearTimeout(toastTimeout);
        msgEl.textContent = message;
        if (icon) {
            icon.textContent = type === 'success' ? 'check_circle' : 'error';
            icon.className = 'material-symbols-outlined text-2xl ' + (type === 'success' ? 'text-emerald-500' : 'text-[#0297FE]');
        }
        toast.classList.remove('hidden');
        toastTimeout = setTimeout(function() { toast.classList.add('hidden'); }, 5000);
    }
    document.getElementById('product-toast-close') && document.getElementById('product-toast-close').addEventListener('click', function() {
        document.getElementById('product-toast').classList.add('hidden');
        if (toastTimeout) clearTimeout(toastTimeout);
    });

    function formatMoney(amount) {
        try {
            if (typeof Intl !== 'undefined' && Intl.NumberFormat) {
                return new Intl.NumberFormat(undefined, { style: 'currency', currency: CURRENCY_CODE }).format(amount);
            }
        } catch (e) {}
        return CURRENCY_SYMBOL + (Math.round(amount * 100) / 100).toFixed(2);
    }

    function getSelectedSize() {
        var el = document.querySelector('.size-badge.active');
        return el ? (el.getAttribute('data-size') || '').trim() : '';
    }
    function getSelectedShape() {
        var el = document.querySelector('.shape-button.active');
        return el ? (el.getAttribute('data-shape') || '').trim() : '';
    }
    function pickAttr(attrs, keys) {
        if (!attrs) return null;
        for (var i = 0; i < keys.length; i++) {
            var k = keys[i];
            if (attrs[k] !== undefined && attrs[k] !== null && String(attrs[k]).trim() !== '') return String(attrs[k]).trim();
        }
        return null;
    }
    function getVariantPrice(selectedSize, selectedShape) {
        if (!selectedSize && !selectedShape) return null;
        for (var i = 0; i < PRODUCT_VARIANTS.length; i++) {
            var v = PRODUCT_VARIANTS[i];
            var attrs = v.attributes || {};
            var vSize = pickAttr(attrs, ['Size', 'size']);
            var vShape = pickAttr(attrs, ['Nail Shape', 'Shape', 'shape']);
            var sizeOk = selectedSize ? (vSize === selectedSize) : true;
            var shapeOk = selectedShape ? (vShape === selectedShape) : true;
            if (sizeOk && shapeOk) return v.price;
        }
        return null;
    }
    function getVariantListPrice(selectedSize, selectedShape) {
        if (!selectedSize && !selectedShape) return null;
        for (var i = 0; i < PRODUCT_VARIANTS.length; i++) {
            var v = PRODUCT_VARIANTS[i];
            var attrs = v.attributes || {};
            var vSize = pickAttr(attrs, ['Size', 'size']);
            var vShape = pickAttr(attrs, ['Nail Shape', 'Shape', 'shape']);
            var sizeOk = selectedSize ? (vSize === selectedSize) : true;
            var shapeOk = selectedShape ? (vShape === selectedShape) : true;
            if (sizeOk && shapeOk && v.list_price != null) return v.list_price;
        }
        return null;
    }
    function getCustomizationTotal() {
        var total = 0;
        document.querySelectorAll('.customization-row').forEach(function(row) {
            var price = parseFloat(row.getAttribute('data-price')) || 0;
            if (!price) return;
            var field = row.querySelector('.customization-field');
            if (!field) return;
            var value = field.type === 'checkbox' ? (field.checked ? '1' : '') : (field.value || '').trim();
            if (value) total += price;
        });
        return total;
    }
    function updateDisplayedPrice() {
        var priceEl = document.getElementById('product-price');
        if (!priceEl) return;
        var base = parseFloat(priceEl.getAttribute('data-base-price')) || 0;
        var selectedSize = getSelectedSize();
        var selectedShape = getSelectedShape();
        var variantPrice = getVariantPrice(selectedSize, selectedShape);
        var baseToUse = (variantPrice !== null && !isNaN(variantPrice)) ? parseFloat(variantPrice) : base;
        var customizationTotal = getCustomizationTotal();
        var finalPrice = baseToUse + customizationTotal;
        priceEl.textContent = formatMoney(finalPrice);

        var listPriceEl = document.getElementById('product-list-price');
        if (listPriceEl) {
            var variantListPrice = getVariantListPrice(selectedSize, selectedShape);
            var listPrice = (variantListPrice !== null && !isNaN(variantListPrice)) ? parseFloat(variantListPrice) : (parseFloat(priceEl.getAttribute('data-list-price')) || 0);
            if (listPrice > 0 && listPrice > finalPrice) {
                listPriceEl.textContent = formatMoney(listPrice);
                listPriceEl.classList.remove('hidden');
            } else {
                listPriceEl.classList.add('hidden');
            }
        }

        var note = document.getElementById('product-price-note');
        if (note) {
            if (customizationTotal > 0) {
                note.textContent = '(+' + formatMoney(customizationTotal) + ' custom)';
                note.classList.remove('hidden');
            } else {
                note.textContent = '';
                note.classList.add('hidden');
            }
        }
    }

    // Update price when customizing
    document.querySelectorAll('.customization-field').forEach(function(field) {
        field.addEventListener('input', updateDisplayedPrice);
        field.addEventListener('change', updateDisplayedPrice);
    });
    updateDisplayedPrice();

    // Quantity +/- 
    var qtyEl = document.getElementById('product-quantity');
    if (qtyEl) {
        document.getElementById('qty-minus') && document.getElementById('qty-minus').addEventListener('click', function() {
            var n = Math.max(1, parseInt(qtyEl.value, 10) - 1);
            qtyEl.value = n;
        });
        document.getElementById('qty-plus') && document.getElementById('qty-plus').addEventListener('click', function() {
            var n = Math.min(99, (parseInt(qtyEl.value, 10) || 1) + 1);
            qtyEl.value = n;
        });
        qtyEl.addEventListener('change', function() {
            var n = Math.min(99, Math.max(1, parseInt(qtyEl.value, 10) || 1));
            qtyEl.value = n;
        });
    }

    function getMatchingVariant(selectedSize, selectedShape) {
        for (var i = 0; i < PRODUCT_VARIANTS.length; i++) {
            var v = PRODUCT_VARIANTS[i];
            var attrs = v.attributes || {};
            var vSize = pickAttr(attrs, ['Size', 'size']);
            var vShape = pickAttr(attrs, ['Nail Shape', 'Shape', 'shape']);
            var sizeOk = selectedSize ? (vSize === selectedSize) : true;
            var shapeOk = selectedShape ? (vShape === selectedShape) : true;
            if (sizeOk && shapeOk) return v;
        }
        return null;
    }

    var addBtn = document.getElementById('add-to-cart-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            var customizations = [];
            var customizationTotal = 0;
            var valid = true;
            document.querySelectorAll('.customization-row').forEach(function(row) {
                var label = row.getAttribute('data-label');
                var price = parseFloat(row.getAttribute('data-price')) || 0;
                var field = row.querySelector('.customization-field');
                if (!field) return;
                var value = field.type === 'checkbox' ? (field.checked ? (field.value || 'Yes') : '') : (field.value || '').trim();
                if (field.getAttribute('data-required') && !value) {
                    if (valid) {
                        showToast('Please fill in: ' + label);
                        field.focus();
                        field.closest('.customization-row') && field.closest('.customization-row').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    valid = false;
                    return;
                }
                if (value) {
                    customizations.push({ label: label, value: value, price: price });
                    customizationTotal += price;
                }
            });
            if (!valid) return;

            // Cập nhật số tiền hiển thị cho đúng với giá sẽ gửi (base + customization)
            updateDisplayedPrice();

            var selectedSize = getSelectedSize();
            var selectedShape = getSelectedShape();
            if (PRODUCT_VARIANTS.length > 0) {
                var match = getMatchingVariant(selectedSize, selectedShape);
                if (!match) {
                    if (!selectedSize && document.getElementById('size-options')) {
                        showToast('Please select a size.');
                        document.querySelector('.size-badge') && document.querySelector('.size-badge').focus();
                        return;
                    }
                    if (!selectedShape && document.getElementById('shape-options')) {
                        showToast('Please select a nail shape.');
                        document.querySelector('.shape-button') && document.querySelector('.shape-button').focus();
                        return;
                    }
                    showToast('Please select size and nail shape.');
                    return;
                }
            }

            var basePrice = (function() {
                var base = parseFloat(document.getElementById('product-price')?.getAttribute('data-base-price') || '0') || 0;
                var vp = getVariantPrice(selectedSize, selectedShape);
                return (vp !== null && !isNaN(vp)) ? parseFloat(vp) : base;
            })();
            var unitPrice = basePrice + customizationTotal;
            var quantity = Math.min(99, Math.max(1, parseInt(document.getElementById('product-quantity')?.value, 10) || 1));
            if (document.getElementById('product-quantity')) document.getElementById('product-quantity').value = quantity;

            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('id', '{{ $product->id }}');
            formData.append('quantity', String(quantity));
            formData.append('price', String(unitPrice));

            var matchingVariant = PRODUCT_VARIANTS.length > 0 ? getMatchingVariant(selectedSize, selectedShape) : null;
            if (matchingVariant) {
                formData.append('selectedVariant[id]', String(matchingVariant.id));
                var attrs = matchingVariant.attributes || {};
                Object.keys(attrs).forEach(function(k) {
                    // Don't encode bracket keys; PHP will parse them as-is
                    formData.append('selectedVariant[attributes][' + k + ']', attrs[k]);
                });
            }

            customizations.forEach(function(c) {
                // Don't encode bracket keys; otherwise "Nail Shape" becomes "Nail%20Shape" and breaks merging
                formData.append('customizations[' + c.label + '][value]', c.value);
                formData.append('customizations[' + c.label + '][price]', String(c.price));
            });

            addBtn.disabled = true;
            fetch('{{ route("api.cart.add") }}', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    addBtn.disabled = false;
                    if (data.success) {
                        showToast('Added to cart.', 'success');
                        if (typeof window.promoPopupShow === 'function') {
                            setTimeout(function() { window.promoPopupShow('add_to_cart'); }, 400);
                        }
                        // Sync header badge
                        fetch('{{ route("api.cart.get") }}', { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function(res) { return res.json(); })
                            .then(function(cartData) {
                                if (cartData.success && cartData.cart_items) {
                                    var backendCart = cartData.cart_items.map(function(item) {
                                        return {
                                            id: item.product_id,
                                            name: item.product && item.product.name ? item.product.name : '',
                                            price: parseFloat(item.price) || 0,
                                            quantity: item.quantity,
                                            selectedVariant: item.selected_variant || null,
                                            customizations: item.customizations || null,
                                            addedAt: Date.now()
                                        };
                                    });
                                    try {
                                        localStorage.setItem('cart', JSON.stringify(backendCart));
                                        window.dispatchEvent(new CustomEvent('cartUpdated'));
                                    } catch (e) {}
                                }
                                window.dispatchEvent(new CustomEvent('cartDrawerOpen'));
                            })
                            .catch(function() { window.dispatchEvent(new CustomEvent('cartDrawerOpen')); });
                    } else {
                        showToast(data.message || 'Could not add to cart.');
                    }
                })
                .catch(function() {
                    addBtn.disabled = false;
                    showToast('Could not add to cart. Please try again.');
                });
        });
    }

    var shareBtn = document.getElementById('product-share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            var url = window.location.href;
            var title = (document.querySelector('h1') && document.querySelector('h1').textContent) ? document.querySelector('h1').textContent.trim() : @json($product->name);
            if (navigator.share) {
                navigator.share({ title: title, url: url }).catch(function() {});
            } else if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() { showToast('Link copied!', 'success'); }).catch(function() {});
            }
        });
    }

    var productId = {{ $product->id }};
    try {
        var stored = localStorage.getItem('recentlyViewedIds');
        var ids = stored ? JSON.parse(stored) : [];
        ids = ids.filter(function(id) { return id !== productId; });
        ids.unshift(productId);
        ids = ids.slice(0, 20);
        localStorage.setItem('recentlyViewedIds', JSON.stringify(ids));
    } catch (e) {}
});
</script>
@endsection
