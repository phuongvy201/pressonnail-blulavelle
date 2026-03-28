@extends('layouts.app')

@section('title', 'Reviews — ' . $shop->shop_name)

@section('content')
@php
    $fmtCount = function (int $n): string {
        if ($n >= 1_000_000) {
            return round($n / 1_000_000, 1) . 'M';
        }
        if ($n >= 1000) {
            $k = $n / 1000;

            return (abs($k - round($k)) < 0.05 ? (string) (int) round($k) : rtrim(rtrim(number_format($k, 1), '0'), '.')) . 'k';
        }

        return number_format($n);
    };
    $countLabel = $fmtCount(max(0, $totalReviews));
@endphp
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<main class="min-h-screen bg-[#f8f6f6]" style="font-family:'Plus Jakarta Sans',system-ui,sans-serif;">
    <div class="max-w-[920px] mx-auto w-full px-4 sm:px-6 py-8 md:py-12 pb-20">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-slate-500 mb-6 flex flex-wrap items-center gap-2">
            <a href="{{ route('home') }}" class="hover:text-[#0297FE]">Home</a>
            <span>/</span>
            <a href="{{ route('shops.show', $shop->shop_slug) }}" class="hover:text-[#0297FE] truncate max-w-[200px]">{{ $shop->shop_name }}</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">Reviews</span>
        </nav>

        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden">
            <div class="p-6 md:p-10">
                <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight">
                    Reviews for this shop ({{ $countLabel }})
                </h1>

                @if($totalReviews === 0)
                    <div class="mt-10 text-center py-16 px-4">
                        <span class="material-symbols-outlined text-6xl text-slate-200">rate_review</span>
                        <p class="mt-4 text-slate-600 font-medium">There are no reviews for this shop’s products yet.</p>
                        <a href="{{ route('shops.show', $shop->shop_slug) }}" class="inline-flex mt-6 items-center gap-2 px-6 py-3 rounded-xl bg-[#0297FE] text-white font-bold hover:opacity-90 transition-opacity">
                            Back to shop
                        </a>
                    </div>
                @else
                    {{-- Summary: rating + distribution --}}
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12">
                        <div>
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-5xl text-amber-400" style="font-variation-settings:'FILL' 1;">star</span>
                                <div>
                                    <p class="text-4xl md:text-5xl font-black text-slate-900 leading-none">{{ number_format($avgRating, 1) }}</p>
                                    <p class="mt-2 text-sm text-slate-500">{{ $countLabel }} ratings</p>
                                </div>
                            </div>
                            <p class="mt-4 text-sm text-slate-500 leading-relaxed">
                                The average rating is calculated from all approved reviews. Recent reviews better reflect the current experience at this shop.
                            </p>
                        </div>
                        <div class="space-y-2.5">
                            @for($star = 5; $star >= 1; $star--)
                                @php $row = $distribution[$star] ?? ['percent' => 0, 'count' => 0]; @endphp
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="w-16 text-slate-600 shrink-0">{{ $star }} {{ $star === 1 ? 'star' : 'stars' }}</span>
                                    <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-amber-400 transition-all" style="width: {{ $row['percent'] }}%"></div>
                                    </div>
                                    <span class="w-10 text-right text-slate-500 tabular-nums">{{ $row['percent'] }}%</span>
                                </div>
                            @endfor
                        </div>
                    </div>

                    {{-- Filters --}}
                    <form method="get" action="{{ route('shops.reviews', $shop->shop_slug) }}" id="shop-reviews-filters" class="mt-8 flex flex-wrap items-center gap-3">
                        <div class="relative">
                            <label class="sr-only">Sort</label>
                            <select name="sort" onchange="this.form.submit()"
                                    class="appearance-none pl-4 pr-10 py-2.5 rounded-full border border-slate-200 bg-white text-sm font-semibold text-slate-800 shadow-sm focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE] cursor-pointer">
                                <option value="suggested" @selected($sort === 'suggested')>Suggested</option>
                                <option value="newest" @selected($sort === 'newest')>Newest</option>
                                <option value="oldest" @selected($sort === 'oldest')>Oldest</option>
                                <option value="highest" @selected($sort === 'highest')>Highest rating</option>
                                <option value="lowest" @selected($sort === 'lowest')>Lowest rating</option>
                            </select>
                            <span class="material-symbols-outlined pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-xl">expand_more</span>
                        </div>
                        <div class="relative">
                            <label class="sr-only">Rating filter</label>
                            <select name="rating" onchange="this.form.submit()"
                                    class="appearance-none pl-4 pr-10 py-2.5 rounded-full border border-slate-200 bg-white text-sm font-semibold text-slate-800 shadow-sm focus:ring-2 focus:ring-[#0297FE]/30 focus:border-[#0297FE] cursor-pointer">
                                <option value="" @selected($ratingFilter === null || $ratingFilter === '')>All ratings</option>
                                <option value="5" @selected((string)$ratingFilter === '5')>5 stars</option>
                                <option value="4" @selected((string)$ratingFilter === '4')>4 stars</option>
                                <option value="3" @selected((string)$ratingFilter === '3')>3 stars</option>
                                <option value="2" @selected((string)$ratingFilter === '2')>2 stars</option>
                                <option value="1" @selected((string)$ratingFilter === '1')>1 star</option>
                            </select>
                            <span class="material-symbols-outlined pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-xl">expand_more</span>
                        </div>
                    </form>

                    {{-- Photo strip --}}
                    @if($photoReviews->isNotEmpty())
                    <div class="mt-8">
                        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-3">Photos from reviews</h2>
                        <p class="text-xs text-slate-500 mb-2">Click a photo to enlarge</p>
                        <div class="relative group">
                            <div id="review-photo-strip" class="flex gap-2 overflow-x-auto pb-2 scroll-smooth no-scrollbar snap-x snap-mandatory">
                                @foreach($photoReviews as $pr)
                                    <div class="snap-start shrink-0 w-[100px] sm:w-[120px] aspect-square rounded-xl overflow-hidden border border-slate-100 bg-slate-50">
                                        <img src="{{ $pr->image_url_for_display }}" alt="Review photo" class="w-full h-full object-cover cursor-pointer js-review-photo-trigger hover:opacity-95 transition-opacity" loading="lazy" onerror="this.closest('div').classList.add('hidden')">
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" id="review-photo-next" class="absolute right-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white shadow-lg border border-slate-200 flex items-center justify-center opacity-90 hover:opacity-100 transition-opacity md:group-hover:opacity-100 opacity-0 md:opacity-0 pointer-events-none md:pointer-events-auto"
                                    aria-label="Scroll photos">
                                <span class="material-symbols-outlined text-slate-700">chevron_right</span>
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Review list --}}
                    <div class="mt-10 divide-y divide-slate-100">
                        @forelse($reviews as $review)
                        <article class="py-8 first:pt-0">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex text-amber-400">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="material-symbols-outlined text-lg {{ $i <= (int) $review->rating ? 'fill-current' : 'text-slate-200' }}">star</span>
                                    @endfor
                                </div>
                                @if((int) $review->rating >= 4)
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full shrink-0">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                    Recommends
                                </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 mb-3">
                                @if($review->user && $review->user->avatar)
                                    <img src="{{ $review->user->avatar }}" alt="" class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow">
                                @else
                                    <span class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0297FE] to-cyan-500 text-white flex items-center justify-center text-sm font-bold">
                                        {{ strtoupper(substr($review->display_name, 0, 1)) }}
                                    </span>
                                @endif
                                <div>
                                    <p class="font-bold text-slate-900">{{ $review->display_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $review->created_at?->format('M j, Y') }}</p>
                                </div>
                            </div>
                            @if($review->product)
                            <p class="text-sm text-slate-600 mb-2">
                                <span class="text-slate-500">Purchased item:</span>
                                <a href="{{ route('products.show', $review->product->slug) }}" class="font-semibold text-[#0297FE] hover:underline">{{ Str::limit($review->product->name, 80) }}</a>
                            </p>
                            @endif
                            <div class="flex flex-col sm:flex-row gap-4 sm:items-start">
                                <div class="flex-1 min-w-0">
                                    @if(!empty($review->title))
                                        <p class="font-semibold text-slate-900 mb-1">{{ $review->title }}</p>
                                    @endif
                                    @if(!empty($review->review_text))
                                        <p class="text-slate-700 text-sm leading-relaxed">{{ $review->review_text }}</p>
                                    @endif
                                </div>
                                @if(!empty($review->image_url_for_display))
                                <div class="shrink-0 w-full sm:w-28">
                                    <img src="{{ $review->image_url_for_display }}" alt="Review photo — click to enlarge" class="w-full aspect-square object-cover rounded-lg border border-slate-200 cursor-pointer js-review-photo-trigger hover:ring-2 hover:ring-[#0297FE]/40 transition-shadow" loading="lazy" onerror="this.style.display='none'">
                                </div>
                                @endif
                            </div>
                        </article>
                        @empty
                        <p class="py-10 text-center text-slate-600">No reviews match your filters. Try changing the star filter or sort order.</p>
                        @endforelse
                    </div>

                    @if($reviews->hasPages())
                    <div class="mt-10 flex justify-center border-t border-slate-100 pt-8">
                        {{ $reviews->links() }}
                    </div>
                    @endif

                    <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                        <a href="{{ route('shops.show', $shop->shop_slug) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#0297FE] hover:underline">
                            <span class="material-symbols-outlined text-lg">storefront</span>
                            Back to {{ $shop->shop_name }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>

@if($totalReviews > 0)
{{-- Full-screen image modal (no backdrop-blur: avoids flicker when scroll is locked) --}}
<div id="review-image-modal" class="review-image-modal-root fixed inset-0 z-[100] hidden flex items-center justify-center p-2 sm:p-3" role="dialog" aria-modal="true" aria-labelledby="review-image-modal-title">
    <div class="absolute inset-0 review-image-modal-backdrop" data-review-image-modal-close aria-hidden="true"></div>
    <p id="review-image-modal-title" class="sr-only">Review photo</p>
    <button type="button" class="absolute top-3 right-3 sm:top-4 sm:right-4 z-20 w-11 h-11 rounded-full bg-white/95 border border-slate-200 shadow-lg flex items-center justify-center text-slate-700 hover:bg-slate-100 transition-colors" data-review-image-modal-close aria-label="Close">
        <span class="material-symbols-outlined text-2xl">close</span>
    </button>
    <div class="relative z-10 w-full max-w-[min(100vw-0.5rem,1600px)] max-h-[96vh] flex items-center justify-center pointer-events-none px-1">
        <img id="review-image-modal-img" src="" alt="Review photo" class="pointer-events-auto max-h-[min(94vh,94dvh)] w-auto max-w-full object-contain rounded-xl shadow-2xl ring-1 ring-white/10">
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('review-image-modal');
    var modalImg = document.getElementById('review-image-modal-img');
    var strip = document.getElementById('review-photo-strip');
    var btn = document.getElementById('review-photo-next');

    function openReviewImageModal(src) {
        if (!modal || !modalImg || !src) return;
        modalImg.src = src;
        modal.classList.remove('hidden');
        var sb = window.innerWidth - document.documentElement.clientWidth;
        if (sb > 0) {
            document.body.style.paddingRight = sb + 'px';
        }
        document.body.style.overflow = 'hidden';
    }

    function closeReviewImageModal() {
        if (!modal || !modalImg) return;
        modal.classList.add('hidden');
        modalImg.removeAttribute('src');
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
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeReviewImageModal();
        }
    });

    if (strip && btn) {
        btn.classList.remove('opacity-0', 'pointer-events-none');
        btn.addEventListener('click', function() {
            strip.scrollBy({ left: Math.max(280, strip.clientWidth * 0.6), behavior: 'smooth' });
        });
    }
});
</script>
@endif
<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.review-image-modal-backdrop {
    background: rgba(15, 23, 42, 0.92);
}
.review-image-modal-root {
    isolation: isolate;
    contain: layout style;
}
</style>
@endsection
