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
        'bg_color' => null, // e.g. #ffffff
    ]);
    $heroImageUrl = $hero['image'] ?? '';
    if ($heroImageUrl && !str_starts_with($heroImageUrl, 'http')) {
        $heroImageUrl = asset($heroImageUrl);
    }
    $heroSchema = [
        ['key' => 'tagline', 'label' => 'Tagline', 'type' => 'text'],
        ['key' => 'heading', 'label' => 'Heading (phần trước)', 'type' => 'text'],
        ['key' => 'heading_highlight', 'label' => 'Heading (từ nổi bật)', 'type' => 'text'],
        ['key' => 'subheading', 'label' => 'Mô tả', 'type' => 'textarea'],
        ['key' => 'cta_primary_label', 'label' => 'Nút chính - chữ', 'type' => 'text'],
        ['key' => 'cta_primary_url', 'label' => 'Nút chính - link', 'type' => 'url'],
        ['key' => 'cta_secondary_label', 'label' => 'Nút phụ - chữ', 'type' => 'text'],
        ['key' => 'cta_secondary_url', 'label' => 'Nút phụ - link', 'type' => 'url'],
        ['key' => 'image', 'label' => 'Ảnh Hero', 'type' => 'image'],
        ['key' => 'bg_color', 'label' => 'Màu nền section (HEX, vd: #ffffff) – để trống dùng mặc định', 'type' => 'text'],
    ];
@endphp
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

<!-- Hero Section -->
<section class="relative px-4 sm:px-6 lg:px-20 py-12 lg:py-24 overflow-hidden bg-background-light" data-content-block="home.hero" @if(!empty($hero['bg_color'])) style="background-color: {{ $hero['bg_color'] }};" @endif>
    @if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
    <div class="absolute top-4 right-4 z-20">
        <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.hero">Chỉnh sửa Hero</button>
    </div>
    @endif
    <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-12">
        <div class="flex flex-col gap-6 lg:w-1/2 z-10">
            <span class="text-primary-fg font-bold tracking-widest text-sm uppercase" data-content-field="tagline">{{ $hero['tagline'] ?? '' }}</span>
            <h1 class="text-slate-900 text-4xl sm:text-5xl lg:text-7xl font-black leading-tight tracking-tight">
                {{ $hero['heading'] ?? 'Manicure in' }} <span class="text-primary-fg" data-content-field="heading_highlight">{{ $hero['heading_highlight'] ?? 'Minutes' }}</span>
            </h1>
            <p class="text-slate-600 text-lg lg:text-xl max-w-lg leading-relaxed" data-content-field="subheading">{{ $hero['subheading'] ?? '' }}</p>
            <div class="flex flex-wrap gap-4 pt-4">
                <a href="{{ $hero['cta_primary_url'] ?? route('products.index') }}" class="inline-block px-8 py-4 bg-primary text-white rounded-lg font-bold text-lg hover:shadow-lg hover:shadow-primary/30 transition-all active:scale-95" data-content-field="cta_primary_url"><span data-content-field="cta_primary_label">{{ $hero['cta_primary_label'] ?? 'Shop the Collection' }}</span></a>
                <a href="{{ $hero['cta_secondary_url'] ?? '#' }}" class="inline-block px-8 py-4 border-2 border-primary text-primary-fg rounded-lg font-bold text-lg hover:bg-primary hover:text-white transition-all" data-content-field="cta_secondary_url"><span data-content-field="cta_secondary_label">{{ $hero['cta_secondary_label'] ?? 'How it Works' }}</span></a>
            </div>
        </div>
        <div class="lg:w-1/2 relative min-w-0 pb-0 md:pb-10">
            <div class="relative w-full overflow-hidden rounded-2xl shadow-2xl bg-slate-200 aspect-[4/5] lg:aspect-square">
                <img
                    alt="Premium press-on nails"
                    class="absolute inset-0 w-full h-full object-cover hero-main-image"
                    data-content-field="image"
                    src="https://blulavelle.com/_media/resize?p=content-blocks%2Fimages%2F20260328092256_DOtRByFv.jpeg&amp;w=960&amp;signature=1db01bb5dccd9bb8b21a12c704d5ac81bef7209d9985c548b0028175a9e13bc1"
                    srcset="https://blulavelle.com/_media/resize?p=content-blocks%2Fimages%2F20260328092256_DOtRByFv.jpeg&amp;w=640&amp;signature=dc2e18d8c5c379a96def410d2290aa45478a699dc4d5c37c98a891bf64ad7b8b 640w, https://blulavelle.com/_media/resize?p=content-blocks%2Fimages%2F20260328092256_DOtRByFv.jpeg&amp;w=960&amp;signature=1db01bb5dccd9bb8b21a12c704d5ac81bef7209d9985c548b0028175a9e13bc1 960w, https://blulavelle.com/_media/resize?p=content-blocks%2Fimages%2F20260328092256_DOtRByFv.jpeg&amp;w=1280&amp;signature=c6b603cdda3012ed6fa97fb647c47825c773549502586a1418517e2da891a36b 1280w"
                    sizes="(max-width: 1023px) 100vw, 46vw"
                    width="960"
                    height="1200"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                >
        
                <div class="hidden absolute inset-0 bg-gradient-to-br from-primary/20 to-primary/5 items-center justify-center text-primary-fg">
                    <svg class="w-24 h-24 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                    </svg>
                </div>
            </div>
        
            <div class="absolute left-0 bottom-0 translate-y-0 md:-bottom-6 md:-left-6 md:translate-y-0 bg-white p-6 rounded-xl shadow-xl hidden md:flex items-center gap-4 border border-primary/10 z-10">
                <div class="flex -space-x-3">
                    <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center border-2 border-white">
                        <span class="text-primary-fg text-sm">★</span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-200 border-2 border-white"></div>
                    <div class="w-10 h-10 rounded-full bg-slate-300 border-2 border-white"></div>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">50k+ Happy Customers</p>
                    <div class="flex text-primary-fg leading-none">★★★★★</div>
                </div>
            </div>
        </div>
    </div>
</section>

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
        'heading' => 'Shop Our Bestsellers',
        'subheading' => 'The most-loved styles by our community',
        'view_all_label' => 'View All Sets',
        'bg_color' => null,
    ]);
    $newArrivalsBlock = content_block('home.new_arrivals', [
        'heading' => 'New Arrivals',
        'subheading' => 'Fresh styles just added to our collection',
        'view_all_label' => 'View All New',
        'bg_color' => null,
    ]);
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
        ['key' => 'heading', 'label' => 'Tiêu đề', 'type' => 'text'],
        ['key' => 'subheading', 'label' => 'Mô tả', 'type' => 'text'],
        ['key' => 'view_all_label', 'label' => 'Chữ nút View All', 'type' => 'text'],
        ['key' => 'bg_color', 'label' => 'Màu nền section (HEX) – để trống dùng mặc định', 'type' => 'text'],
    ];
    $newArrivalsSchema = [
        ['key' => 'heading', 'label' => 'Tiêu đề', 'type' => 'text'],
        ['key' => 'subheading', 'label' => 'Mô tả', 'type' => 'text'],
        ['key' => 'view_all_label', 'label' => 'Chữ nút View All', 'type' => 'text'],
        ['key' => 'bg_color', 'label' => 'Màu nền section (HEX) – để trống dùng mặc định', 'type' => 'text'],
    ];
    $collectionsSchema = [
        ['key' => 'heading', 'label' => 'Tiêu đề', 'type' => 'text'],
        ['key' => 'bg_color', 'label' => 'Màu nền section (HEX) – để trống dùng mặc định', 'type' => 'text'],
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

<!-- Shop Our Bestsellers -->
<section class="px-4 sm:px-6 lg:px-20 py-16 sm:py-20 md:py-24 bg-white" data-content-block="home.bestsellers" @if(!empty($bestsellersBlock['bg_color'])) style="background-color: {{ $bestsellersBlock['bg_color'] }};" @endif>
    @if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
    <div class="max-w-7xl mx-auto flex justify-end mb-2">
        <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.bestsellers">Chỉnh sửa</button>
    </div>
    @endif
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center md:items-end mb-12 gap-6 text-center md:text-left">
            <div>
                <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-4" data-content-field="heading">{{ $bestsellersBlock['heading'] ?? 'Shop Our Bestsellers' }}</h2>
                <p class="text-slate-600" data-content-field="subheading">{{ $bestsellersBlock['subheading'] ?? 'The most-loved styles by our community' }}</p>
            </div>
            <a class="text-primary-fg font-bold flex items-center justify-center md:justify-start gap-2 hover:underline underline-offset-4 shrink-0" href="{{ route('products.index', ['sort' => 'bestsellers']) }}" data-content-field="view_all_label">{{ $bestsellersBlock['view_all_label'] ?? 'View All Sets' }}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 gap-6 md:grid-cols-3 xl:grid-cols-4">
            @foreach($bestsellers as $product)
                <x-product-card :product="$product" :show-quick-view="true" />
            @endforeach
        </div>
    </div>
</section>

<!-- New Arrivals -->
<section class="px-4 sm:px-6 lg:px-20 py-16 sm:py-20 md:py-24 bg-slate-50" data-content-block="home.new_arrivals" @if(!empty($newArrivalsBlock['bg_color'])) style="background-color: {{ $newArrivalsBlock['bg_color'] }};" @endif>
    @if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
    <div class="max-w-7xl mx-auto flex justify-end mb-2">
        <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.new_arrivals">Chỉnh sửa</button>
    </div>
    @endif
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center md:items-end mb-12 gap-6 text-center md:text-left">
            <div>
                <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-4" data-content-field="heading">{{ $newArrivalsBlock['heading'] ?? 'New Arrivals' }}</h2>
                <p class="text-slate-600" data-content-field="subheading">{{ $newArrivalsBlock['subheading'] ?? 'Fresh styles just added to our collection' }}</p>
            </div>
            <a class="text-primary-fg font-bold flex items-center justify-center md:justify-start gap-2 hover:underline underline-offset-4 shrink-0" href="{{ route('products.index', ['sort' => 'newest']) }}" data-content-field="view_all_label">{{ $newArrivalsBlock['view_all_label'] ?? 'View All New' }}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 gap-6 md:grid-cols-3 xl:grid-cols-4">
            @foreach($newArrivals as $product)
                <x-product-card :product="$product" :show-quick-view="true" />
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

    <div class="w-full max-w-[100vw] sm:max-w-7xl sm:mx-auto sm:px-6 lg:px-20">
        <div id="collections-scroll" class="collections-scroll flex w-full sm:grid sm:grid-cols-2 lg:grid-cols-3 gap-0 sm:gap-8 lg:gap-10 overflow-x-auto sm:overflow-visible overscroll-x-contain snap-x snap-mandatory sm:snap-none scroll-smooth touch-pan-x sm:touch-auto">
            @foreach($featuredCollections as $idx => $collection)
                <a href="{{ route('collections.show', $collection->slug) }}" class="collection-slide group block flex-[0_0_100%] w-full min-w-0 max-w-full snap-start box-border px-5 sm:flex-[unset] sm:px-0 sm:min-w-0 sm:max-w-none sm:w-auto" data-slide="{{ $idx }}">
                        <div class="relative overflow-hidden rounded-2xl aspect-[3/4] mb-4 sm:mb-5 shadow-md shadow-slate-200/80 sm:shadow-sm bg-slate-100 ring-1 ring-slate-900/5">
                            @if($collection->image)
                                <img alt="{{ $collection->name }}" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" src="{{ $collection->image }}" loading="lazy">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-primary/30 to-primary/5"></div>
                            @endif

                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent sm:opacity-0 sm:group-hover:opacity-100 transition-opacity duration-500"></div>

                            <div class="absolute inset-x-0 bottom-0 p-4 sm:p-6 sm:translate-y-4 sm:group-hover:translate-y-0 sm:opacity-0 sm:group-hover:opacity-100 transition-all duration-500">
                                <div class="w-full py-3 sm:py-3.5 bg-white text-slate-900 font-extrabold rounded-xl shadow-lg sm:shadow-2xl group-hover:bg-primary group-hover:text-white transition-colors text-center text-sm sm:text-base">
                                    Shop the Collection
                                </div>
                            </div>

                            <div class="absolute top-3 left-3 sm:top-4 sm:left-4 flex flex-wrap items-center gap-1.5 sm:gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 sm:px-3 rounded-full bg-white/95 backdrop-blur text-slate-900 text-[11px] sm:text-xs font-extrabold shadow-sm">
                                    {{ $collection->active_products_count }} items
                                </span>
                                @if($collection->featured)
                                    <span class="inline-flex items-center px-2.5 py-1 sm:px-3 rounded-full bg-primary text-white text-[11px] sm:text-xs font-extrabold shadow-sm">
                                        Featured
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-center sm:px-4 pb-1 sm:pb-0">
                            <h3 class="font-serif text-xl sm:text-3xl text-slate-900 mb-1.5 sm:mb-2 group-hover:text-primary-fg transition-colors line-clamp-2">
                                {{ $collection->name }}
                            </h3>

                            @if($collection->description)
                                <p class="text-slate-600 text-sm italic mb-0 sm:mb-3 line-clamp-2 leading-relaxed">{{ $collection->description }}</p>
                            @else
                                <p class="text-slate-600 text-sm italic mb-0 sm:mb-3">Curated sets designed to match your mood.</p>
                            @endif

                            <span class="hidden sm:inline-block lg:hidden text-xs font-bold uppercase tracking-[0.2em] text-primary-fg border-b border-primary-fg/40 pb-1 group-hover:border-primary transition-all">
                                View Series
                            </span>
                        </div>
                    </a>
            @endforeach
        </div>

        @if($featuredCollections->count() > 1)
        <div id="collections-dots" class="sm:hidden flex justify-center items-center gap-2 mt-5" role="tablist" aria-label="Collections">
            @foreach($featuredCollections as $idx => $collection)
                <button type="button" role="tab" class="collections-dot h-2 rounded-full transition-all duration-300 {{ $idx === 0 ? 'w-7 bg-primary' : 'w-2 bg-slate-300' }}" data-slide="{{ $idx }}" aria-label="{{ $collection->name }}" aria-selected="{{ $idx === 0 ? 'true' : 'false' }}"></button>
            @endforeach
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
.collections-scroll { scrollbar-width: none; -ms-overflow-style: none; }
.collections-scroll::-webkit-scrollbar { display: none; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var track = document.getElementById('collections-scroll');
    var dotsWrap = document.getElementById('collections-dots');
    if (!track || !dotsWrap) return;

    var slides = track.querySelectorAll('.collection-slide');
    var dots = dotsWrap.querySelectorAll('.collections-dot');
    if (slides.length < 2) return;

    function setActive(index) {
        dots.forEach(function (dot, i) {
            var active = i === index;
            dot.classList.toggle('w-7', active);
            dot.classList.toggle('bg-primary', active);
            dot.classList.toggle('w-2', !active);
            dot.classList.toggle('bg-slate-300', !active);
            dot.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function activeIndexFromScroll() {
        if (!track.clientWidth) return 0;
        var index = Math.round(track.scrollLeft / track.clientWidth);
        return Math.max(0, Math.min(slides.length - 1, index));
    }

    var scrollTimer;
    track.addEventListener('scroll', function () {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function () {
            setActive(activeIndexFromScroll());
        }, 80);
    }, { passive: true });

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            var index = parseInt(this.dataset.slide, 10);
            var slide = slides[index];
            if (!slide) return;
            track.scrollTo({ left: slide.offsetLeft, behavior: 'smooth' });
            setActive(index);
        });
    });
});
</script>

@php
    $seeItBlock = content_block('home.see_it_in_action', [
        'heading' => 'See it in action.',
        'subheading' => 'No matter the style, our nails fit perfectly.',
    ]);
    $defaultSeeItTabs = [
        ['key' => 'bikini', 'label' => 'Bikini', 'image_url' => null, 'video_url' => null],
        ['key' => 'leg', 'label' => 'Leg', 'image_url' => null, 'video_url' => null],
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
        ['key' => 'heading', 'label' => 'Tiêu đề', 'type' => 'text'],
        ['key' => 'subheading', 'label' => 'Phụ đề (in nghiêng)', 'type' => 'text'],
        ['key' => 'tabs', 'type' => 'tabs', 'label' => 'Các tab (chữ nút + ảnh mỗi tab)', 'tabKeys' => ['bikini', 'leg', 'arm', 'back']],
    ];
@endphp
<x-see-it-in-action
    :heading="$seeItBlock['heading'] ?? 'See it in action.'"
    :subheading="$seeItBlock['subheading'] ?? 'No matter the style, our nails fit perfectly.'"
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
    'home.new_arrivals': @json($newArrivalsSchema),
    'home.collections': @json($collectionsSchema),
    'home.see_it_in_action': @json($seeItSchema),
    'home.indulge': @json($indulgeSchema),
    'layout.footer_faq': @json(footer_faq_block_schema()),
});
Object.assign(window.CONTENT_BLOCK_DATA, {
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
