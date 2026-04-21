@extends('layouts.app')

@section('title', $product->name)

@section('content')
@php
    $currentCurrency = currency();
    $currencySymbol = currency_symbol();
    $analyticsDebugOn = $analyticsDebugOn ?? (bool) request()->boolean('analytics_debug', false);
    // Gallery = ảnh product trước, sau đó + media từ template (ảnh + video)
    $galleryItems = [];
    $gallerySlot = 0;
    $galleryAlt = function ($raw) use ($product, &$gallerySlot) {
        return $product->altForMediaItem($raw, null, $gallerySlot++);
    };
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
                if ($url) {
                    $galleryItems[] = ['type' => 'image', 'url' => $url, 'alt' => $galleryAlt($m)];
                }
            } elseif (is_array($m)) {
                $u = $m['url'] ?? $m['path'] ?? reset($m);
                if (!$u) continue;
                if (isset($m['type']) && ($m['type'] ?? '') === 'video') {
                    $poster = isset($m['poster']) ? $normalizeUrl($m['poster']) : null;
                    $galleryItems[] = ['type' => 'video', 'url' => $normalizeUrl($u), 'poster' => $poster, 'alt' => $galleryAlt($m)];
                } else {
                    $w = isset($m['webp']) ? $normalizeUrl($m['webp']) : null;
                    $galleryItems[] = ['type' => 'image', 'url' => $normalizeUrl($u), 'webp' => $w, 'alt' => $galleryAlt($m)];
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
                    if ($url && !preg_match('/\.(mp4|webm|ogg|mov|avi)$/i', $m)) $galleryItems[] = ['type' => 'image', 'url' => $url, 'alt' => $galleryAlt($m)];
                } elseif (is_array($m)) {
                    if (isset($m['type']) && ($m['type'] ?? '') === 'video') {
                        $u = $m['url'] ?? $m['path'] ?? null;
                        if ($u) {
                            $poster = isset($m['poster']) ? $normalizeUrl($m['poster']) : null;
                            $galleryItems[] = ['type' => 'video', 'url' => $normalizeUrl($u), 'poster' => $poster, 'alt' => $galleryAlt($m)];
                        }
                    } else {
                        $u = $m['url'] ?? $m['path'] ?? reset($m);
                        if ($u) {
                            $w = isset($m['webp']) ? $normalizeUrl($m['webp']) : null;
                            $galleryItems[] = ['type' => 'image', 'url' => $normalizeUrl($u), 'webp' => $w, 'alt' => $galleryAlt($m)];
                        }
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
                if (empty($existingUrls[$url] ?? null)) { $galleryItems[] = ['type' => 'video', 'url' => $url, 'poster' => $galleryItems[0]['url'] ?? null, 'alt' => $galleryAlt($m)]; $existingUrls[$url] = true; }
            } else {
                if (empty($existingUrls[$url] ?? null)) { $galleryItems[] = ['type' => 'image', 'url' => $url, 'alt' => $galleryAlt($m)]; $existingUrls[$url] = true; }
            }
        } elseif (is_array($m)) {
            if (isset($m['type']) && ($m['type'] ?? '') === 'video') {
                $u = $m['url'] ?? $m['path'] ?? null;
                if ($u) {
                    $url = $normalizeUrl($u);
                    if (empty($existingUrls[$url] ?? null)) {
                        $poster = isset($m['poster']) ? $normalizeUrl($m['poster']) : ($galleryItems[0]['url'] ?? null);
                        $galleryItems[] = ['type' => 'video', 'url' => $url, 'poster' => $poster, 'alt' => $galleryAlt($m)];
                        $existingUrls[$url] = true;
                    }
                }
            } else {
                $u = $m['url'] ?? $m['path'] ?? reset($m);
                if ($u) {
                    $url = $normalizeUrl($u);
                    if (empty($existingUrls[$url] ?? null)) {
                        $w = isset($m['webp']) ? $normalizeUrl($m['webp']) : null;
                        $galleryItems[] = ['type' => 'image', 'url' => $url, 'webp' => $w, 'alt' => $galleryAlt($m)];
                        $existingUrls[$url] = true;
                    }
                }
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

    // Tên thuộc tính = key JSON trong cột attributes. Quét cả ProductVariant và TemplateVariant của template sản phẩm
    // (tránh rơi về mặc định "Nail Shape" khi product variant thiếu key nhưng template DB đã có đủ).
    // Đọc cột attributes an toàn (tránh nhầm với $model->attributes nội bộ của Eloquent).
    $readVariantAttributesJson = function ($model) {
        if (! is_object($model) || ! method_exists($model, 'getRawOriginal')) {
            return [];
        }
        $raw = $model->getRawOriginal('attributes');
        if ($raw === null || $raw === '') {
            $via = $model->getAttribute('attributes');

            return is_array($via) ? $via : [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    };
    $legacySecondAttrKeys = ['SHAPE & LENGTH', 'Shape & Length', 'shape & length', 'Shape & length', 'Nail Shape', 'Shape', 'shape'];
    $orderedVariantAttrKeys = [];
    $variantAttrValueSets = [];
    $variantAttrKeyCanonByLower = [];
    $accumulateVariantAttributeKeys = function ($variants) use (&$orderedVariantAttrKeys, &$variantAttrValueSets, &$variantAttrKeyCanonByLower, $readVariantAttributesJson) {
        foreach ($variants as $v) {
            $attrs = $readVariantAttributesJson($v);
            if ($attrs === []) {
                continue;
            }
            foreach ($attrs as $k => $val) {
                if ($k === '' || $k === null) {
                    continue;
                }
                $lower = mb_strtolower((string) $k);
                if (! isset($variantAttrKeyCanonByLower[$lower])) {
                    $variantAttrKeyCanonByLower[$lower] = $k;
                    $orderedVariantAttrKeys[] = $k;
                    $variantAttrValueSets[$k] = $variantAttrValueSets[$k] ?? [];
                }
                $canonical = $variantAttrKeyCanonByLower[$lower];
                if ($val !== null && $val !== '') {
                    $variantAttrValueSets[$canonical] = $variantAttrValueSets[$canonical] ?? [];
                    $variantAttrValueSets[$canonical][(string) $val] = true;
                }
            }
        }
    };
    $accumulateVariantAttributeKeys($product->variants);
    if ($product->template) {
        if (! $product->template->relationLoaded('variants')) {
            $product->template->load('variants');
        }
        if ($product->template->variants && $product->template->variants->isNotEmpty()) {
            $accumulateVariantAttributeKeys($product->template->variants);
        }
    }
    $keysWithMultipleValues = [];
    foreach ($orderedVariantAttrKeys as $k) {
        if (count($variantAttrValueSets[$k] ?? []) >= 2) {
            $keysWithMultipleValues[] = $k;
        }
    }
    $pickedVariantAttrKeys = [];
    foreach ($orderedVariantAttrKeys as $k) {
        if (count($pickedVariantAttrKeys) >= 2) {
            break;
        }
        if (in_array($k, $keysWithMultipleValues, true)) {
            $pickedVariantAttrKeys[] = $k;
        }
    }
    foreach ($orderedVariantAttrKeys as $k) {
        if (count($pickedVariantAttrKeys) >= 2) {
            break;
        }
        if (! in_array($k, $pickedVariantAttrKeys, true)) {
            $pickedVariantAttrKeys[] = $k;
        }
    }
    if ($orderedVariantAttrKeys === []) {
        $variantAttrKeyFirst = null;
        $variantAttrKeySecond = null;
        $variantHasSecondPicker = false;
    } else {
        $variantAttrKeyFirst = $pickedVariantAttrKeys[0] ?? null;
        $variantAttrKeySecond = $pickedVariantAttrKeys[1] ?? null;
        $variantHasSecondPicker = $variantAttrKeySecond !== null;
    }
    $pickVariantAttrValue = function ($a, ?string $primaryKey, array $legacyKeys) {
        if (! is_array($a)) {
            return null;
        }
        if ($primaryKey !== null && $primaryKey !== '' && array_key_exists($primaryKey, $a) && $a[$primaryKey] !== null && $a[$primaryKey] !== '') {
            return $a[$primaryKey];
        }
        $wantLower = ($primaryKey !== null && $primaryKey !== '') ? mb_strtolower((string) $primaryKey) : null;
        if ($wantLower !== null) {
            foreach ($a as $ak => $av) {
                if ($av === null || $av === '') {
                    continue;
                }
                if (mb_strtolower((string) $ak) === $wantLower) {
                    return $av;
                }
            }
        }
        foreach ($legacyKeys as $lk) {
            if (isset($a[$lk]) && $a[$lk] !== null && $a[$lk] !== '') {
                return $a[$lk];
            }
        }
        foreach ($legacyKeys as $lk) {
            $ll = mb_strtolower((string) $lk);
            foreach ($a as $ak => $av) {
                if ($av === null || $av === '') {
                    continue;
                }
                if (mb_strtolower((string) $ak) === $ll) {
                    return $av;
                }
            }
        }

        return null;
    };
    $sizes = collect();
    if ($variantAttrKeyFirst) {
        $sizes = $product->variants->map(function ($v) use ($readVariantAttributesJson, $pickVariantAttrValue, $variantAttrKeyFirst) {
            return $pickVariantAttrValue($readVariantAttributesJson($v), $variantAttrKeyFirst, ['Size', 'size']);
        })->filter()->unique()->values();
        if ($sizes->isEmpty() && $product->template && $product->template->variants && $product->template->variants->isNotEmpty()) {
            $sizes = $product->template->variants->map(function ($v) use ($readVariantAttributesJson, $pickVariantAttrValue, $variantAttrKeyFirst) {
                return $pickVariantAttrValue($readVariantAttributesJson($v), $variantAttrKeyFirst, ['Size', 'size']);
            })->filter()->unique()->values();
        }
    }
    $shapes = collect();
    if ($variantHasSecondPicker && $variantAttrKeySecond) {
        $shapes = $product->variants->map(function ($v) use ($readVariantAttributesJson, $pickVariantAttrValue, $variantAttrKeySecond, $legacySecondAttrKeys) {
            return $pickVariantAttrValue($readVariantAttributesJson($v), $variantAttrKeySecond, $legacySecondAttrKeys);
        })->filter()->unique()->values();
        if ($shapes->isEmpty() && $product->template && $product->template->variants && $product->template->variants->isNotEmpty()) {
            $shapes = $product->template->variants->map(function ($v) use ($readVariantAttributesJson, $pickVariantAttrValue, $variantAttrKeySecond, $legacySecondAttrKeys) {
                return $pickVariantAttrValue($readVariantAttributesJson($v), $variantAttrKeySecond, $legacySecondAttrKeys);
            })->filter()->unique()->values();
        }
    }
    $hasVariantSizeOptions = $sizes->isNotEmpty();
    $hasVariantShapeOptions = $variantHasSecondPicker && $shapes->isNotEmpty();
    $productPrice = (float) ($product->price ?? $product->base_price ?? 0);
    $productListPrice = (float) ($product->list_price ?? $product->template->list_price ?? 0);
    $showProductListPrice = $productListPrice > 0 && $productListPrice > $productPrice;
    $variantsForJs = $product->variants->map(function ($v) use ($readVariantAttributesJson) {
        return [
            'id' => $v->id,
            'price' => $v->price !== null ? (float) $v->price : null,
            'list_price' => $v->list_price !== null ? (float) $v->list_price : null,
            'attributes' => $readVariantAttributesJson($v),
        ];
    })->values();

    // Shipping snippet (UI giống mẫu). Country lấy theo tool detect (header/session/currency/domain).
    $location = app(\App\Services\CustomerLocationService::class);
    $shipCountryCode = $location->detectCountryCode(request(), 'VN');
    $shipCountryName = $location->getCountryName($shipCountryCode);
    $shipsFrom = 'United States';
    $deliveryMinDays = 11;
    $deliveryMaxDays = 20;
    try {
        $rateForDelivery = app(\App\Services\ShippingCalculator::class)->findRateForProduct($product, $shipCountryCode, 1, $productPrice);
        if ($rateForDelivery && ($rateForDelivery->delivery_min_days !== null || $rateForDelivery->delivery_max_days !== null)) {
            $min = $rateForDelivery->delivery_min_days;
            $max = $rateForDelivery->delivery_max_days;
            if ($min === null) {
                $min = $max;
            }
            if ($max === null) {
                $max = $min;
            }
            if ($min !== null && $max !== null) {
                $deliveryMinDays = max(0, (int) $min);
                $deliveryMaxDays = max($deliveryMinDays, (int) $max);
            }
        }
    } catch (\Throwable $e) {
        // giữ mặc định 11–20
    }
    $deliveryStart = now()->startOfDay()->addDays($deliveryMinDays);
    $deliveryEnd = now()->startOfDay()->addDays($deliveryMaxDays);
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

    // GTM / Pixel: category cho ecommerce items (ưu tiên category, fallback collection)
    $gtagPrimaryCategory = null;
    $productCategoriesForGtm = $product->categories ?? collect();
    if (!($productCategoriesForGtm instanceof \Illuminate\Support\Collection)) {
        $productCategoriesForGtm = collect($productCategoriesForGtm);
    }
    if ($productCategoriesForGtm->isNotEmpty()) {
        $gtagPrimaryCategory = optional($productCategoriesForGtm->first())->name;
    }
    if (!$gtagPrimaryCategory && $product->collections && $product->collections->isNotEmpty()) {
        $gtagPrimaryCategory = $product->collections->first()->name;
    }

    // Combo (volume) discount rules for preview on this page.
    // Real discount application happens in cart based on TOTAL quantity in cart.
    $bulkDiscountRules = [];
    $bulkDiscountRaw = \App\Support\Settings::get('pricing.bulk_discounts');
    if (is_string($bulkDiscountRaw) && $bulkDiscountRaw !== '') {
        $decoded = json_decode($bulkDiscountRaw, true);
        $bulkDiscountRules = is_array($decoded) ? $decoded : [];
    } elseif (is_array($bulkDiscountRaw)) {
        $bulkDiscountRules = $bulkDiscountRaw;
    }

    $volumeDiscountPreviewPercent = \App\Models\Cart::getComboDiscountPercentForQty(1);
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
                                    <video id="product-main-video" class="w-full h-full object-cover" controls playsinline preload="metadata" poster="{{ $mainPoster }}" src="{{ $galleryItems[0]['url'] }}"></video>
                                    <img alt="{{ $galleryItems[0]['alt'] ?? $product->name }}" class="w-full h-full object-cover hidden" id="product-main-image" src="">
                                @else
                                    <video id="product-main-video" class="w-full h-full object-cover hidden" controls playsinline></video>
                                    <img alt="{{ $galleryItems[0]['alt'] ?? $product->name }}" class="w-full h-full object-cover" id="product-main-image" src="{{ !empty($galleryItems[0]['webp']) ? $galleryItems[0]['webp'] : $galleryItems[0]['url'] }}">
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
                                            data-webp="{{ $item['webp'] ?? '' }}"
                                            data-poster="{{ $item['poster'] ?? '' }}"
                                            data-alt="{{ $item['alt'] ?? $product->name }}"
                                            aria-label="{{ $item['type'] === 'video' ? 'Xem video ' : 'Xem ảnh ' }}{{ $index + 1 }}">
                                        @if($item['type'] === 'video')
                                            @php
                                                $thumbPoster = $item['poster'] ?? null;
                                                if ($thumbPoster && preg_match('/\.(mp4|webm|ogg|mov|avi)$/i', $thumbPoster)) { $thumbPoster = null; }
                                                $thumbPoster = $thumbPoster ?: $fallbackPosterUrl;
                                            @endphp
                                            <img class="w-full h-full object-cover" src="{{ $thumbPoster }}" alt="{{ $item['alt'] ?? $product->name }}">
                                            <span class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-black/40 transition-colors">
                                                <span class="material-symbols-outlined text-white text-3xl drop-shadow">play_circle</span>
                                            </span>
                                        @else
                                            <img class="w-full h-full object-cover" src="{{ !empty($item['webp']) ? $item['webp'] : $item['url'] }}" alt="{{ $item['alt'] ?? $product->name }}">
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
                    @php
                        $tiersUi = collect($bulkDiscountRules)->filter(function($r) {
                            return !empty($r['min_qty']) && !empty($r['percent']);
                        })->sortBy('min_qty')->values()->take(3)->all();
                    @endphp
                    @if(!empty($tiersUi))
                        <div class="mt-2 bg-[#0297FE]/10 border border-[#0297FE]/20 rounded-2xl p-3">
                            <div class="text-center text-xs font-extrabold text-[#0297FE]">
                                Buy More, Save More
                            </div>
                            <div class="mt-2 grid grid-cols-3 gap-1.5">
                                @foreach($tiersUi as $tier)
                                    @php
                                        $minQty = (int) ($tier['min_qty'] ?? 0);
                                        $percent = (float) ($tier['percent'] ?? 0);
                                    @endphp
                                    @if($minQty > 0 && $percent > 0)
                                        <div class="bg-white/80 border border-[#0297FE]/20 rounded-xl px-2 py-2 flex flex-col items-center justify-center">
                                            <div class="text-[10px] font-extrabold tracking-widest text-[#0297FE] uppercase">
                                                BUY {{ $minQty }}
                                            </div>
                                            <div class="text-sm font-extrabold text-slate-900">
                                                {{ number_format($percent, 0) }}% OFF
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <p id="volume-discount-dynamic-message" class="mt-2 text-center text-xs font-semibold text-[#0297FE] hidden"></p>
                        </div>
                    @endif
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
                        <div class="space-y-1.5 customization-row" data-customization-index="{{ $index }}" data-field-type="{{ $type }}" data-label="{{ e($label) }}" data-price="{{ $price }}">
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
                            @elseif($type === 'file')
                                <input type="hidden" class="customization-field customization-file-value" name="customization[{{ $index }}]" value="" autocomplete="off" @if($required) data-required="1" @endif>
                                <input type="file" class="customization-file-input sr-only" id="customization-file-input-{{ $index }}" accept="image/*,video/*,.pdf,.doc,.docx,.txt">
                                <div class="flex flex-col gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" class="customization-file-pick inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-800 touch-manipulation">
                                            <span class="material-symbols-outlined text-lg" aria-hidden="true">upload_file</span>
                                            Choose file
                                        </button>
                                        <span class="customization-file-status text-xs text-slate-600 truncate max-w-[min(100%,240px)]" aria-live="polite"></span>
                                        <button type="button" class="customization-file-clear hidden text-xs font-semibold text-red-600 hover:underline touch-manipulation">Remove</button>
                                    </div>
                                    <p class="text-xs text-slate-500">{{ $placeholder ?: 'JPG, PNG, PDF, video… Max 10MB per file.' }}</p>
                                    <p class="customization-file-error text-xs text-red-600 hidden"></p>
                                </div>
                            @else
                                <input type="text" class="customization-field w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE]" name="customization[{{ $index }}]" placeholder="{{ $placeholder }}" @if($required) data-required="1" @endif>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Thuộc tính biến thể: chỉ hiển thị khi có key + giá trị lấy từ DB (product/template variants) --}}
                    @if($variantAttrKeyFirst && $sizes->isNotEmpty())
                    <div class="space-y-2">
                        <div class="flex items-end justify-between gap-3">
                            <label class="text-xs font-semibold text-slate-700 uppercase tracking-wide">{{ $variantAttrKeyFirst }}</label>
                            <button type="button"
                                    id="size-guide-open"
                                    class="group inline-flex items-center gap-1 shrink-0 bg-transparent border-0 p-0 cursor-pointer text-[11px] sm:text-xs font-medium text-[#0195FE] underline underline-offset-2 decoration-[#0195FE]/80 hover:opacity-85 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0195FE]/40 focus-visible:ring-offset-2 rounded-sm touch-manipulation"
                                    aria-haspopup="dialog"
                                    aria-controls="size-guide-modal"
                                    aria-expanded="false">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0 text-[#0195FE]" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <rect x="1.5" y="5" width="13" height="6" rx="0.5" stroke="currentColor" stroke-width="1.25"/>
                                    <path d="M3.5 5v6M5.5 5v4M7.5 5v6M9.5 5v4M11.5 5v6" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
                                </svg>
                                <span>Size guide</span>
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2" id="size-options">
                            @foreach($sizes as $size)
                            <div class="size-badge" data-size="{{ $size }}" role="button" tabindex="0">
                                <span class="size-label">{{ $size }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($variantHasSecondPicker && $variantAttrKeySecond && $shapes->isNotEmpty())
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-slate-700 uppercase tracking-wide">{{ $variantAttrKeySecond }}</label>
                        <div class="flex flex-wrap gap-2" id="shape-options">
                            @foreach($shapes as $shape)
                            <div class="shape-button shape-button-text" data-shape="{{ $shape }}" role="button" tabindex="0">
                                <span class="text-xs font-medium text-slate-900">{{ $shape }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Quantity --}}
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-slate-700 uppercase tracking-wide" for="product-quantity">Quantity</label>
                        <div class="flex items-center gap-2 w-fit">
                            <button type="button" id="qty-minus" class="w-10 h-10 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Decrease quantity">
                                <span class="material-symbols-outlined text-xl">remove</span>
                            </button>
                            <input type="number" id="product-quantity" name="quantity" min="1" max="99" value="1" class="w-16 text-center py-2 text-sm font-medium border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE]">
                            <button type="button" id="qty-plus" class="w-10 h-10 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Increase quantity">
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

        @if($reviewsCount > 0)
        <div id="customer-reviews" class="mt-8 sm:mt-12 space-y-4 sm:space-y-6 min-w-0">
            @php
                $displayReviews = $product->approvedReviews ?? collect();
                $reviewPhotos = $displayReviews->filter(function ($r) {
                    return !empty($r->image_url_for_display);
                })->values();
            @endphp
            <div class="rounded-2xl bg-white p-4 sm:p-6 shadow-sm border border-slate-100 min-w-0 overflow-hidden">
                <div class="mb-4 sm:mb-5">
                    <h3 class="text-lg sm:text-2xl font-extrabold text-slate-900 leading-snug break-words">Reviews for this item ({{ $reviewsCount }})</h3>
                </div>

                <div class="flex flex-wrap items-center gap-3 sm:gap-4 mb-5 sm:mb-6">
                    <div class="flex items-center gap-0.5 sm:gap-1 text-amber-400 shrink-0">
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
                    <p class="text-2xl sm:text-3xl font-light text-slate-900 leading-none tabular-nums">
                        {{ number_format($averageRating, 1) }}<span class="text-base sm:text-lg text-slate-500">/5</span>
                    </p>
                    <span class="text-xs sm:text-sm text-slate-500 w-full sm:w-auto basis-full sm:basis-auto">({{ $reviewsCount }} {{ $reviewsCount === 1 ? 'review' : 'reviews' }})</span>
                </div>

                @if($displayReviews->isNotEmpty())
                    {{-- Mobile: kéo ngang để xem từng review; sm+: danh sách dọc --}}
                    <div
                        class="flex flex-row sm:flex-col gap-4 sm:gap-0 overflow-x-auto sm:overflow-visible overscroll-x-contain snap-x snap-mandatory sm:snap-none scroll-smooth pb-2 sm:pb-0 -mx-4 px-4 sm:mx-0 sm:px-0 sm:divide-y sm:divide-slate-200 touch-pan-x sm:touch-auto no-scrollbar"
                        role="region"
                        aria-label="Danh sách đánh giá — vuốt ngang trên điện thoại"
                    >
                        @foreach($displayReviews as $review)
                            <article class="snap-start shrink-0 w-[min(88vw,340px)] sm:w-full sm:shrink rounded-xl border border-slate-100 bg-slate-50/90 p-4 shadow-sm sm:rounded-none sm:border-0 sm:bg-transparent sm:shadow-none sm:p-0 sm:py-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4 min-w-0">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-1">
                                            <div class="flex items-center text-amber-400 shrink-0">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <span class="material-symbols-outlined text-base {{ $i <= (int) $review->rating ? 'fill-current' : 'text-slate-200' }}">star</span>
                                                @endfor
                                            </div>
                                            <span class="text-xs font-semibold text-slate-500">{{ (int) $review->rating }}</span>
                                            @if($review->is_verified_purchase)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 max-w-full">
                                                    <span class="material-symbols-outlined text-xs shrink-0">verified</span>
                                                    <span class="whitespace-nowrap">This item</span>
                                                </span>
                                            @endif
                                        </div>
                                        @if(!empty($review->title))
                                            <p class="text-sm font-semibold text-slate-900 break-words">{{ $review->title }}</p>
                                        @endif
                                        @if(!empty($review->review_text))
                                            <p class="mt-1 text-sm text-slate-700 leading-relaxed break-words">{{ $review->review_text }}</p>
                                        @endif
                                    </div>

                                    <div class="shrink-0 flex flex-row sm:flex-col items-center sm:items-end gap-3 sm:gap-0 sm:text-right w-full sm:w-auto sm:min-w-[120px] border-t border-slate-100 pt-3 sm:border-t-0 sm:pt-0">
                                        <div class="min-w-0 flex-1 sm:flex-none text-left sm:text-right">
                                            <p class="text-xs font-semibold text-slate-900 break-words">{{ $review->display_name }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">{{ $review->created_at?->format('M d, Y') }}</p>
                                        </div>
                                        @if(!empty($review->image_url_for_display))
                                            <img
                                                src="{{ $review->image_url_for_display }}"
                                                alt="Review image by {{ $review->display_name }}"
                                                class="w-14 h-14 sm:mt-2 sm:ml-auto rounded-md border border-slate-200 object-cover cursor-pointer js-review-photo-trigger hover:ring-2 hover:ring-[#0297FE]/40 transition-shadow shrink-0"
                                                loading="lazy"
                                                onerror="this.style.display='none';"
                                            >
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    @if($displayReviews->count() > 1)
                    <p class="sm:hidden text-center text-[11px] text-slate-400 mt-1 mb-0">← Vuốt để xem thêm →</p>
                    @endif

                    @if($reviewsCount > $displayReviews->count())
                        <div class="pt-5 flex justify-center">
                            <button type="button" class="w-full sm:w-auto px-5 py-2.5 sm:py-2 text-sm font-semibold rounded-full border border-slate-300 text-slate-700 hover:bg-slate-50 transition touch-manipulation">
                                View all reviews for this item
                            </button>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-slate-500">There are no reviews for this product yet.</p>
                @endif

                @if($reviewPhotos->isNotEmpty())
                    <div class="mt-6 sm:mt-8">
                        <h4 class="text-sm font-bold text-slate-900 mb-3">Photos from reviews</h4>
                        {{-- Mobile: cuộn ngang; md+: lưới --}}
                        <div class="flex md:grid md:grid-cols-3 lg:grid-cols-5 gap-2 sm:gap-3 overflow-x-auto md:overflow-visible snap-x snap-mandatory md:snap-none scroll-smooth pb-2 md:pb-0 -mx-4 px-4 md:mx-0 md:px-0 no-scrollbar touch-pan-x md:touch-auto">
                            @foreach($reviewPhotos->take(10) as $photoReview)
                                <img
                                    src="{{ $photoReview->image_url_for_display }}"
                                    alt="Photo from review by {{ $photoReview->display_name }}"
                                    class="shrink-0 snap-start w-[min(42vw,160px)] aspect-square md:w-full rounded-lg border border-slate-200 object-cover cursor-pointer js-review-photo-trigger hover:ring-2 hover:ring-[#0297FE]/40 transition-shadow"
                                    loading="lazy"
                                    onerror="this.style.display='none';"
                                >
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-2xl bg-white p-4 sm:p-6 shadow-sm border border-slate-100 min-w-0 overflow-hidden">
                <h3 class="text-base sm:text-lg font-extrabold text-slate-900 mb-4">Write a Review</h3>

                @if(session('success'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 text-sm font-medium">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm font-medium">
                        {{ session('error') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
                        <ul class="list-disc ml-5 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @guest
                    <p class="text-sm text-slate-600">
                        Please <a href="{{ route('login') }}" class="text-[#0297FE] font-semibold hover:underline">log in</a> to submit a review.
                    </p>
                @else
                    @if($canSubmitReview ?? false)
                        <form action="{{ route('products.reviews.store', $product->slug) }}#customer-reviews" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Rating</label>
                                <div class="flex items-stretch gap-1.5 sm:gap-2 flex-wrap">
                                    @for($i = 1; $i <= 5; $i++)
                                        <label class="inline-flex flex-1 min-w-[2.75rem] sm:flex-initial sm:min-w-0 justify-center items-center gap-1 px-2 py-2 sm:px-3 sm:py-2 rounded-lg border border-slate-200 hover:border-[#0297FE]/50 cursor-pointer touch-manipulation">
                                            <input type="radio" name="rating" value="{{ $i }}" class="accent-[#0297FE] shrink-0" {{ (int) old('rating', 5) === $i ? 'checked' : '' }}>
                                            <span class="text-xs sm:text-sm font-medium text-slate-700 whitespace-nowrap">{{ $i }}★</span>
                                        </label>
                                    @endfor
                                </div>
                            </div>
                            <div>
                                <label for="review-title" class="block text-sm font-semibold text-slate-700 mb-1">Title (optional)</label>
                                <input id="review-title" type="text" name="title" value="{{ old('title') }}" maxlength="120"
                                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE]"
                                       placeholder="Example: Beautiful set and long-lasting">
                            </div>
                            <div>
                                <label for="review-text" class="block text-sm font-semibold text-slate-700 mb-1">Your review</label>
                                <textarea id="review-text" name="review_text" rows="4" required maxlength="2000"
                                          class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE]"
                                          placeholder="Share your experience with this product...">{{ old('review_text') }}</textarea>
                            </div>
                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 sm:py-2.5 rounded-lg bg-[#0297FE] text-white text-sm font-bold hover:opacity-90 transition touch-manipulation">
                                Submit Review
                            </button>
                        </form>
                    @elseif($userExistingReview ?? false)
                        <p class="text-sm text-slate-600">You have already submitted a review for this product. Thank you!</p>
                    @else
                        <p class="text-sm text-slate-600">You can submit a review after completing an order that includes this product.</p>
                    @endif
                @endguest
            </div>
        </div>
        @endif

        @if($product->shop && isset($shopSpotlightReviews) && $shopSpotlightReviews->isNotEmpty())
        @php
            $spotlightPerSlide = 3;
            $spotlightSlides = $shopSpotlightReviews->chunk($spotlightPerSlide)->values();
            $spotlightSlideTotal = $spotlightSlides->count();
        @endphp
        <div class="mt-8 sm:mt-12 rounded-2xl bg-white p-4 sm:p-6 shadow-sm border border-slate-100 min-w-0 overflow-hidden">
            <div class="mb-4 sm:mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between sm:gap-2">
                <div class="min-w-0">
                    <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 leading-snug break-words">Featured reviews from {{ $product->shop->shop_name }}</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">What customers say about other products from this shop</p>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 shrink-0 justify-start sm:justify-end">
                    <a href="{{ route('shops.reviews', $product->shop->shop_slug ?? $product->shop->id) }}" class="text-sm font-semibold text-[#0297FE] hover:underline touch-manipulation">All shop reviews</a>
                    <a href="{{ route('shops.show', $product->shop->shop_slug ?? $product->shop->id) }}" class="text-sm font-semibold text-slate-600 hover:text-[#0297FE] hover:underline touch-manipulation">Visit shop</a>
                </div>
            </div>
            {{-- Mobile & tablet: cuộn ngang toàn bộ review shop --}}
            <div class="lg:hidden -mx-4 px-4 pb-1" role="region" aria-label="Đánh giá nổi bật từ shop — vuốt ngang">
                <div class="flex gap-3 overflow-x-auto overscroll-x-contain snap-x snap-mandatory scroll-smooth pb-2 no-scrollbar touch-pan-x">
                    @foreach($shopSpotlightReviews as $sReview)
                    <article class="snap-start shrink-0 w-[min(88vw,340px)] max-w-[340px] rounded-xl border border-slate-100 bg-slate-50/80 p-4 flex flex-col h-full min-h-0 min-w-0">
                        <div class="flex flex-col gap-2 mb-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-center text-amber-400 min-w-0 shrink-0">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="material-symbols-outlined text-base {{ $i <= (int) $sReview->rating ? 'fill-current' : 'text-slate-200' }}">star</span>
                                @endfor
                            </div>
                            @if($sReview->product)
                                <a href="{{ route('products.show', $sReview->product->slug) }}" class="text-xs font-semibold text-[#0297FE] hover:underline line-clamp-2 break-words w-full" title="{{ $sReview->product->name }}">{{ Str::limit($sReview->product->name, 42) }}</a>
                            @endif
                        </div>
                        @if(!empty($sReview->title))
                            <p class="text-sm font-semibold text-slate-900 line-clamp-2">{{ $sReview->title }}</p>
                        @endif
                        @if(!empty($sReview->review_text))
                            <p class="mt-1 text-sm text-slate-700 leading-relaxed line-clamp-4">{{ $sReview->review_text }}</p>
                        @endif
                        <div class="mt-auto pt-3 flex flex-wrap items-center justify-between gap-x-2 gap-y-1 text-xs text-slate-500">
                            <span class="font-semibold text-slate-800 truncate min-w-0 max-w-[65%]">{{ $sReview->display_name }}</span>
                            <span class="shrink-0">{{ $sReview->created_at?->format('M j, Y') }}</span>
                        </div>
                        @if(!empty($sReview->image_url_for_display))
                            <div class="mt-3">
                                <img src="{{ $sReview->image_url_for_display }}" alt="" class="w-full max-h-32 object-cover rounded-lg border border-slate-200 cursor-pointer js-review-photo-trigger hover:ring-2 hover:ring-[#0297FE]/40 transition-shadow" loading="lazy" onerror="this.style.display='none';">
                            </div>
                        @endif
                    </article>
                    @endforeach
                </div>
                @if($shopSpotlightReviews->count() > 1)
                <p class="text-center text-[11px] text-slate-400 mt-1">← Vuốt để xem thêm →</p>
                @endif
            </div>
            <div class="relative min-w-0 hidden lg:block" id="shop-spotlight-carousel">
                <div class="overflow-hidden">
                    <div class="shop-spotlight-slides flex transition-transform duration-300 ease-out" style="width: {{ $spotlightSlideTotal * 100 }}%">
                        @foreach($spotlightSlides as $slideReviews)
                        <div class="shop-spotlight-slide flex-shrink-0 px-0 sm:px-2" style="width: {{ 100 / $spotlightSlideTotal }}%">
                            <div class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach($slideReviews as $sReview)
                                <article class="rounded-xl border border-slate-100 bg-slate-50/80 p-3 sm:p-4 flex flex-col h-full min-h-0 min-w-0">
                                    <div class="flex flex-col gap-2 mb-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex items-center text-amber-400 min-w-0 shrink-0">
                                            @for($i = 1; $i <= 5; $i++)
                                                <span class="material-symbols-outlined text-base {{ $i <= (int) $sReview->rating ? 'fill-current' : 'text-slate-200' }}">star</span>
                                            @endfor
                                        </div>
                                        @if($sReview->product)
                                            <a href="{{ route('products.show', $sReview->product->slug) }}" class="text-xs font-semibold text-[#0297FE] hover:underline line-clamp-2 sm:truncate sm:max-w-[55%] sm:text-right w-full sm:w-auto break-words" title="{{ $sReview->product->name }}">{{ Str::limit($sReview->product->name, 42) }}</a>
                                        @endif
                                    </div>
                                    @if(!empty($sReview->title))
                                        <p class="text-sm font-semibold text-slate-900 line-clamp-2">{{ $sReview->title }}</p>
                                    @endif
                                    @if(!empty($sReview->review_text))
                                        <p class="mt-1 text-sm text-slate-700 leading-relaxed line-clamp-4">{{ $sReview->review_text }}</p>
                                    @endif
                                    <div class="mt-auto pt-3 flex flex-wrap items-center justify-between gap-x-2 gap-y-1 text-xs text-slate-500">
                                        <span class="font-semibold text-slate-800 truncate min-w-0 max-w-[65%] sm:max-w-none">{{ $sReview->display_name }}</span>
                                        <span class="shrink-0">{{ $sReview->created_at?->format('M j, Y') }}</span>
                                    </div>
                                    @if(!empty($sReview->image_url_for_display))
                                        <div class="mt-3">
                                            <img src="{{ $sReview->image_url_for_display }}" alt="" class="w-full max-h-32 object-cover rounded-lg border border-slate-200 cursor-pointer js-review-photo-trigger hover:ring-2 hover:ring-[#0297FE]/40 transition-shadow" loading="lazy" onerror="this.style.display='none';">
                                        </div>
                                    @endif
                                </article>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @if($spotlightSlideTotal > 1)
                <div class="flex justify-center items-center gap-3 sm:gap-4 mt-6 sm:mt-8 px-1">
                    <button type="button" class="shop-spotlight-prev touch-manipulation inline-flex items-center justify-center w-11 h-11 sm:w-12 sm:h-12 rounded-full border-2 border-slate-300 text-slate-600 hover:bg-slate-100 hover:border-slate-400 transition-colors shrink-0" aria-label="Previous reviews">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <span class="text-sm font-medium text-slate-600 shop-spotlight-pagination">1 / {{ $spotlightSlideTotal }}</span>
                    <button type="button" class="shop-spotlight-next touch-manipulation inline-flex items-center justify-center w-11 h-11 sm:w-12 sm:h-12 rounded-full border-2 border-slate-300 text-slate-600 hover:bg-slate-100 hover:border-slate-400 transition-colors shrink-0" aria-label="Next reviews">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
                <script>
                (function() {
                    var root = document.getElementById('shop-spotlight-carousel');
                    if (!root) return;
                    var slidesEl = root.querySelector('.shop-spotlight-slides');
                    var total = {{ $spotlightSlideTotal }};
                    var current = 0;
                    var paginationEl = root.querySelector('.shop-spotlight-pagination');
                    function go(idx) {
                        current = Math.max(0, Math.min(idx, total - 1));
                        if (slidesEl) slidesEl.style.transform = 'translateX(-' + (current * (100 / total)) + '%)';
                        if (paginationEl) paginationEl.textContent = (current + 1) + ' / ' + total;
                    }
                    var prev = root.querySelector('.shop-spotlight-prev');
                    var next = root.querySelector('.shop-spotlight-next');
                    if (prev) prev.addEventListener('click', function() { go(current === 0 ? total - 1 : current - 1); });
                    if (next) next.addEventListener('click', function() { go(current === total - 1 ? 0 : current + 1); });
                })();
                </script>
                @endif
            </div>
        </div>
        @endif

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
                    <video class="w-full h-full object-cover" src="{{ $videoUrl }}" controls preload="none" poster="{{ $primaryImageUrl }}"></video>
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

{{-- Size guide modal --}}
<div id="size-guide-modal" class="size-guide-modal-root fixed inset-0 z-[120] hidden flex items-center justify-center p-3 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="size-guide-modal-title">
    <div class="absolute inset-0 size-guide-modal-backdrop" data-size-guide-modal-close aria-hidden="true"></div>
    <div class="relative z-10 w-full max-w-lg max-h-[min(90vh,90dvh)] flex flex-col rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden pointer-events-auto">
        <div class="flex items-start justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-[#0297FE]/10 to-white shrink-0">
            <div class="min-w-0 pr-2">
                <h2 id="size-guide-modal-title" class="text-base font-extrabold text-slate-900">Size Guide</h2>
                <p class="text-xs text-slate-600 mt-0.5 leading-snug">mm per finger; numbers in ( ) are sample tip numbers.</p>
            </div>
            <button type="button" class="shrink-0 w-10 h-10 rounded-full border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" data-size-guide-modal-close aria-label="Close size guide">
                <span class="material-symbols-outlined text-2xl">close</span>
            </button>
        </div>
        <div class="overflow-y-auto overscroll-contain px-4 py-4">
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full text-left text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-[#0297FE] text-white">
                            <th class="px-3 py-2.5 font-bold uppercase tracking-wide whitespace-nowrap">Preset</th>
                            <th class="px-3 py-2.5 font-bold uppercase tracking-wide whitespace-nowrap">Thumb</th>
                            <th class="px-3 py-2.5 font-bold uppercase tracking-wide whitespace-nowrap">Index</th>
                            <th class="px-3 py-2.5 font-bold uppercase tracking-wide whitespace-nowrap">Middle</th>
                            <th class="px-3 py-2.5 font-bold uppercase tracking-wide whitespace-nowrap">Ring</th>
                            <th class="px-3 py-2.5 font-bold uppercase tracking-wide whitespace-nowrap">Pinky</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($sizeChartTable as $row)
                        <tr class="hover:bg-[#0297FE]/5 transition-colors">
                            <td class="px-3 py-2.5 font-bold text-[#0297FE] whitespace-nowrap">{{ $row['preset'] }}</td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap">{{ $row['thumb']['mm'] }}mm ({{ $row['thumb']['num'] }})</td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap">{{ $row['index']['mm'] }}mm ({{ $row['index']['num'] }})</td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap">{{ $row['middle']['mm'] }}mm ({{ $row['middle']['num'] }})</td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap">{{ $row['ring']['mm'] }}mm ({{ $row['ring']['num'] }})</td>
                            <td class="px-3 py-2.5 text-slate-700 whitespace-nowrap">{{ $row['pinky']['mm'] }}mm ({{ $row['pinky']['num'] }})</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-[11px] text-slate-500 italic">Approximate reference — shape and fit can vary.</p>
            <a href="{{ route('sizing-kit.index') }}#size-chart" class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-[#0297FE] hover:underline">
                Full guide &amp; sizing kit
                <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>
    </div>
</div>

{{-- Review image lightbox (same behavior as shops/reviews). No backdrop-blur: blur on fixed layers flickers when body scroll locks. --}}
<div id="review-image-modal" class="review-image-modal-root fixed inset-0 z-[110] hidden flex items-center justify-center p-2 sm:p-3" role="dialog" aria-modal="true" aria-labelledby="review-image-modal-title">
    <div class="absolute inset-0 review-image-modal-backdrop" data-review-image-modal-close aria-hidden="true"></div>
    <p id="review-image-modal-title" class="sr-only">Review photo</p>
    <button type="button" class="absolute top-3 right-3 sm:top-4 sm:right-4 z-20 w-11 h-11 rounded-full bg-white/95 border border-slate-200 shadow-lg flex items-center justify-center text-slate-700 hover:bg-slate-100 transition-colors" data-review-image-modal-close aria-label="Close">
        <span class="material-symbols-outlined text-2xl">close</span>
    </button>
    <div class="relative z-10 w-full max-w-[min(100vw-0.5rem,1600px)] max-h-[96vh] flex items-center justify-center pointer-events-none px-1">
        <img id="review-image-modal-img" src="" alt="Review photo" class="pointer-events-auto max-h-[min(94vh,94dvh)] w-auto max-w-full object-contain rounded-xl shadow-2xl ring-1 ring-white/10">
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
/* Review lightbox: solid overlay (no backdrop-filter) + stable layer to avoid flicker */
.review-image-modal-backdrop {
    background: rgba(15, 23, 42, 0.92);
}
.review-image-modal-root {
    isolation: isolate;
    contain: layout style;
}
.size-guide-modal-backdrop {
    background: rgba(15, 23, 42, 0.6);
}
.size-guide-modal-root {
    isolation: isolate;
    contain: layout style;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    /** GTM dataLayer: view_item + add_to_cart */
    var ANALYTICS_DEBUG = @json($analyticsDebugOn);
    var GTM_CURRENCY = @json($currentCurrency);
    var GTM_PRODUCT_ITEM = {
        item_id: @json($product->sku ?? (string) $product->id),
        item_name: @json($product->name),
        item_category: @json($gtagPrimaryCategory),
    };
    function analyticsDebugLog(title, detail) {
        if (!ANALYTICS_DEBUG) return;
        var body = document.getElementById('analytics-debug-body');
        if (!body) return;
        var dlLen = (typeof dataLayer !== 'undefined' && dataLayer.length) ? dataLayer.length : 0;
        var block = {
            title: title,
            time: new Date().toISOString(),
            dataLayer_length_after: dlLen,
            payload: detail
        };
        var line = JSON.stringify(block, null, 2);
        body.textContent = line + '\n\n' + (body.textContent || '');
        console.log('[analytics-debug]', title, detail);
    }
    (function initAnalyticsDebugPanel() {
        if (!ANALYTICS_DEBUG) return;
        var panel = document.getElementById('analytics-debug-panel');
        var body = document.getElementById('analytics-debug-body');
        var toggle = document.getElementById('analytics-debug-toggle');
        var clearBtn = document.getElementById('analytics-debug-clear');
        if (toggle && body) {
            toggle.addEventListener('click', function() {
                var open = body.classList.toggle('hidden');
                toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
                toggle.textContent = open ? 'Mở rộng' : 'Thu gọn';
            });
        }
        if (clearBtn && body) {
            clearBtn.addEventListener('click', function() { body.textContent = ''; });
        }
    })();
    function pushViewItemAnalytics() {
        var basePrice = @json(round((float) $productPrice, 2));
        var item = Object.assign({}, GTM_PRODUCT_ITEM, { price: basePrice, quantity: 1 });
        if (typeof dataLayer !== 'undefined') {
            dataLayer.push({ ecommerce: null });
            dataLayer.push({
                event: 'view_item',
                ecommerce: {
                    currency: GTM_CURRENCY,
                    value: basePrice,
                    items: [item]
                }
            });
            console.log('✅ GTM: view_item tracked', { value: basePrice, items: item });
        }
    }
    function pushAddToCartAnalytics(unitPrice, quantity, variantAttrs) {
        var price = Math.round(parseFloat(unitPrice) * 100) / 100;
        var qty = Math.max(1, parseInt(quantity, 10) || 1);
        var value = Math.round(price * qty * 100) / 100;
        var item = Object.assign({}, GTM_PRODUCT_ITEM, { price: price, quantity: qty });
        if (variantAttrs && typeof variantAttrs === 'object') {
            var parts = [];
            Object.keys(variantAttrs).forEach(function (k) {
                parts.push(k + ': ' + variantAttrs[k]);
            });
            if (parts.length) item.item_variant = parts.join(' / ');
        }
        if (typeof dataLayer !== 'undefined') {
            dataLayer.push({ ecommerce: null });
            dataLayer.push({
                event: 'add_to_cart',
                ecommerce: {
                    currency: GTM_CURRENCY,
                    value: value,
                    items: [item]
                }
            });
            console.log('✅ GTM: add_to_cart tracked', { value: value, items: item });
        }
        if (typeof fbq !== 'undefined') {
            try {
                fbq('track', 'AddToCart', {
                    content_name: GTM_PRODUCT_ITEM.item_name,
                    content_ids: [String(GTM_PRODUCT_ITEM.item_id)],
                    content_type: 'product',
                    value: value,
                    currency: GTM_CURRENCY,
                    num_items: qty,
                });
            } catch (e) {}
        }
        if (typeof window.ttq !== 'undefined') {
            try {
                window.ttq.track('AddToCart', {
                    contents: [{
                        content_id: String(GTM_PRODUCT_ITEM.item_id),
                        content_type: 'product',
                        content_name: GTM_PRODUCT_ITEM.item_name,
                        quantity: qty,
                        price: price,
                    }],
                    value: value,
                    currency: GTM_CURRENCY,
                });
            } catch (e) {}
        }
    }

    pushViewItemAnalytics();

    var CUSTOM_FILE_PRODUCT_ID = {{ (int) $product->id }};
    var CUSTOM_FILE_UPLOAD_URL = @json(route('api.custom-files.upload'));
    var CUSTOM_FILES_API_BASE = @json(rtrim(url('/api/custom-files'), '/'));

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
        var webp = btn.getAttribute('data-webp') || '';
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
            if (mainImg) {
                var imgSrc = (type === 'image' && webp) ? webp : url;
                mainImg.src = imgSrc;
                mainImg.alt = btn.getAttribute('data-alt') || '';
                mainImg.classList.remove('hidden');
            }
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

    (function initGalleryFromQuery() {
        var params = new URLSearchParams(window.location.search);
        var g = params.get('gallery');
        if (g === null || g === '') return;
        var idx = parseInt(g, 10);
        if (isNaN(idx) || totalItems < 1) return;
        showMainMediaIndex(Math.min(Math.max(0, idx), totalItems - 1));
    })();

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

    var PRODUCT_VARIANTS = @json($variantsForJs);
    var VARIANT_ATTR_KEY_FIRST = @json($variantAttrKeyFirst);
    var VARIANT_ATTR_KEY_SECOND = @json($variantAttrKeySecond);
    var VARIANT_HAS_SECOND_PICKER = @json($variantHasSecondPicker);
    var VARIANT_HAS_SIZE_OPTIONS = @json($hasVariantSizeOptions);
    var VARIANT_HAS_SHAPE_OPTIONS = @json($hasVariantShapeOptions);
    var VARIANT_LABEL_FIRST = VARIANT_ATTR_KEY_FIRST || '';
    var VARIANT_LABEL_SECOND = (VARIANT_HAS_SECOND_PICKER && VARIANT_ATTR_KEY_SECOND) ? VARIANT_ATTR_KEY_SECOND : '';

    function noVariantPickersShown() {
        return !VARIANT_HAS_SIZE_OPTIONS && !(VARIANT_HAS_SECOND_PICKER && VARIANT_HAS_SHAPE_OPTIONS);
    }

    function variantAttrKeysFirst() {
        var keys = [];
        if (VARIANT_ATTR_KEY_FIRST) keys.push(VARIANT_ATTR_KEY_FIRST);
        keys.push('Size', 'size');
        var seen = {};
        return keys.filter(function (k) {
            if (!k || seen[k]) return false;
            seen[k] = true;
            return true;
        });
    }
    function variantAttrKeysSecond() {
        var keys = [];
        if (VARIANT_HAS_SECOND_PICKER && VARIANT_ATTR_KEY_SECOND) keys.push(VARIANT_ATTR_KEY_SECOND);
        keys.push('SHAPE & LENGTH', 'Shape & Length', 'shape & length', 'Nail Shape', 'Shape', 'shape');
        var seen = {};
        return keys.filter(function (k) {
            if (!k || seen[k]) return false;
            seen[k] = true;
            return true;
        });
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
    if (VARIANT_HAS_SECOND_PICKER && document.querySelector('.shape-button')) document.querySelector('.shape-button').classList.add('active');

    var CURRENCY_CODE = @json($currentCurrency);
    var CURRENCY_SYMBOL = @json($currencySymbol);
    var BULK_DISCOUNT_RULES = @json($bulkDiscountRules);

    function getComboDiscountPercentPreview(qty) {
        var q = parseInt(qty, 10) || 0;
        if (q < 1 || !Array.isArray(BULK_DISCOUNT_RULES) || BULK_DISCOUNT_RULES.length === 0) return 0;
        var best = 0;
        BULK_DISCOUNT_RULES.forEach(function (rule) {
            var minQty = parseInt(rule.min_qty, 10) || 0;
            var percent = parseFloat(rule.percent) || 0;
            if (minQty > 0 && q >= minQty) best = Math.max(best, percent);
        });
        best = Math.max(0, Math.min(95, best));
        return best;
    }

    function getNextDiscountTier(qty) {
        if (!Array.isArray(BULK_DISCOUNT_RULES) || BULK_DISCOUNT_RULES.length === 0) return null;

        var q = parseInt(qty, 10) || 0;
        if (q < 1) return null;

        // Sort ascending by min_qty
        var sorted = BULK_DISCOUNT_RULES.slice().sort(function(a, b) {
            return (parseInt(a.min_qty, 10) || 0) - (parseInt(b.min_qty, 10) || 0);
        });

        for (var i = 0; i < sorted.length; i++) {
            var minQty = parseInt(sorted[i].min_qty, 10) || 0;
            if (minQty > 0 && q < minQty) {
                return {
                    min_qty: minQty,
                    percent: parseFloat(sorted[i].percent) || 0
                };
            }
        }

        return null;
    }

    function updateVolumeDiscountDynamicMessage() {
        var qtyEl = document.getElementById('product-quantity');
        var el = document.getElementById('volume-discount-dynamic-message');
        if (!qtyEl || !el) return;

        var qty = parseInt(qtyEl.value, 10) || 1;

        if (!Array.isArray(BULK_DISCOUNT_RULES) || BULK_DISCOUNT_RULES.length === 0) {
            el.classList.add('hidden');
            return;
        }

        var nextTier = getNextDiscountTier(qty);
        var currentPercent = getComboDiscountPercentPreview(qty);

        if (nextTier) {
            var more = nextTier.min_qty - qty;
            more = Math.max(1, more);
            var nextPercent = parseFloat(nextTier.percent) || 0;

            el.textContent = 'Add ' + more + ' more to get ' + nextPercent.toFixed(0) + '% OFF';
        } else if (currentPercent > 0) {
            el.textContent = 'You\'re currently getting ' + currentPercent.toFixed(0) + '% OFF';
        } else {
            el.textContent = 'Choose a quantity to unlock combo discounts!';
        }

        el.classList.remove('hidden');
    }

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
            if (k && attrs[k] !== undefined && attrs[k] !== null && String(attrs[k]).trim() !== '') return String(attrs[k]).trim();
        }
        for (var j = 0; j < keys.length; j++) {
            var want = keys[j];
            if (!want) continue;
            var lw = String(want).toLowerCase();
            for (var attrKey in attrs) {
                if (!Object.prototype.hasOwnProperty.call(attrs, attrKey)) continue;
                if (String(attrKey).toLowerCase() !== lw) continue;
                var v = attrs[attrKey];
                if (v !== undefined && v !== null && String(v).trim() !== '') return String(v).trim();
            }
        }
        return null;
    }
    function getVariantPrice(selectedSize, selectedShape) {
        if (!PRODUCT_VARIANTS || PRODUCT_VARIANTS.length === 0) return null;
        if (noVariantPickersShown() && PRODUCT_VARIANTS.length === 1) {
            var p0 = PRODUCT_VARIANTS[0].price;
            return (p0 !== null && p0 !== undefined && !isNaN(parseFloat(p0))) ? parseFloat(p0) : null;
        }
        if (noVariantPickersShown()) return null;
        if (VARIANT_HAS_SIZE_OPTIONS && !selectedSize) return null;
        if (VARIANT_HAS_SHAPE_OPTIONS && !selectedShape) return null;
        for (var i = 0; i < PRODUCT_VARIANTS.length; i++) {
            var v = PRODUCT_VARIANTS[i];
            var attrs = v.attributes || {};
            var vSize = pickAttr(attrs, variantAttrKeysFirst());
            var vShape = VARIANT_HAS_SECOND_PICKER ? pickAttr(attrs, variantAttrKeysSecond()) : null;
            var sizeOk = !VARIANT_HAS_SIZE_OPTIONS || (vSize === selectedSize);
            var shapeOk = !VARIANT_HAS_SHAPE_OPTIONS || (vShape === selectedShape);
            if (sizeOk && shapeOk) return v.price;
        }
        return null;
    }
    function getVariantListPrice(selectedSize, selectedShape) {
        if (!PRODUCT_VARIANTS || PRODUCT_VARIANTS.length === 0) return null;
        if (noVariantPickersShown() && PRODUCT_VARIANTS.length === 1) {
            var lp0 = PRODUCT_VARIANTS[0].list_price;
            return (lp0 !== null && lp0 !== undefined && !isNaN(parseFloat(lp0))) ? parseFloat(lp0) : null;
        }
        if (noVariantPickersShown()) return null;
        if (VARIANT_HAS_SIZE_OPTIONS && !selectedSize) return null;
        if (VARIANT_HAS_SHAPE_OPTIONS && !selectedShape) return null;
        for (var i = 0; i < PRODUCT_VARIANTS.length; i++) {
            var v = PRODUCT_VARIANTS[i];
            var attrs = v.attributes || {};
            var vSize = pickAttr(attrs, variantAttrKeysFirst());
            var vShape = VARIANT_HAS_SECOND_PICKER ? pickAttr(attrs, variantAttrKeysSecond()) : null;
            var sizeOk = !VARIANT_HAS_SIZE_OPTIONS || (vSize === selectedSize);
            var shapeOk = !VARIANT_HAS_SHAPE_OPTIONS || (vShape === selectedShape);
            if (sizeOk && shapeOk && v.list_price != null) return v.list_price;
        }
        return null;
    }
    function getCustomizationRowValueForTotal(row, field) {
        var ft = row.getAttribute('data-field-type') || '';
        if (!field) return '';
        if (ft === 'file') return (field.value || '').trim();
        if (field.type === 'checkbox') return field.checked ? '1' : '';
        return (field.value || '').trim();
    }
    function getCustomizationRowValueForCart(row, field) {
        var ft = row.getAttribute('data-field-type') || '';
        if (!field) return '';
        if (ft === 'file') return (field.value || '').trim();
        if (field.type === 'checkbox') return field.checked ? (field.value || 'Yes') : '';
        return (field.value || '').trim();
    }
    function getCustomizationTotal() {
        var total = 0;
        document.querySelectorAll('.customization-row').forEach(function(row) {
            var price = parseFloat(row.getAttribute('data-price')) || 0;
            if (!price) return;
            var field = row.querySelector('.customization-field');
            if (!field) return;
            var value = getCustomizationRowValueForTotal(row, field);
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

    function parseCustomizationFilePayload(hiddenVal) {
        if (!hiddenVal) return null;
        try {
            var o = JSON.parse(hiddenVal);
            if (o && o.id) return o;
        } catch (e) {}
        return null;
    }
    function clearCustomizationUploadedFile(row) {
        var hidden = row.querySelector('.customization-file-value');
        var status = row.querySelector('.customization-file-status');
        var errEl = row.querySelector('.customization-file-error');
        var clr = row.querySelector('.customization-file-clear');
        var finput = row.querySelector('.customization-file-input');
        var pick = row.querySelector('.customization-file-pick');
        var prev = hidden ? hidden.value : '';
        var payload = parseCustomizationFilePayload(prev);
        function resetUi() {
            if (hidden) {
                hidden.value = '';
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (status) status.textContent = '';
            if (errEl) {
                errEl.textContent = '';
                errEl.classList.add('hidden');
            }
            if (clr) clr.classList.add('hidden');
            if (finput) finput.value = '';
            if (pick) pick.disabled = false;
            updateDisplayedPrice();
        }
        if (payload && payload.id) {
            fetch(CUSTOM_FILES_API_BASE + '/' + encodeURIComponent(payload.id), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function() { resetUi(); }).catch(function() { resetUi(); });
        } else {
            resetUi();
        }
    }
    function uploadCustomizationFile(row, file, fileInput) {
        var hidden = row.querySelector('.customization-file-value');
        var status = row.querySelector('.customization-file-status');
        var errEl = row.querySelector('.customization-file-error');
        var clr = row.querySelector('.customization-file-clear');
        var pick = row.querySelector('.customization-file-pick');
        var maxBytes = 10 * 1024 * 1024;
        if (file.size > maxBytes) {
            if (errEl) {
                errEl.textContent = 'File is too large (max 10MB).';
                errEl.classList.remove('hidden');
            }
            if (fileInput) fileInput.value = '';
            return;
        }
        if (errEl) {
            errEl.textContent = '';
            errEl.classList.add('hidden');
        }
        var prev = hidden ? hidden.value : '';
        var oldPayload = parseCustomizationFilePayload(prev);
        if (pick) pick.disabled = true;
        if (status) status.textContent = 'Uploading…';
        function doUpload() {
            var fd = new FormData();
            fd.append('product_id', String(CUSTOM_FILE_PRODUCT_ID));
            fd.append('files[]', file, file.name);
            console.log('🔹 Uploading custom file', {
                product_id: CUSTOM_FILE_PRODUCT_ID,
                name: file.name,
                size: file.size
            });
            fetch(CUSTOM_FILE_UPLOAD_URL, {
                method: 'POST',
                body: fd,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function(r) {
                    return r.json().then(function(j) {
                        var out = { ok: r.ok, status: r.status, j: j };
                        console.log('🔹 Custom file upload response', out);
                        return out;
                    });
                })
                .then(function(res) {
                    if (pick) pick.disabled = false;
                    if (fileInput) fileInput.value = '';
                    if (!res.ok || !res.j || !res.j.success || !res.j.data || !res.j.data.files || !res.j.data.files[0]) {
                        if (status) status.textContent = '';
                        if (errEl) {
                            var msg = (res.j && res.j.message) ? res.j.message : 'Upload failed.';
                            console.error('❌ Custom file upload error', {
                                status: res.status,
                                ok: res.ok,
                                message: msg,
                                response: res.j
                            });
                            errEl.textContent = msg;
                            errEl.classList.remove('hidden');
                        }
                        return;
                    }
                    var f = res.j.data.files[0];
                    var payload = JSON.stringify({ id: f.id, file_url: f.file_url, original_name: f.original_name || '' });
                    if (hidden) {
                        hidden.value = payload;
                        hidden.dispatchEvent(new Event('input', { bubbles: true }));
                        hidden.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (status) status.textContent = f.original_name || 'Uploaded';
                    if (clr) clr.classList.remove('hidden');
                    console.log('✅ Custom file uploaded successfully', f);
                    updateDisplayedPrice();
                })
                .catch(function(err) {
                    if (pick) pick.disabled = false;
                    if (fileInput) fileInput.value = '';
                    if (status) status.textContent = '';
                    console.error('❌ Custom file upload failed (network/JS error)', err);
                    if (errEl) {
                        errEl.textContent = 'Upload failed. Please try again.';
                        errEl.classList.remove('hidden');
                    }
                });
        }
        if (oldPayload && oldPayload.id) {
            fetch(CUSTOM_FILES_API_BASE + '/' + encodeURIComponent(oldPayload.id), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).finally(function() { doUpload(); });
        } else {
            doUpload();
        }
    }
    var customizationsRoot = document.getElementById('product-customizations');
    if (customizationsRoot) {
        customizationsRoot.addEventListener('click', function(e) {
            var pickBtn = e.target.closest('.customization-file-pick');
            if (pickBtn) {
                e.preventDefault();
                var row = pickBtn.closest('.customization-row');
                if (!row) return;
                var finput = row.querySelector('.customization-file-input');
                if (finput && !pickBtn.disabled) finput.click();
                return;
            }
            if (e.target.closest('.customization-file-clear')) {
                e.preventDefault();
                var row2 = e.target.closest('.customization-row');
                if (row2) clearCustomizationUploadedFile(row2);
            }
        });
        customizationsRoot.addEventListener('change', function(e) {
            var t = e.target;
            if (!t || !t.classList || !t.classList.contains('customization-file-input')) return;
            var row = t.closest('.customization-row');
            if (row && t.files && t.files[0]) uploadCustomizationFile(row, t.files[0], t);
        });
    }

    updateDisplayedPrice();

    // Quantity +/- 
    var qtyEl = document.getElementById('product-quantity');
    if (qtyEl) {
        document.getElementById('qty-minus') && document.getElementById('qty-minus').addEventListener('click', function() {
            var n = Math.max(1, parseInt(qtyEl.value, 10) - 1);
            qtyEl.value = n;
            updateVolumeDiscountDynamicMessage();
        });
        document.getElementById('qty-plus') && document.getElementById('qty-plus').addEventListener('click', function() {
            var n = Math.min(99, (parseInt(qtyEl.value, 10) || 1) + 1);
            qtyEl.value = n;
            updateVolumeDiscountDynamicMessage();
        });
        qtyEl.addEventListener('change', function() {
            var n = Math.min(99, Math.max(1, parseInt(qtyEl.value, 10) || 1));
            qtyEl.value = n;
            updateVolumeDiscountDynamicMessage();
        });

        updateVolumeDiscountDynamicMessage();
    }

    function getMatchingVariant(selectedSize, selectedShape) {
        if (!PRODUCT_VARIANTS || PRODUCT_VARIANTS.length === 0) return null;
        if (noVariantPickersShown() && PRODUCT_VARIANTS.length === 1) {
            return PRODUCT_VARIANTS[0];
        }
        for (var i = 0; i < PRODUCT_VARIANTS.length; i++) {
            var v = PRODUCT_VARIANTS[i];
            var attrs = v.attributes || {};
            var vSize = pickAttr(attrs, variantAttrKeysFirst());
            var vShape = VARIANT_HAS_SECOND_PICKER ? pickAttr(attrs, variantAttrKeysSecond()) : null;
            var sizeOk = !VARIANT_HAS_SIZE_OPTIONS || (vSize === selectedSize);
            var shapeOk = !VARIANT_HAS_SHAPE_OPTIONS || (vShape === selectedShape);
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
                var fieldType = row.getAttribute('data-field-type') || '';
                if (!field) return;
                var value = getCustomizationRowValueForCart(row, field);
                if (field.getAttribute('data-required') && !value) {
                    if (valid) {
                        showToast('Please fill in: ' + label);
                        if (fieldType === 'file') {
                            var pickEl = row.querySelector('.customization-file-pick');
                            if (pickEl && pickEl.focus) pickEl.focus();
                        } else if (typeof field.focus === 'function') {
                            field.focus();
                        }
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
                    if (VARIANT_HAS_SIZE_OPTIONS && !selectedSize && document.getElementById('size-options')) {
                        showToast('Please select ' + (VARIANT_LABEL_FIRST || 'an option') + '.');
                        document.querySelector('.size-badge') && document.querySelector('.size-badge').focus();
                        return;
                    }
                    if (VARIANT_HAS_SHAPE_OPTIONS && !selectedShape && document.getElementById('shape-options')) {
                        showToast('Please select ' + (VARIANT_LABEL_SECOND || 'an option') + '.');
                        document.querySelector('.shape-button') && document.querySelector('.shape-button').focus();
                        return;
                    }
                    showToast('This combination is not available. Try another option.');
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
                        var attrsForTrack = matchingVariant && matchingVariant.attributes ? matchingVariant.attributes : {};
                        pushAddToCartAnalytics(unitPrice, quantity, attrsForTrack);
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

    var sizeGuideModal = document.getElementById('size-guide-modal');
    var sizeGuideOpenBtn = document.getElementById('size-guide-open');
    function openSizeGuideModal() {
        if (!sizeGuideModal || !sizeGuideOpenBtn) return;
        sizeGuideModal.classList.remove('hidden');
        sizeGuideOpenBtn.setAttribute('aria-expanded', 'true');
        var sb = window.innerWidth - document.documentElement.clientWidth;
        if (sb > 0) document.body.style.paddingRight = sb + 'px';
        document.body.style.overflow = 'hidden';
    }
    function closeSizeGuideModal() {
        if (!sizeGuideModal || !sizeGuideOpenBtn) return;
        sizeGuideModal.classList.add('hidden');
        sizeGuideOpenBtn.setAttribute('aria-expanded', 'false');
        document.body.style.paddingRight = '';
        document.body.style.overflow = '';
        try { sizeGuideOpenBtn.focus(); } catch (err) {}
    }
    if (sizeGuideOpenBtn) {
        sizeGuideOpenBtn.addEventListener('click', function() { openSizeGuideModal(); });
    }

    var reviewImgModal = document.getElementById('review-image-modal');
    var reviewImgModalEl = document.getElementById('review-image-modal-img');
    function openReviewImageModal(src) {
        if (!reviewImgModal || !reviewImgModalEl || !src) return;
        reviewImgModalEl.src = src;
        reviewImgModal.classList.remove('hidden');
        var sb = window.innerWidth - document.documentElement.clientWidth;
        if (sb > 0) {
            document.body.style.paddingRight = sb + 'px';
        }
        document.body.style.overflow = 'hidden';
    }
    function closeReviewImageModal() {
        if (!reviewImgModal || !reviewImgModalEl) return;
        reviewImgModal.classList.add('hidden');
        reviewImgModalEl.removeAttribute('src');
        document.body.style.paddingRight = '';
        document.body.style.overflow = '';
    }
    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('.js-review-photo-trigger');
        if (trigger && trigger.tagName === 'IMG' && trigger.src) {
            e.preventDefault();
            openReviewImageModal(trigger.currentSrc || trigger.src);
            return;
        }
        if (e.target.closest('[data-review-image-modal-close]')) {
            closeReviewImageModal();
        }
        if (e.target.closest('[data-size-guide-modal-close]')) {
            closeSizeGuideModal();
        }
    });
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        if (sizeGuideModal && !sizeGuideModal.classList.contains('hidden')) {
            closeSizeGuideModal();
            return;
        }
        if (reviewImgModal && !reviewImgModal.classList.contains('hidden')) {
            closeReviewImageModal();
        }
    });
});
</script>
@endsection
