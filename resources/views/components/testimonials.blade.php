@props([
    'title' => 'Over 50,000+ Happy Customers',
    'rating' => '4.8/5',
    'reviews' => null,
    'bgColor' => null,
    /** URL trang tất cả review nền tảng (mặc định route reviews.public) */
    'allReviewsUrl' => null,
])
@php
    $allReviewsHref = $allReviewsUrl ?? route('reviews.public');
    $perSlide = 3;
    $totalToLoad = 9;
    $bgSetting = \App\Support\Settings::get('theme.testimonials_bg', config('theme.testimonials_bg'));
    $bgCustom = null;
    if (is_string($bgColor) && (str_starts_with(trim($bgColor), '#') || str_starts_with(trim($bgColor), 'rgb'))) {
        $bgCustom = trim($bgColor);
    } elseif (is_string($bgSetting) && (str_starts_with(trim($bgSetting), '#') || str_starts_with(trim($bgSetting), 'rgb'))) {
        $bgCustom = trim($bgSetting);
    }
    if (!isset($reviews)) {
        $pinned = \App\Models\Review::approved()->pinnedToHome()->orderByDesc('created_at')->limit($totalToLoad)->get();
        if ($pinned->count() >= $totalToLoad) {
            $reviews = $pinned;
        } else {
            $excludeIds = $pinned->pluck('id')->all();
            $fill = \App\Models\Review::approved()->when(count($excludeIds) > 0, fn($q) => $q->whereNotIn('id', $excludeIds))->orderByDesc('created_at')->limit($totalToLoad - $pinned->count())->get();
            $reviews = $pinned->concat($fill)->take($totalToLoad);
        }
        $reviewsSlides = $reviews->chunk($perSlide)->values();
    } else {
        $reviewsSlides = $reviews->chunk($perSlide)->values();
    }
@endphp
<section {{ $attributes->merge(['class' => 'px-4 sm:px-6 lg:px-20 py-16 sm:py-20 md:py-24 bg-white']) }} @if($bgCustom) style="background-color: {{ $bgCustom }};" @endif>
    <div class="max-w-7xl mx-auto text-center">
        <div class="flex justify-center text-primary mb-2">
            @for($i = 0; $i < 5; $i++)
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
            @endfor
            <span class="ml-2 font-bold text-slate-900">{{ $rating }}</span>
        </div>
        <h2 class="text-3xl lg:text-5xl font-black text-slate-900 mb-4">{{ $title }}</h2>
        <div class="mb-10 sm:mb-14">
            <a href="{{ $allReviewsHref }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold text-[#0297FE] border-2 border-[#0297FE]/35 bg-white hover:bg-[#0297FE]/8 transition-colors shadow-sm">
                View all reviews
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        @if(isset($cards))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                {{ $cards }}
            </div>
        @elseif($reviews->isNotEmpty())
            @php
                $getInitials = function($name) {
                    $name = trim((string) $name);
                    if ($name === '') return '?';
                    $parts = preg_split('/\s+/', $name, 3);
                    if (count($parts) >= 2) {
                        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
                    }
                    return strtoupper(mb_substr($name, 0, 2));
                };
            @endphp
            <div class="relative testimonials-carousel">
                <div class="overflow-hidden">
                    <div class="testimonials-slides flex transition-transform duration-300 ease-out" style="width: {{ $reviewsSlides->count() * 100 }}%">
                        @foreach($reviewsSlides as $slideIndex => $slideReviews)
                            <div class="testimonials-slide flex-shrink-0 px-2" style="width: {{ 100 / $reviewsSlides->count() }}%">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                                    @foreach($slideReviews as $review)
                                        @php
                                            $hasImage = !empty($review->image_url);
                                            $imgUrl = $hasImage ? (str_starts_with($review->image_url, 'http') ? $review->image_url : asset($review->image_url)) : '';
                                            $cardTitle = $review->title ?? \Illuminate\Support\Str::limit($review->review_text ?? '', 40);
                                            $initials = $getInitials($review->customer_name ?? $review->display_name ?? '');
                                        @endphp
                                        <div class="flex flex-col items-center">
                                            <div class="relative rounded-2xl overflow-hidden mb-6 aspect-square shadow-md max-w-xs w-full mx-auto">
                                                @if($hasImage)
                                                    <img alt="{{ $review->display_name }}" class="w-full h-full object-cover"
                                                         src="{{ optimized_local_img($imgUrl, 560) }}"
                                                         sizes="(max-width: 768px) 88vw, 320px"
                                                         width="560" height="560" loading="lazy" decoding="async">
                                                @else
                                                    <div class="w-full h-full bg-slate-200 flex items-center justify-center text-slate-500 text-4xl font-bold">{{ $initials }}</div>
                                                @endif
                                            </div>
                                            <h3 class="text-xl font-extrabold mb-4 text-slate-900">{{ $cardTitle }}</h3>
                                            <p class="text-slate-600 text-sm italic mb-6">"{{ $review->review_text ?? '' }}"</p>
                                            <div class="flex flex-col items-center">
                                                <div class="w-10 h-10 rounded-full bg-slate-200 mb-2 overflow-hidden flex items-center justify-center flex-shrink-0" aria-hidden="true">
                                                    <span class="text-slate-600 text-sm font-bold">{{ $initials }}</span>
                                                </div>
                                                <p class="font-bold text-slate-900">{{ $review->display_name }}</p>
                                                @if($review->is_verified_purchase)
                                                    <p class="text-[10px] text-slate-600 flex items-center gap-1"><span class="text-primary">✓</span> Verified Buyer</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @if($reviewsSlides->count() > 1)
                    <div class="flex justify-center items-center gap-4 mt-10">
                        <button type="button" class="testimonials-prev inline-flex items-center justify-center w-12 h-12 rounded-full border-2 border-slate-300 text-slate-600 hover:bg-slate-100 hover:border-slate-400 transition-colors" aria-label="Xem trước">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        <span class="text-sm font-medium text-slate-600 testimonials-pagination">1 / {{ $reviewsSlides->count() }}</span>
                        <button type="button" class="testimonials-next inline-flex items-center justify-center w-12 h-12 rounded-full border-2 border-slate-300 text-slate-600 hover:bg-slate-100 hover:border-slate-400 transition-colors" aria-label="Xem thêm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                    <script>
                    (function() {
                        var carousel = document.querySelector('.testimonials-carousel');
                        if (!carousel) return;
                        var slidesEl = carousel.querySelector('.testimonials-slides');
                        var total = {{ $reviewsSlides->count() }};
                        var current = 0;
                        var paginationEl = carousel.querySelector('.testimonials-pagination');
                        function go(slideIndex) {
                            current = Math.max(0, Math.min(slideIndex, total - 1));
                            if (slidesEl) slidesEl.style.transform = 'translateX(-' + (current * (100 / total)) + '%)';
                            if (paginationEl) paginationEl.textContent = (current + 1) + ' / ' + total;
                        }
                        carousel.querySelector('.testimonials-prev')?.addEventListener('click', function() { go(current - 1); });
                        carousel.querySelector('.testimonials-next')?.addEventListener('click', function() { go(current + 1); });
                    })();
                    </script>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="flex flex-col items-center">
                    <div class="relative rounded-2xl overflow-hidden mb-6 aspect-square shadow-md max-w-xs w-full mx-auto">
                        <img alt="Customer Sarah" class="w-full h-full object-cover" loading="lazy" decoding="async" sizes="(max-width: 768px) 88vw, 320px" width="560" height="560"
                             src="{{ optimized_local_img(asset('storage/images/c768ab6feb861eabf2beb33c0fb2cebc.jpg'), 560) }}">
                    </div>
                    <h3 class="text-xl font-extrabold mb-4 text-slate-900">I'm totally blown away.</h3>
                    <p class="text-slate-600 text-sm italic mb-6">"Salon quality at home was the dream. These press-on nails have changed the way I do my nails forever. So many compliments!"</p>
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-slate-200 mb-2 flex items-center justify-center text-slate-600 text-xs font-bold" aria-hidden="true">SF</div>
                        <p class="font-bold text-slate-900">Sarah F.</p>
                        <p class="text-[10px] text-slate-600 flex items-center gap-1"><span class="text-primary">✓</span> Verified Buyer</p>
                    </div>
                </div>
                <div class="flex flex-col items-center">
                    <div class="relative rounded-2xl overflow-hidden mb-6 aspect-square shadow-md max-w-xs w-full mx-auto">
                        <img alt="Customer Susan" class="w-full h-full object-cover" loading="lazy" decoding="async" sizes="(max-width: 768px) 88vw, 320px" width="560" height="560"
                             src="{{ optimized_local_img(asset('storage/images/44ad1fa40f4f3b0b55214cf29e1dd8a2.jpg'), 560) }}">
                    </div>
                    <h3 class="text-xl font-extrabold mb-4 text-slate-900">Smooth, chic, and easy.</h3>
                    <p class="text-slate-600 text-sm italic mb-6">"I've only been using these for a week, but they look amazing. Application was so easy. My nails have never looked better!"</p>
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-slate-200 mb-2 flex items-center justify-center text-slate-600 text-xs font-bold" aria-hidden="true">ST</div>
                        <p class="font-bold text-slate-900">Susan T.</p>
                        <p class="text-[10px] text-slate-600 flex items-center gap-1"><span class="text-primary">✓</span> Verified Buyer</p>
                    </div>
                </div>
                <div class="flex flex-col items-center">
                    <div class="relative rounded-2xl overflow-hidden mb-6 aspect-square shadow-md max-w-xs w-full mx-auto">
                        <img alt="Customer Mariah" class="w-full h-full object-cover" loading="lazy" decoding="async" sizes="(max-width: 768px) 88vw, 320px" width="560" height="560"
                             src="{{ optimized_local_img(asset('storage/images/1769484507_zFot4Im9WW.png'), 560) }}">
                    </div>
                    <h3 class="text-xl font-extrabold mb-4 text-slate-900">You have to try this!</h3>
                    <p class="text-slate-600 text-sm italic mb-6">"Money well spent! I can't believe what I used to pay for salon manicures. These do the same thing, minus the wait and the cost."</p>
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-slate-200 mb-2 flex items-center justify-center text-slate-600 text-xs font-bold" aria-hidden="true">MB</div>
                        <p class="font-bold text-slate-900">Mariah B.</p>
                        <p class="text-[10px] text-slate-600 flex items-center gap-1"><span class="text-primary">✓</span> Verified Buyer</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
