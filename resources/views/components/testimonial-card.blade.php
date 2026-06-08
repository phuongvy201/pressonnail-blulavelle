@props([
    'review',
    'compact' => false,
])

@php
    $displayName = $review->display_name;
    $cardTitle = $review->title ?? \Illuminate\Support\Str::limit($review->review_text ?? '', 40);
    $reviewText = $review->review_text ?? '';
    $nameForInitials = $review->customer_name ?? $review->display_name ?? '';
    $nameForInitials = trim((string) $nameForInitials);
    $parts = $nameForInitials !== '' ? preg_split('/\s+/', $nameForInitials, 3) : [];
    if ($nameForInitials === '') {
        $initials = '?';
    } elseif (count($parts) >= 2) {
        $initials = strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    } else {
        $initials = strtoupper(mb_substr($nameForInitials, 0, 2));
    }
    $hasImage = !empty($review->image_url);
    $imgUrl = $hasImage ? (str_starts_with($review->image_url, 'http') ? $review->image_url : asset($review->image_url)) : '';
    $verified = (bool) $review->is_verified_purchase;
@endphp

@if($compact)
    <div {{ $attributes->merge(['class' => 'testimonial-card-compact h-full bg-slate-50 rounded-2xl border border-slate-100 shadow-sm p-5 text-left flex flex-col']) }}>
        <div class="flex gap-4 items-start mb-3">
            <div class="relative w-20 h-20 rounded-xl overflow-hidden shadow-sm shrink-0 bg-slate-200">
                @if($hasImage)
                    <img alt="{{ $displayName }}" class="w-full h-full object-cover"
                         src="{{ optimized_local_img($imgUrl, 160) }}"
                         sizes="80px" width="160" height="160" loading="lazy" decoding="async">
                @else
                    <div class="w-full h-full flex items-center justify-center text-slate-500 text-lg font-bold">{{ $initials }}</div>
                @endif
            </div>
            <div class="min-w-0 flex-1 pt-0.5">
                <h3 class="text-base font-extrabold text-slate-900 leading-snug line-clamp-2">{{ $cardTitle }}</h3>
                <div class="flex text-primary mt-1.5" aria-hidden="true">
                    @for($i = 0; $i < 5; $i++)
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    @endfor
                </div>
            </div>
        </div>
        <p class="text-slate-600 text-sm italic leading-relaxed line-clamp-4 flex-1">"{{ $reviewText }}"</p>
        <div class="flex items-center gap-2.5 mt-4 pt-4 border-t border-slate-200/80">
            <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center shrink-0" aria-hidden="true">
                <span class="text-slate-600 text-xs font-bold">{{ $initials }}</span>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-slate-900 text-sm truncate">{{ $displayName }}</p>
                @if($verified)
                    <p class="text-[10px] text-slate-600 flex items-center gap-1"><span class="text-primary">✓</span> Verified Buyer</p>
                @endif
            </div>
        </div>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex flex-col items-center']) }}>
        <div class="relative rounded-2xl overflow-hidden mb-6 aspect-square shadow-md max-w-xs w-full mx-auto">
            @if($hasImage)
                <img alt="{{ $displayName }}" class="w-full h-full object-cover"
                     src="{{ optimized_local_img($imgUrl, 560) }}"
                     sizes="(max-width: 768px) 88vw, 320px"
                     width="560" height="560" loading="lazy" decoding="async">
            @else
                <div class="w-full h-full bg-slate-200 flex items-center justify-center text-slate-500 text-4xl font-bold">{{ $initials }}</div>
            @endif
        </div>
        <h3 class="text-xl font-extrabold mb-4 text-slate-900">{{ $cardTitle }}</h3>
        <p class="text-slate-600 text-sm italic mb-6">"{{ $reviewText }}"</p>
        <div class="flex flex-col items-center">
            <div class="w-10 h-10 rounded-full bg-slate-200 mb-2 overflow-hidden flex items-center justify-center flex-shrink-0" aria-hidden="true">
                <span class="text-slate-600 text-sm font-bold">{{ $initials }}</span>
            </div>
            <p class="font-bold text-slate-900">{{ $displayName }}</p>
            @if($verified)
                <p class="text-[10px] text-slate-600 flex items-center gap-1"><span class="text-primary">✓</span> Verified Buyer</p>
            @endif
        </div>
    </div>
@endif
