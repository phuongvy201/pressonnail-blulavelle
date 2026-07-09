@extends('layouts.app')

@section('content')
@php
    // Nội dung các block bên dưới lưu trong DB (bảng content_blocks). Khi chỉnh qua admin inline-edit sẽ ghi đè; default ở đây chỉ dùng khi chưa có bản ghi.
    $currentCurrency = currency();
    $currencySymbol = currency_symbol();
    $homeOaiqEventId = 'page_viewed-home-' . session()->getId();
    $hero = content_block('home.hero', [
        'tagline' => 'Professional Grade at Home',
        'heading' => 'Manicure in',
        'heading_highlight' => 'Minutes',
        'subheading' => 'Get the perfect salon-quality look at home without the wait or the price tag. Reusable, durable, and effortlessly chic.',
        'cta_primary_label' => 'Shop the Collection',
        'cta_primary_url' => route('products.index'),
        'cta_secondary_label' => 'How it Works',
        'cta_secondary_url' => route('page.show', 'about-us') ?? route('page.show', 'faqs') ?? '#',
        'image' => asset('storage/images/44ad1fa40f4f3b0b55214cf29e1dd8a2.jpg'),
        // Optional colors (admin configurable)
        'bg_color' => null, // màu nền panel trái (HEX) — để trống dùng trắng mặc định
        'badge_number' => '50k+',
        'badge_label' => 'Happy Customers',
        'meta1_value' => '50K+',
        'meta1_label' => 'Happy Customers',
        'meta2_value' => '4.9/5',
        'meta2_label' => 'Average Rating',
        'meta3_value' => '10 Min',
        'meta3_label' => 'To Apply',
        // Ảnh facet — upload thủ công (ưu tiên); collection chỉ là fallback khi chưa upload
        'slide1_images' => [],
        'slide1_collection' => '',
        'slide2_tagline' => 'Limited Edition',
        'slide2_heading' => 'Iridescent',
        'slide2_heading_highlight' => 'Pearl Ombré',
        'slide2_subheading' => 'Subtle mother-of-pearl shimmer on every tip — hand-finished for a jewelry-like glow.',
        'slide2_images' => [],
        'slide2_collection' => '',
        'slide2_badge_number' => '4.9★',
        'slide2_badge_label' => 'Average Rating',
        'slide3_tagline' => 'New Arrival',
        'slide3_heading' => 'Sculpted',
        'slide3_heading_highlight' => 'Gem Architecture',
        'slide3_subheading' => 'Inspired by cathedral stained-glass windows — hand-set gems, made to order.',
        'slide3_images' => [],
        'slide3_collection' => '',
        'slide3_badge_number' => '1-on-1',
        'slide3_badge_label' => 'Custom Fit',
    ]);
    $heroLeftBg = !empty($hero['bg_color']) ? $hero['bg_color'] : '#ffffff';
    $heroLeftIsLight = (function ($color) {
        $color = trim((string) $color);
        if ($color === '') {
            return true;
        }
        if (preg_match('/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $color, $m)) {
            $r = (int) $m[1]; $g = (int) $m[2]; $b = (int) $m[3];
        } elseif (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color, $m)) {
            $hex = $m[1];
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        } else {
            return true;
        }
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luminance > 0.55;
    })($heroLeftBg);
    $heroLeftThemeClass = $heroLeftIsLight ? 'hero-glass-left--light' : 'hero-glass-left--dark';

    // Ảnh fallback mặc định khi collection trống / chưa chọn
    $heroFallbackImage = $hero['image'] ?? '';
    if ($heroFallbackImage !== '' && !str_starts_with($heroFallbackImage, 'http')) {
        $heroFallbackImage = asset($heroFallbackImage);
    }

    // Chuẩn hoá URL ảnh đã upload (lưu trong content_blocks)
    $heroResolveImages = function ($raw) {
        if (!is_array($raw)) {
            return [];
        }
        return array_values(array_filter(array_map(function ($u) {
            $u = trim((string) $u);
            if ($u === '') {
                return null;
            }
            return str_starts_with($u, 'http') ? $u : asset($u);
        }, $raw)));
    };

    // Đủ 4 ô facet: ảnh upload + fallback cho ô còn trống
    $heroFillFacetImages = function (array $manual, string $fallback) {
        $imgs = array_values(array_unique(array_filter($manual, fn ($u) => trim((string) $u) !== '')));
        if (empty($imgs)) {
            return [];
        }
        $fallback = trim((string) $fallback);
        while (count($imgs) < 4 && $fallback !== '') {
            $imgs[] = $fallback;
        }
        return array_slice($imgs, 0, 4);
    };

    // Fallback khi chưa upload: random 1 ảnh / sản phẩm từ collection hoặc catalog
    $heroCollectionImages = function ($ref, int $count = 4, array $excludeUrls = []) {
        $resolveUrl = function ($u) {
            $u = trim((string) $u);
            if ($u === '') {
                return null;
            }
            return str_starts_with($u, 'http') ? $u : asset('storage/' . ltrim($u, '/'));
        };

        $firstImageFromProduct = function ($product) use ($resolveUrl) {
            foreach ($product->getEffectiveMedia() as $mediaItem) {
                $u = is_string($mediaItem) ? $mediaItem : ($mediaItem['url'] ?? $mediaItem['path'] ?? (reset($mediaItem) ?: null));
                $resolved = $resolveUrl($u);
                if ($resolved) {
                    return $resolved;
                }
            }
            return null;
        };

        $urls = [];
        $used = array_fill_keys(array_values(array_filter($excludeUrls)), true);

        $addFromProducts = function ($products) use (&$urls, &$used, $firstImageFromProduct, $count) {
            foreach ($products->shuffle() as $product) {
                if (count($urls) >= $count) {
                    break;
                }
                $url = $firstImageFromProduct($product);
                if (!$url || isset($used[$url])) {
                    continue;
                }
                $used[$url] = true;
                $urls[] = $url;
            }
        };

        $ref = trim((string) $ref);
        if ($ref !== '') {
            $query = \App\Models\Collection::query();
            $query->where(ctype_digit($ref) ? 'id' : 'slug', ctype_digit($ref) ? (int) $ref : $ref);
            $collection = $query->first();
            if ($collection) {
                $addFromProducts($collection->products()->availableForDisplay()->with('template')->get());
            }
        }

        if (count($urls) < $count) {
            $addFromProducts(
                \App\Models\Product::availableForDisplay()->with('template')->inRandomOrder()->limit(60)->get()
            );
        }

        return array_slice($urls, 0, $count);
    };

    // Đảm bảo đủ $n ảnh khác nhau; chỉ lặp fallback khi catalog thật sự không đủ SP
    $heroPadImages = function (array $imgs, int $n, string $fallback, array $excludeUrls = []) {
        $imgs = array_values(array_unique(array_filter($imgs, fn ($u) => trim((string) $u) !== '')));
        if (count($imgs) >= $n) {
            return array_slice($imgs, 0, $n);
        }

        $used = array_fill_keys(array_merge($imgs, array_values(array_filter($excludeUrls))), true);
        $more = \App\Models\Product::availableForDisplay()
            ->with('template')
            ->inRandomOrder()
            ->limit(40)
            ->get();

        foreach ($more as $product) {
            if (count($imgs) >= $n) {
                break;
            }
            foreach ($product->getEffectiveMedia() as $mediaItem) {
                $u = is_string($mediaItem) ? $mediaItem : ($mediaItem['url'] ?? $mediaItem['path'] ?? null);
                if (!$u) {
                    continue;
                }
                $url = str_starts_with($u, 'http') ? $u : asset('storage/' . ltrim($u, '/'));
                if (!isset($used[$url])) {
                    $used[$url] = true;
                    $imgs[] = $url;
                    break;
                }
            }
        }

        if (count($imgs) < $n && $fallback !== '') {
            while (count($imgs) < $n) {
                $imgs[] = $fallback;
            }
        }

        return array_slice($imgs, 0, $n);
    };

    // Chuẩn hoá 3 slide cho slider "Asymmetric Split" bên phải hero
    $heroSlidesRaw = [
        [
            'tagline' => $hero['tagline'] ?? '',
            'heading' => $hero['heading'] ?? '',
            'heading_highlight' => $hero['heading_highlight'] ?? '',
            'subheading' => $hero['subheading'] ?? '',
            'manual_images' => $heroResolveImages($hero['slide1_images'] ?? []),
            'collection' => $hero['slide1_collection'] ?? '',
            'badge_number' => $hero['badge_number'] ?? '50k+',
            'badge_label' => $hero['badge_label'] ?? 'Happy Customers',
        ],
        [
            'tagline' => $hero['slide2_tagline'] ?? '',
            'heading' => $hero['slide2_heading'] ?? '',
            'heading_highlight' => $hero['slide2_heading_highlight'] ?? '',
            'subheading' => $hero['slide2_subheading'] ?? '',
            'manual_images' => $heroResolveImages($hero['slide2_images'] ?? []),
            'collection' => $hero['slide2_collection'] ?? '',
            'badge_number' => $hero['slide2_badge_number'] ?: ($hero['badge_number'] ?? '50k+'),
            'badge_label' => $hero['slide2_badge_label'] ?: ($hero['badge_label'] ?? 'Happy Customers'),
        ],
        [
            'tagline' => $hero['slide3_tagline'] ?? '',
            'heading' => $hero['slide3_heading'] ?? '',
            'heading_highlight' => $hero['slide3_heading_highlight'] ?? '',
            'subheading' => $hero['slide3_subheading'] ?? '',
            'manual_images' => $heroResolveImages($hero['slide3_images'] ?? []),
            'collection' => $hero['slide3_collection'] ?? '',
            'badge_number' => $hero['slide3_badge_number'] ?: ($hero['badge_number'] ?? '50k+'),
            'badge_label' => $hero['slide3_badge_label'] ?: ($hero['badge_label'] ?? 'Happy Customers'),
        ],
    ];
    $heroSlides = [];
    $heroUsedUrls = [];
    foreach ($heroSlidesRaw as $slideRaw) {
        if (trim((string) ($slideRaw['heading'] ?? '')) === '') {
            continue;
        }
        if (!empty($slideRaw['manual_images'])) {
            $imgs = $heroFillFacetImages($slideRaw['manual_images'], $heroFallbackImage);
        } else {
            $imgs = $heroCollectionImages($slideRaw['collection'], 4, array_keys($heroUsedUrls));
            $imgs = $heroPadImages($imgs, 4, $heroFallbackImage, array_keys($heroUsedUrls));
        }
        foreach ($imgs as $u) {
            $heroUsedUrls[$u] = true;
        }
        $slideRaw['images'] = $imgs;
        if (!empty($slideRaw['images'])) {
            $heroSlides[] = $slideRaw;
        }
    }
    if (empty($heroSlides)) {
        $heroSlides = [[
            'tagline' => '', 'heading' => 'Manicure in', 'heading_highlight' => 'Minutes',
            'subheading' => '', 'collection' => '',
            'images' => $heroPadImages([], 4, $heroFallbackImage ?: asset('storage/images/44ad1fa40f4f3b0b55214cf29e1dd8a2.jpg')),
            'badge_number' => '50k+', 'badge_label' => 'Happy Customers',
        ]];
    }
    $heroSlidesJs = collect($heroSlides)->map(fn ($s) => [
        'tagline' => $s['tagline'],
        'heading' => $s['heading'],
        'highlight' => $s['heading_highlight'],
        'subheading' => $s['subheading'],
        'badgeNumber' => $s['badge_number'],
        'badgeLabel' => $s['badge_label'],
    ])->values()->all();

    $heroSchema = [
        ['key' => 'tagline', 'label' => 'Slide 1 — Tagline', 'type' => 'text'],
        ['key' => 'heading', 'label' => 'Slide 1 — Heading (phần trước)', 'type' => 'text'],
        ['key' => 'heading_highlight', 'label' => 'Slide 1 — Heading (từ nổi bật)', 'type' => 'text'],
        ['key' => 'subheading', 'label' => 'Slide 1 — Mô tả', 'type' => 'textarea'],
        ['key' => 'slide1_images', 'label' => 'Slide 1 — 4 ảnh facet (upload; thứ tự = ô 1→4)', 'type' => 'images'],
        ['key' => 'slide1_collection', 'label' => 'Slide 1 — Collection fallback (slug/ID; chỉ dùng khi chưa upload ảnh)', 'type' => 'text'],
        ['key' => 'badge_number', 'label' => 'Slide 1 — Badge số liệu (vd: 50k+)', 'type' => 'text'],
        ['key' => 'badge_label', 'label' => 'Slide 1 — Badge nhãn (vd: Happy Customers)', 'type' => 'text'],
        ['key' => 'slide2_tagline', 'label' => 'Slide 2 — Tagline', 'type' => 'text'],
        ['key' => 'slide2_heading', 'label' => 'Slide 2 — Heading (phần trước)', 'type' => 'text'],
        ['key' => 'slide2_heading_highlight', 'label' => 'Slide 2 — Heading (từ nổi bật)', 'type' => 'text'],
        ['key' => 'slide2_subheading', 'label' => 'Slide 2 — Mô tả', 'type' => 'textarea'],
        ['key' => 'slide2_images', 'label' => 'Slide 2 — 4 ảnh facet (upload; thứ tự = ô 1→4)', 'type' => 'images'],
        ['key' => 'slide2_collection', 'label' => 'Slide 2 — Collection fallback (slug/ID)', 'type' => 'text'],
        ['key' => 'slide2_badge_number', 'label' => 'Slide 2 — Badge số liệu', 'type' => 'text'],
        ['key' => 'slide2_badge_label', 'label' => 'Slide 2 — Badge nhãn', 'type' => 'text'],
        ['key' => 'slide3_tagline', 'label' => 'Slide 3 — Tagline', 'type' => 'text'],
        ['key' => 'slide3_heading', 'label' => 'Slide 3 — Heading (phần trước)', 'type' => 'text'],
        ['key' => 'slide3_heading_highlight', 'label' => 'Slide 3 — Heading (từ nổi bật)', 'type' => 'text'],
        ['key' => 'slide3_subheading', 'label' => 'Slide 3 — Mô tả', 'type' => 'textarea'],
        ['key' => 'slide3_images', 'label' => 'Slide 3 — 4 ảnh facet (upload; thứ tự = ô 1→4)', 'type' => 'images'],
        ['key' => 'slide3_collection', 'label' => 'Slide 3 — Collection fallback (slug/ID)', 'type' => 'text'],
        ['key' => 'slide3_badge_number', 'label' => 'Slide 3 — Badge số liệu', 'type' => 'text'],
        ['key' => 'slide3_badge_label', 'label' => 'Slide 3 — Badge nhãn', 'type' => 'text'],
        ['key' => 'cta_primary_label', 'label' => 'Nút chính - chữ', 'type' => 'text'],
        ['key' => 'cta_primary_url', 'label' => 'Nút chính - link', 'type' => 'url'],
        ['key' => 'cta_secondary_label', 'label' => 'Nút phụ - chữ', 'type' => 'text'],
        ['key' => 'cta_secondary_url', 'label' => 'Nút phụ - link', 'type' => 'url'],
        ['key' => 'meta1_value', 'label' => 'Thống kê 1 — số liệu', 'type' => 'text'],
        ['key' => 'meta1_label', 'label' => 'Thống kê 1 — nhãn', 'type' => 'text'],
        ['key' => 'meta2_value', 'label' => 'Thống kê 2 — số liệu', 'type' => 'text'],
        ['key' => 'meta2_label', 'label' => 'Thống kê 2 — nhãn', 'type' => 'text'],
        ['key' => 'meta3_value', 'label' => 'Thống kê 3 — số liệu', 'type' => 'text'],
        ['key' => 'meta3_label', 'label' => 'Thống kê 3 — nhãn', 'type' => 'text'],
        ['key' => 'bg_color', 'label' => 'Màu nền panel trái (HEX, vd: #ffffff) – để trống dùng trắng mặc định', 'type' => 'text'],
    ];
@endphp
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,600&family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
<script>
const CURRENT_CURRENCY = @json($currentCurrency);
const CURRENCY_SYMBOL = @json($currencySymbol);
// Track homepage view (Meta, ChatGPT Pixel)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof fbq !== 'undefined') {
        fbq('track', 'ViewContent', {
            content_name: 'Home Page',
            content_type: 'home'
        });
    }
    if (typeof oaiq === 'function') {
        try {
            oaiq('measure', 'page_viewed', {
                type: 'contents',
                contents: [
                    {
                        id: 'home',
                        name: 'Home Page',
                        content_type: 'page',
                    },
                ],
            }, {
                event_id: @json($homeOaiqEventId),
            });
        } catch (e) {
            console.error('oaiq page_viewed error:', e);
        }
    }
});
</script>

<!-- Hero Section — Asymmetric Split Slider (stained-glass) -->
<section class="hero-glass relative overflow-hidden" data-content-block="home.hero">
    @if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
    <div class="absolute top-4 right-4 z-30">
        <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.hero">Chỉnh sửa Hero</button>
    </div>
    @endif

    <div class="hero-glass-grid grid grid-cols-1 lg:grid-cols-[42%_58%]">
        {{-- Panel trái — nội dung, đứng yên --}}
        <div class="hero-glass-left {{ $heroLeftThemeClass }} order-2 lg:order-1 relative z-[3] flex flex-col justify-center gap-5 sm:gap-6 px-6 sm:px-10 lg:px-14 py-14 sm:py-16 lg:py-0" style="background-color: {{ $heroLeftBg }};">
            <div class="flex items-center gap-2.5">
                <span class="hero-eyebrow-line w-5 h-px shrink-0"></span>
                <span id="hero-eyebrow" class="hero-eyebrow text-[11px] sm:text-xs font-bold uppercase tracking-[0.22em]" data-content-field="tagline">{{ $heroSlides[0]['tagline'] }}</span>
            </div>
            <h1 class="hero-heading-font hero-title text-4xl sm:text-5xl lg:text-6xl leading-[1.05] tracking-tight">
                <span id="hero-heading-main" data-content-field="heading">{{ $heroSlides[0]['heading'] }}</span>
                <em id="hero-heading-highlight" class="hero-heading-highlight not-italic" data-content-field="heading_highlight">{{ $heroSlides[0]['heading_highlight'] }}</em>
            </h1>
            <p id="hero-subheading" class="hero-subheading text-base sm:text-lg leading-relaxed max-w-md" data-content-field="subheading">{{ $heroSlides[0]['subheading'] }}</p>
            <div class="flex flex-wrap gap-3 sm:gap-4 pt-2">
                <a href="{{ $hero['cta_primary_url'] ?? route('products.index') }}" class="hero-btn-primary inline-block px-7 sm:px-8 py-3.5 sm:py-4 rounded font-bold text-sm sm:text-base uppercase tracking-wide transition-all active:scale-95" data-content-field="cta_primary_url"><span data-content-field="cta_primary_label">{{ $hero['cta_primary_label'] ?? 'Shop the Collection' }}</span></a>
                <a href="{{ $hero['cta_secondary_url'] ?? '#' }}" class="hero-btn-secondary inline-block px-7 sm:px-8 py-3.5 sm:py-4 rounded font-bold text-sm sm:text-base uppercase tracking-wide transition-all" data-content-field="cta_secondary_url"><span data-content-field="cta_secondary_label">{{ $hero['cta_secondary_label'] ?? 'How it Works' }}</span></a>
            </div>
            <div class="hero-meta-row flex flex-wrap gap-6 sm:gap-8 pt-6 sm:pt-7 mt-1 border-t">
                <div>
                    <p class="hero-meta-label text-[10px] sm:text-[11px] uppercase tracking-[0.1em] font-bold mb-1" data-content-field="meta1_label">{{ $hero['meta1_label'] ?? 'Happy Customers' }}</p>
                    <p class="hero-heading-font hero-meta-value text-xl sm:text-2xl" data-content-field="meta1_value">{{ $hero['meta1_value'] ?? '50K+' }}</p>
                </div>
                <div>
                    <p class="hero-meta-label text-[10px] sm:text-[11px] uppercase tracking-[0.1em] font-bold mb-1" data-content-field="meta2_label">{{ $hero['meta2_label'] ?? 'Average Rating' }}</p>
                    <p class="hero-heading-font hero-meta-value text-xl sm:text-2xl" data-content-field="meta2_value">{{ $hero['meta2_value'] ?? '4.9/5' }}</p>
                </div>
                <div>
                    <p class="hero-meta-label text-[10px] sm:text-[11px] uppercase tracking-[0.1em] font-bold mb-1" data-content-field="meta3_label">{{ $hero['meta3_label'] ?? 'To Apply' }}</p>
                    <p class="hero-heading-font hero-meta-value text-xl sm:text-2xl" data-content-field="meta3_value">{{ $hero['meta3_value'] ?? '10 Min' }}</p>
                </div>
            </div>
        </div>

        {{-- Field collection ẩn: để inline editor đọc/ghi đúng giá trị đã lưu --}}
        <span class="hidden" data-content-field="slide1_collection">{{ $hero['slide1_collection'] ?? '' }}</span>
        <span class="hidden" data-content-field="slide2_collection">{{ $hero['slide2_collection'] ?? '' }}</span>
        <span class="hidden" data-content-field="slide3_collection">{{ $hero['slide3_collection'] ?? '' }}</span>

        {{-- Panel phải — "kính màu vỡ" chứa mosaic 4 ảnh + slider --}}
        <div class="hero-glass-right order-1 lg:order-2 relative min-h-[380px] sm:min-h-[420px] lg:min-h-0">
            <div class="hero-glass-right-clip absolute inset-0 overflow-hidden">
                @foreach($heroSlides as $idx => $slide)
                    @php $slideAltBase = trim(($slide['heading'] ?? '').' '.($slide['heading_highlight'] ?? '')) ?: 'Premium press-on nails'; @endphp
                    <div class="hero-slide-photo absolute inset-0 transition-opacity duration-700 ease-out {{ $idx === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0' }}" data-hero-slide="{{ $idx }}">
                        <div class="hero-facets absolute inset-0">
                            @foreach($slide['images'] as $tileIdx => $tileUrl)
                                <div class="hero-img-facet hero-img-facet-{{ $tileIdx + 1 }}">
                                    <img alt="{{ $slideAltBase }}"
                                         class="hero-img-facet__img"
                                         src="{{ $tileUrl }}"
                                         sizes="(max-width: 1023px) 45vw, 28vw"
                                         loading="{{ $idx === 0 ? 'eager' : 'lazy' }}"
                                         @if($idx === 0 && $tileIdx === 0) fetchpriority="high" @endif
                                         decoding="async">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="absolute inset-0 bg-gradient-to-t from-[#0B1B2B]/35 via-transparent to-[#0B1B2B]/5 pointer-events-none z-[4]"></div>

                <svg class="hero-lead-line absolute inset-0" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M6,0 L0,100" stroke="#0195FE" stroke-width="0.4" fill="none" vector-effect="non-scaling-stroke"/>
                    <path d="M20,0 L14,26 L4,42" stroke="#0195FE" stroke-width="0.25" opacity="0.55" fill="none" vector-effect="non-scaling-stroke"/>
                    <path d="M62,10 L48,46 L58,90" stroke="#0195FE" stroke-width="0.25" opacity="0.5" fill="none" vector-effect="non-scaling-stroke"/>
                </svg>
                <div class="hero-rivet" style="top:8%;left:8%;"></div>
                <div class="hero-rivet" style="top:52%;left:5%;"></div>
                <div class="hero-rivet" style="top:12%;right:22%;"></div>
                <div class="hero-rivet" style="bottom:8%;right:14%;"></div>

                @if(count($heroSlides) > 1)
                <div class="hero-slide-index absolute top-5 right-5 sm:top-6 sm:right-6 z-20">
                    <b id="hero-slide-idx">01</b> / {{ sprintf('%02d', count($heroSlides)) }}
                </div>
                <div class="absolute bottom-5 right-5 sm:bottom-6 sm:right-6 z-20 flex items-center gap-3">
                    <button type="button" class="hero-arrow" data-hero-dir="-1" aria-label="Previous slide">‹</button>
                    <div class="flex items-center gap-2" id="hero-dots">
                        @foreach($heroSlides as $idx => $slide)
                            <button type="button" class="hero-dot {{ $idx === 0 ? 'is-active' : '' }}" data-hero-dot="{{ $idx }}" aria-label="Slide {{ $idx + 1 }}"></button>
                        @endforeach
                    </div>
                    <button type="button" class="hero-arrow" data-hero-dir="1" aria-label="Next slide">›</button>
                </div>
                @endif

                {{-- Badge nổi — trong vùng ảnh, tránh bị cắt trên mobile --}}
                <div class="hero-badge z-20 flex items-center gap-3 sm:gap-4 bg-white rounded-xl shadow-2xl shadow-black/25 px-4 sm:px-5 py-3 sm:py-4 border-l-4 border-[#0195FE]">
                    <div class="text-[#0195FE] text-sm tracking-widest shrink-0">★★★★★</div>
                    <div>
                        <b id="hero-badge-num" class="hero-heading-font block text-lg sm:text-xl text-slate-900 leading-none" data-content-field="badge_number">{{ $heroSlides[0]['badge_number'] }}</b>
                        <span id="hero-badge-label" class="text-[11px] sm:text-xs text-slate-500 font-bold tracking-wide" data-content-field="badge_label">{{ $heroSlides[0]['badge_label'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<style>
.hero-heading-font { font-family: 'Fraunces', Georgia, serif; font-weight: 400; }
.hero-glass-left, .hero-glass-left p, .hero-glass-left span, .hero-glass-left a { font-family: 'Manrope', sans-serif; }
.hero-glass-left .hero-heading-font { font-family: 'Fraunces', Georgia, serif; }

/* Panel trái — nền sáng (mặc định trắng) */
.hero-glass-left--light .hero-eyebrow-line { background: #0195FE; }
.hero-glass-left--light .hero-eyebrow { color: #0195FE; }
.hero-glass-left--light .hero-title { color: #0f172a; }
.hero-glass-left--light .hero-heading-highlight {
    font-style: italic; font-weight: 300;
    background: linear-gradient(100deg, #0195FE, #5eb8ff 70%);
    -webkit-background-clip: text; background-clip: text; color: transparent;
}
.hero-glass-left--light .hero-subheading { color: #475569; }
.hero-glass-left--light .hero-btn-primary {
    background: #0195FE; color: #fff;
    box-shadow: 0 8px 24px rgba(1, 149, 254, 0.28);
}
.hero-glass-left--light .hero-btn-primary:hover { background: #0180d9; }
.hero-glass-left--light .hero-btn-secondary {
    border: 2px solid #0195FE; color: #0195FE; background: transparent;
}
.hero-glass-left--light .hero-btn-secondary:hover { background: rgba(1, 149, 254, 0.08); }
.hero-glass-left--light .hero-meta-row { border-color: #e2e8f0; }
.hero-glass-left--light .hero-meta-label { color: #64748b; }
.hero-glass-left--light .hero-meta-value { color: #0195FE; }

/* Panel trái — nền tối (khi admin đặt màu đậm) */
.hero-glass-left--dark .hero-eyebrow-line { background: #5eb8ff; }
.hero-glass-left--dark .hero-eyebrow { color: #5eb8ff; }
.hero-glass-left--dark .hero-title { color: #F6F4EE; }
.hero-glass-left--dark .hero-heading-highlight {
    font-style: italic; font-weight: 300;
    background: linear-gradient(100deg, #5eb8ff, #0195FE 65%);
    -webkit-background-clip: text; background-clip: text; color: transparent;
}
.hero-glass-left--dark .hero-subheading { color: #CBD8E3; }
.hero-glass-left--dark .hero-btn-primary { background: #0195FE; color: #fff; }
.hero-glass-left--dark .hero-btn-primary:hover { background: #0180d9; }
.hero-glass-left--dark .hero-btn-secondary {
    border: 2px solid rgba(255,255,255,0.4); color: #F6F4EE; background: transparent;
}
.hero-glass-left--dark .hero-btn-secondary:hover { border-color: #0195FE; color: #5eb8ff; background: rgba(1,149,254,0.12); }
.hero-glass-left--dark .hero-meta-row { border-color: rgba(255,255,255,0.15); }
.hero-glass-left--dark .hero-meta-label { color: rgba(255,255,255,0.5); }
.hero-glass-left--dark .hero-meta-value { color: #5eb8ff; }

.hero-glass-grid { min-height: 560px; }
@media (min-width: 1024px) { .hero-glass-grid { min-height: 660px; } }
@media (max-width: 1023px) {
    .hero-glass { overflow: visible; }
    .hero-glass-grid { min-height: 0; }
}
.hero-glass-right-clip {
    clip-path: polygon(6% 0, 100% 0, 100% 100%, 0% 100%);
    background: linear-gradient(160deg, #DCE9F1 0%, #B9D3E2 55%, #8FB6CC 100%);
}
@media (max-width: 1023px) { .hero-glass-right-clip { clip-path: none; } }

/* 4 mảnh kính bất đối xứng — mỗi ô chứa 1 ảnh sản phẩm */
.hero-facets { position: absolute; inset: 0; }
.hero-img-facet {
    position: absolute;
    overflow: hidden;
    filter: saturate(1.05);
    box-shadow: 0 8px 28px rgba(11, 27, 43, 0.12);
}
.hero-img-facet__img {
    position: absolute;
    inset: -8%;
    width: 116%;
    height: 116%;
    object-fit: cover;
}
.hero-img-facet-1 {
    width: 46%; height: 58%; top: 6%; left: 8%;
    clip-path: polygon(20% 0, 100% 8%, 86% 100%, 0 88%);
}
.hero-img-facet-2 {
    width: 38%; height: 46%; top: 2%; right: 6%;
    clip-path: polygon(0 0, 100% 10%, 92% 100%, 10% 92%);
}
.hero-img-facet-3 {
    width: 42%; height: 50%; bottom: 4%; right: 10%;
    clip-path: polygon(10% 0, 100% 14%, 90% 100%, 0 86%);
}
.hero-img-facet-4 {
    width: 30%; height: 34%; bottom: 6%; left: 14%;
    clip-path: polygon(0 12%, 88% 0, 100% 90%, 14% 100%);
}
@media (max-width: 1023px) {
    .hero-img-facet-1 { width: 48%; height: 52%; top: 4%; left: 4%; }
    .hero-img-facet-2 { width: 40%; height: 42%; top: 2%; right: 4%; }
    .hero-img-facet-3 { width: 44%; height: 46%; bottom: 3%; right: 6%; }
    .hero-img-facet-4 { width: 32%; height: 32%; bottom: 4%; left: 8%; }
}
.hero-lead-line { width: 100%; height: 100%; z-index: 6; pointer-events: none; }
@media (max-width: 1023px) { .hero-lead-line { display: none; } }
.hero-rivet { position: absolute; width: 9px; height: 9px; border-radius: 50%; background: #0195FE; box-shadow: 0 0 0 3px rgba(1,149,254,0.25); z-index: 7; }
@media (max-width: 1023px) { .hero-rivet { display: none; } }
.hero-badge { position: absolute; left: 1rem; bottom: 1rem; z-index: 25; max-width: calc(100% - 2rem); }
@media (min-width: 1024px) {
    .hero-badge { left: -3%; bottom: 10%; max-width: none; }
}
.hero-slide-index { font-family: 'Fraunces', Georgia, serif; font-size: 13px; color: #475569; letter-spacing: 0.05em; z-index: 20; }
.hero-slide-index b { color: #0195FE; font-weight: 600; }
.hero-arrow { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.92); display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 6px 18px rgba(1,149,254,0.15); font-size: 16px; line-height: 1; color: #0195FE; transition: .2s; border: none; }
.hero-arrow:hover { background: #0195FE; color: #fff; }
.hero-dot { width: 7px; height: 7px; border-radius: 50%; background: rgba(1,149,254,0.25); cursor: pointer; transition: .2s; border: none; padding: 0; }
.hero-dot.is-active { background: #0195FE; width: 22px; border-radius: 4px; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var slides = @json($heroSlidesJs);
    if (!Array.isArray(slides) || slides.length < 2) return;

    var isEditMode = @json(isset($editMode) && $editMode ? true : false);
    var eyebrowEl = document.getElementById('hero-eyebrow');
    var headingEl = document.getElementById('hero-heading-main');
    var highlightEl = document.getElementById('hero-heading-highlight');
    var subheadingEl = document.getElementById('hero-subheading');
    var badgeNumEl = document.getElementById('hero-badge-num');
    var badgeLabelEl = document.getElementById('hero-badge-label');
    var idxEl = document.getElementById('hero-slide-idx');
    var photos = document.querySelectorAll('.hero-slide-photo');
    var dots = document.querySelectorAll('.hero-dot');
    var arrows = document.querySelectorAll('[data-hero-dir]');
    var root = document.querySelector('.hero-glass');

    var total = slides.length;
    var current = 0;
    var timer = null;
    var intervalMs = 6000;

    function render(index) {
        var s = slides[index] || slides[0];
        if (eyebrowEl) eyebrowEl.textContent = s.tagline || '';
        if (headingEl) headingEl.textContent = s.heading || '';
        if (highlightEl) highlightEl.textContent = s.highlight || '';
        if (subheadingEl) subheadingEl.textContent = s.subheading || '';
        if (badgeNumEl) badgeNumEl.textContent = s.badgeNumber || '';
        if (badgeLabelEl) badgeLabelEl.textContent = s.badgeLabel || '';
        if (idxEl) idxEl.textContent = String(index + 1).padStart(2, '0');
        photos.forEach(function (photo) {
            var active = parseInt(photo.dataset.heroSlide, 10) === index;
            photo.classList.toggle('opacity-100', active);
            photo.classList.toggle('z-10', active);
            photo.classList.toggle('opacity-0', !active);
            photo.classList.toggle('z-0', !active);
        });
        dots.forEach(function (dot) {
            var active = parseInt(dot.dataset.heroDot, 10) === index;
            dot.classList.toggle('is-active', active);
        });
    }

    function goTo(index) {
        current = (index + total) % total;
        render(current);
    }

    function startAuto() {
        if (isEditMode) return;
        clearInterval(timer);
        timer = setInterval(function () { goTo(current + 1); }, intervalMs);
    }

    function pauseAuto() {
        clearInterval(timer);
        timer = null;
    }

    arrows.forEach(function (btn) {
        btn.addEventListener('click', function () {
            pauseAuto();
            goTo(current + parseInt(this.dataset.heroDir, 10));
            startAuto();
        });
    });
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            var index = parseInt(this.dataset.heroDot, 10);
            if (Number.isNaN(index) || index === current) return;
            pauseAuto();
            goTo(index);
            startAuto();
        });
    });
    if (root) {
        root.addEventListener('mouseenter', pauseAuto);
        root.addEventListener('mouseleave', function () { if (!timer) startAuto(); });
    }

    startAuto();
});
</script>

@php
    // Tổng số lượng đã bán (chỉ tính đơn đã thanh toán và không bị hủy)
    $soldQuantitySub = \Illuminate\Support\Facades\DB::table('order_items')
        ->join('orders', 'orders.id', '=', 'order_items.order_id')
        ->selectRaw('COALESCE(SUM(order_items.quantity), 0)')
        ->whereColumn('order_items.product_id', 'products.id')
        ->where('orders.payment_status', 'paid')
        ->where('orders.status', '!=', 'cancelled');

    $bestsellers = \App\Models\Product::with(['shop', 'template'])
        ->availableForDisplay()
        ->select('products.*')
        ->selectSub($soldQuantitySub, 'sold_quantity')
        ->having('sold_quantity', '>', 0)
        ->orderByDesc('sold_quantity')
        ->orderBy('created_at', 'desc')
        ->limit(8)
        ->get();

    // Nếu chưa đủ 8 sản phẩm đã bán, bổ sung bằng sản phẩm mới nhất để không trống section
    if ($bestsellers->count() < 8) {
        $fallbackProducts = \App\Models\Product::with(['shop', 'template'])
            ->availableForDisplay()
            ->whereNotIn('products.id', $bestsellers->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(8 - $bestsellers->count())
            ->get();
        $bestsellers = $bestsellers->concat($fallbackProducts);
    }
    $newArrivals = \App\Models\Product::with(['shop', 'template'])
        ->availableForDisplay()
        ->orderBy('created_at', 'desc')
        ->limit(8)
        ->get();
    $homeGtagItems = $bestsellers->values()->map(function ($product, $index) {
        $categoryName = optional(($product->categories ?? collect())->first())->name
            ?? optional(($product->collections ?? collect())->first())->name;

        return [
            'item_id' => $product->sku ?? $product->id,
            'item_name' => $product->name,
            'item_category' => $categoryName,
            'price' => round((float) ($product->price ?? $product->base_price ?? 0), 2),
            'quantity' => 1,
            'index' => $index + 1,
        ];
    })->all();
    $featuredCollections = \App\Models\Collection::where('status', 'active')
        ->where('admin_approved', true)
        ->where('featured', true)
        ->orderBy('sort_order')
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();
    $whyChoose = content_block('home.why_choose', [
        'title' => 'Why Choose Our Press-on Nails?',
        'bg_color' => null,
        'card1_title' => 'Salon Quality',
        'card1_body' => 'Durable, non-chipping, and high-gloss finish that lasts up to 2 weeks.',
        'card2_title' => 'Reusable',
        'card2_body' => 'Sustainable beauty that can be worn multiple times with proper care.',
        'card3_title' => 'Easy Application',
        'card3_body' => 'Apply in less than 10 minutes with our professional adhesive kits.',
        'card4_title' => 'Custom Designs',
        'card4_body' => 'Unique hand-painted looks designed by top global nail artists.',
    ]);
    $bestsellersBlock = content_block('home.bestsellers', [
        'eyebrow' => 'Best Sellers',
        'heading' => 'Shop Our',
        'heading_highlight' => 'Bestsellers',
        'subheading' => 'The most-loved styles by our community',
        'view_all_label' => 'View All Sets',
        'bg_color' => null,
    ]);
    $newArrivalsBlock = content_block('home.new_arrivals', [
        'eyebrow' => 'Just In',
        'heading' => 'New',
        'heading_highlight' => 'Arrivals',
        'subheading' => 'Fresh styles just added to our collection',
        'view_all_label' => 'View All New',
        'bg_color' => null,
    ]);

    $normalizeProductSectionHeading = function (array $block, array $legacyMap, string $defaultEyebrow): array {
        if (empty($block['eyebrow'])) {
            $block['eyebrow'] = $defaultEyebrow;
        }
        if (!empty($block['heading_highlight'])) {
            return $block;
        }
        $full = trim((string) ($block['heading'] ?? ''));
        if ($full !== '' && isset($legacyMap[$full])) {
            $block['heading'] = $legacyMap[$full]['heading'];
            $block['heading_highlight'] = $legacyMap[$full]['heading_highlight'];
        }
        return $block;
    };
    $bestsellersBlock = $normalizeProductSectionHeading($bestsellersBlock, [
        'Shop Our Bestsellers' => ['heading' => 'Shop Our', 'heading_highlight' => 'Bestsellers'],
    ], 'Best Sellers');
    $newArrivalsBlock = $normalizeProductSectionHeading($newArrivalsBlock, [
        'New Arrivals' => ['heading' => 'New', 'heading_highlight' => 'Arrivals'],
    ], 'Just In');
    $collectionsBlock = content_block('home.collections', [
        'heading' => 'Explore Our Collections',
        'bg_color' => null,
    ]);
    $whyChooseSchema = [
        ['key' => 'title', 'label' => 'Tiêu đề', 'type' => 'text'],
        ['key' => 'bg_color', 'label' => 'Màu nền section (HEX, vd: #f8fafc) – để trống dùng mặc định', 'type' => 'text'],
        ['key' => 'card1_title', 'label' => 'Thẻ 1 — tiêu đề', 'type' => 'text'],
        ['key' => 'card1_body', 'label' => 'Thẻ 1 — mô tả', 'type' => 'textarea'],
        ['key' => 'card2_title', 'label' => 'Thẻ 2 — tiêu đề', 'type' => 'text'],
        ['key' => 'card2_body', 'label' => 'Thẻ 2 — mô tả', 'type' => 'textarea'],
        ['key' => 'card3_title', 'label' => 'Thẻ 3 — tiêu đề', 'type' => 'text'],
        ['key' => 'card3_body', 'label' => 'Thẻ 3 — mô tả', 'type' => 'textarea'],
        ['key' => 'card4_title', 'label' => 'Thẻ 4 — tiêu đề', 'type' => 'text'],
        ['key' => 'card4_body', 'label' => 'Thẻ 4 — mô tả', 'type' => 'textarea'],
    ];
    $bestsellersSchema = [
        ['key' => 'eyebrow', 'label' => 'Dòng phụ (eyebrow)', 'type' => 'text'],
        ['key' => 'heading', 'label' => 'Tiêu đề chính', 'type' => 'text'],
        ['key' => 'heading_highlight', 'label' => 'Tiêu đề highlight (in nghiêng)', 'type' => 'text'],
        ['key' => 'subheading', 'label' => 'Mô tả', 'type' => 'text'],
        ['key' => 'view_all_label', 'label' => 'Chữ nút View All', 'type' => 'text'],
        ['key' => 'bg_color', 'label' => 'Màu nền section (HEX) – để trống dùng mặc định', 'type' => 'text'],
    ];
    $newArrivalsSchema = [
        ['key' => 'eyebrow', 'label' => 'Dòng phụ (eyebrow)', 'type' => 'text'],
        ['key' => 'heading', 'label' => 'Tiêu đề chính', 'type' => 'text'],
        ['key' => 'heading_highlight', 'label' => 'Tiêu đề highlight (in nghiêng)', 'type' => 'text'],
        ['key' => 'subheading', 'label' => 'Mô tả', 'type' => 'text'],
        ['key' => 'view_all_label', 'label' => 'Chữ nút View All', 'type' => 'text'],
        ['key' => 'bg_color', 'label' => 'Màu nền section (HEX) – để trống dùng mặc định', 'type' => 'text'],
    ];
    $collectionsSchema = [
        ['key' => 'heading', 'label' => 'Tiêu đề', 'type' => 'text'],
        ['key' => 'bg_color', 'label' => 'Màu nền section (HEX) – để trống dùng mặc định', 'type' => 'text'],
    ];
    $customerFavoritesBlock = content_block('home.customer_favorites', [
        'eyebrow' => 'BluLavelle Community',
        'heading' => 'Customer',
        'heading_highlight' => 'Favorites',
        'view_all_label' => 'View All',
        'view_all_url' => '#',
        'bg_color' => null,
    ]);
    $defaultCfTabs = [
        ['key' => 'card1', 'label' => 'lynhtran', 'avatar_url' => null, 'image_url' => null],
        ['key' => 'card2', 'label' => 'may.nails', 'avatar_url' => null, 'image_url' => null],
        ['key' => 'card3', 'label' => 'blulavelle', 'avatar_url' => null, 'image_url' => null],
        ['key' => 'card4', 'label' => 'nailista', 'avatar_url' => null, 'image_url' => null],
        ['key' => 'card5', 'label' => 'thuyhan', 'avatar_url' => null, 'image_url' => null],
    ];
    $cfTabs = $customerFavoritesBlock['tabs'] ?? null;
    if (!is_array($cfTabs) || count($cfTabs) === 0) {
        $cfTabs = $defaultCfTabs;
    } else {
        $cfTabs = collect($cfTabs)->map(function ($t, $i) use ($defaultCfTabs) {
            $t = is_array($t) ? $t : [];
            $key = $t['key'] ?? ($defaultCfTabs[$i]['key'] ?? 'card' . ($i + 1));
            $fallback = collect($defaultCfTabs)->firstWhere('key', $key) ?? ($defaultCfTabs[$i] ?? ['key' => $key, 'label' => 'user', 'avatar_url' => null, 'image_url' => null]);
            return array_merge($fallback, $t);
        })->values()->all();
    }
    while (count($cfTabs) < 5) {
        $i = count($cfTabs);
        $cfTabs[] = $defaultCfTabs[$i] ?? ['key' => 'card' . ($i + 1), 'label' => 'user', 'avatar_url' => null, 'image_url' => null];
    }
    $cfTabs = array_slice($cfTabs, 0, 5);
    $customerFavoritesBlock['tabs'] = $cfTabs;
    $cfItems = collect($cfTabs)->map(function ($t) {
        return [
            'username' => $t['label'] ?? '',
            'avatar_url' => $t['avatar_url'] ?? null,
            'image_url' => $t['image_url'] ?? null,
        ];
    })->all();
    $customerFavoritesSchema = [
        ['key' => 'eyebrow', 'label' => 'Dòng phụ (eyebrow)', 'type' => 'text'],
        ['key' => 'heading', 'label' => 'Tiêu đề chính', 'type' => 'text'],
        ['key' => 'heading_highlight', 'label' => 'Tiêu đề highlight (in nghiêng)', 'type' => 'text'],
        ['key' => 'view_all_label', 'label' => 'Chữ link Xem tất cả', 'type' => 'text'],
        ['key' => 'view_all_url', 'label' => 'Link Xem tất cả', 'type' => 'url'],
        ['key' => 'bg_color', 'label' => 'Màu nền section (HEX) – để trống dùng mặc định', 'type' => 'text'],
        ['key' => 'tabs', 'type' => 'community_cards', 'label' => '5 thẻ GIF cộng đồng', 'tabKeys' => ['card1', 'card2', 'card3', 'card4', 'card5']],
    ];
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof dataLayer !== 'undefined') {
        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            event: 'view_item_list',
            ecommerce: {
                item_list_name: 'Home Bestsellers',
                items: @json($homeGtagItems)
            }
        });
    }
});
</script>

@once
<style>
.rv-heading-highlight {
    font-family: 'Fraunces', Georgia, serif;
    background: linear-gradient(100deg, #0195FE, #5eb8ff 70%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
</style>
@endonce

<!-- Shop Our Bestsellers -->
<section class="px-4 sm:px-6 lg:px-20 py-16 sm:py-20 md:py-24 bg-white" data-content-block="home.bestsellers" @if(!empty($bestsellersBlock['bg_color'])) style="background-color: {{ $bestsellersBlock['bg_color'] }};" @endif>
    @if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
    <div class="max-w-7xl mx-auto flex justify-end mb-2">
        <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.bestsellers">Chỉnh sửa</button>
    </div>
    @endif
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5 sm:gap-6 mb-8 sm:mb-10">
            <div>
                <p class="flex items-center gap-2 text-[11px] sm:text-xs font-bold uppercase tracking-[0.22em] text-[#0195FE] mb-3">
                    <span class="w-4 h-px bg-[#0195FE]"></span>
                    <span data-content-field="eyebrow">{{ $bestsellersBlock['eyebrow'] ?? 'Best Sellers' }}</span>
                </p>
                <h2 class="text-3xl sm:text-4xl lg:text-[2.75rem] leading-tight">
                    <span class="font-black text-slate-900" data-content-field="heading">{{ $bestsellersBlock['heading'] ?? 'Shop Our' }}</span>
                    <span class="rv-heading-highlight font-normal italic" data-content-field="heading_highlight">{{ $bestsellersBlock['heading_highlight'] ?? 'Bestsellers' }}</span>
                </h2>
                <p class="text-slate-600 mt-3 text-sm sm:text-base max-w-xl" data-content-field="subheading">{{ $bestsellersBlock['subheading'] ?? 'The most-loved styles by our community' }}</p>
            </div>
            <a class="inline-flex items-center gap-2 text-[11px] sm:text-xs font-bold uppercase tracking-[0.18em] text-[#0195FE] hover:underline underline-offset-4 shrink-0 self-start sm:self-auto" href="{{ route('products.index', ['sort' => 'bestsellers']) }}">
                <span data-content-field="view_all_label">{{ $bestsellersBlock['view_all_label'] ?? 'View All Sets' }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 gap-6 md:grid-cols-3 xl:grid-cols-4">
            @foreach($bestsellers as $product)
                <x-product-card :product="$product" :show-quick-view="true" item-list-name="Home Bestsellers" />
            @endforeach
        </div>
    </div>
</section>

<!-- Why Choose Our Press-on Nails? -->
<section class="px-4 sm:px-6 lg:px-20 py-8 sm:py-16 md:py-20 lg:py-24 bg-slate-50 overflow-x-hidden" data-content-block="home.why_choose" @if(!empty($whyChoose['bg_color'])) style="background-color: {{ $whyChoose['bg_color'] }};" @endif>
    @if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
    <div class="max-w-7xl mx-auto flex justify-end mb-2">
        <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.why_choose">Chỉnh sửa</button>
    </div>
    @endif
    <div class="max-w-7xl mx-auto text-center mb-5 sm:mb-12 lg:mb-16 px-1">
        <h2 class="text-xl sm:text-3xl lg:text-4xl font-black text-slate-900 mb-2 sm:mb-4 leading-tight" data-content-field="title">{{ $whyChoose['title'] ?? 'Why Choose Our Press-on Nails?' }}</h2>
        <div class="w-12 sm:w-20 h-1 bg-primary mx-auto rounded-full"></div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-2.5 md:gap-6 lg:gap-8 max-w-7xl mx-auto">
        <div class="bg-white p-3 md:p-6 lg:p-8 rounded-xl md:rounded-2xl shadow-none md:shadow-sm border border-primary/5 md:hover:border-primary/30 transition-all group flex flex-col items-center md:items-stretch text-center md:text-left gap-2 md:gap-0">
            <div class="w-9 h-9 md:w-14 md:h-14 bg-primary/10 rounded-lg md:rounded-xl flex items-center justify-center text-primary-fg md:mb-6 group-hover:bg-primary group-hover:text-white transition-all shrink-0">
                <svg class="w-4 h-4 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
            </div>
            <h3 class="text-xs md:text-xl font-bold md:mb-3 text-slate-900 leading-snug" data-content-field="card1_title">{{ $whyChoose['card1_title'] ?? 'Salon Quality' }}</h3>
            <p class="text-[11px] md:text-base text-slate-600 leading-snug md:leading-relaxed line-clamp-3 md:line-clamp-none" data-content-field="card1_body">{{ $whyChoose['card1_body'] ?? '' }}</p>
        </div>
        <div class="bg-white p-3 md:p-6 lg:p-8 rounded-xl md:rounded-2xl shadow-none md:shadow-sm border border-primary/5 md:hover:border-primary/30 transition-all group flex flex-col items-center md:items-stretch text-center md:text-left gap-2 md:gap-0">
            <div class="w-9 h-9 md:w-14 md:h-14 bg-primary/10 rounded-lg md:rounded-xl flex items-center justify-center text-primary-fg md:mb-6 group-hover:bg-primary group-hover:text-white transition-all shrink-0">
                <svg class="w-4 h-4 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </div>
            <h3 class="text-xs md:text-xl font-bold md:mb-3 text-slate-900 leading-snug" data-content-field="card2_title">{{ $whyChoose['card2_title'] ?? 'Reusable' }}</h3>
            <p class="text-[11px] md:text-base text-slate-600 leading-snug md:leading-relaxed line-clamp-3 md:line-clamp-none" data-content-field="card2_body">{{ $whyChoose['card2_body'] ?? '' }}</p>
        </div>
        <div class="bg-white p-3 md:p-6 lg:p-8 rounded-xl md:rounded-2xl shadow-none md:shadow-sm border border-primary/5 md:hover:border-primary/30 transition-all group flex flex-col items-center md:items-stretch text-center md:text-left gap-2 md:gap-0">
            <div class="w-9 h-9 md:w-14 md:h-14 bg-primary/10 rounded-lg md:rounded-xl flex items-center justify-center text-primary-fg md:mb-6 group-hover:bg-primary group-hover:text-white transition-all shrink-0">
                <svg class="w-4 h-4 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
            </div>
            <h3 class="text-xs md:text-xl font-bold md:mb-3 text-slate-900 leading-snug" data-content-field="card3_title">{{ $whyChoose['card3_title'] ?? 'Easy Application' }}</h3>
            <p class="text-[11px] md:text-base text-slate-600 leading-snug md:leading-relaxed line-clamp-3 md:line-clamp-none" data-content-field="card3_body">{{ $whyChoose['card3_body'] ?? '' }}</p>
        </div>
        <div class="bg-white p-3 md:p-6 lg:p-8 rounded-xl md:rounded-2xl shadow-none md:shadow-sm border border-primary/5 md:hover:border-primary/30 transition-all group flex flex-col items-center md:items-stretch text-center md:text-left gap-2 md:gap-0">
            <div class="w-9 h-9 md:w-14 md:h-14 bg-primary/10 rounded-lg md:rounded-xl flex items-center justify-center text-primary-fg md:mb-6 group-hover:bg-primary group-hover:text-white transition-all shrink-0">
                <svg class="w-4 h-4 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path></svg>
            </div>
            <h3 class="text-xs md:text-xl font-bold md:mb-3 text-slate-900 leading-snug" data-content-field="card4_title">{{ $whyChoose['card4_title'] ?? 'Custom Designs' }}</h3>
            <p class="text-[11px] md:text-base text-slate-600 leading-snug md:leading-relaxed line-clamp-3 md:line-clamp-none" data-content-field="card4_body">{{ $whyChoose['card4_body'] ?? '' }}</p>
        </div>
    </div>
</section>

<x-customer-favorites-gallery
    :eyebrow="$customerFavoritesBlock['eyebrow'] ?? 'BluLavelle Community'"
    :heading="$customerFavoritesBlock['heading'] ?? 'Customer'"
    :heading-highlight="$customerFavoritesBlock['heading_highlight'] ?? 'Favorites'"
    :view-all-label="$customerFavoritesBlock['view_all_label'] ?? 'View All'"
    :view-all-url="$customerFavoritesBlock['view_all_url'] ?? '#'"
    :items="$cfItems"
    :can-edit="isset($canEdit) && $canEdit"
    :edit-mode="isset($editMode) && $editMode"
    :bg-color="$customerFavoritesBlock['bg_color'] ?? null"
/>

<!-- New Arrivals -->
<section class="px-4 sm:px-6 lg:px-20 py-16 sm:py-20 md:py-24 bg-slate-50" data-content-block="home.new_arrivals" @if(!empty($newArrivalsBlock['bg_color'])) style="background-color: {{ $newArrivalsBlock['bg_color'] }};" @endif>
    @if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
    <div class="max-w-7xl mx-auto flex justify-end mb-2">
        <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.new_arrivals">Chỉnh sửa</button>
    </div>
    @endif
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5 sm:gap-6 mb-8 sm:mb-10">
            <div>
                <p class="flex items-center gap-2 text-[11px] sm:text-xs font-bold uppercase tracking-[0.22em] text-[#0195FE] mb-3">
                    <span class="w-4 h-px bg-[#0195FE]"></span>
                    <span data-content-field="eyebrow">{{ $newArrivalsBlock['eyebrow'] ?? 'Just In' }}</span>
                </p>
                <h2 class="text-3xl sm:text-4xl lg:text-[2.75rem] leading-tight">
                    <span class="font-black text-slate-900" data-content-field="heading">{{ $newArrivalsBlock['heading'] ?? 'New' }}</span>
                    <span class="rv-heading-highlight font-normal italic" data-content-field="heading_highlight">{{ $newArrivalsBlock['heading_highlight'] ?? 'Arrivals' }}</span>
                </h2>
                <p class="text-slate-600 mt-3 text-sm sm:text-base max-w-xl" data-content-field="subheading">{{ $newArrivalsBlock['subheading'] ?? 'Fresh styles just added to our collection' }}</p>
            </div>
            <a class="inline-flex items-center gap-2 text-[11px] sm:text-xs font-bold uppercase tracking-[0.18em] text-[#0195FE] hover:underline underline-offset-4 shrink-0 self-start sm:self-auto" href="{{ route('products.index', ['sort' => 'newest']) }}">
                <span data-content-field="view_all_label">{{ $newArrivalsBlock['view_all_label'] ?? 'View All New' }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 gap-6 md:grid-cols-3 xl:grid-cols-4">
            @foreach($newArrivals as $product)
                <x-product-card :product="$product" :show-quick-view="true" item-list-name="Home New Arrivals" />
            @endforeach
        </div>
    </div>
</section>

<!-- Explore Our Collections -->
<section class="py-16 sm:py-20 md:py-24 bg-white overflow-x-hidden" data-content-block="home.collections" @if(!empty($collectionsBlock['bg_color'])) style="background-color: {{ $collectionsBlock['bg_color'] }};" @endif>
    @if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
    <div class="px-4 sm:px-6 lg:px-20 max-w-7xl mx-auto flex justify-end mb-2">
        <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.collections">Chỉnh sửa</button>
    </div>
    @endif
    <div class="px-4 sm:px-6 lg:px-20 max-w-7xl mx-auto">
        <div class="text-center mb-8 sm:mb-16">
            <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-4" data-content-field="heading">{{ $collectionsBlock['heading'] ?? 'Explore Our Collections' }}</h2>
            <div class="w-20 h-1 bg-primary mx-auto rounded-full"></div>
        </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-20 max-w-7xl mx-auto">
        @if($featuredCollections->isEmpty())
            <p class="text-center text-slate-500">No collections available yet.</p>
        @else
        <div id="collections-spotlight" class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-8 items-stretch">
            {{-- Spotlight: 1 collection lớn, tự đổi slide --}}
            <div class="lg:col-span-8 relative min-h-0">
                <div class="relative overflow-hidden rounded-3xl aspect-[4/5] sm:aspect-[5/6] lg:aspect-[16/11] bg-slate-100 shadow-xl shadow-slate-200/80 ring-1 ring-slate-900/5">
                    @foreach($featuredCollections as $idx => $collection)
                        <a href="{{ route('collections.show', $collection->slug) }}"
                           class="collection-spotlight-panel absolute inset-0 transition-all duration-700 ease-out {{ $idx === 0 ? 'opacity-100 z-10 pointer-events-auto' : 'opacity-0 z-0 pointer-events-none' }}"
                           data-spotlight="{{ $idx }}"
                           aria-hidden="{{ $idx === 0 ? 'false' : 'true' }}">
                            @if($collection->image)
                                <img alt="{{ $collection->name }}"
                                     class="collection-spotlight-image absolute inset-0 w-full h-full object-cover {{ $idx === 0 ? 'scale-100' : 'scale-105' }}"
                                     src="{{ $collection->image }}"
                                     loading="{{ $idx === 0 ? 'eager' : 'lazy' }}">
                            @else
                                <div class="absolute inset-0 bg-gradient-to-br from-primary/40 via-primary/20 to-primary/5"></div>
                            @endif

                            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-black/5"></div>

                            <div class="absolute top-4 left-4 sm:top-6 sm:left-6 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-white/95 backdrop-blur text-slate-900 text-xs font-extrabold shadow-sm">
                                    {{ $collection->active_products_count }} items
                                </span>
                                @if($collection->featured)
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-primary text-white text-xs font-extrabold shadow-sm">
                                        Featured
                                    </span>
                                @endif
                            </div>

                            <div class="absolute inset-x-0 bottom-0 p-5 sm:p-8 lg:p-10">
                                <p class="text-white/70 text-xs sm:text-sm font-bold uppercase tracking-[0.25em] mb-2 sm:mb-3">Curated Collection</p>
                                <h3 class="font-serif text-2xl sm:text-4xl lg:text-5xl text-white mb-2 sm:mb-3 leading-tight line-clamp-2">
                                    {{ $collection->name }}
                                </h3>
                                <p class="text-white/85 text-sm sm:text-base max-w-xl leading-relaxed line-clamp-2 mb-5 sm:mb-6">
                                    {{ $collection->description ?: 'Curated sets designed to match your mood.' }}
                                </p>
                                <span class="inline-flex items-center gap-2 px-5 sm:px-6 py-3 bg-white text-slate-900 font-extrabold rounded-xl shadow-lg group-hover:bg-primary group-hover:text-white transition-colors text-sm sm:text-base">
                                    Shop the Collection
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </span>
                            </div>
                        </a>
                    @endforeach

                    @if($featuredCollections->count() > 1)
                        <div class="absolute top-4 right-4 sm:top-6 sm:right-6 z-20 flex items-center gap-2">
                            <button type="button" class="collection-spotlight-prev w-10 h-10 rounded-full bg-black/35 hover:bg-black/55 backdrop-blur-sm text-white flex items-center justify-center transition-colors" aria-label="Previous collection">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            </button>
                            <button type="button" class="collection-spotlight-next w-10 h-10 rounded-full bg-black/35 hover:bg-black/55 backdrop-blur-sm text-white flex items-center justify-center transition-colors" aria-label="Next collection">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 z-20 h-1 bg-white/20">
                            <div id="collection-spotlight-progress" class="h-full bg-primary transition-none" style="width: 0%"></div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Thumbnail chọn collection — hàng dọc (mobile + desktop) --}}
            @if($featuredCollections->count() > 1)
            <div class="lg:col-span-4 flex flex-col gap-3 sm:gap-4">
                <p class="hidden lg:block text-xs font-extrabold uppercase tracking-[0.2em] text-slate-400 mb-1">Pick a mood</p>
                <div class="flex flex-col gap-3 sm:gap-4">
                    @foreach($featuredCollections as $idx => $collection)
                        <button type="button"
                                class="collection-spotlight-thumb group text-left rounded-2xl overflow-hidden border-2 transition-all duration-300 {{ $idx === 0 ? 'border-primary shadow-lg shadow-primary/15 ring-2 ring-primary/20' : 'border-transparent hover:border-primary/30' }}"
                                data-spotlight-thumb="{{ $idx }}"
                                aria-label="Show {{ $collection->name }}"
                                aria-pressed="{{ $idx === 0 ? 'true' : 'false' }}">
                            <div class="flex items-center gap-3 sm:gap-4 p-2 sm:p-3 bg-white">
                                <div class="relative w-16 h-20 sm:w-20 sm:h-24 lg:w-24 lg:h-28 shrink-0 rounded-xl overflow-hidden bg-slate-100">
                                    @if($collection->image)
                                        <img alt="" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" src="{{ $collection->image }}" loading="lazy">
                                    @else
                                        <div class="w-full h-full bg-gradient-to-br from-primary/30 to-primary/5"></div>
                                    @endif
                                    <div class="absolute inset-0 bg-primary/0 group-hover:bg-primary/10 transition-colors"></div>
                                </div>
                                <div class="min-w-0 flex-1 py-1">
                                    <h4 class="font-serif text-sm sm:text-lg text-slate-900 leading-snug line-clamp-2 group-hover:text-primary-fg transition-colors">
                                        {{ $collection->name }}
                                    </h4>
                                    <p class="text-xs sm:text-sm text-slate-500 mt-1">{{ $collection->active_products_count }} items</p>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>

    <div class="px-4 sm:px-6 lg:px-20 max-w-7xl mx-auto text-center mt-8">
        <a href="{{ route('collections.index') }}" class="text-primary-fg font-bold inline-flex items-center gap-2 hover:underline underline-offset-4">
            View All Collections
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
        </a>
    </div>
</section>
<style>
@keyframes collectionKenBurns {
    from { transform: scale(1); }
    to { transform: scale(1.08); }
}
.collection-spotlight-panel.is-active .collection-spotlight-image {
    animation: collectionKenBurns 6s ease-out forwards;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('collections-spotlight');
    if (!root) return;

    var panels = root.querySelectorAll('.collection-spotlight-panel');
    var thumbs = root.querySelectorAll('.collection-spotlight-thumb');
    var prevBtn = root.querySelector('.collection-spotlight-prev');
    var nextBtn = root.querySelector('.collection-spotlight-next');
    var progress = document.getElementById('collection-spotlight-progress');
    var total = panels.length;
    if (total < 2) return;

    var current = 0;
    var intervalMs = 5500;
    var timer = null;
    var progressFrame = null;
    var progressStart = 0;

    function setThumbState(index) {
        thumbs.forEach(function (thumb, i) {
            var active = i === index;
            thumb.classList.toggle('border-primary', active);
            thumb.classList.toggle('shadow-lg', active);
            thumb.classList.toggle('shadow-primary/15', active);
            thumb.classList.toggle('ring-2', active);
            thumb.classList.toggle('ring-primary/20', active);
            thumb.classList.toggle('border-transparent', !active);
            thumb.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function setPanelState(index) {
        panels.forEach(function (panel, i) {
            var active = i === index;
            var image = panel.querySelector('.collection-spotlight-image');
            panel.classList.toggle('opacity-100', active);
            panel.classList.toggle('z-10', active);
            panel.classList.toggle('pointer-events-auto', active);
            panel.classList.toggle('opacity-0', !active);
            panel.classList.toggle('z-0', !active);
            panel.classList.toggle('pointer-events-none', !active);
            panel.classList.toggle('is-active', active);
            panel.setAttribute('aria-hidden', active ? 'false' : 'true');
            if (image) {
                image.classList.toggle('scale-100', active);
                image.classList.toggle('scale-105', !active);
                if (active) {
                    image.style.animation = 'none';
                    void image.offsetWidth;
                    image.style.animation = '';
                }
            }
        });
        setThumbState(index);
    }

    function stopProgress() {
        if (progressFrame) {
            cancelAnimationFrame(progressFrame);
            progressFrame = null;
        }
        if (progress) {
            progress.style.transition = 'none';
            progress.style.width = '0%';
        }
    }

    function startProgress() {
        if (!progress) return;
        stopProgress();
        progressStart = performance.now();
        function tick(now) {
            var elapsed = now - progressStart;
            var pct = Math.min(100, (elapsed / intervalMs) * 100);
            progress.style.width = pct + '%';
            if (pct < 100) {
                progressFrame = requestAnimationFrame(tick);
            }
        }
        progressFrame = requestAnimationFrame(tick);
    }

    function goTo(index) {
        current = (index + total) % total;
        setPanelState(current);
        startProgress();
    }

    function startAuto() {
        clearInterval(timer);
        timer = setInterval(function () {
            goTo(current + 1);
        }, intervalMs);
        startProgress();
    }

    function pauseAuto() {
        clearInterval(timer);
        timer = null;
        stopProgress();
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            pauseAuto();
            goTo(current - 1);
            startAuto();
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            pauseAuto();
            goTo(current + 1);
            startAuto();
        });
    }
    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var index = parseInt(this.dataset.spotlightThumb, 10);
            if (Number.isNaN(index) || index === current) return;
            pauseAuto();
            goTo(index);
            startAuto();
        });
    });

    root.addEventListener('mouseenter', pauseAuto);
    root.addEventListener('mouseleave', function () {
        if (!timer) startAuto();
    });

    setPanelState(0);
    startAuto();
});
</script>

@php
    $seeItBlock = content_block('home.see_it_in_action', [
        'heading' => 'See It In',
        'heading_highlight' => 'Action.',
        'subheading' => 'No matter the style, your nails always fit perfectly.',
        'panel_title' => 'Not sure about your size?',
        'panel_body' => 'Use our Sizing Kit to measure accurately before ordering, ensuring a perfect fit for all 10 nails.',
        'panel_cta_label' => 'View Sizing Kit',
        'panel_cta_url' => route('sizing-kit.index'),
        'feature1' => '10 minutes for a full application',
        'feature2' => 'No filing, no extra glue required',
        'feature3' => 'Reusable 20+ times with proper care',
    ]);
    $defaultSeeItTabs = [
        ['key' => 'nail', 'label' => 'Nail', 'image_url' => null, 'video_url' => null],
        ['key' => 'box', 'label' => 'Box', 'image_url' => null, 'video_url' => null],
        ['key' => 'arm', 'label' => 'Arm', 'image_url' => null, 'video_url' => null],
        ['key' => 'back', 'label' => 'Back', 'image_url' => null, 'video_url' => null],
    ];
    $seeItTabs = $seeItBlock['tabs'] ?? null;
    if (!is_array($seeItTabs) || count($seeItTabs) === 0) {
        $seeItTabs = $defaultSeeItTabs;
    } else {
        $seeItTabs = collect($seeItTabs)->map(function ($t) use ($defaultSeeItTabs) {
            $t = is_array($t) ? $t : [];
            $key = $t['key'] ?? null;
            $key = is_string($key) && $key !== '' ? $key : null;
            $fallback = collect($defaultSeeItTabs)->firstWhere('key', $key) ?? ['key' => $key ?? \Illuminate\Support\Str::random(6), 'label' => 'Tab', 'image_url' => null, 'video_url' => null];
            return array_merge($fallback, $t);
        })->values()->all();
    }
    $seeItBlock['tabs'] = $seeItTabs;
    $seeItSchema = [
        ['key' => 'heading', 'label' => 'Tiêu đề chính', 'type' => 'text'],
        ['key' => 'heading_highlight', 'label' => 'Tiêu đề highlight (in nghiêng)', 'type' => 'text'],
        ['key' => 'subheading', 'label' => 'Phụ đề', 'type' => 'text'],
        ['key' => 'panel_title', 'label' => 'Panel phải — tiêu đề', 'type' => 'text'],
        ['key' => 'panel_body', 'label' => 'Panel phải — mô tả', 'type' => 'textarea'],
        ['key' => 'feature1', 'label' => 'Panel phải — tính năng 1', 'type' => 'text'],
        ['key' => 'feature2', 'label' => 'Panel phải — tính năng 2', 'type' => 'text'],
        ['key' => 'feature3', 'label' => 'Panel phải — tính năng 3', 'type' => 'text'],
        ['key' => 'panel_cta_label', 'label' => 'Panel phải — chữ link CTA', 'type' => 'text'],
        ['key' => 'panel_cta_url', 'label' => 'Panel phải — link CTA', 'type' => 'url'],
        ['key' => 'tabs', 'type' => 'tabs', 'label' => 'Các tab (chữ nút + ảnh/video mỗi tab)', 'tabKeys' => ['nail', 'box', 'arm', 'back']],
    ];
@endphp
<x-see-it-in-action
    :heading="$seeItBlock['heading'] ?? 'See It In'"
    :heading-highlight="$seeItBlock['heading_highlight'] ?? 'Action.'"
    :subheading="$seeItBlock['subheading'] ?? 'No matter the style, your nails always fit perfectly.'"
    :panel-title="$seeItBlock['panel_title'] ?? 'Not sure about your size?'"
    :panel-body="$seeItBlock['panel_body'] ?? 'Use our Sizing Kit to measure accurately before ordering, ensuring a perfect fit for all 10 nails.'"
    :panel-cta-label="$seeItBlock['panel_cta_label'] ?? 'View Sizing Kit'"
    :panel-cta-url="$seeItBlock['panel_cta_url'] ?? route('sizing-kit.index')"
    :feature1="$seeItBlock['feature1'] ?? '10 minutes for a full application'"
    :feature2="$seeItBlock['feature2'] ?? 'No filing, no extra glue required'"
    :feature3="$seeItBlock['feature3'] ?? 'Reusable 20+ times with proper care'"
    :can-edit="isset($canEdit) && $canEdit"
    :edit-mode="isset($editMode) && $editMode"
/>

@php
    $indulge = content_block('home.indulge', [
        'heading' => 'Indulge in salon-quality at home',
        'button_label' => 'SHOP NOW',
        'button_url' => route('products.index'),
        'review_text' => '50k+ Reviews',
        'title' => 'Premium Press-on Nails',
        'subtitle' => 'Complete set • Reusable • Easy to apply',
        'bg_color' => '#2b2533',
    ]);
    $indulgeFallback = asset('storage/images/fbe2e728-728c-4815-bc47-db0f790a5b1b.mp4');
    $indulgeImages = isset($indulge['images']) && is_array($indulge['images']) ? array_filter($indulge['images']) : [];
    $indulgeImageAlts = [];
    if (empty($indulgeImages)) {
        foreach ($bestsellers as $p) {
            $m = $p->getEffectiveMedia();
            if ($m && count($m) > 0) {
                $first = $m[0];
                $url = is_string($first) ? $first : ($first['url'] ?? $first['path'] ?? null);
                if ($url) {
                    $indulgeImages[] = (str_starts_with($url, 'http') ? $url : asset('storage/' . $url));
                    $indulgeImageAlts[] = $p->altForMediaItem($first, null, 0);
                }
            }
        }
    }
    if (empty($indulgeImages)) $indulgeImages[] = $indulgeFallback;
    while (count($indulgeImageAlts) < count($indulgeImages)) {
        $indulgeImageAlts[] = $indulge['title'] ?? 'Premium Press-on Nails';
    }
    $indulgeSchema = [
        ['key' => 'heading', 'label' => 'Tiêu đề', 'type' => 'text'],
        ['key' => 'button_label', 'label' => 'Chữ nút', 'type' => 'text'],
        ['key' => 'button_url', 'label' => 'Link nút', 'type' => 'url'],
        ['key' => 'review_text', 'label' => 'Chữ đánh giá (vd: 50k+ Reviews)', 'type' => 'text'],
        ['key' => 'title', 'label' => 'Tiêu đề nhỏ', 'type' => 'text'],
        ['key' => 'subtitle', 'label' => 'Mô tả nhỏ', 'type' => 'text'],
        ['key' => 'images', 'label' => 'Ảnh carousel', 'type' => 'images'],
        ['key' => 'bg_color', 'label' => 'Màu nền section (HEX) – mặc định #2b2533', 'type' => 'text'],
    ];
    $indulgeDataForEdit = array_merge($indulge, [
        'images' => (isset($indulge['images']) && is_array($indulge['images']) && count($indulge['images']) > 0)
            ? $indulge['images']
            : $indulgeImages,
    ]);
@endphp
<!-- Featured product / CTA -->
<section class="px-4 sm:px-6 lg:px-20 py-16 sm:py-20 md:py-24 bg-[#2b2533] text-white overflow-hidden" data-content-block="home.indulge" style="background-color: {{ $indulge['bg_color'] ?? '#2b2533' }};">
    @if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
    <div class="max-w-7xl mx-auto flex justify-end mb-2">
        <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.indulge">Chỉnh sửa CTA</button>
    </div>
    @endif
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-14 xl:gap-20 items-center min-w-0">
            {{-- Carousel — chiếm nửa màn hình trên desktop --}}
            <div class="relative w-full min-w-0 order-1">
                <div class="relative rounded-2xl overflow-hidden aspect-[4/5] sm:aspect-[3/4] lg:aspect-square bg-primary/20 shadow-2xl shadow-black/30 border border-white/10" id="indulge-carousel">
                    <div class="relative w-full h-full overflow-hidden">
                        @foreach($indulgeImages as $idx => $imgUrl)
                            @php
                                $src = str_starts_with($imgUrl, 'http') ? $imgUrl : asset($imgUrl);
                                $slideAlt = $indulgeImageAlts[$idx] ?? ($indulge['title'] ?? 'Premium Press-on Nails');
                                $indulgeOpt640 = storage_image_resize_url($src, 640);
                                $indulgeOpt960 = storage_image_resize_url($src, 960);
                                $indulgeOpt1280 = storage_image_resize_url($src, 1280);
                            @endphp
                            <div class="indulge-slide absolute inset-0 transition-opacity duration-300 {{ $idx === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0' }}" data-slide="{{ $idx }}">
                                @if($indulgeOpt640)
                                    <img alt="{{ $slideAlt }}" class="w-full h-full object-cover"
                                         src="{{ $indulgeOpt640 }}"
                                         @if($indulgeOpt960 && $indulgeOpt1280) srcset="{{ $indulgeOpt640 }} 640w, {{ $indulgeOpt960 }} 960w, {{ $indulgeOpt1280 }} 1280w" @elseif($indulgeOpt960) srcset="{{ $indulgeOpt640 }} 640w, {{ $indulgeOpt960 }} 960w" @endif
                                         sizes="(max-width: 1023px) 100vw, 50vw"
                                         width="960" height="960" loading="{{ $idx === 0 ? 'eager' : 'lazy' }}" decoding="async">
                                @else
                                    <img alt="{{ $slideAlt }}" class="w-full h-full object-cover" src="{{ $src }}" loading="{{ $idx === 0 ? 'eager' : 'lazy' }}" decoding="async" sizes="(max-width: 1023px) 100vw, 50vw">
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if(count($indulgeImages) > 1)
                        <button type="button" class="indulge-prev absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-black/30 hover:bg-black/50 backdrop-blur-sm flex items-center justify-center text-white transition-colors" aria-label="Previous">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        <button type="button" class="indulge-next absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-black/30 hover:bg-black/50 backdrop-blur-sm flex items-center justify-center text-white transition-colors" aria-label="Next">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                        <div class="absolute bottom-3 sm:bottom-4 left-0 right-0 z-20 flex justify-center items-center gap-1.5">
                            @foreach($indulgeImages as $idx => $imgUrl)
                                <button type="button" class="indulge-dot inline-flex min-w-10 min-h-10 items-center justify-center rounded-full touch-manipulation" data-slide="{{ $idx }}" aria-label="Slide {{ $idx + 1 }}">
                                    <span class="indulge-dot-inner pointer-events-none block h-2 w-2 sm:h-2.5 sm:w-2.5 shrink-0 rounded-full transition-colors {{ $idx === 0 ? 'bg-white' : 'bg-white/40' }}" aria-hidden="true"></span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <div class="absolute top-4 left-4 bg-white text-slate-900 text-[10px] sm:text-xs font-bold py-1.5 px-3 sm:px-4 rounded-full z-20 tracking-wide">FREE SHIPPING</div>
                </div>
            </div>

            {{-- Nội dung CTA — bên phải trên desktop --}}
            <div class="flex flex-col gap-6 sm:gap-8 text-center lg:text-left min-w-0 order-2 px-1 sm:px-0">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black leading-tight" data-content-field="heading">{{ $indulge['heading'] ?? 'Indulge in salon-quality at home' }}</h2>

                <div class="flex flex-wrap justify-center lg:justify-start items-center gap-x-2 gap-y-1">
                    <span class="flex text-amber-400" aria-hidden="true">
                    @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    @endfor
                    </span>
                    <span class="text-sm sm:text-base font-bold text-white/90" data-content-field="review_text">{{ $indulge['review_text'] ?? '50k+ Reviews' }}</span>
                </div>

                <div>
                    <h3 class="text-2xl sm:text-3xl font-bold mb-2" data-content-field="title">{{ $indulge['title'] ?? 'Premium Press-on Nails' }}</h3>
                    <p class="text-white/75 text-base sm:text-lg leading-relaxed max-w-md mx-auto lg:mx-0" data-content-field="subtitle">{{ $indulge['subtitle'] ?? 'Complete set • Reusable • Easy to apply' }}</p>
                </div>

                <div class="pt-2">
                    <a href="{{ $indulge['button_url'] ?? route('products.index') }}" class="inline-block w-full sm:w-auto px-10 sm:px-14 py-4 sm:py-5 bg-primary hover:bg-primary/90 text-white font-black rounded-xl text-lg sm:text-xl text-center transition-all hover:shadow-lg hover:shadow-primary/30 active:scale-[0.98]" data-content-field="button_url"><span data-content-field="button_label">{{ $indulge['button_label'] ?? 'SHOP NOW' }}</span></a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Recently Viewed (dùng chung: guest localStorage, user login DB) --}}
<section class="px-4 sm:px-6 lg:px-20 py-16 sm:py-20 md:py-24 bg-white">
    <x-recently-viewed :limit="5" />
</section>

<x-testimonials />

@push('inline_edit_config')
@php
    $footerFaqBlock = content_block('layout.footer_faq', footer_faq_block_defaults());
@endphp
<script>
Object.assign(window.CONTENT_BLOCK_SCHEMAS, {
    'home.hero': @json($heroSchema),
    'home.why_choose': @json($whyChooseSchema),
    'home.bestsellers': @json($bestsellersSchema),
    'home.customer_favorites': @json($customerFavoritesSchema),
    'home.new_arrivals': @json($newArrivalsSchema),
    'home.collections': @json($collectionsSchema),
    'home.see_it_in_action': @json($seeItSchema),
    'home.indulge': @json($indulgeSchema),
    'layout.footer_faq': @json(footer_faq_block_schema()),
});
Object.assign(window.CONTENT_BLOCK_DATA, {
    'home.hero': @json($hero),
    'home.bestsellers': @json($bestsellersBlock),
    'home.new_arrivals': @json($newArrivalsBlock),
    'home.customer_favorites': @json($customerFavoritesBlock),
    'home.see_it_in_action': @json($seeItBlock),
    'home.indulge': @json($indulgeDataForEdit),
    'layout.footer_faq': @json($footerFaqBlock),
});
</script>
@endpush

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Indulge carousel
    var indulgeCarousel = document.getElementById('indulge-carousel');
    if (indulgeCarousel) {
        var slides = indulgeCarousel.querySelectorAll('.indulge-slide');
        var dots = indulgeCarousel.querySelectorAll('.indulge-dot');
        var total = slides.length;
        var current = 0;
        function goTo(n) {
            current = (n + total) % total;
            slides.forEach(function(s, i) {
                s.classList.toggle('opacity-100', i === current);
                s.classList.toggle('opacity-0', i !== current);
                s.classList.toggle('z-10', i === current);
                s.classList.toggle('z-0', i !== current);
            });
            dots.forEach(function(d, i) {
                var inner = d.querySelector('.indulge-dot-inner');
                if (inner) {
                    inner.classList.toggle('bg-white', i === current);
                    inner.classList.toggle('bg-white/40', i !== current);
                }
            });
        }
        indulgeCarousel.querySelectorAll('.indulge-prev').forEach(function(btn) {
            btn.addEventListener('click', function() { goTo(current - 1); });
        });
        indulgeCarousel.querySelectorAll('.indulge-next').forEach(function(btn) {
            btn.addEventListener('click', function() { goTo(current + 1); });
        });
        dots.forEach(function(dot) {
            dot.addEventListener('click', function() { goTo(parseInt(this.dataset.slide, 10)); });
        });
    }
});
</script>
@endsection
