@extends('layouts.app')

@section('title', 'Virtual Nail Try-On')

@section('content')
@php
    $__vntPageJs = public_path('js/virtual-nail-trial-page.js');
    $__vntPageJsV = is_file($__vntPageJs) ? (string) filemtime($__vntPageJs) : '1';
    $__vntCaptureCss = public_path('css/virtual-nail-capture.css');
    $__vntCaptureCssV = is_file($__vntCaptureCss) ? (string) filemtime($__vntCaptureCss) : '1';
    $__vntPageCss = public_path('css/virtual-nail-page.css');
    $__vntPageCssV = is_file($__vntPageCss) ? (string) filemtime($__vntPageCss) : '1';
@endphp

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/virtual-nail-page.css') }}?v={{ $__vntPageCssV }}">

<div class="vnt-page">
    @if (! $enabled)
    <div class="vnt-page__wrap">
        <div class="vnt-empty-box">
            <h1>Virtual Try-On Unavailable</h1>
            <p>This feature is not configured yet. Please check back soon.</p>
            <a href="{{ route('products.index') }}" class="vnt-btn vnt-btn--primary">Browse Nails</a>
        </div>
    </div>
    @else
    <div class="vnt-page__wrap">
        <header class="vnt-page__hero">
            <div class="vnt-page__eyebrow">Virtual Try-On</div>
            <h1 class="vnt-page__title">Try it on before<br><em>you press &amp; go</em></h1>
            <p class="vnt-page__sub">Upload a hand photo, pick your design, shape, and length — see how press-on nails look on you before you buy.</p>
            <svg class="vnt-page__brush" viewBox="0 0 180 14" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M2 8 C 30 -2, 60 16, 90 7 S 150 -2, 178 8" stroke="#0195fe" stroke-width="2.5" fill="none" stroke-linecap="round"/>
            </svg>
        </header>

        <div class="vnt-async-tip" role="note">
            <div class="vnt-async-tip__icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <p class="vnt-async-tip__text"><strong>Tip:</strong> Generation can take 1–3 minutes. You can browse the store while you wait — we’ll notify you when your preview is ready.</p>
        </div>

        <div class="vnt-page__grid">
            <div>
                {{-- Step 1 --}}
                <div class="vnt-card">
                    <div class="vnt-card__head">
                        <span class="vnt-card__step">01</span>
                        <span class="vnt-card__title">Your hand photo</span>
                    </div>

                    <div class="vnt-tips">
                        <div class="vnt-tips__title">Capture tips</div>
                        <ul>
                            @foreach(($captureTips ?? []) as $tip)
                            <li>{{ $tip }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="vnt-dropzone" id="vnt-dropzone" role="button" tabindex="0">
                        <img id="vnt-preview" class="vnt-dropzone__img is-hidden" src="" alt="">
                        <span id="vnt-dz-badge" class="vnt-dz-badge is-hidden">Photo ready</span>
                        <div id="vnt-preview-placeholder">
                            <div class="vnt-cam-circle">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            </div>
                            <p class="vnt-dz-text">Take a photo or upload — we'll show a positioning guide on camera</p>
                        </div>
                    </div>
                    <input type="file" id="vnt-file-input" accept="image/jpeg,image/jpg,image/png,image/webp" class="hidden" style="display:none">

                    <div class="vnt-btn-row">
                        <button type="button" id="vnt-capture-btn" class="vnt-btn vnt-btn--primary">Take Photo</button>
                        <button type="button" id="vnt-upload-btn" class="vnt-btn vnt-btn--secondary">Upload Image</button>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div id="vnt-design-section" class="vnt-card @if($selectedProduct) is-hidden @endif">
                    <div class="vnt-card__head">
                        <span class="vnt-card__step">02</span>
                        <span class="vnt-card__title">Choose a design</span>
                    </div>
                    <div class="vnt-search">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A7A76" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
                        <input type="search" id="vnt-product-search" placeholder="Search nail designs...">
                    </div>
                    <div id="vnt-product-list" class="vnt-design-grid"></div>
                    <p id="vnt-product-empty" class="vnt-is-hidden" style="text-align:center;font-size:13px;color:#8A7A76;padding:24px 0;">No matching designs found.</p>
                </div>

                <div id="vnt-selected-product" class="vnt-card @if(!$selectedProduct) is-hidden @endif">
                    <div class="vnt-card__head">
                        <span class="vnt-card__step">02</span>
                        <span class="vnt-card__title">Selected design</span>
                    </div>
                    <div class="vnt-selected-row">
                        <img id="vnt-selected-product-img" src="{{ $selectedProduct['image'] ?? '' }}" alt="">
                        <div class="min-w-0 flex-1">
                            <p id="vnt-selected-product-name" class="vnt-selected-row__name">{{ $selectedProduct['name'] ?? '' }}</p>
                            <button type="button" id="vnt-change-design" class="vnt-link @if(!$selectedProduct) is-hidden @endif">Change design</button>
                        </div>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="vnt-card">
                    <div class="vnt-card__head">
                        <span class="vnt-card__step">03</span>
                        <span class="vnt-card__title">Shape &amp; length</span>
                    </div>

                    <div class="vnt-notice" role="note">
                        <div class="vnt-notice__icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>
                        </div>
                        <div class="vnt-notice__body">
                            <p class="vnt-notice__title">{{ $noticeTitle ?? 'For the best AI preview' }}</p>
                            <p class="vnt-notice__text">{{ $noticeText ?? '' }}</p>
                        </div>
                    </div>

                    <p class="vnt-group-label">Nail shape</p>
                    <div id="vnt-shape-options" class="vnt-pill-row">
                        @foreach($shapes as $shape)
                        <button type="button" class="vnt-pill vnt-option-btn {{ $shape === $defaultShape ? 'is-selected' : '' }}" data-group="shape" data-value="{{ $shape }}">
                            <span class="vnt-shape-icon vnt-s-{{ strtolower($shape) }}"></span>
                            {{ $shape }}
                        </button>
                        @endforeach
                    </div>

                    <p class="vnt-group-label">Nail length</p>
                    <div id="vnt-length-options" class="vnt-pill-row">
                        @foreach($lengths as $i => $length)
                        <button type="button" class="vnt-pill vnt-option-btn {{ $length === $defaultLength ? 'is-selected' : '' }}" data-group="length" data-value="{{ $length }}">
                            <span class="vnt-len-icon" style="width: {{ 12 + $i * 4 }}px"></span>
                            {{ $length }}
                        </button>
                        @endforeach
                    </div>
                </div>

                <button type="button" id="vnt-generate-btn" class="vnt-btn vnt-btn--primary vnt-btn--block" disabled>Generate Preview</button>
                <p id="vnt-background-notice" class="vnt-background-notice is-hidden">Your preview is generating in the background. Feel free to browse — we'll notify you when it's ready.</p>
                <p id="vnt-error" class="vnt-error is-hidden"></p>

                <div id="vnt-history-wrap" class="vnt-card is-hidden">
                    <div class="vnt-card__head">
                        <span class="vnt-card__step">★</span>
                        <span class="vnt-card__title">Your try-on history</span>
                    </div>
                    <div id="vnt-history-list" class="vnt-history"></div>
                </div>
            </div>

            {{-- Preview panel --}}
            <div class="vnt-preview-panel">
                <div class="vnt-preview-head">
                    <span class="vnt-preview-title">AI Preview</span>
                    <span class="vnt-status-dot">
                        <span class="vnt-dot" id="vnt-status-dot"></span>
                        <span id="vnt-status-text">Not ready</span>
                    </span>
                </div>

                <div class="vnt-preview-stage" id="vnt-preview-stage">
                    <div id="vnt-result-idle" style="display:flex;flex-direction:column;align-items:center;padding:24px;">
                        <div class="vnt-lock-circle">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        </div>
                        <div class="vnt-placeholder-text">Upload a hand photo and choose a design to see your <b>AI preview</b> here.</div>
                    </div>

                    <div id="vnt-result-processing" class="is-hidden" style="flex-direction:column;align-items:center;">
                        <div class="vnt-spinner"></div>
                        <div class="vnt-spin-text">Building your preview...</div>
                        <div class="vnt-spin-text" style="margin-top:4px;opacity:.75">This may take up to a minute</div>
                    </div>

                    <div id="vnt-result-done" class="is-hidden" style="position:absolute;inset:0;">
                        <img id="vnt-result-image" class="vnt-zoomable" src="" alt="Virtual try-on result" title="Click to enlarge">
                        <img id="vnt-before-image" class="vnt-zoomable is-hidden" src="" alt="Original hand photo" title="Click to enlarge">
                        <button type="button" id="vnt-before-toggle" class="vnt-before-toggle" aria-pressed="false">Before</button>
                        <span class="vnt-zoom-hint">Tap to enlarge</span>
                    </div>
                </div>

                <div id="vnt-preview-actions" class="vnt-preview-actions is-hidden">
                    <button type="button" id="vnt-try-another" class="vnt-btn vnt-btn--secondary">Try Again</button>
                    <button type="button" id="vnt-download-btn" class="vnt-btn vnt-btn--primary">Save Image</button>
                    <a id="vnt-view-product" href="#" class="vnt-btn vnt-btn--secondary is-hidden" style="flex-basis:100%">View Product</a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@if($enabled)
{{-- Result image lightbox --}}
<div id="vnt-lightbox" class="vnt-lightbox is-hidden" aria-hidden="true" role="dialog" aria-label="Enlarged preview">
    <button type="button" id="vnt-lightbox-close" class="vnt-lightbox__close" aria-label="Close">×</button>
    <img id="vnt-lightbox-img" class="vnt-lightbox__img" src="" alt="Enlarged try-on preview">
</div>
{{-- Camera capture modal with alignment overlay --}}
<div id="vnt-camera-modal" class="fixed inset-0 z-[120] hidden" aria-hidden="true" role="dialog" aria-labelledby="vnt-camera-title">
    <div class="absolute inset-0 bg-black/95"></div>
    <div class="relative z-10 flex h-full flex-col safe-area-inset">
        <div class="shrink-0 px-4 pt-4 pb-2 text-center">
            <div class="flex items-center justify-center gap-2">
                <p id="vnt-camera-title" class="text-white text-sm font-bold uppercase tracking-wide">Position your hand</p>
                <span id="vnt-camera-demo-badge" class="hidden rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white/90">Demo</span>
            </div>
            <p class="mt-1 text-white/75 text-xs sm:text-sm">Align your palm and fingers inside the outline, then tap capture</p>
            <p id="vnt-camera-error" class="hidden mt-3 mx-auto max-w-md rounded-xl px-4 py-2 text-center text-xs sm:text-sm font-medium text-white/90"></p>
        </div>

        <div class="relative flex-1 min-h-0 mx-auto w-full max-w-lg px-4 pb-2">
            <div class="vnt-viewfinder relative h-full w-full overflow-hidden rounded-2xl bg-black shadow-2xl ring-1 ring-white/10">
                <video id="vnt-camera-video" class="vnt-viewfinder__video absolute inset-0 h-full w-full object-cover object-center" autoplay playsinline muted></video>
                <div id="vnt-camera-demo-bg" class="vnt-viewfinder__demo hidden absolute inset-0 bg-gradient-to-b from-slate-600 via-slate-500 to-slate-700">
                    <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 30% 40%, rgba(255,255,255,0.35) 0%, transparent 45%), radial-gradient(circle at 70% 60%, rgba(0,0,0,0.25) 0%, transparent 40%);"></div>
                </div>
                <canvas id="vnt-camera-canvas" class="hidden"></canvas>

                <div id="vnt-capture-guide" class="vnt-viewfinder__guide vnt-capture-guide absolute inset-0 pointer-events-none" aria-hidden="true">
                    <div class="vnt-capture-guide__vignette absolute inset-0"></div>
                    <div class="absolute inset-0 flex items-center justify-center px-3 py-4 sm:px-4 sm:py-6">
                        <div class="vnt-capture-guide__frame relative h-[88%] max-h-[34rem] w-auto aspect-[3/4] sm:h-[90%]">
                            <img
                                src="{{ asset('images/virtual-nail/hand-guide-traced.svg') }}"
                                alt=""
                                class="vnt-capture-guide__hand-img absolute inset-0 h-full w-full object-contain pointer-events-none select-none"
                                draggable="false"
                            >
                            <p class="absolute -bottom-6 left-0 right-0 text-center text-[10px] sm:text-xs font-medium text-white/75">
                                Spread fingers · Keep palm flat
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="shrink-0 px-4 py-5 sm:py-6">
            <div class="mx-auto flex max-w-lg items-center justify-between gap-4">
                <button type="button" id="vnt-camera-cancel" class="inline-flex min-w-[5.5rem] items-center justify-center rounded-2xl border border-white/25 px-5 py-3.5 text-sm font-bold text-white hover:bg-white/10 transition-colors">
                    Cancel
                </button>
                <button type="button" id="vnt-camera-shutter" class="relative inline-flex h-[4.5rem] w-[4.5rem] shrink-0 items-center justify-center rounded-full border-4 border-white bg-white/10 hover:bg-white/20 transition-colors disabled:opacity-40" aria-label="Capture photo">
                    <span class="block h-[3.25rem] w-[3.25rem] rounded-full bg-white"></span>
                </button>
                <button type="button" id="vnt-camera-switch" class="inline-flex min-w-[5.5rem] items-center justify-center rounded-2xl border border-white/25 px-5 py-3.5 text-sm font-bold text-white hover:bg-white/10 transition-colors" aria-label="Switch camera">
                    Flip
                </button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/virtual-nail-capture.css') }}?v={{ $__vntCaptureCssV }}">

<script>
window.virtualNailPageConfig = {
    routes: {
        products: @json(route('api.virtual-nail.products')),
        productOptions: @json(url('/api/virtual-nail/products')),
        try: @json(route('api.virtual-nail.try')),
        history: @json(route('api.virtual-nail.history')),
        trialStatus: @json(url('/api/virtual-nail/trials/__UUID__/status')),
    },
    csrf: @json(csrf_token()),
    product: @json($selectedProduct),
    defaultShape: @json($defaultShape),
    defaultLength: @json($defaultLength),
    cameraDemo: @json($cameraDemo ?? false),
    asyncEnabled: @json($asyncEnabled ?? true),
};
</script>
<script src="{{ asset('js/virtual-nail-trial-page.js') }}?v={{ $__vntPageJsV }}" defer></script>
@endif
@endsection
