@extends('layouts.app')

@section('content')
@php
    // Nội dung các block bên dưới lưu trong DB (bảng content_blocks). Khi chỉnh qua admin inline-edit sẽ ghi đè; default ở đây chỉ dùng khi chưa có bản ghi.
    $currentCurrency = currency();
    $currencySymbol = currency_symbol();
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
// Track Facebook Pixel ViewContent for home page
document.addEventListener('DOMContentLoaded', function() {
    if (typeof fbq !== 'undefined') {
        fbq('track', 'ViewContent', {
            content_name: 'Home Page',
            content_type: 'home'
        });
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
            <span class="text-primary font-bold tracking-widest text-sm uppercase" data-content-field="tagline">{{ $hero['tagline'] ?? '' }}</span>
            <h1 class="text-slate-900 text-4xl sm:text-5xl lg:text-7xl font-black leading-tight tracking-tight">
                {{ $hero['heading'] ?? 'Manicure in' }} <span class="text-primary" data-content-field="heading_highlight">{{ $hero['heading_highlight'] ?? 'Minutes' }}</span>
            </h1>
            <p class="text-slate-600 text-lg lg:text-xl max-w-lg leading-relaxed" data-content-field="subheading">{{ $hero['subheading'] ?? '' }}</p>
            <div class="flex flex-wrap gap-4 pt-4">
                <a href="{{ $hero['cta_primary_url'] ?? route('products.index') }}" class="inline-block px-8 py-4 bg-primary text-white rounded-lg font-bold text-lg hover:shadow-lg hover:shadow-primary/30 transition-all active:scale-95" data-content-field="cta_primary_url"><span data-content-field="cta_primary_label">{{ $hero['cta_primary_label'] ?? 'Shop the Collection' }}</span></a>
                <a href="{{ $hero['cta_secondary_url'] ?? '#' }}" class="inline-block px-8 py-4 border-2 border-primary text-primary rounded-lg font-bold text-lg hover:bg-primary hover:text-white transition-all" data-content-field="cta_secondary_url"><span data-content-field="cta_secondary_label">{{ $hero['cta_secondary_label'] ?? 'How it Works' }}</span></a>
            </div>
        </div>
        <div class="lg:w-1/2 relative">
            <div class="relative rounded-2xl overflow-hidden shadow-2xl aspect-[4/5] lg:aspect-square bg-slate-200">
                <img alt="Premium press-on nails" class="w-full h-full object-cover hero-main-image" data-content-field="image" src="{{ $heroImageUrl }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div class="hidden w-full h-full bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center text-primary">
                    <svg class="w-24 h-24 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path></svg>
                </div>
            </div>
            <div class="absolute -bottom-6 -left-6 bg-white p-6 rounded-xl shadow-xl hidden md:flex items-center gap-4 border border-primary/10">
                <div class="flex -space-x-3">
                    <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center border-2 border-white"><span class="text-primary text-sm">★</span></div>
                    <div class="w-10 h-10 rounded-full bg-slate-200 border-2 border-white"></div>
                    <div class="w-10 h-10 rounded-full bg-slate-300 border-2 border-white"></div>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">50k+ Happy Customers</p>
                    <div class="flex text-primary">★★★★★</div>
                </div>
            </div>
        </div>
    </div>
</section>

@php
    $bestsellers = \App\Models\Product::with(['shop', 'template'])
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
    ]);
    $bestsellersBlock = content_block('home.bestsellers', [
        'heading' => 'Shop Our Bestsellers',
        'subheading' => 'The most-loved styles by our community',
        'view_all_label' => 'View All Sets',
        'bg_color' => null,
    ]);
    $collectionsBlock = content_block('home.collections', [
        'heading' => 'Explore Our Collections',
        'bg_color' => null,
    ]);
    $whyChooseSchema = [
        ['key' => 'title', 'label' => 'Tiêu đề', 'type' => 'text'],
        ['key' => 'bg_color', 'label' => 'Màu nền section (HEX, vd: #f8fafc) – để trống dùng mặc định', 'type' => 'text'],
    ];
    $bestsellersSchema = [
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
<section class="px-4 sm:px-6 lg:px-20 py-12 sm:py-16 md:py-20 lg:py-24 bg-slate-50" data-content-block="home.why_choose" @if(!empty($whyChoose['bg_color'])) style="background-color: {{ $whyChoose['bg_color'] }};" @endif>
    @if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
    <div class="max-w-7xl mx-auto flex justify-end mb-2">
        <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.why_choose">Chỉnh sửa</button>
    </div>
    @endif
    <div class="max-w-7xl mx-auto text-center mb-10 sm:mb-12 lg:mb-16 px-1">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 mb-3 sm:mb-4 leading-tight" data-content-field="title">{{ $whyChoose['title'] ?? 'Why Choose Our Press-on Nails?' }}</h2>
        <div class="w-16 sm:w-20 h-1 bg-primary mx-auto rounded-full"></div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6 lg:gap-8 max-w-7xl mx-auto">
        <div class="bg-white p-5 sm:p-6 lg:p-8 rounded-xl sm:rounded-2xl shadow-sm border border-primary/5 hover:border-primary/30 transition-all group flex flex-col">
            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-primary/10 rounded-lg sm:rounded-xl flex items-center justify-center text-primary mb-4 sm:mb-6 group-hover:bg-primary group-hover:text-white transition-all flex-shrink-0">
                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
            </div>
            <h3 class="text-lg sm:text-xl font-bold mb-2 sm:mb-3 text-slate-900">Salon Quality</h3>
            <p class="text-slate-600 text-sm sm:text-base leading-relaxed">Durable, non-chipping, and high-gloss finish that lasts up to 2 weeks.</p>
        </div>
        <div class="bg-white p-5 sm:p-6 lg:p-8 rounded-xl sm:rounded-2xl shadow-sm border border-primary/5 hover:border-primary/30 transition-all group flex flex-col">
            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-primary/10 rounded-lg sm:rounded-xl flex items-center justify-center text-primary mb-4 sm:mb-6 group-hover:bg-primary group-hover:text-white transition-all flex-shrink-0">
                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </div>
            <h3 class="text-lg sm:text-xl font-bold mb-2 sm:mb-3 text-slate-900">Reusable</h3>
            <p class="text-slate-600 text-sm sm:text-base leading-relaxed">Sustainable beauty that can be worn multiple times with proper care.</p>
        </div>
        <div class="bg-white p-5 sm:p-6 lg:p-8 rounded-xl sm:rounded-2xl shadow-sm border border-primary/5 hover:border-primary/30 transition-all group flex flex-col">
            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-primary/10 rounded-lg sm:rounded-xl flex items-center justify-center text-primary mb-4 sm:mb-6 group-hover:bg-primary group-hover:text-white transition-all flex-shrink-0">
                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
            </div>
            <h3 class="text-lg sm:text-xl font-bold mb-2 sm:mb-3 text-slate-900">Easy Application</h3>
            <p class="text-slate-600 text-sm sm:text-base leading-relaxed">Apply in less than 10 minutes with our professional adhesive kits.</p>
        </div>
        <div class="bg-white p-5 sm:p-6 lg:p-8 rounded-xl sm:rounded-2xl shadow-sm border border-primary/5 hover:border-primary/30 transition-all group flex flex-col">
            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-primary/10 rounded-lg sm:rounded-xl flex items-center justify-center text-primary mb-4 sm:mb-6 group-hover:bg-primary group-hover:text-white transition-all flex-shrink-0">
                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path></svg>
            </div>
            <h3 class="text-lg sm:text-xl font-bold mb-2 sm:mb-3 text-slate-900">Custom Designs</h3>
            <p class="text-slate-600 text-sm sm:text-base leading-relaxed">Unique hand-painted looks designed by top global nail artists.</p>
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
            <a class="text-primary font-bold flex items-center justify-center md:justify-start gap-2 hover:underline underline-offset-4 shrink-0" href="{{ route('products.index', ['filter' => 'bestsellers']) }}" data-content-field="view_all_label">{{ $bestsellersBlock['view_all_label'] ?? 'View All Sets' }}
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

<!-- Explore Our Collections -->
<section class="px-4 sm:px-6 lg:px-20 py-16 sm:py-20 md:py-24 bg-white" data-content-block="home.collections" @if(!empty($collectionsBlock['bg_color'])) style="background-color: {{ $collectionsBlock['bg_color'] }};" @endif>
    @if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
    <div class="max-w-7xl mx-auto flex justify-end mb-2">
        <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.collections">Chỉnh sửa</button>
    </div>
    @endif
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-4" data-content-field="heading">{{ $collectionsBlock['heading'] ?? 'Explore Our Collections' }}</h2>
            <div class="w-20 h-1 bg-primary mx-auto rounded-full"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10">
            @foreach($featuredCollections as $collection)
                <a href="{{ route('collections.show', $collection->slug) }}" class="group block">
                    <div class="relative overflow-hidden rounded-2xl aspect-[3/4] mb-5 shadow-sm bg-slate-100">
                        @if($collection->image)
                            <img alt="{{ $collection->name }}" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" src="{{ $collection->image }}" loading="lazy">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-primary/30 to-primary/5"></div>
                        @endif

                        <div class="absolute inset-0 bg-gradient-to-t from-black/45 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        <div class="absolute inset-x-0 bottom-0 p-6 translate-y-4 group-hover:translate-y-0 opacity-0 group-hover:opacity-100 transition-all duration-500">
                            <div class="w-full py-3.5 bg-white text-slate-900 font-extrabold rounded-xl shadow-2xl hover:bg-primary hover:text-white transition-colors text-center">
                                Shop the Collection
                            </div>
                        </div>

                        <div class="absolute top-4 left-4 flex items-center gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-white/90 backdrop-blur text-slate-900 text-xs font-extrabold">
                                {{ $collection->active_products_count }} items
                            </span>
                            @if($collection->featured)
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-primary text-white text-xs font-extrabold">
                                    Featured
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="text-center px-4">
                        <h3 class="font-serif text-2xl sm:text-3xl text-slate-900 mb-2 group-hover:text-primary transition-colors line-clamp-2">
                            {{ $collection->name }}
                        </h3>

                        @if($collection->description)
                            <p class="text-slate-500 text-sm italic mb-3 line-clamp-2">{{ $collection->description }}</p>
                        @else
                            <p class="text-slate-500 text-sm italic mb-3">Curated sets designed to match your mood.</p>
                        @endif

                        <span class="inline-block text-xs font-bold uppercase tracking-[0.2em] text-primary border-b border-primary/30 pb-1 group-hover:border-primary transition-all lg:hidden">
                            View Series
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="text-center mt-8">
            <a href="{{ route('collections.index') }}" class="text-primary font-bold inline-flex items-center gap-2 hover:underline underline-offset-4">
                View All Collections
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
            </a>
        </div>
    </div>
</section>

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
    if (empty($indulgeImages)) {
        foreach ($bestsellers as $p) {
            $m = $p->getEffectiveMedia();
            if ($m && count($m) > 0) {
                $first = $m[0];
                $url = is_string($first) ? $first : ($first['url'] ?? $first['path'] ?? null);
                if ($url) $indulgeImages[] = (str_starts_with($url, 'http') ? $url : asset('storage/' . $url));
            }
        }
    }
    if (empty($indulgeImages)) $indulgeImages[] = $indulgeFallback;
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
        <h2 class="text-3xl lg:text-4xl font-black text-center mb-12" data-content-field="heading">{{ $indulge['heading'] ?? 'Indulge in salon-quality at home' }}</h2>
        <div class="flex flex-col items-center">
            <div class="bg-white/10 p-6 rounded-2xl backdrop-blur-sm max-w-sm w-full border border-white/20">
                <div class="relative rounded-xl overflow-hidden mb-6 aspect-square bg-primary/20 flex items-center justify-center" id="indulge-carousel">
                    <div class="relative w-full h-full overflow-hidden rounded-xl">
                        @foreach($indulgeImages as $idx => $imgUrl)
                            @php $src = str_starts_with($imgUrl, 'http') ? $imgUrl : asset($imgUrl); @endphp
                            <div class="indulge-slide absolute inset-0 flex items-center justify-center transition-opacity duration-300 {{ $idx === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0' }}" data-slide="{{ $idx }}">
                                <img alt="Press-on Nails Set" class="w-4/5 h-4/5 object-contain" src="{{ $src }}">
                            </div>
                        @endforeach
                    </div>
                    @if(count($indulgeImages) > 1)
                        <button type="button" class="indulge-prev absolute left-2 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 flex items-center justify-center text-white transition-colors" aria-label="Previous">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        <button type="button" class="indulge-next absolute right-2 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 flex items-center justify-center text-white transition-colors" aria-label="Next">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                        <div class="absolute bottom-2 left-0 right-0 z-20 flex justify-center gap-1.5">
                            @foreach($indulgeImages as $idx => $imgUrl)
                                <button type="button" class="indulge-dot w-2 h-2 rounded-full transition-colors {{ $idx === 0 ? 'bg-white' : 'bg-white/40' }}" data-slide="{{ $idx }}" aria-label="Slide {{ $idx + 1 }}"></button>
                            @endforeach
                        </div>
                    @endif
                    <div class="absolute top-4 left-4 bg-white text-slate-900 text-[10px] font-bold py-1 px-3 rounded-full flex flex-col items-center z-20">FREE SHIPPING</div>
                </div>
                <a href="{{ $indulge['button_url'] ?? route('products.index') }}" class="block w-full py-4 bg-primary hover:bg-primary/90 text-white font-black rounded-lg text-lg mb-6 text-center transition-colors" data-content-field="button_url"><span data-content-field="button_label">{{ $indulge['button_label'] ?? 'SHOP NOW' }}</span></a>
                <div class="text-center">
                    <div class="flex justify-center text-primary mb-2">
                        @for($i = 0; $i < 5; $i++)
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        @endfor
                        <span class="text-sm ml-2 font-bold" data-content-field="review_text">{{ $indulge['review_text'] ?? '50k+ Reviews' }}</span>
                    </div>
                    <h3 class="text-xl font-bold mb-1" data-content-field="title">{{ $indulge['title'] ?? 'Premium Press-on Nails' }}</h3>
                    <p class="text-white/80 text-sm" data-content-field="subtitle">{{ $indulge['subtitle'] ?? 'Complete set • Reusable • Easy to apply' }}</p>
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

@if(isset($canEdit) && $canEdit)
<div id="inline-edit-toolbar" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 {{ isset($editMode) && $editMode ? '' : '' }}">
    @if(isset($editMode) && $editMode)
    <a href="{{ url()->current() }}" class="inline-flex items-center gap-2 px-4 py-3 bg-slate-800 text-white rounded-xl font-bold shadow-xl hover:bg-slate-700">Thoát chỉnh sửa</a>
    @else
    <a href="{{ url()->current() }}?edit=1" class="inline-flex items-center gap-2 px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-xl hover:opacity-90">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
        Chỉnh sửa trang
    </a>
    @endif
</div>
@endif

@if(isset($canEdit) && $canEdit && isset($editMode) && $editMode)
<!-- Modal chỉnh sửa nội dung -->
<div id="inline-edit-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 p-4" aria-modal="true" role="dialog">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-4 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-900">Chỉnh sửa nội dung</h3>
            <button type="button" id="inline-edit-modal-close" class="p-2 rounded-lg hover:bg-slate-100 text-slate-600">×</button>
        </div>
        <form id="inline-edit-form" class="p-4 overflow-y-auto flex-1">
            <div id="inline-edit-fields"></div>
        </form>
        <div class="p-4 border-t border-slate-200 flex justify-end gap-2">
            <button type="button" id="inline-edit-cancel" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 font-medium">Hủy</button>
            <button type="submit" form="inline-edit-form" class="px-4 py-2 bg-primary text-white rounded-lg font-bold hover:opacity-90">Lưu</button>
        </div>
    </div>
</div>
<script>
window.INLINE_EDIT_CONFIG = {
    apiBase: @json(url('/admin/api/content-blocks')),
    csrfToken: @json(csrf_token()),
    uploadImageUrl: @json(route('admin.api.content-blocks.upload-image')),
    uploadVideoUrl: @json(route('admin.api.content-blocks.upload-video')),
};
window.CONTENT_BLOCK_SCHEMAS = {
    'home.hero': @json($heroSchema),
    'home.why_choose': @json($whyChooseSchema),
    'home.bestsellers': @json($bestsellersSchema),
    'home.collections': @json($collectionsSchema),
    'home.see_it_in_action': @json($seeItSchema),
    'home.indulge': @json($indulgeSchema),
};
window.CONTENT_BLOCK_DATA = window.CONTENT_BLOCK_DATA || {};
window.CONTENT_BLOCK_DATA['home.see_it_in_action'] = @json($seeItBlock);
window.CONTENT_BLOCK_DATA['home.indulge'] = @json($indulgeDataForEdit);
</script>
@endif

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
                d.classList.toggle('bg-white', i === current);
                d.classList.toggle('bg-white/40', i !== current);
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

    // Inline edit (chỉ chạy khi có config)
    if (window.INLINE_EDIT_CONFIG) {
        var modal = document.getElementById('inline-edit-modal');
        var form = document.getElementById('inline-edit-form');
        var fieldsContainer = document.getElementById('inline-edit-fields');
        var currentBlock = null;
        var currentSchema = null;
        var currentTabInitialData = null;

        function openModal(blockKey, schemaArray) {
            currentBlock = blockKey;
            currentSchema = Array.isArray(schemaArray) ? schemaArray : [];
            currentTabInitialData = null;
            var section = document.querySelector('[data-content-block="' + blockKey + '"]');
            if (!section) return;
            var values = {};
            var blockData = window.CONTENT_BLOCK_DATA && window.CONTENT_BLOCK_DATA[blockKey];
            currentSchema.forEach(function(f) {
                if (f.type === 'tabs') {
                    currentTabInitialData = (blockData && blockData.tabs) ? blockData.tabs : [];
                    values[f.key] = [];
                    return;
                }
                if (f.type === 'images') {
                    values[f.key] = (blockData && Array.isArray(blockData.images)) ? blockData.images.slice() : [];
                    return;
                }
                var el = section.querySelector('[data-content-field="' + f.key + '"]');
                if (el) {
                    if (el.tagName === 'IMG') values[f.key] = el.src || '';
                    else if (el.href !== undefined) values[f.key] = f.key.indexOf('url') !== -1 ? (el.getAttribute('href') || '') : (el.textContent || '').trim();
                    else values[f.key] = (el.textContent || '').trim();
                } else values[f.key] = '';
            });
            fieldsContainer.innerHTML = '';
            currentSchema.forEach(function(f) {
                if (f.type === 'tabs') {
                    var tabKeys = f.tabKeys || ['bikini', 'leg', 'arm', 'back'];
                    var tabs = Array.isArray(currentTabInitialData) && currentTabInitialData.length ? currentTabInitialData : tabKeys.map(function(k, i) { return { key: k, label: k.charAt(0).toUpperCase() + k.slice(1), image_url: null, video_url: null }; });
                    var sectionLabel = document.createElement('div');
                    sectionLabel.className = 'block text-sm font-medium text-slate-700 mt-4 first:mt-0 mb-2';
                    sectionLabel.textContent = f.label;
                    fieldsContainer.appendChild(sectionLabel);
                    tabKeys.forEach(function(tabKey, i) {
                        var tab = tabs[i] || { key: tabKey, label: tabKey, image_url: null, video_url: null };
                        var card = document.createElement('div');
                        card.className = 'border border-slate-200 rounded-lg p-3 mb-3 bg-slate-50';
                        var rowLabel = document.createElement('label');
                        rowLabel.className = 'block text-xs font-medium text-slate-500 mb-2';
                        rowLabel.textContent = 'Tab ' + (i + 1) + ' — ' + tabKey;
                        card.appendChild(rowLabel);
                        var labelInput = document.createElement('input');
                        labelInput.type = 'text';
                        labelInput.name = 'tabs_' + i + '_label';
                        labelInput.placeholder = 'Chữ trên nút';
                        labelInput.value = (tab.label || '').trim();
                        labelInput.className = 'w-full px-3 py-2 border border-slate-300 rounded-lg bg-white mb-2';
                        card.appendChild(labelInput);
                        var hiddenImg = document.createElement('input');
                        hiddenImg.type = 'hidden';
                        hiddenImg.name = 'tabs_' + i + '_image';
                        hiddenImg.value = tab.image_url || '';
                        card.appendChild(hiddenImg);
                        var hiddenVid = document.createElement('input');
                        hiddenVid.type = 'hidden';
                        hiddenVid.name = 'tabs_' + i + '_video';
                        hiddenVid.value = tab.video_url || '';
                        card.appendChild(hiddenVid);
                        var preview = document.createElement('div');
                        preview.className = 'mb-2';
                        var img = document.createElement('img');
                        img.className = 'max-h-20 rounded border border-slate-200 object-cover';
                        img.style.maxWidth = '160px';
                        img.alt = 'Preview';
                        if (tab.image_url) { img.src = tab.image_url; img.style.display = 'block'; } else { img.style.display = 'none'; }
                        var fileInput = document.createElement('input');
                        fileInput.type = 'file';
                        fileInput.accept = 'image/jpeg,image/jpg,image/png,image/webp';
                        fileInput.className = 'hidden';
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'px-2 py-1.5 bg-slate-200 hover:bg-slate-300 rounded text-sm font-medium text-slate-700 mr-2';
                        btn.textContent = 'Chọn ảnh (S3)';
                        preview.appendChild(img);
                        card.appendChild(preview);
                        card.appendChild(hiddenImg);
                        card.appendChild(fileInput);
                        card.appendChild(btn);
                        btn.addEventListener('click', function() { fileInput.click(); });
                        fileInput.addEventListener('change', function() {
                            var file = this.files && this.files[0];
                            if (!file) return;
                            var allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                            if (allowed.indexOf(file.type) === -1) { alert('Chỉ chấp nhận ảnh: JPG, PNG, WebP'); return; }
                            if (file.size > 10 * 1024 * 1024) { alert('Ảnh tối đa 10MB'); return; }
                            btn.disabled = true;
                            btn.textContent = 'Đang tải...';
                            var formData = new FormData();
                            formData.append('image', file);
                            formData.append('_token', window.INLINE_EDIT_CONFIG.csrfToken);
                            fetch(window.INLINE_EDIT_CONFIG.uploadImageUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
                                .then(function(r) { return r.json(); })
                                .then(function(data) {
                                    if (!data.success || !data.url) throw new Error(data.message || 'Upload thất bại');
                                    hiddenImg.value = data.url;
                                    img.src = data.url;
                                    img.style.display = 'block';
                                })
                                .catch(function(err) { alert('Upload thất bại: ' + (err.message || 'Vui lòng thử lại')); })
                                .finally(function() { btn.disabled = false; btn.textContent = 'Chọn ảnh (S3)'; fileInput.value = ''; });
                        });
                        var videoWrap = document.createElement('div');
                        videoWrap.className = 'mt-2 flex items-center gap-2 flex-wrap';
                        var videoLabel = document.createElement('span');
                        videoLabel.className = 'text-xs text-slate-500';
                        videoLabel.textContent = 'Video: ';
                        videoWrap.appendChild(videoLabel);
                        var videoLink = document.createElement('a');
                        videoLink.className = 'text-xs text-primary truncate max-w-[180px]';
                        videoLink.target = '_blank';
                        videoLink.rel = 'noopener';
                        if (tab.video_url) { videoLink.href = tab.video_url; videoLink.textContent = 'Đã có video'; } else { videoLink.textContent = 'Chưa chọn'; videoLink.href = '#'; }
                        videoWrap.appendChild(videoLink);
                        var videoFileInput = document.createElement('input');
                        videoFileInput.type = 'file';
                        videoFileInput.accept = 'video/mp4,video/webm,video/ogg,video/quicktime';
                        videoFileInput.className = 'hidden';
                        var videoBtn = document.createElement('button');
                        videoBtn.type = 'button';
                        videoBtn.className = 'px-2 py-1.5 bg-slate-200 hover:bg-slate-300 rounded text-sm font-medium text-slate-700';
                        videoBtn.textContent = 'Chọn video (S3)';
                        card.appendChild(videoWrap);
                        card.appendChild(videoFileInput);
                        card.appendChild(videoBtn);
                        videoBtn.addEventListener('click', function() { videoFileInput.click(); });
                        videoFileInput.addEventListener('change', function() {
                            var file = this.files && this.files[0];
                            if (!file) return;
                            var allowed = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
                            if (allowed.indexOf(file.type) === -1) { alert('Chỉ chấp nhận video: MP4, WebM, OGG, MOV'); return; }
                            if (file.size > 100 * 1024 * 1024) { alert('Video tối đa 100MB'); return; }
                            videoBtn.disabled = true;
                            videoBtn.textContent = 'Đang tải...';
                            var formData = new FormData();
                            formData.append('video', file);
                            formData.append('_token', window.INLINE_EDIT_CONFIG.csrfToken);
                            fetch(window.INLINE_EDIT_CONFIG.uploadVideoUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
                                .then(function(r) { return r.json(); })
                                .then(function(data) {
                                    if (!data.success || !data.url) throw new Error(data.message || 'Upload thất bại');
                                    hiddenVid.value = data.url;
                                    videoLink.href = data.url;
                                    videoLink.textContent = 'Đã upload video';
                                })
                                .catch(function(err) { alert('Upload video thất bại: ' + (err.message || 'Vui lòng thử lại')); })
                                .finally(function() { videoBtn.disabled = false; videoBtn.textContent = 'Chọn video (S3)'; videoFileInput.value = ''; });
                        });
                        fieldsContainer.appendChild(card);
                    });
                    return;
                }
                if (f.type === 'images') {
                    var imagesList = Array.isArray(values[f.key]) ? values[f.key].slice() : [];
                    var container = document.createElement('div');
                    container.className = 'mt-3 first:mt-0';
                    var sectionLabel = document.createElement('div');
                    sectionLabel.className = 'block text-sm font-medium text-slate-700 mb-2';
                    sectionLabel.textContent = f.label;
                    container.appendChild(sectionLabel);
                    var listEl = document.createElement('div');
                    listEl.className = 'space-y-2 mb-2';
                    listEl.dataset.imagesList = 'true';
                    container.appendChild(listEl);
                    function renderImagesList() {
                        listEl.innerHTML = '';
                        imagesList.forEach(function(url, idx) {
                            var row = document.createElement('div');
                            row.className = 'flex items-center gap-2 p-2 border border-slate-200 rounded-lg bg-slate-50';
                            var hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'images_' + idx;
                            hidden.value = url;
                            var img = document.createElement('img');
                            img.className = 'w-14 h-14 object-cover rounded border border-slate-200 flex-shrink-0';
                            img.src = url;
                            img.alt = 'Preview';
                            var removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'ml-auto px-2 py-1 text-red-600 hover:bg-primary rounded text-sm font-medium';
                            removeBtn.textContent = 'Xóa';
                            removeBtn.addEventListener('click', function() {
                                imagesList.splice(idx, 1);
                                renderImagesList();
                            });
                            row.appendChild(hidden);
                            row.appendChild(img);
                            row.appendChild(removeBtn);
                            listEl.appendChild(row);
                        });
                    }
                    renderImagesList();
                    var addBtn = document.createElement('button');
                    addBtn.type = 'button';
                    addBtn.className = 'px-3 py-2 bg-slate-200 hover:bg-slate-300 rounded-lg text-sm font-medium text-slate-700';
                    addBtn.textContent = 'Thêm ảnh (upload lên AWS)';
                    var fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.accept = 'image/jpeg,image/jpg,image/png,image/webp';
                    fileInput.className = 'hidden';
                    addBtn.addEventListener('click', function() { fileInput.click(); });
                    fileInput.addEventListener('change', function() {
                        var file = this.files && this.files[0];
                        if (!file) return;
                        var allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                        if (allowed.indexOf(file.type) === -1) { alert('Chỉ chấp nhận ảnh: JPG, PNG, WebP'); return; }
                        if (file.size > 10 * 1024 * 1024) { alert('Ảnh tối đa 10MB'); return; }
                        addBtn.disabled = true;
                        addBtn.textContent = 'Đang tải...';
                        var formData = new FormData();
                        formData.append('image', file);
                        formData.append('_token', window.INLINE_EDIT_CONFIG.csrfToken);
                        fetch(window.INLINE_EDIT_CONFIG.uploadImageUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (!data.success || !data.url) throw new Error(data.message || 'Upload thất bại');
                                imagesList.push(data.url);
                                renderImagesList();
                            })
                            .catch(function(err) { alert('Upload thất bại: ' + (err.message || 'Vui lòng thử lại')); })
                            .finally(function() { addBtn.disabled = false; addBtn.textContent = 'Thêm ảnh (upload lên AWS)'; fileInput.value = ''; });
                    });
                    container.appendChild(addBtn);
                    fieldsContainer.appendChild(container);
                    return;
                }
                var label = document.createElement('label');
                label.className = 'block text-sm font-medium text-slate-700 mt-3 first:mt-0';
                label.textContent = f.label;
                var wrap = document.createElement('div');
                wrap.className = 'mt-1';
                if (f.type === 'image') {
                    var hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = f.key;
                    hiddenInput.value = values[f.key] || '';
                    hiddenInput.dataset.fieldKey = f.key;
                    var preview = document.createElement('div');
                    preview.className = 'mb-2';
                    var img = document.createElement('img');
                    img.className = 'max-h-28 rounded-lg border border-slate-200 object-cover';
                    img.style.maxWidth = '200px';
                    img.alt = 'Preview';
                    if (values[f.key]) { img.src = values[f.key]; img.style.display = 'block'; } else { img.style.display = 'none'; }
                    var fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.accept = 'image/jpeg,image/jpg,image/png,image/webp';
                    fileInput.className = 'hidden';
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'px-3 py-2 bg-slate-200 hover:bg-slate-300 rounded-lg text-sm font-medium text-slate-700';
                    btn.textContent = 'Chọn ảnh (upload lên AWS)';
                    preview.appendChild(img);
                    wrap.appendChild(preview);
                    wrap.appendChild(hiddenInput);
                    wrap.appendChild(fileInput);
                    wrap.appendChild(btn);
                    btn.addEventListener('click', function() { fileInput.click(); });
                    fileInput.addEventListener('change', function() {
                        var file = this.files && this.files[0];
                        if (!file) return;
                        var allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                        if (allowed.indexOf(file.type) === -1) {
                            alert('Chỉ chấp nhận ảnh: JPG, PNG, WebP');
                            return;
                        }
                        if (file.size > 10 * 1024 * 1024) {
                            alert('Ảnh tối đa 10MB');
                            return;
                        }
                        btn.disabled = true;
                        btn.textContent = 'Đang tải lên...';
                        var formData = new FormData();
                        formData.append('image', file);
                        formData.append('_token', window.INLINE_EDIT_CONFIG.csrfToken);
                        fetch(window.INLINE_EDIT_CONFIG.uploadImageUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (!data.success || !data.url) throw new Error(data.message || 'Upload thất bại');
                            hiddenInput.value = data.url;
                            img.src = data.url;
                            img.style.display = 'block';
                        })
                        .catch(function(err) {
                            alert('Upload thất bại: ' + (err.message || 'Vui lòng thử lại'));
                        })
                        .finally(function() {
                            btn.disabled = false;
                            btn.textContent = 'Chọn ảnh (upload lên AWS)';
                            fileInput.value = '';
                        });
                    });
                } else if (f.type === 'textarea') {
                    var input = document.createElement('textarea');
                    input.rows = 3;
                    input.className = 'w-full px-3 py-2 border border-slate-300 rounded-lg bg-white';
                    input.name = f.key;
                    input.value = values[f.key] || '';
                    input.dataset.fieldKey = f.key;
                    wrap.appendChild(input);
                } else {
                    var input = document.createElement('input');
                    input.type = f.type === 'url' ? 'url' : 'text';
                    input.className = 'w-full px-3 py-2 border border-slate-300 rounded-lg bg-white';
                    input.name = f.key;
                    input.value = values[f.key] || '';
                    input.dataset.fieldKey = f.key;
                    wrap.appendChild(input);
                }
                label.appendChild(wrap);
                fieldsContainer.appendChild(label);
            });
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }
        window.openInlineEditModal = function(blockKey) {
            var schema = window.CONTENT_BLOCK_SCHEMAS && window.CONTENT_BLOCK_SCHEMAS[blockKey];
            if (blockKey && schema && Array.isArray(schema)) openModal(blockKey, schema);
        };

        function closeModal() {
            currentBlock = null;
            currentSchema = null;
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        document.querySelectorAll('.inline-edit-trigger').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var block = this.getAttribute('data-block');
                var schema = window.CONTENT_BLOCK_SCHEMAS && window.CONTENT_BLOCK_SCHEMAS[block];
                if (block && schema && Array.isArray(schema)) openModal(block, schema);
            });
        });
        document.getElementById('inline-edit-modal-close') && document.getElementById('inline-edit-modal-close').addEventListener('click', closeModal);
        document.getElementById('inline-edit-cancel') && document.getElementById('inline-edit-cancel').addEventListener('click', closeModal);
        modal && modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!currentBlock || !window.INLINE_EDIT_CONFIG) return;
                var content = {};
                var tabKeys = null;
                currentSchema.forEach(function(f) {
                    if (f.type === 'tabs') {
                        tabKeys = f.tabKeys || ['bikini', 'leg', 'arm', 'back'];
                        return;
                    }
                    if (f.type === 'images') {
                        var inputs = form.querySelectorAll('input[name^="images_"]');
                        var sorted = Array.from(inputs).sort(function(a, b) {
                            var na = parseInt(a.name.replace('images_', ''), 10);
                            var nb = parseInt(b.name.replace('images_', ''), 10);
                            return na - nb;
                        });
                        content[f.key] = sorted.map(function(inp) { return inp.value.trim(); }).filter(Boolean);
                        return;
                    }
                    var input = form.querySelector('[name="' + f.key + '"]');
                    if (input) content[f.key] = input.value.trim();
                });
                if (tabKeys && tabKeys.length) {
                    content.tabs = tabKeys.map(function(k, i) {
                        var labelInput = form.querySelector('[name="tabs_' + i + '_label"]');
                        var imageInput = form.querySelector('[name="tabs_' + i + '_image"]');
                        var videoInput = form.querySelector('[name="tabs_' + i + '_video"]');
                        var prev = currentTabInitialData && currentTabInitialData[i] ? currentTabInitialData[i] : {};
                        var imgUrl = (imageInput && imageInput.value) ? imageInput.value.trim() : (prev.image_url || null);
                        var vidUrl = (videoInput && videoInput.value) ? videoInput.value.trim() : (prev.video_url || null);
                        return {
                            key: k,
                            label: (labelInput && labelInput.value) ? labelInput.value.trim() : (prev.label || k),
                            image_url: imgUrl || null,
                            video_url: vidUrl || null
                        };
                    });
                }
                fetch(window.INLINE_EDIT_CONFIG.apiBase, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.INLINE_EDIT_CONFIG.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ key: currentBlock, content: content }),
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (currentBlock === 'home.see_it_in_action' || currentBlock === 'home.indulge') {
                        closeModal();
                        location.reload();
                        return;
                    }
                    var section = document.querySelector('[data-content-block="' + currentBlock + '"]');
                    if (section && data.content) {
                        Object.keys(data.content).forEach(function(key) {
                            if (key === 'tabs') return;
                            var el = section.querySelector('[data-content-field="' + key + '"]');
                            if (el) {
                                if (el.tagName === 'IMG') el.src = data.content[key] || el.src;
                                else if (el.href !== undefined) {
                                    if (key.indexOf('url') !== -1) el.setAttribute('href', data.content[key] || '#');
                                    else el.textContent = data.content[key] || '';
                                } else el.textContent = data.content[key] || '';
                            }
                        });
                    }
                    closeModal();
                })
                .catch(function() { alert('Không thể lưu. Vui lòng thử lại.'); });
            });
        }
    }
});
</script>
@endsection
