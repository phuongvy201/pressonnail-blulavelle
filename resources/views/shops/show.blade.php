@extends('layouts.app')

@section('title', $shop->shop_name . ' - Shop Profile')

@section('content')
@php
    $currentCurrency = currency();
    $currencySymbol = currency_symbol();
@endphp
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script>
// Track Facebook Pixel ViewContent for shop page
document.addEventListener('DOMContentLoaded', function() {
    if (typeof fbq !== 'undefined') {
        fbq('track', 'ViewContent', {
            content_name: '{{ addslashes($shop->name) }}',
            content_type: 'product_group'
        });
    }
});
</script>

<main class="shop-page bg-background-light flex flex-col flex-1 px-4 md:px-20 lg:px-40 py-8 max-w-[1440px] mx-auto w-full pb-20">
    <!-- Shop Cover Banner -->
    <div class="relative w-full aspect-[21/9] min-h-[200px] max-h-[380px] overflow-hidden rounded-xl -mx-4 md:-mx-20 lg:-mx-40 mt-0">
        @if($shop->shop_banner)
            <img alt="Shop Cover Banner" class="absolute inset-0 w-full h-full object-cover object-center" src="{{ $shop->shop_banner }}">
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-primary/10 to-primary/5"></div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent pointer-events-none"></div>
    </div>

    <!-- Shop Profile Card -->
    <div class="-mt-10 relative z-10">
        <div class="bg-white rounded-xl shadow-lg border border-primary/5 p-6 md:p-8">
            <div class="flex flex-col md:flex-row items-center md:items-end gap-6">
                <!-- Shop Avatar -->
                <div class="relative">
                    <div class="w-28 h-28 rounded-full border-4 border-white overflow-hidden shadow-lg bg-white">
                        @if($shop->shop_logo)
                            <img alt="{{ $shop->shop_name }} Profile" class="w-full h-full object-cover" src="{{ $shop->shop_logo }}">
                        @else
                            <div class="w-full h-full bg-slate-200 flex items-center justify-center">
                                <span class="text-3xl font-bold text-slate-400">{{ substr($shop->shop_name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    @if($shop->verified)
                        <div class="absolute -bottom-1 -right-1 w-7 h-7 bg-primary rounded-full flex items-center justify-center border-2 border-white">
                            <span class="material-symbols-outlined text-white text-sm">check</span>
                        </div>
                    @endif
                </div>

                <!-- Shop Info -->
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-2xl font-bold text-slate-900 mb-2">{{ $shop->shop_name }}</h2>
                    <div class="flex items-center justify-center md:justify-start gap-6 text-slate-500 text-sm font-medium">
                        <span class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-lg">group</span>
                            <span data-followers>{{ number_format($stats['followers']) }}</span> Followers
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-lg">favorite</span>
                            {{ number_format($stats['favorited']) }} Favorited
                        </span>
                        @if(($shopReviewsCount ?? 0) > 0)
                        <a href="{{ route('shops.reviews', $shop->shop_slug) }}" class="flex items-center gap-1.5 text-primary font-semibold hover:underline">
                            <span class="material-symbols-outlined text-lg">star</span>
                            {{ number_format($shopReviewsAvg ?? 0, 1) }} · {{ number_format($shopReviewsCount) }} reviews
                        </a>
                        @endif
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <button id="followBtn"
                            onclick="toggleFollow()"
                            class="flex-1 md:flex-none bg-primary hover:opacity-90 text-white font-semibold py-3 px-8 rounded-lg flex items-center justify-center gap-2 transition-all shadow-md shadow-primary/20 {{ $isFollowing ? '!bg-slate-200 !text-slate-700 hover:!bg-slate-300 !shadow-none' : '' }}"
                            style="{{ !$isFollowing ? 'background-color: var(--primary);' : '' }}">
                        <span class="material-symbols-outlined text-lg">favorite</span>
                        <span id="followText">{{ $isFollowing ? 'Unfollow' : 'Follow' }}</span>
                    </button>
                    <button onclick="openContactModal()"
                            class="flex-1 md:flex-none border border-primary/20 hover:bg-primary/10 text-slate-700 font-semibold py-3 px-8 rounded-lg flex items-center justify-center gap-2 transition-all">
                        <span class="material-symbols-outlined text-lg">mail</span>
                        <span>Contact</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Shop Categories Section -->
    @if($categories->count() > 0)
    <section class="mt-10">
        <h3 class="text-xl font-bold text-slate-900 mb-4">Shop Categories</h3>
        <div id="categoriesContainer" class="flex gap-3 overflow-x-auto pb-2 hide-scrollbar">
            @foreach($categories as $category)
            @php
                $firstProduct = null;
                if ($category->templates->isNotEmpty()) {
                    foreach ($category->templates as $template) {
                        if ($template->products->isNotEmpty()) {
                            $firstProduct = $template->products->first();
                            break;
                        }
                    }
                }
                $imageUrl = null;
                if ($firstProduct && count($firstProduct->getEffectiveMedia()) > 0) {
                    $media = $firstProduct->getEffectiveMedia();
                    $imageUrl = is_array($media) && isset($media[0]) ? $media[0] : (is_string($media) ? $media : '');
                }
            @endphp
            <button type="button" onclick="filterByCategory('{{ $category->id }}')" class="shop-category-pill flex-shrink-0 flex items-center gap-3 rounded-full pl-1 pr-5 py-1.5 bg-white border border-slate-200/80 shadow-sm hover:border-primary/30 hover:shadow-md hover:bg-primary/5 transition-all duration-200 text-left group">
                <span class="w-12 h-12 rounded-full overflow-hidden bg-slate-100 flex-shrink-0 ring-2 ring-white shadow-sm">
                    @if($imageUrl)
                        <img alt="{{ $category->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" src="{{ $imageUrl }}">
                    @else
                        <span class="w-full h-full flex items-center justify-center text-slate-400">
                            <span class="material-symbols-outlined text-2xl">category</span>
                        </span>
                    @endif
                </span>
                <span class="flex flex-col min-w-0">
                    <span class="font-semibold text-slate-800 group-hover:text-primary transition-colors text-sm truncate">{{ $category->name }}</span>
                    <span class="text-xs text-slate-500">{{ $category->templates->count() }} items</span>
                </span>
            </button>
            @endforeach
        </div>
    </section>
    @endif

    <!-- All Products Section -->
    <section id="productsContent" class="mt-10">
        <div class="flex items-baseline justify-between mb-6 border-b border-primary/5 pb-4">
            <h3 class="text-2xl font-bold text-slate-900">All Products</h3>
            <div class="flex gap-2">
                <select class="bg-white border border-primary/10 rounded-lg px-4 py-2 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option>Newest first</option>
                    <option>Price: Low to High</option>
                    <option>Price: High to Low</option>
                    <option>Popular</option>
                </select>
            </div>
        </div>

        @if($allProducts->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
            @foreach($allProducts as $product)
            @php
                $media = $product->getEffectiveMedia();
                $imageUrl = null;
                if ($media && count($media) > 0) {
                    if (is_string($media[0])) {
                        $imageUrl = $media[0];
                    } elseif (is_array($media[0])) {
                        $imageUrl = $media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? null;
                    }
                }
                $avgRating = $product->getAverageRating();
                $reviewsCount = $product->getTotalReviews();
                $variants = $product->variants ?? collect();
                $colorSwatches = $variants->take(8)->map(function ($v) {
                    $attrs = $v->attributes ?? [];
                    $color = $attrs['color'] ?? $attrs['Color'] ?? null;
                    return $color && (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color) || preg_match('/^rgb|^hsl/', $color)) ? $color : null;
                })->filter()->unique()->take(4)->values();
            @endphp
            <div class="group flex flex-col bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border border-primary/5">
                <!-- Product Image -->
                <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                    @if($imageUrl)
                        <img alt="{{ $product->name }}" class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500" src="{{ $imageUrl }}">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-slate-400 text-6xl">image</span>
                        </div>
                    @endif
                    <button type="button" class="absolute top-3 right-3 z-10 size-8 flex items-center justify-center bg-white/80 backdrop-blur rounded-full text-primary hover:bg-primary hover:text-white transition-all shadow-sm">
                        <span class="material-symbols-outlined text-xl">favorite</span>
                    </button>
                </div>
                <!-- Product Info -->
                <div class="p-4 flex flex-col gap-2">
                    <div class="flex justify-between items-start">
                        <h4 class="font-bold text-slate-900 line-clamp-1 group-hover:text-primary transition-colors">
                            <a href="{{ route('products.show', $product->slug) }}">{{ Str::limit($product->name, 45) }}</a>
                        </h4>
                        <span class="font-bold text-primary shrink-0">{{ format_price_usd((float) $product->base_price) }}</span>
                    </div>
                    @if($reviewsCount > 0)
                    <div class="flex items-center gap-1 text-yellow-500">
                        <span class="material-symbols-outlined text-sm fill-1">star</span>
                        <span class="text-xs font-semibold text-slate-600">{{ number_format($avgRating, 1) }} ({{ $reviewsCount }})</span>
                    </div>
                    @endif
                    @if($colorSwatches->isNotEmpty())
                    <div class="flex gap-1.5 mt-1">
                        @foreach($colorSwatches as $color)
                        <span class="size-4 rounded-full border border-slate-200 shrink-0" style="background-color: {{ $color }};" title="{{ $color }}"></span>
                        @endforeach
                    </div>
                    @endif
                    <a href="{{ route('products.show', $product->slug) }}" class="mt-2 w-full py-2 bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white rounded-lg text-sm font-bold transition-all text-center">
                        Add to Cart
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8 text-center">
            {{ $allProducts->links() }}
        </div>
        @else
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="size-24 bg-primary/10 text-primary rounded-full flex items-center justify-center mb-6">
                <span class="material-symbols-outlined text-5xl">inventory_2</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-2">No products yet</h2>
            <p class="text-slate-500 mb-8 max-w-md">This shop has no products yet. Check back later.</p>
        </div>
        @endif
    </section>
</main>

<!-- Contact Modal -->
<div id="contactModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-primary/5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900">Contact Shop</h3>
            <button type="button" onclick="closeContactModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="contactForm">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Subject</label>
                <input type="text" id="subject" name="subject" required
                       class="w-full px-3 py-2 border border-primary/10 rounded-lg bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Message</label>
                <textarea id="message" name="message" rows="4" required
                          class="w-full px-3 py-2 border border-primary/10 rounded-lg bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeContactModal()"
                        class="flex-1 px-4 py-2 border border-primary/20 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-primary hover:opacity-90 text-white rounded-lg transition-colors font-bold shadow-md shadow-primary/20">
                    Send Message
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .shop-page {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    :root {
        --primary: #0297FE;
        --background-light: #f8f6f6;
    }
    .bg-primary { background-color: var(--primary); }
    .text-primary { color: var(--primary); }
    .border-primary { border-color: var(--primary); }
    .bg-background-light { background-color: var(--background-light); }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    #contactModal.flex { display: flex !important; }
</style>

<script>
// Follow/Unfollow functionality
function toggleFollow() {
    @guest
        window.location.href = '{{ route("login") }}';
        return;
    @endguest

    const followBtn = document.getElementById('followBtn');
    const followText = document.getElementById('followText');
    const isCurrentlyFollowing = followBtn.classList.contains('!bg-slate-200');

    const action = isCurrentlyFollowing ? 'unfollow' : 'follow';
    const followUrl = '{{ route("shops.follow", $shop->shop_slug ?? $shop->id) }}';

    fetch(followUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ action: action })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Request failed');
            }).catch(() => {
                throw new Error('Server error: ' + response.status);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (action === 'follow') {
                followBtn.classList.remove('!bg-slate-200', '!text-slate-700');
                followBtn.classList.add('text-white');
                followBtn.style.backgroundColor = 'var(--primary)';
                followText.textContent = 'Unfollow';
            } else {
                followBtn.classList.remove('text-white');
                followBtn.classList.add('!bg-slate-200', '!text-slate-700');
                followBtn.style.backgroundColor = '';
                followText.textContent = 'Follow';
            }
            const followersElement = document.querySelector('[data-followers]');
            if (followersElement) {
                followersElement.textContent = data.followers_count;
            }
            if (typeof showNotification === 'function') {
                showNotification(data.message, 'success');
            } else {
                alert(data.message);
            }
        } else {
            if (typeof showNotification === 'function') {
                showNotification(data.message, 'error');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Follow shop error:', error);
        const msg = error.message || 'An error occurred. Please try again.';
        if (typeof showNotification === 'function') {
            showNotification(msg, 'error');
        } else {
            alert(msg);
        }
    });
}

function openContactModal() {
    var modal = document.getElementById('contactModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.style.display = 'flex';
}

function closeContactModal() {
    var modal = document.getElementById('contactModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.style.display = 'none';
}

document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var data = {
        subject: form.subject.value,
        message: form.message.value
    };
    fetch('{{ route("shops.contact", $shop) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(function(data) {
        if (data.success) {
            showNotification(data.message, 'success');
            closeContactModal();
            form.reset();
        } else {
            showNotification(data.message || 'Error', 'error');
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
});

function scrollCategories(direction) {
    var container = document.getElementById('categoriesContainer');
    if (!container) return;
    var scrollAmount = 200;
    container.scrollBy({ left: direction === 'left' ? -scrollAmount : scrollAmount, behavior: 'smooth' });
}

function filterByCategory(categoryId) {
    var productsGrid = document.querySelector('#productsContent .grid');
    if (productsGrid) {
        productsGrid.style.opacity = '0.5';
        productsGrid.style.pointerEvents = 'none';
    }
    showNotification('Loading products...', 'info');
    var el = document.getElementById('productsContent');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    setTimeout(function() {
        if (productsGrid) {
            productsGrid.style.opacity = '1';
            productsGrid.style.pointerEvents = 'auto';
        }
    }, 1000);
}

document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('categoriesContainer');
    if (container) {
        container.addEventListener('scroll', function() {
            var leftArrow = document.querySelector('button[onclick="scrollCategories(\'left\')"]');
            var rightArrow = document.querySelector('button[onclick="scrollCategories(\'right\')"]');
            if (leftArrow && rightArrow) {
                leftArrow.style.opacity = this.scrollLeft > 0 ? '1' : '0';
                rightArrow.style.opacity = this.scrollLeft < (this.scrollWidth - this.clientWidth) ? '1' : '0';
            }
        });
    }
});

function showNotification(message, type) {
    var notification = document.createElement('div');
    var bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : type === 'info' ? 'bg-blue-500' : 'bg-slate-500';
    notification.className = 'fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-[60] text-white ' + bgColor;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(function() { notification.remove(); }, 3000);
}
</script>
@endsection
