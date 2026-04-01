@extends('layouts.app')

@section('title', $category->meta_title ?? $category->name . ' - Bluprinter')
@section('meta_description', $category->meta_description ?? 'Browse ' . $category->name . ' products and designs')

@section('content')
@php
    $currentCurrency = currency();
    $currencySymbol = currency_symbol();
@endphp
<script>
// Track Facebook Pixel ViewContent for category page
document.addEventListener('DOMContentLoaded', function() {
    if (typeof fbq !== 'undefined') {
        fbq('track', 'ViewContent', {
            content_name: '{{ addslashes($category->name) }}',
            content_category: '{{ addslashes($category->name) }}',
            content_type: 'product_group'
        });
    }
});
</script>
<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-50px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(50px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .animate-fadeInUp {
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .animate-fadeIn {
        animation: fadeIn 0.6s ease-out forwards;
    }

    .animate-scaleIn {
        animation: scaleIn 0.5s ease-out forwards;
    }

    .animate-slideInLeft {
        animation: slideInLeft 0.6s ease-out forwards;
    }

    .animate-slideInRight {
        animation: slideInRight 0.6s ease-out forwards;
    }

    .animate-float {
        animation: float 3s ease-in-out infinite;
    }

    .animate-pulse {
        animation: pulse 2s ease-in-out infinite;
    }

    .stagger-1 { animation-delay: 0.1s; opacity: 0; }
    .stagger-2 { animation-delay: 0.2s; opacity: 0; }
    .stagger-3 { animation-delay: 0.3s; opacity: 0; }
    .stagger-4 { animation-delay: 0.4s; opacity: 0; }
    .stagger-5 { animation-delay: 0.5s; opacity: 0; }
    .stagger-6 { animation-delay: 0.6s; opacity: 0; }

    .scroll-reveal {
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.6s ease-out, transform 0.6s ease-out;
    }

    .scroll-reveal.revealed {
        opacity: 1;
        transform: translateY(0);
    }

    .gradient-text {
        background: linear-gradient(135deg, #005366 0%, #E2150C 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .category-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .category-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    /* Mobile horizontal scroll */
    @media (max-width: 1024px) {
        #recentlyViewedContainer {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
            scroll-behavior: smooth;
        }
        
        #recentlyViewedContainer::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        #recently-viewed-container {
            display: flex;
            flex-wrap: nowrap;
            transition: none;
            gap: 12px;
        }
        
        .mobile-scroll-hide {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .mobile-scroll-hide::-webkit-scrollbar {
            display: none;
        }
    }
</style>

<div class="min-h-screen bg-gray-50">
    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-200 animate-fadeIn">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('home') }}" class="text-gray-500 hover:text-[#005366] transition">Home</a>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-gray-900 font-medium">{{ $category->name }}</span>
            </nav>
        </div>
    </div>

    <!-- Category Header -->
    <div class="bg-gradient-to-r from-[#005366] to-[#E2150C] text-white py-16 relative overflow-hidden">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full -translate-y-48 translate-x-48 animate-float"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/5 rounded-full translate-y-32 -translate-x-32 animate-pulse"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 animate-slideInLeft">{{ $category->name }}</h1>
            <p class="text-xl text-white/90 max-w-2xl mx-auto animate-slideInRight">
                Discover amazing {{ strtolower($category->name) }} products and designs
            </p>
            <div class="mt-8 flex items-center justify-center space-x-8 animate-fadeInUp stagger-1">
                <div class="text-center">
                    <div class="text-3xl font-bold">{{ $products->total() }}</div>
                    <div class="text-sm text-white/80">Products</div>
                </div>
                @if($subcategories->count() > 0)
                    <div class="text-center">
                        <div class="text-3xl font-bold">{{ $subcategories->count() }}</div>
                        <div class="text-sm text-white/80">Subcategories</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Subcategories -->
    @if($subcategories->count() > 0)
        <div class="bg-white py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12 scroll-reveal">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ $category->name }}</h2>
                    <p class="text-lg text-gray-600">Transform your space with our stylish and functional home decor.</p>
                    <div class="flex items-center justify-center mt-4">
                        <div class="flex items-center">
                            @for($i = 0; $i < 5; $i++)
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            @endfor
                            <span class="ml-2 text-sm text-gray-600">4.7</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                    @foreach($subcategories as $index => $subcategory)
                        <a href="{{ route('category.show', $subcategory->slug) }}" class="group scroll-reveal" style="animation-delay: {{ $index * 0.1 }}s">
                            <div class="relative overflow-hidden rounded-xl bg-white shadow-lg hover:shadow-xl transition-all duration-300 group-hover:scale-105">
                                @if($subcategory->image)
                                    <div class="aspect-[4/3]">
                                        <img src="{{ $subcategory->image }}" 
                                             alt="{{ $subcategory->name }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    </div>
                                @else
                                    <div class="aspect-[4/3] bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                    </div>
                                @endif
                                
                                <!-- Category Label -->
                                <div class="absolute inset-0 bg-black/20 flex items-end">
                                    <div class="w-full p-3 bg-gradient-to-t from-black/70 to-transparent">
                                        <h4 class="text-white font-bold text-sm text-center">
                                            {{ $subcategory->name }}
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif


    <!-- All Products -->
    <div class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Filter & Sort Bar -->
            <div class="bg-gray-50 rounded-xl p-4 mb-8 scroll-reveal">
                <form method="GET" class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Search -->
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" name="search" placeholder="Search products..." 
                                   value="{{ request('search') }}"
                                   class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Sort -->
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-medium text-gray-700">Sort by:</label>
                        <select name="sort" onchange="this.form.submit()"
                                class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent">
                            <option value="default" {{ request('sort') == 'default' ? 'selected' : '' }}>Default</option>
                            <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name: A-Z</option>
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Products Grid -->
            @if($products->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                    @foreach($products as $product)
                        <a href="{{ route('products.show', $product->slug) }}" class="group bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 scroll-reveal">
                            <div class="relative aspect-square bg-gray-100 overflow-hidden">
                                @php
                                    $media = $product->getEffectiveMedia();
                                    $imageUrl = null;
                                    $cardImgAlt = $product->name;
                                    if ($media && count($media) > 0) {
                                        $cardImgAlt = $product->altForMediaItem($media[0], null, 0);
                                        if (is_string($media[0])) {
                                            $imageUrl = $media[0];
                                        } elseif (is_array($media[0])) {
                                            $imageUrl = $media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? null;
                                        }
                                    }
                                @endphp
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" 
                                         alt="{{ $cardImgAlt }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-[#005366] transition line-clamp-2">
                                    {{ $product->name }}
                                </h3>
                                <div class="flex items-center justify-between">
                                    <span class="text-2xl font-bold text-[#005366]">{{ format_price_usd((float) $product->base_price) }}</span>
                                    @if($product->shop)
                                        <span class="text-sm text-gray-500">{{ $product->shop->shop_name }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8 scroll-reveal">
                    {{ $products->links() }}
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center scroll-reveal">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4 animate-float" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Products Found</h3>
                    <p class="text-gray-600">This category doesn't have any products yet.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Related Categories -->
    @if($relatedCategories->count() > 0)
        <div class="bg-gray-50 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center scroll-reveal">Explore Other Categories</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($relatedCategories as $related)
                        <a href="{{ route('category.show', $related->slug) }}" class="group bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 scroll-reveal">
                            <div class="relative aspect-[4/3] bg-gray-100 overflow-hidden">
                                @if($related->image)
                                    <img src="{{ $related->image }}" 
                                         alt="{{ $related->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#F0427C] to-[#d6386a]">
                                        <svg class="w-16 h-16 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="absolute bottom-2 left-2">
                                    <span class="inline-block px-3 py-1 bg-white/90 backdrop-blur text-gray-900 text-sm font-semibold rounded-full">
                                        {{ $related->products_count }} products
                                    </span>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="text-xl font-semibold text-gray-900 group-hover:text-[#005366] transition">
                                    {{ $related->name }}
                                </h3>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Recently Viewed Section -->
    <div class="py-16 sm:py-20 md:py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-6">
            <div class="text-center scroll-reveal">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl md:text-5xl">
                    Recently 
                    <span class="gradient-text">Viewed</span>
                </h2>
                <p class="mt-4 sm:mt-6 max-w-3xl mx-auto text-lg sm:text-xl text-gray-600 px-4">
                    Continue exploring products you've shown interest in
                </p>
            </div>

            <!-- Recently Viewed Products -->
            <div class="mt-12 sm:mt-16 md:mt-20">
                <div class="relative" id="recently-viewed-wrapper">
                    <!-- Navigation Buttons (Desktop only) -->
                    <button id="recentlyViewedPrevBtn" 
                            onclick="scrollRecentlyViewed('prev')"
                            class="hidden lg:block absolute left-0 top-1/2 -translate-y-1/2 -translate-x-3 z-10 bg-white rounded-full p-2 shadow-lg hover:bg-gray-50 transition-all opacity-0 group-hover:opacity-100">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    
                    <button id="recentlyViewedNextBtn" 
                            onclick="scrollRecentlyViewed('next')"
                            class="hidden lg:block absolute right-0 top-1/2 -translate-y-1/2 translate-x-3 z-10 bg-white rounded-full p-2 shadow-lg hover:bg-gray-50 transition-all">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <!-- Products Container -->
                    <div id="recentlyViewedContainer" class="overflow-x-auto lg:overflow-hidden mobile-scroll-hide group pb-2" style="scroll-behavior: smooth;">
                        <div id="recently-viewed-container" class="flex gap-3 lg:transition-transform lg:duration-300">
                            <!-- Products will be loaded here by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div id="recently-viewed-empty" class="text-center py-12 hidden">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <p class="text-gray-500 text-lg mb-2">No products viewed yet</p>
                    <p class="text-gray-400 text-sm">Products you view will appear here</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe all scroll-reveal elements
        document.querySelectorAll('.scroll-reveal').forEach(element => {
            observer.observe(element);
        });

        // Load Recently Viewed products
        loadRecentlyViewed();
    });

    // Recently Viewed Functions
    function loadRecentlyViewed() {
        const recentlyViewed = JSON.parse(localStorage.getItem('recentlyViewed') || '[]');
        const container = document.getElementById('recently-viewed-container');
        const emptyState = document.getElementById('recently-viewed-empty');
        const wrapper = document.getElementById('recently-viewed-wrapper');
        
        console.log('Loading recently viewed products:', recentlyViewed);
        
        if (!container) {
            console.log('Recently viewed container not found');
            return;
        }
        
        // Filter out current product and limit to 12 products
        const productsToShow = recentlyViewed.slice(0, 12);
        
        console.log('Products to show:', productsToShow.length);
        
        if (productsToShow.length === 0) {
            if (wrapper) wrapper.classList.add('hidden');
            if (emptyState) emptyState.classList.remove('hidden');
            console.log('No recently viewed products to display');
            return;
        }
        
        if (wrapper) wrapper.classList.remove('hidden');
        emptyState.classList.add('hidden');
        
        // Generate HTML for each product (same style as Related Products)
        container.innerHTML = productsToShow.map(product => `
            <a href="/products/${product.slug}" 
               class="flex-shrink-0 w-40 bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-300 group/item overflow-hidden border border-gray-200">
                <!-- Product Image -->
                <div class="relative aspect-square overflow-hidden">
                    ${product.image ? `
                        <img src="${product.image}" 
                             alt="${product.name}" 
                             class="w-full h-full object-cover group-hover/item:scale-105 transition-transform duration-300"
                             onerror="this.parentElement.innerHTML='<div class=\\'w-full h-full bg-gray-200 flex items-center justify-center\\'><svg class=\\'w-6 h-6 text-gray-400\\' fill=\\'none\\' stroke=\\'currentColor\\' viewBox=\\'0 0 24 24\\'><path stroke-linecap=\\'round\\' stroke-linejoin=\\'round\\' stroke-width=\\'2\\' d=\\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\\'></path></svg></div>'">
                    ` : `
                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    `}
                </div>

                <!-- Product Info (Compact) -->
                <div class="p-2.5">
                    <h4 class="font-medium text-gray-900 text-xs line-clamp-2 group-hover/item:text-[#005366] transition-colors mb-1.5 h-8 overflow-hidden" title="${product.name}">
                        ${product.name.length > 30 ? product.name.substring(0, 30) + '...' : product.name}
                    </h4>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#E2150C]">${CURRENCY_SYMBOL}${parseFloat(product.price).toFixed(2)}</span>
                        <div class="flex items-center text-xs text-gray-500">
                            <svg class="w-3 h-3 text-yellow-400 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <span class="text-xs">4.5</span>
                        </div>
                    </div>
                </div>
            </a>
        `).join('');
        
        // Show/hide navigation buttons based on number of products
        updateRecentlyViewedNavigation(productsToShow.length);
    }

    // Recently Viewed Carousel Navigation
    let recentlyViewedCurrentIndex = 0;

    function scrollRecentlyViewed(direction) {
        const container = document.getElementById('recentlyViewedContainer');
        const track = document.getElementById('recently-viewed-container');
        const prevBtn = document.getElementById('recentlyViewedPrevBtn');
        const nextBtn = document.getElementById('recentlyViewedNextBtn');
        
        if (!track) return;
        
        const itemWidth = 160 + 12; // w-40 (160px) + gap-3 (12px)
        const containerWidth = container.offsetWidth;
        const itemsVisible = Math.floor(containerWidth / itemWidth);
        const totalItems = track.children.length;
        const maxIndex = Math.max(0, totalItems - itemsVisible);
        
        if (direction === 'next') {
            recentlyViewedCurrentIndex = Math.min(recentlyViewedCurrentIndex + itemsVisible, maxIndex);
        } else {
            recentlyViewedCurrentIndex = Math.max(0, recentlyViewedCurrentIndex - itemsVisible);
        }
        
        const translateX = -recentlyViewedCurrentIndex * itemWidth;
        track.style.transform = `translateX(${translateX}px)`;
        
        // Update button states
        if (prevBtn) {
            if (recentlyViewedCurrentIndex === 0) {
                prevBtn.classList.add('opacity-0');
            } else {
                prevBtn.classList.remove('opacity-0');
            }
        }
        
        if (nextBtn) {
            if (recentlyViewedCurrentIndex >= maxIndex) {
                nextBtn.classList.add('opacity-0');
            } else {
                nextBtn.classList.remove('opacity-0');
            }
        }
    }

    function updateRecentlyViewedNavigation(totalProducts) {
        const prevBtn = document.getElementById('recentlyViewedPrevBtn');
        const nextBtn = document.getElementById('recentlyViewedNextBtn');
        
        if (!prevBtn || !nextBtn) return;
        
        // Only show navigation buttons on desktop (lg: 1024px+) if more than what can fit on screen
        const isDesktop = window.innerWidth >= 1024;
        
        if (isDesktop && totalProducts > 5) {
            prevBtn.classList.remove('hidden');
            prevBtn.classList.add('lg:block');
            nextBtn.classList.remove('hidden');
            nextBtn.classList.add('lg:block');
            
            // Set initial state
            prevBtn.classList.add('opacity-0');
            nextBtn.classList.remove('opacity-0');
            
            // Reset index
            recentlyViewedCurrentIndex = 0;
            
            // Reset transform (only for desktop)
            const track = document.getElementById('recently-viewed-container');
            if (track) {
                track.style.transform = 'translateX(0px)';
            }
        } else {
            // Hide navigation buttons on mobile or if 5 or fewer products
            prevBtn.classList.add('hidden');
            nextBtn.classList.add('hidden');
            
            // Remove transform on mobile
            const track = document.getElementById('recently-viewed-container');
            if (track && !isDesktop) {
                track.style.transform = '';
            }
        }
    }
</script>
@endsection
