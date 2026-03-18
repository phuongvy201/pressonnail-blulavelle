<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Google Consent Mode -->
    <script>
        // Define dataLayer and the gtag function.
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}

        // IMPORTANT - DO NOT COPY/PASTE WITHOUT MODIFYING REGION LIST
        // Set default consent for specific regions according to your requirements
        gtag('consent', 'default', {
          'ad_storage': 'denied',
          'ad_user_data': 'denied',
          'ad_personalization': 'denied',
          'analytics_storage': 'denied',
          'regions': ['US', 'GB']
        });

        // Set default consent for all other regions according to your requirements
        gtag('consent', 'default', {
          'ad_storage': 'denied',
          'ad_user_data': 'denied',
          'ad_personalization': 'denied',
          'analytics_storage': 'denied'
        });
    </script>

    @php
        $metaPixelId = \App\Support\Settings::get('analytics.meta_pixel_id', config('services.meta.pixel_id'));
        $tiktokPixelId = \App\Support\Settings::get('analytics.tiktok_pixel_id', config('services.tiktok.pixel_id'));
        $googleTagManagerId = \App\Support\Settings::get('analytics.google_tag_manager_id', config('services.google.tag_manager_id'));
        
        // Currency configuration - available in all views
        $siteCurrency = currency();
        $siteCurrencyRate = currency_rate();
        $siteCurrencySymbol = currency_symbol();
    @endphp
    
    <!-- Currency Configuration for JavaScript -->
    <script>
        window.SITE_CURRENCY = @json($siteCurrency);
        window.SITE_CURRENCY_SYMBOL = @json($siteCurrencySymbol);
    </script>

    <!-- Cookie Script -->
    <script type="text/javascript" charset="UTF-8" src="//cdn.cookie-script.com/s/4a353d27e80af68f255e8b4bff37f75c.js"></script>

    @if($googleTagManagerId)
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $googleTagManagerId }}');</script>
        <!-- End Google Tag Manager -->
    @endif
    
    
    @if($metaPixelId)
        <!-- Meta Pixel Code -->
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $metaPixelId }}');
        fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1"
        /></noscript>
        <!-- End Meta Pixel Code -->
    @endif

    @if($tiktokPixelId)
        <!-- TikTok Pixel Code Start -->
        <script>
        !function (w, d, t) {
          w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(
        var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js",o=n&&n.partner;ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._o=ttq._o||{},ttq._o[e]=n||{};n=document.createElement("script")
        ;n.type="text/javascript",n.async=!0,n.src=r+"?sdkid="+e+"&lib="+t;e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};


          ttq.load('{{ $tiktokPixelId }}');
          ttq.page();
        }(window, document, 'ttq');
        </script>
        <!-- TikTok Pixel Code End -->
    @endif

    @auth
    <script>
    (function () {
        const rawData = {
            email: {!! json_encode(strtolower(trim(auth()->user()->email ?? ''))) !!},
            phone: {!! json_encode(auth()->user()->phone ?? auth()->user()->phone_number ?? '') !!},
            externalId: {!! json_encode((string) auth()->user()->id) !!}
        };

        const canHash = typeof window !== 'undefined'
            && window.crypto
            && window.crypto.subtle
            && typeof TextEncoder !== 'undefined';

        if (!canHash) {
            console.warn('TikTok identify skipped: SubtleCrypto/TextEncoder unavailable');
            return;
        }

        const encoder = new TextEncoder();

        const hashSHA256 = async (value) => {
            const data = encoder.encode(value);
            const hashBuffer = await window.crypto.subtle.digest('SHA-256', data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');
        };

        (async () => {
            try {
                const payload = {};

                if (rawData.email) {
                    const normalizedEmail = rawData.email.trim().toLowerCase();
                    if (normalizedEmail) {
                        payload.email = await hashSHA256(normalizedEmail);
                    }
                }

                if (rawData.phone) {
                    const normalizedPhone = String(rawData.phone).replace(/\D+/g, '');
                    if (normalizedPhone) {
                        payload.phone_number = await hashSHA256(normalizedPhone);
                    }
                }

                if (rawData.externalId) {
                    const normalizedId = String(rawData.externalId).trim();
                    if (normalizedId) {
                        payload.external_id = await hashSHA256(normalizedId);
                    }
                }

                if (Object.keys(payload).length > 0 && window.ttq && typeof window.ttq.identify === 'function') {
                    window.ttq.identify(payload);
                }
            } catch (error) {
                console.error('TikTok identify error:', error);
            }
        })();
    })();
    </script>
    @endauth
    
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="gGIR-fmeNV2oZz1duWvcwwKqTbqtvKM2OsiaTUyiLZc" />

    <title>{{ config('app.name', 'Blulavelle') }} - {{ $title ?? 'Home' }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Global CSS for select styling -->
    <style>
    /* Hide default select arrows globally */
    select {
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        background-image: none !important;
    }
    
    select::-ms-expand {
        display: none !important;
    }
    
    select::-webkit-appearance {
        -webkit-appearance: none !important;
    }
    .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }

    /* Material Symbols Outlined - icons (override Tailwind inheritance) */
    .material-symbols-outlined {
        font-family: 'Material Symbols Outlined' !important;
        font-weight: normal;
        font-style: normal;
        font-size: 24px;
        line-height: 1;
        letter-spacing: normal;
        text-rendering: optimizeLegibility;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        display: inline-block;
        white-space: nowrap;
        word-wrap: normal;
    }
    .material-symbols-outlined.fill-current { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }

    /* Promo banner color-shift animation (màu chủ đạo #0297FE) */
    @keyframes promo-banner-shift {
        0%, 100% { background-color: #0297FE; }
        33% { background-color: #3d9ad1; }
        66% { background-color: #1565a0; }
    }
    .promo-banner-animate {
        animation: promo-banner-shift 6s ease-in-out infinite;
    }
    </style>
    @stack('styles')
</head>
<body class="font-display antialiased bg-background-light text-slate-900">
    <!-- Promotional Banner -->
    @php
        $promoBannerBg = \App\Support\Settings::get('site.promo_banner_bg', config('theme.promo_banner_bg'));
        $promoBannerCustom = (is_string($promoBannerBg) && (str_starts_with(trim($promoBannerBg), '#') || str_starts_with(trim($promoBannerBg), 'rgb'))) ? trim($promoBannerBg) : null;
        $footerFaqBg = \App\Support\Settings::get('theme.footer_faq_bg', config('theme.footer_faq_bg'));
        $footerBg = \App\Support\Settings::get('theme.footer_bg', config('theme.footer_bg', '#242B3D'));
        $footerFaqBgCustom = (is_string($footerFaqBg) && (str_starts_with(trim($footerFaqBg), '#') || str_starts_with(trim($footerFaqBg), 'rgb'))) ? trim($footerFaqBg) : null;
        $footerBgCustom = (is_string($footerBg) && (str_starts_with(trim($footerBg), '#') || str_starts_with(trim($footerBg), 'rgb'))) ? trim($footerBg) : null;
    @endphp
    <div class="text-white text-center py-1.5 sm:py-2 px-3 sm:px-4 text-xs sm:text-sm font-bold tracking-wide {{ $promoBannerCustom ? '' : 'bg-primary promo-banner-animate' }}" @if($promoBannerCustom) style="background-color: {{ $promoBannerCustom }};" @endif>
        {{ \App\Support\Settings::get('site.promo_banner', config('theme.promo_banner', 'Free Shipping on Orders Over $100 • Premium Press-on Nails')) }}
    </div>
    @if($googleTagManagerId)
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $googleTagManagerId }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    @endif
    
    <div class="min-h-screen">
        <!-- Header Component -->
        <x-header />

        <!-- Email Verification Notice -->
        @auth
            @if(!auth()->user()->hasVerifiedEmail())
                <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white">
                    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-2.5 sm:py-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <p class="text-xs sm:text-sm md:text-base font-medium truncate min-w-0">
                                    Please verify your email address to access all features.
                                </p>
                            </div>
                            <div class="flex items-center flex-wrap gap-2 sm:space-x-3 sm:gap-0">
                                <a href="{{ route('verification.notice') }}" class="text-xs sm:text-sm font-semibold underline hover:text-orange-100 transition whitespace-nowrap">
                                    Click here to verify
                                </a>
                                <form method="POST" action="{{ route('verification.send') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs sm:text-sm font-semibold bg-white text-orange-600 px-3 sm:px-4 py-1 sm:py-1.5 rounded-lg hover:bg-orange-50 transition whitespace-nowrap">
                                        Resend Email
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endauth

        <!-- Page Content -->
        <main>
            @yield('content')
        </main>

        <!-- FAQ Section (Minimalist Footer) -->
        <section class="px-4 sm:px-6 lg:px-20 py-10 sm:py-14 lg:py-20 bg-white" id="footer-faq" @if($footerFaqBgCustom) style="background-color: {{ $footerFaqBgCustom }};" @endif>
            <div class="max-w-3xl mx-auto">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 text-center mb-8 sm:mb-12">Frequently Asked Questions</h2>
                <div class="space-y-3 sm:space-y-4">
                    <!-- FAQ Item 1 (Active) -->
                    <div class="footer-faq-item bg-white rounded-xl border-2 border-primary/30 overflow-hidden shadow-sm" data-open="true">
                        <button type="button" class="footer-faq-btn w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-5 lg:py-6 flex items-center justify-between text-left text-slate-900 gap-3 sm:gap-4">
                            <span class="font-bold text-sm sm:text-base">Where can I wear press-on nails?</span>
                            <span class="footer-faq-icon flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-slate-200">
                                <svg class="footer-faq-icon-remove w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                <svg class="footer-faq-icon-add w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </span>
                        </button>
                        <div class="footer-faq-content px-4 sm:px-6 lg:px-8 pb-4 sm:pb-6 lg:pb-8 text-slate-600 text-xs sm:text-sm leading-relaxed space-y-4">
                            <p>Our press-on nails are perfect for everyday wear—work, events, travel, or at home. They're durable and water-resistant, so you can shower, wash hands, and do light tasks. For best longevity, avoid prolonged soaking or heavy manual work while wearing them.</p>
                        </div>
                    </div>
                    <!-- FAQ Item 2 -->
                    <div class="footer-faq-item bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <button type="button" class="footer-faq-btn w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-5 lg:py-6 flex items-center justify-between text-left text-slate-900 gap-3 sm:gap-4">
                            <span class="font-bold text-sm sm:text-base">How long do press-on nails last?</span>
                            <span class="footer-faq-icon flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-slate-200">
                                <svg class="footer-faq-icon-remove w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                <svg class="footer-faq-icon-add w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </span>
                        </button>
                        <div class="footer-faq-content hidden px-4 sm:px-6 lg:px-8 pb-4 sm:pb-6 lg:pb-8 text-slate-600 text-xs sm:text-sm leading-relaxed">
                            <p>With proper application and care, our press-on nails can last up to 1–2 weeks. Reusable sets can be worn multiple times. Avoid excessive water exposure and use gloves for cleaning to extend wear.</p>
                        </div>
                    </div>
                    <!-- FAQ Item 3 -->
                    <div class="footer-faq-item bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <button type="button" class="footer-faq-btn w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-5 lg:py-6 flex items-center justify-between text-left text-slate-900 gap-3 sm:gap-4">
                            <span class="font-bold text-sm sm:text-base">How do I apply and remove them?</span>
                            <span class="footer-faq-icon flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-slate-200">
                                <svg class="footer-faq-icon-remove w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                <svg class="footer-faq-icon-add w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </span>
                        </button>
                        <div class="footer-faq-content hidden px-4 sm:px-6 lg:px-8 pb-4 sm:pb-6 lg:pb-8 text-slate-600 text-xs sm:text-sm leading-relaxed">
                            <p>Clean and dry your nails, choose the right size, apply the adhesive tab or glue, then press firmly for a few seconds. To remove, gently lift from the sides or soak in warm soapy water. Never force or rip them off to protect your natural nails.</p>
                        </div>
                    </div>
                    <!-- FAQ Item 4 -->
                    <div class="footer-faq-item bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <button type="button" class="footer-faq-btn w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-5 lg:py-6 flex items-center justify-between text-left text-slate-900 gap-3 sm:gap-4">
                            <span class="font-bold text-sm sm:text-base">Do you offer returns or refunds?</span>
                            <span class="footer-faq-icon flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-slate-200">
                                <svg class="footer-faq-icon-remove w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                <svg class="footer-faq-icon-add w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </span>
                        </button>
                        <div class="footer-faq-content hidden px-4 sm:px-6 lg:px-8 pb-4 sm:pb-6 lg:pb-8 text-slate-600 text-xs sm:text-sm leading-relaxed">
                            <p>Yes. We offer exchange or money-back guarantee for eligible orders. Please see our <a href="{{ route('page.show', 'returns-exchanges-policy') }}" class="text-primary hover:underline">Returns & Exchanges Policy</a> for details and timeframes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer (dark slate layout) -->
        <footer class="bg-[#242B3D] text-slate-300 px-4 sm:px-6 lg:px-20 py-10 sm:py-14 lg:py-16" style="background-color: {{ $footerBgCustom ?? '#242B3D' }};">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">
                    {{-- Col 1: Logo, About, Operating entities, Follow us, Buttons, Badges --}}
                    <div class="lg:col-span-5 space-y-6">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('images/logo/BABYBLUE.png') }}" alt="Baby Blue" class="h-20 sm:h-24 lg:h-28 w-auto object-contain">
                        </div>
                        <p class="text-sm text-slate-400 leading-relaxed max-w-md">
                            Blulavelle.com is a global online marketplace where people come together to make, sell, buy, and collect unique items. There's no Blulavelle warehouse – just independent sellers selling the things they love. We make the whole process easy, helping you connect directly with makers to find something extraordinary.
                        </p>
                        <div>
                            <p class="text-sm font-bold text-white mb-2">The website is jointly operated by:</p>
                            <ul class="text-xs text-slate-400 space-y-1.5 leading-relaxed">
                                <li><strong class="text-slate-300">HM FULFILL COMPANY LIMITED</strong> — 63/9D, Ap Chanh 1, Tan Xuan, Hoc Mon, Ho Chi Minh City 700000, Vietnam</li>
                                <li><strong class="text-slate-300">BLUE STAR TRADING LIMITED</strong> — RM C, G/F, WORLD TRUST TOWER, 50 STANLEY STREET, CENTRAL HONG KONG</li>
                                <li><strong class="text-slate-300">Blulavelle LTD (UK)</strong> — Company Number 16342015, 71-75 Shelton Street, Covent Garden, London, WC2H 9JQ, United Kingdom</li>
                                <li><strong class="text-slate-300">Blulavelle LLC (US)</strong> — 5900 BALCONES DR STE 100, AUSTIN, TX 78731, USA</li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400"><strong class="text-slate-300">US Warehouse:</strong> 1301 S ARAPAHO RD, STE 101 RICHARDSON, TX 75081, USA</p>
                            <p class="text-xs text-slate-400 mt-1"><strong class="text-slate-300">UK Warehouse:</strong> 3 Kincraig Rd, Blackpool FY2 0FY, United Kingdom</p>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white mb-3">Follow us:</p>
                            <div class="flex gap-2 flex-wrap">
                                <a class="w-9 h-9 rounded-full bg-[#1877F2] flex items-center justify-center text-white hover:opacity-90" href="https://www.facebook.com" target="_blank" rel="noopener" aria-label="Facebook"><span class="text-sm font-bold">f</span></a>
                                <a class="w-9 h-9 rounded-full bg-gradient-to-br from-[#833AB4] to-[#FD1D1D] to-[#F77737] flex items-center justify-center text-white hover:opacity-90" href="https://www.instagram.com/blu.printer" target="_blank" rel="noopener" aria-label="Instagram"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>
                                <a class="w-9 h-9 rounded-full bg-[#FF0000] flex items-center justify-center text-white hover:opacity-90" href="https://www.youtube.com" target="_blank" rel="noopener" aria-label="YouTube"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
                                <a class="w-9 h-9 rounded-full bg-black flex items-center justify-center text-white hover:opacity-90" href="https://www.tiktok.com" target="_blank" rel="noopener" aria-label="TikTok"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg></a>
                                <a class="w-9 h-9 rounded-full bg-[#E60023] flex items-center justify-center text-white hover:opacity-90" href="https://www.pinterest.com" target="_blank" rel="noopener" aria-label="Pinterest"><span class="text-sm font-bold">P</span></a>
                                <a class="w-9 h-9 rounded-full bg-[#1DA1F2] flex items-center justify-center text-white hover:opacity-90" href="https://x.com/Blulavelle25" target="_blank" rel="noopener" aria-label="X"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('support.ticket.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800/80 border border-primary/50 text-white text-sm font-medium rounded-lg hover:bg-slate-700/80 transition-colors">
                                <span class="material-symbols-outlined text-base">confirmation_number</span>
                                Submit Ticket
                            </a>
                            <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800/80 border border-primary/50 text-white text-sm font-medium rounded-lg hover:bg-slate-700/80 transition-colors">
                                <span class="material-symbols-outlined text-base">description</span>
                                Submit Request
                            </a>
                            <a href="{{ route('bulk.order.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800/80 border border-primary/50 text-white text-sm font-medium rounded-lg hover:bg-slate-700/80 transition-colors">
                                <span class="material-symbols-outlined text-base">inventory_2</span>
                                Bulk Order
                            </a>
                        </div>
                        <div class="flex gap-3 items-center">
                            <span class="inline-flex items-center px-3 py-1.5 rounded bg-emerald-600/20 text-emerald-400 text-xs font-bold">DMCA</span>
                            <span class="inline-flex items-center px-3 py-1.5 rounded bg-white/10 text-slate-300 text-xs font-bold"><a href="https://www.dmca.com/Protection/Status.aspx?id=7afce096-ea62-47a0-8c3b-a3fbd663bf4d&refurl=https%3a%2f%2fbluprinter.com%2f&rlo=true">Trustpilot</span></a>
                        </div>
                    </div>

                    {{-- Col 2–4: Company, Get Help, Shop --}}
                    <div class="lg:col-span-4 grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Company</h3>
                            <ul class="space-y-2.5 text-sm">
                                <li><a href="{{ route('page.show', 'about-us') }}" class="text-slate-400 hover:text-primary transition-colors">About Us</a></li>
                                <li><a href="{{ route('page.show', 'privacy-policy') }}" class="text-slate-400 hover:text-primary transition-colors">Privacy Policy</a></li>
                                <li><a href="{{ route('page.show', 'terms-of-service') }}" class="text-slate-400 hover:text-primary transition-colors">Terms of Service</a></li>
                                <li><a href="{{ route('page.show', 'secure-payments') }}" class="text-slate-400 hover:text-primary transition-colors">Secure Payments</a></li>
                                <li><a href="{{ route('page.show','contact-us') }}" class="text-slate-400 hover:text-primary transition-colors">Contact Us</a></li>
                                <li><a href="{{ route('page.show', 'help-center') }}" class="text-slate-400 hover:text-primary transition-colors">Help Center</a></li>
                                <li><a href="{{ route('page.show', 'sitemap') }}" class="text-slate-400 hover:text-primary transition-colors">Sitemap</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Get Help</h3>
                            <ul class="space-y-2.5 text-sm">
                                <li><a href="{{ route('page.show', 'faqs') }}" class="text-slate-400 hover:text-primary transition-colors">FAQs</a></li>
                                <li><a href="{{ route('orders.track') }}" class="text-slate-400 hover:text-primary transition-colors">Order Tracking</a></li>
                                <li><a href="{{ route('page.show','shipping-delivery') }}" class="text-slate-400 hover:text-primary transition-colors">Shipping & Delivery</a></li>
                                <li><a href="{{ route('page.show', 'cancelchange-order') }}" class="text-slate-400 hover:text-primary transition-colors">Cancel/Change Order</a></li>
                                <li><a href="{{ route('page.show', 'refund-policy') }}" class="text-slate-400 hover:text-primary transition-colors">Refund Policy</a></li>
                                <li><a href="{{ route('page.show', 'returns-exchanges-policy') }}" class="text-slate-400 hover:text-primary transition-colors">Returns & Exchanges Policy</a></li>
                                <li><a href="{{ route('page.show', 'dmca') }}" class="text-slate-400 hover:text-primary transition-colors">DMCA</a></li>
                                <li><a href="{{ route('page.show', 'intellectual-property-policy') }}" class="text-slate-400 hover:text-primary transition-colors">Our Intellectual Property Policy</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Shop</h3>
                            <ul class="space-y-2.5 text-sm">
                                <li><a href="{{ route('bulk.order.create') }}" class="text-slate-400 hover:text-primary transition-colors">Bulk Order</a></li>
                                <li><a href="{{ route('promo.code.create') }}" class="text-slate-400 hover:text-primary transition-colors">Promo Code</a></li>
                                <li><a href="/become-a-seller" class="text-slate-400 hover:text-primary transition-colors">Sell on Blulavelle</a></li>
                            </ul>
                        </div>
                    </div>

                    {{-- Col 5: Newsletter --}}
                    <div class="lg:col-span-3">
                        <h3 class="text-lg font-bold text-white mb-2">Never miss out on a moment</h3>
                        <p class="text-sm text-slate-400 mb-4 leading-relaxed">
                            Stay updated with the latest trends, exclusive offers, and exciting updates by signing up for our newsletter. Secret privileges for your purchase will be delivered straight to your inbox.
                        </p>
                        <form id="newsletter-form" class="flex gap-2 mb-3" action="{{ route('newsletter.subscribe') }}" method="POST">
                            @csrf
                            <input type="email" id="newsletter-email" name="email" placeholder="Your email address" required
                                class="flex-1 min-w-0 px-4 py-3 rounded-xl bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                            <button type="submit" id="newsletter-submit" class="shrink-0 w-12 h-12 rounded-xl bg-primary text-white flex items-center justify-center hover:opacity-90 transition-opacity" aria-label="Subscribe">
                                <span class="material-symbols-outlined">mail</span>
                            </button>
                        </form>
                        <div id="newsletter-message" class="hidden mt-3 px-4 py-3 rounded-xl text-sm font-medium" role="alert"></div>
                        <p class="text-xs text-slate-500 leading-relaxed mt-2">
                            By clicking Subscribe, you agree to our <a href="{{ route('page.show', 'privacy-policy') }}" class="text-primary hover:underline">Privacy Policy</a> and to receive our promotional emails (opt out anytime).
                        </p>
                    </div>
                </div>

                {{-- Bottom bar: Language, Copyright, Payment icons --}}
                <div class="max-w-7xl mx-auto mt-10 sm:mt-12 pt-6 sm:pt-8 border-t border-slate-600/80 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-2 text-sm text-slate-400">
                        <span class="inline-block w-6 h-4 rounded-sm bg-primary0 flex items-center justify-center text-white text-[10px] font-bold">VN</span>
                        <span>Vietnam</span>
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <p class="text-sm text-slate-500">© {{ date('Y') }} Blulavelle. All Rights Reserved.</p>
                    <div class="flex gap-3 items-center grayscale opacity-80">
                        <span class="material-symbols-outlined text-2xl text-slate-400">credit_card</span>
                        <span class="material-symbols-outlined text-2xl text-slate-400">account_balance</span>
                        <span class="material-symbols-outlined text-2xl text-slate-400">payments</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    {{-- Cart Drawer (popup khi add to cart / click icon giỏ) - full width trên mobile --}}
    <div id="cart-drawer-backdrop" class="fixed inset-0 bg-black/30 z-40 hidden transition-opacity" aria-hidden="true"></div>
    <div id="cart-drawer" class="fixed top-0 right-0 h-full w-full sm:max-w-md bg-white shadow-2xl z-50 flex flex-col border-l border-primary/10 transform translate-x-full transition-transform duration-300 ease-out" role="dialog" aria-label="Giỏ hàng">
        <div class="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b border-primary/10">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">shopping_bag</span>
                <h2 id="cart-drawer-title" class="text-xl font-bold text-slate-900">Your Cart (0)</h2>
            </div>
            <button type="button" id="cart-drawer-close" class="p-2 hover:bg-primary/10 rounded-full transition-colors" aria-label="Đóng">
                <span class="material-symbols-outlined text-slate-600">close</span>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            {{-- Free Shipping Progress (giống cart/index.blade.php) --}}
            <div id="cart-drawer-progress-wrap" class="p-4 sm:p-6 bg-primary/5 border-b border-primary/10 hidden">
                <div class="flex justify-between items-center mb-2">
                    <p class="text-sm font-semibold text-slate-700">Free Shipping Progress</p>
                    <p id="cart-drawer-progress-ratio" class="text-sm font-bold text-primary">$0.00 / $100.00</p>
                </div>
                <div class="h-2.5 w-full bg-primary/10 rounded-full overflow-hidden">
                    <div id="cart-drawer-progress-bar" class="h-full bg-primary rounded-full transition-all duration-500" style="width: 0%;"></div>
                </div>
                <p id="cart-drawer-progress-note" class="mt-2 text-xs font-medium text-slate-500">Add <span class="text-primary font-bold">$0.00</span> more to unlock free shipping!</p>
            </div>
            {{-- Cart Items --}}
            <div id="cart-drawer-items" class="flex flex-col divide-y divide-primary/5">
                {{-- Filled by JS --}}
            </div>
            {{-- Empty state --}}
            <div id="cart-drawer-empty" class="p-6 sm:p-8 text-center hidden">
                <span class="material-symbols-outlined text-6xl text-slate-300">shopping_cart</span>
                <p class="mt-3 text-slate-600 font-medium">Your cart is empty</p>
                <a href="{{ route('products.index') }}" class="inline-block mt-4 px-6 py-2.5 bg-primary text-white font-bold rounded-xl hover:opacity-90 transition-opacity">Continue Shopping</a>
            </div>
            {{-- Complete Your Look (upsell) --}}
            <div id="cart-drawer-upsell" class="p-4 sm:p-6 border-t border-primary/10 hidden">
                <p class="text-sm font-bold text-slate-900 mb-4 uppercase tracking-wider">Complete Your Look</p>
                <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-hide">
                    <a href="{{ route('products.index') }}" class="flex-shrink-0 w-36 p-3 rounded-xl bg-primary/5 border border-primary/10 hover:border-primary/30 transition-colors">
                        <div class="h-24 w-full rounded-lg bg-slate-200 mb-2 overflow-hidden flex items-center justify-center">
                            <span class="material-symbols-outlined text-4xl text-slate-400">inventory_2</span>
                        </div>
                        <p class="text-xs font-bold truncate text-slate-900">Accessories</p>
                        <p class="text-xs text-primary font-bold">Shop now</p>
                    </a>
                    <a href="{{ route('products.index', ['new' => 1]) }}" class="flex-shrink-0 w-36 p-3 rounded-xl bg-primary/5 border border-primary/10 hover:border-primary/30 transition-colors">
                        <div class="h-24 w-full rounded-lg bg-slate-200 mb-2 overflow-hidden flex items-center justify-center">
                            <span class="material-symbols-outlined text-4xl text-slate-400">star</span>
                        </div>
                        <p class="text-xs font-bold truncate text-slate-900">New Arrivals</p>
                        <p class="text-xs text-primary font-bold">Shop now</p>
                    </a>
                </div>
            </div>
        </div>
        {{-- Footer: Subtotal, Discount, Shipping, Total, Promo, Buttons --}}
        <div id="cart-drawer-footer" class="p-4 sm:p-6 border-t border-primary/10 bg-white hidden">
            <div class="flex flex-col gap-3 mb-4">
                <div class="flex justify-between text-slate-500">
                    <span>Subtotal</span>
                    <span id="cart-drawer-subtotal">$0.00</span>
                </div>
                <div id="cart-drawer-discount-row" class="flex justify-between text-slate-500 hidden">
                    <span>Discount</span>
                    <span id="cart-drawer-discount" class="text-emerald-600 font-semibold">-$0.00</span>
                </div>
                <div id="cart-drawer-promo-code-row" class="text-xs text-slate-600 hidden">
                    <span>Code: <strong id="cart-drawer-promo-code" class="text-primary"></strong></span>
                    <button type="button" id="cart-drawer-promo-remove" class="text-primary hover:underline font-semibold ml-2">Remove</button>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>Shipping</span>
                    <span id="cart-drawer-shipping" class="text-primary font-medium">$0.00</span>
                </div>
                <div class="flex justify-between text-xl font-bold border-t border-primary/5 pt-3 text-slate-900">
                    <span>Total</span>
                    <span id="cart-drawer-total">$0.00</span>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Promo Code</label>
                <div class="flex gap-2">
                    <input type="text" id="cart-drawer-promo-input" placeholder="Enter code" class="flex-1 rounded-lg border border-primary/20 bg-slate-50 text-sm px-3 py-2 focus:ring-primary focus:border-primary" autocomplete="off">
                    <button type="button" id="cart-drawer-promo-apply" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-bold hover:bg-primary transition-colors shrink-0">Apply</button>
                </div>
                <p id="cart-drawer-promo-message" class="mt-1 text-xs hidden"></p>
            </div>
            <div class="flex flex-col gap-3">
                <a href="{{ route('checkout.index') }}" id="cart-drawer-checkout-btn" class="w-full py-4 bg-primary text-white font-bold rounded-xl flex items-center justify-center gap-2 hover:opacity-90 transition-opacity uppercase tracking-widest text-sm">
                    Checkout Now
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
                <a href="{{ route('cart.index') }}" class="w-full py-4 border-2 border-primary text-primary font-bold rounded-xl hover:bg-primary/5 transition-colors uppercase tracking-widest text-sm text-center">
                    View My Cart
                </a>
            </div>
            <div class="mt-4 flex justify-center gap-4 grayscale opacity-60">
                <span class="material-symbols-outlined text-2xl">credit_card</span>
                <span class="material-symbols-outlined text-2xl">account_balance</span>
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
        </div>
    </div>

    {{-- Toast thông báo (dùng cho newsletter, v.v.) --}}
    <div id="toast-container" class="fixed top-4 right-4 z-[70] flex flex-col gap-3 pointer-events-none max-w-sm w-full sm:max-w-md" aria-live="polite"></div>

    {{-- Live Chat widget (khách hàng) - responsive --}}
    <div id="live-chat-widget" class="fixed z-[55] bottom-4 right-4 sm:bottom-6 sm:right-6 md:bottom-6 md:right-6" style="padding-bottom: max(0.25rem, env(safe-area-inset-bottom)); padding-right: max(0.25rem, env(safe-area-inset-right));">
        <div id="live-chat-toggle-wrap" class="relative inline-block">
            <button type="button" id="live-chat-toggle" class="live-chat-ring-target w-12 h-12 sm:w-14 sm:h-14 rounded-full shadow-lg flex items-center justify-center text-white hover:opacity-90 transition-opacity flex-shrink-0" style="background: #0297FE;" aria-label="Chat">
                <span class="material-symbols-outlined text-2xl sm:text-3xl">chat</span>
            </button>
            <span id="live-chat-unread-badge" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] sm:min-w-[20px] sm:h-5 px-1 flex items-center justify-center rounded-full bg-primary text-white text-[10px] sm:text-xs font-bold hidden" aria-hidden="true">0</span>
        </div>
        {{-- Panel: mobile = bottom sheet full width, desktop = floating 380px --}}
        <div id="live-chat-panel" class="live-chat-panel hidden fixed left-0 right-0 bottom-20 sm:left-auto sm:right-0 sm:bottom-16 sm:absolute w-full sm:w-[380px] max-h-[calc(100vh-6rem)] sm:max-h-[480px] sm:h-[480px] min-h-[280px] sm:min-h-0 bg-white shadow-2xl border border-slate-200 flex flex-col overflow-hidden rounded-t-2xl sm:rounded-2xl border-b-0 sm:border-b">
            <div class="flex items-center justify-between px-3 py-2.5 sm:px-4 sm:py-3 border-b border-slate-200 flex-shrink-0" style="background: #0297FE;">
                <span class="font-bold text-white text-sm sm:text-base">Chat with us</span>
                <button type="button" id="live-chat-close" class="p-1.5 sm:p-1 rounded-lg text-white/90 hover:bg-white/20 touch-manipulation" aria-label="Close chat">
                    <span class="material-symbols-outlined text-xl sm:text-base">close</span>
                </button>
            </div>
            <div id="live-chat-start" class="p-3 sm:p-4 flex-shrink-0">
                <p class="text-xs sm:text-sm text-slate-600 mb-3 sm:mb-4">Send a message, we'll reply soon.</p>
                <div id="live-chat-guest-form" class="space-y-2.5 sm:space-y-3 {{ auth()->check() ? 'hidden' : '' }}">
                    {{-- Honeypot: ẩn với CSS, bot điền vào sẽ bị từ chối --}}
                    <input type="text" id="live-chat-website" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="absolute left-[-9999px] w-0 h-0 opacity-0 pointer-events-none">
                    <input type="text" id="live-chat-guest-name" placeholder="Your name" class="w-full px-3 py-2 sm:px-4 rounded-xl border border-slate-200 text-sm min-h-[44px] sm:min-h-0" autocomplete="name">
                    <input type="email" id="live-chat-guest-email" placeholder="Email" class="w-full px-3 py-2 sm:px-4 rounded-xl border border-slate-200 text-sm min-h-[44px] sm:min-h-0" autocomplete="email">
                </div>
                <button type="button" id="live-chat-start-btn" class="w-full py-3 rounded-xl font-bold text-white mt-3 sm:mt-4 text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0" style="background: #0297FE;">Start chat</button>
            </div>
            <div id="live-chat-box" class="hidden flex-1 flex flex-col min-h-0 overflow-hidden">
                <div id="live-chat-messages" class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-2 sm:space-y-3 min-h-0"></div>
                <div class="p-2 sm:p-3 border-t border-slate-200 flex-shrink-0 bg-white">
                    <form id="live-chat-send-form" class="flex gap-2">
                        <input type="text" id="live-chat-input" placeholder="Enter message..." class="flex-1 min-w-0 px-3 py-2 sm:px-4 border border-slate-200 rounded-xl text-sm min-h-[44px] sm:min-h-0">
                        <button type="submit" id="live-chat-send-btn" class="px-3 py-2 sm:px-4 rounded-xl font-semibold text-white text-sm flex-shrink-0 touch-manipulation min-h-[44px] sm:min-h-0" style="background: #0297FE;">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Promo popup: sau Add to Cart / Wishlist — "Get 10% OFF! Enter your email..." (responsive) --}}
    <div id="promo-popup-backdrop" class="fixed inset-0 bg-black/40 z-[55] hidden transition-opacity" aria-hidden="true"></div>
    <div id="promo-popup" class="fixed left-4 right-4 top-1/2 -translate-y-1/2 sm:left-1/2 sm:right-auto sm:-translate-x-1/2 w-[calc(100%-2rem)] sm:w-full max-w-md max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl z-[60] hidden flex flex-col" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));" role="dialog" aria-modal="true" aria-labelledby="promo-popup-headline">
        <div class="bg-primary text-white px-4 py-5 sm:px-6 sm:py-6 text-center flex-shrink-0">
            <p id="promo-popup-headline" class="text-xl sm:text-2xl font-extrabold tracking-tight">Get 10% OFF!</p>
            <p id="promo-popup-subline" class="mt-2 text-white/95 text-sm">Enter your email to receive your discount code.</p>
        </div>
        <div class="p-4 sm:p-6 flex-1 min-h-0">
            <div id="promo-popup-form-wrap">
                <form id="promo-popup-form" class="space-y-4">
                    <input type="email" id="promo-popup-email" required placeholder="Your email" class="w-full px-4 py-3.5 sm:py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-primary focus:border-primary text-slate-900 text-base min-h-[48px] sm:min-h-0" autocomplete="email">
                </form>
                <p id="promo-popup-description" class="mt-2 text-sm text-slate-500 hidden"></p>
                <div class="mt-4 flex flex-col sm:flex-row gap-3">
                    <button type="submit" form="promo-popup-form" id="promo-popup-submit" class="flex-1 py-3.5 sm:py-3 min-h-[48px] sm:min-h-0 bg-primary text-white font-bold rounded-xl hover:opacity-90 transition-opacity touch-manipulation">Get my code</button>
                    <button type="button" id="promo-popup-close" class="py-3.5 sm:py-3 min-h-[48px] sm:min-h-0 px-4 border border-slate-200 rounded-xl font-medium text-slate-600 hover:bg-slate-50 transition-colors touch-manipulation">No thanks</button>
                </div>
            </div>
            <div id="promo-popup-success" class="hidden text-center py-2">
                <p class="text-green-600 font-semibold text-sm sm:text-base">Check your inbox for your discount code!</p>
                <button type="button" id="promo-popup-success-close" class="mt-4 px-6 py-3 min-h-[48px] bg-primary text-white font-bold rounded-xl hover:opacity-90 touch-manipulation">Close</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var CART_DRAWER_FREE_SHIP_THRESHOLD = 100;
        var CART_GET_URL = '{{ url("/api/cart/get") }}';
        var CART_APPLY_PROMO_URL = '{{ route("api.cart.apply-promo") }}';
        var CART_REMOVE_PROMO_URL = '{{ route("api.cart.remove-promo") }}';
        var CART_INDEX_URL = '{{ route("cart.index") }}';
        var CHECKOUT_URL = '{{ route("checkout.index") }}';
        var CURRENCY_SYMBOL = window.SITE_CURRENCY_SYMBOL || '$';
        var csrfToken = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;

        function getDrawer() { return document.getElementById('cart-drawer'); }
        function getBackdrop() { return document.getElementById('cart-drawer-backdrop'); }
        function getItemsEl() { return document.getElementById('cart-drawer-items'); }
        function getEmptyEl() { return document.getElementById('cart-drawer-empty'); }
        function getFooterEl() { return document.getElementById('cart-drawer-footer'); }
        function getProgressWrap() { return document.getElementById('cart-drawer-progress-wrap'); }
        function getUpsellEl() { return document.getElementById('cart-drawer-upsell'); }

        function formatPrice(amount) {
            var n = parseFloat(amount);
            if (isNaN(n)) return CURRENCY_SYMBOL + '0.00';
            return CURRENCY_SYMBOL + n.toFixed(2);
        }

        function getProductImage(item) {
            if (!item.product) return '';
            var media = item.product.media || [];
            if (Array.isArray(media) && media.length > 0) {
                var first = media[0];
                if (typeof first === 'string') return first;
                if (first && first.url) return first.url;
                if (first && first.path) return first.path;
            }
            return '';
        }

        function buildVariantLine(item) {
            var v = item.selected_variant || {};
            var attrs = v.attributes || {};
            var parts = [];
            if (attrs.Size || attrs.size) parts.push('Size: ' + (attrs.Size || attrs.size));
            if (attrs['Nail Shape'] || attrs.Shape || attrs.shape) parts.push('Shape: ' + (attrs['Nail Shape'] || attrs.Shape || attrs.shape));
            return parts.length ? parts.join(' | ') : '';
        }

        function renderCartDrawer(data) {
            var items = (data && data.cart_items) ? data.cart_items : [];
            var totalItems = (data && data.total_items) ? data.total_items : 0;
            var summary = (data && data.summary) ? data.summary : {};
            var subtotal = summary.converted_subtotal != null ? summary.converted_subtotal : (data.total_price || 0);
            var shipping = summary.converted_shipping != null ? summary.converted_shipping : (summary.shipping || 0);
            var total = summary.converted_total != null ? summary.converted_total : (subtotal + shipping);

            document.getElementById('cart-drawer-title').textContent = 'Your Cart (' + totalItems + ')';

            var itemsEl = getItemsEl();
            var emptyEl = getEmptyEl();
            var footerEl = getFooterEl();
            var progressWrap = getProgressWrap();
            var upsellEl = getUpsellEl();

            if (!itemsEl) return;

            if (items.length === 0) {
                itemsEl.innerHTML = '';
                if (emptyEl) emptyEl.classList.remove('hidden');
                if (footerEl) footerEl.classList.add('hidden');
                if (progressWrap) progressWrap.classList.add('hidden');
                if (upsellEl) upsellEl.classList.add('hidden');
                return;
            }

            if (emptyEl) emptyEl.classList.add('hidden');
            if (footerEl) footerEl.classList.remove('hidden');
            if (upsellEl) upsellEl.classList.remove('hidden');

            var html = '';
            items.forEach(function(item) {
                var img = getProductImage(item);
                var name = (item.product && item.product.name) ? item.product.name : 'Product';
                var variantLine = buildVariantLine(item);
                var qty = item.quantity || 1;
                var unitPrice = parseFloat(item.price) || 0;
                var lineTotal = unitPrice * qty;
                var cartItemId = item.id;

                html += '<div class="p-4 sm:p-6 flex gap-3 sm:gap-4 cart-drawer-item" data-cart-id="' + cartItemId + '">';
                html += '<div class="h-24 w-20 rounded-lg overflow-hidden bg-slate-100 flex-shrink-0">';
                if (img) html += '<img class="h-full w-full object-cover" alt="" src="' + img + '">';
                else html += '<span class="h-full w-full flex items-center justify-center material-symbols-outlined text-4xl text-slate-300">image</span>';
                html += '</div>';
                html += '<div class="flex flex-col flex-1 min-w-0">';
                html += '<div class="flex justify-between items-start gap-2">';
                html += '<h3 class="font-bold text-slate-900 truncate">' + (name.replace(/</g, '&lt;').replace(/>/g, '&gt;')) + '</h3>';
                html += '<button type="button" class="cart-drawer-remove text-slate-400 hover:text-primary flex-shrink-0" data-cart-id="' + cartItemId + '" aria-label="Remove"><span class="material-symbols-outlined text-sm">delete</span></button>';
                html += '</div>';
                if (variantLine) html += '<p class="text-sm text-slate-500">' + variantLine.replace(/</g, '&lt;') + '</p>';
                html += '<div class="flex justify-between items-center mt-auto gap-2">';
                html += '<div class="flex items-center border border-primary/20 rounded-lg px-2 py-1 gap-1">';
                html += '<button type="button" class="cart-drawer-qty-minus text-primary hover:bg-primary/10 rounded p-0.5" data-cart-id="' + cartItemId + '" data-qty="' + (qty - 1) + '"><span class="material-symbols-outlined text-base">remove</span></button>';
                html += '<span class="text-sm font-bold w-6 text-center cart-drawer-qty">' + qty + '</span>';
                html += '<button type="button" class="cart-drawer-qty-plus text-primary hover:bg-primary/10 rounded p-0.5" data-cart-id="' + cartItemId + '" data-qty="' + (qty + 1) + '"><span class="material-symbols-outlined text-base">add</span></button>';
                html += '</div>';
                html += '<p class="font-bold text-primary">' + formatPrice(lineTotal) + '</p>';
                html += '</div></div></div>';
            });
            itemsEl.innerHTML = html;

            var discount = summary.converted_discount != null ? parseFloat(summary.converted_discount) : 0;
            var appliedPromo = summary.applied_promo_code || '';

            document.getElementById('cart-drawer-subtotal').textContent = formatPrice(subtotal);
            var discountRow = document.getElementById('cart-drawer-discount-row');
            var promoCodeRow = document.getElementById('cart-drawer-promo-code-row');
            if (discountRow) {
                if (discount > 0) {
                    discountRow.classList.remove('hidden');
                    document.getElementById('cart-drawer-discount').textContent = '-' + formatPrice(discount);
                } else {
                    discountRow.classList.add('hidden');
                }
            }
            if (promoCodeRow) {
                if (appliedPromo) {
                    promoCodeRow.classList.remove('hidden');
                    var codeEl = document.getElementById('cart-drawer-promo-code');
                    if (codeEl) codeEl.textContent = appliedPromo;
                } else {
                    promoCodeRow.classList.add('hidden');
                }
            }
            document.getElementById('cart-drawer-shipping').textContent = formatPrice(shipping);
            document.getElementById('cart-drawer-total').textContent = formatPrice(total);

            var needMore = Math.max(0, CART_DRAWER_FREE_SHIP_THRESHOLD - subtotal);
            var pct = CART_DRAWER_FREE_SHIP_THRESHOLD > 0 ? Math.min(100, (subtotal / CART_DRAWER_FREE_SHIP_THRESHOLD) * 100) : 100;
            var progressBar = document.getElementById('cart-drawer-progress-bar');
            var progressRatio = document.getElementById('cart-drawer-progress-ratio');
            var progressNote = document.getElementById('cart-drawer-progress-note');
            if (progressWrap) {
                progressWrap.classList.remove('hidden');
                if (progressRatio) progressRatio.textContent = formatPrice(Math.min(subtotal, CART_DRAWER_FREE_SHIP_THRESHOLD)) + ' / ' + formatPrice(CART_DRAWER_FREE_SHIP_THRESHOLD);
                if (progressBar) progressBar.style.width = pct + '%';
                if (progressNote) {
                    if (needMore > 0) {
                        progressNote.innerHTML = 'Add <span class="text-primary font-bold">' + formatPrice(needMore) + '</span> more to unlock free shipping!';
                        progressNote.className = 'mt-2 text-xs font-medium text-slate-500';
                    } else {
                        progressNote.textContent = "You've unlocked free shipping!";
                        progressNote.className = 'mt-2 text-xs font-semibold text-primary';
                    }
                }
            }

            bindCartDrawerEvents();
        }

        function bindCartDrawerEvents() {
            document.querySelectorAll('.cart-drawer-remove').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.getAttribute('data-cart-id');
                    if (!id) return;
                    fetch('/api/cart/remove/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                        .then(function(r) { return r.json(); })
                        .then(function(res) { if (res.success) fetchAndOpenDrawer(); });
                });
            });
            document.querySelectorAll('.cart-drawer-qty-minus').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.getAttribute('data-cart-id');
                    var qty = parseInt(this.getAttribute('data-qty'), 10) || 0;
                    if (qty < 1) return;
                    updateCartItemQty(id, qty);
                });
            });
            document.querySelectorAll('.cart-drawer-qty-plus').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.getAttribute('data-cart-id');
                    var qty = parseInt(this.getAttribute('data-qty'), 10) || 1;
                    updateCartItemQty(id, qty);
                });
            });
        }

        function updateCartItemQty(cartItemId, newQty) {
            fetch('/api/cart/update/' + cartItemId, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify({ quantity: newQty })
            }).then(function(r) { return r.json(); }).then(function(res) { if (res.success) fetchAndOpenDrawer(); });
        }

        function fetchAndOpenDrawer() {
            fetch(CART_GET_URL, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        renderCartDrawer(data);
                        openCartDrawer();
                    }
                })
                .catch(function() { openCartDrawer(); });
        }

        function openCartDrawer() {
            var drawer = getDrawer();
            var backdrop = getBackdrop();
            if (drawer && backdrop) {
                backdrop.classList.remove('hidden');
                drawer.classList.remove('translate-x-full');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeCartDrawer() {
            var drawer = getDrawer();
            var backdrop = getBackdrop();
            if (drawer && backdrop) {
                backdrop.classList.add('hidden');
                drawer.classList.add('translate-x-full');
                document.body.style.overflow = '';
            }
        }

        window.openCartDrawer = function(andFetch) {
            if (andFetch) {
                fetchAndOpenDrawer();
            } else {
                fetch(CART_GET_URL, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) { if (data.success) renderCartDrawer(data); openCartDrawer(); })
                    .catch(function() { openCartDrawer(); });
            }
        };
        window.closeCartDrawer = closeCartDrawer;

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('cart-drawer-close') && document.getElementById('cart-drawer-close').addEventListener('click', closeCartDrawer);
            getBackdrop() && getBackdrop().addEventListener('click', closeCartDrawer);
            window.addEventListener('cartDrawerOpen', function() { window.openCartDrawer(true); });

            var promoApplyBtn = document.getElementById('cart-drawer-promo-apply');
            var promoInput = document.getElementById('cart-drawer-promo-input');
            var promoMessage = document.getElementById('cart-drawer-promo-message');
            if (promoApplyBtn && promoInput) {
                promoApplyBtn.addEventListener('click', function() {
                    var code = (promoInput.value || '').trim();
                    if (!code) {
                        if (promoMessage) { promoMessage.textContent = 'Please enter a promo code.'; promoMessage.className = 'mt-1 text-xs text-red-600'; promoMessage.classList.remove('hidden'); }
                        return;
                    }
                    promoApplyBtn.disabled = true;
                    fetch(CART_APPLY_PROMO_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: JSON.stringify({ code: code })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        promoApplyBtn.disabled = false;
                        if (data.success) {
                            if (promoMessage) promoMessage.classList.add('hidden');
                            fetch(CART_GET_URL, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                                .then(function(res) { return res.json(); })
                                .then(function(cartData) { if (cartData.success) renderCartDrawer(cartData); });
                        } else {
                            if (promoMessage) { promoMessage.textContent = data.message || 'Invalid or expired promo code.'; promoMessage.className = 'mt-1 text-xs text-red-600'; promoMessage.classList.remove('hidden'); }
                        }
                    })
                    .catch(function() { promoApplyBtn.disabled = false; if (promoMessage) { promoMessage.textContent = 'Something went wrong.'; promoMessage.classList.remove('hidden'); } });
                });
            }
            var promoRemoveBtn = document.getElementById('cart-drawer-promo-remove');
            if (promoRemoveBtn) {
                promoRemoveBtn.addEventListener('click', function() {
                    promoRemoveBtn.disabled = true;
                    fetch(CART_REMOVE_PROMO_URL, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            fetch(CART_GET_URL, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                                .then(function(res) { return res.json(); })
                                .then(function(cartData) { if (cartData.success) renderCartDrawer(cartData); });
                        }
                        promoRemoveBtn.disabled = false;
                    })
                    .catch(function() { promoRemoveBtn.disabled = false; });
                });
            }
        });
    })();
    </script>

    <!-- Footer FAQ accordion -->
    <script>
        document.querySelectorAll('.footer-faq-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var item = this.closest('.footer-faq-item');
                var content = item.querySelector('.footer-faq-content');
                var iconWrap = item.querySelector('.footer-faq-icon');
                var iconRemove = item.querySelector('.footer-faq-icon-remove');
                var iconAdd = item.querySelector('.footer-faq-icon-add');
                var isOpen = content && !content.classList.contains('hidden');
                if (isOpen) {
                    content.classList.add('hidden');
                    item.classList.remove('border-2', 'border-primary/30', 'shadow-md');
                    item.classList.add('border', 'border-slate-200');
                    if (iconRemove) iconRemove.classList.add('hidden'); if (iconAdd) iconAdd.classList.remove('hidden');
                } else {
                    content.classList.remove('hidden');
                    item.classList.remove('border', 'border-slate-200');
                    item.classList.add('border-2', 'border-primary/30', 'shadow-md');
                    if (iconRemove) iconRemove.classList.remove('hidden'); if (iconAdd) iconAdd.classList.add('hidden');
                }
            });
        });
    </script>

    <!-- Toast + Newsletter: toast đẹp thay cho alert -->
    <script>
    (function() {
        function showToast(message, type) {
            type = type || 'success';
            var container = document.getElementById('toast-container');
            if (!container) return;
            var isSuccess = type === 'success';
            var toast = document.createElement('div');
            toast.className = 'pointer-events-auto rounded-xl shadow-lg border overflow-hidden transform transition-all duration-300 ease-out ' +
                (isSuccess
                    ? 'bg-emerald-50 border-emerald-200/80 text-emerald-800'
                    : 'bg-primary border-red-200/80 text-red-800');
            toast.style.animation = 'toastIn 0.35s ease-out';
            var icon = isSuccess
                ? '<svg class="w-6 h-6 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                : '<svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            toast.innerHTML = '<div class="flex items-start gap-3 p-4">' + icon + '<p class="text-sm font-medium leading-snug flex-1 pt-0.5">' + (message || '') + '</p><button type="button" class="toast-close p-1 rounded-lg opacity-60 hover:opacity-100 transition-opacity flex-shrink-0" aria-label="Close">' +
                '<span class="material-symbols-outlined text-lg">close</span></button></div>';
            container.appendChild(toast);
            function remove() {
                toast.style.animation = 'toastOut 0.25s ease-in forwards';
                setTimeout(function() {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 260);
            }
            toast.querySelector('.toast-close').addEventListener('click', remove);
            setTimeout(remove, 5000);
        }
        window.showToast = showToast;

        document.addEventListener('DOMContentLoaded', function() {
            var newsletterForm = document.getElementById('newsletter-form');
            if (!newsletterForm) return;
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var emailInput = document.getElementById('newsletter-email');
                var email = (emailInput && emailInput.value) ? emailInput.value.trim() : '';
                var button = document.getElementById('newsletter-submit');
                var originalHtml = button ? button.innerHTML : '';

                if (!email || !/\S+@\S+\.\S+/.test(email)) {
                    showToast('Please enter a valid email address.', 'error');
                    return;
                }
                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                }
                fetch('{{ route("newsletter.subscribe") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: email })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    showToast(data.message || (data.success ? 'Thank you for subscribing!' : 'Something went wrong.'), data.success ? 'success' : 'error');
                    if (data.success && emailInput) emailInput.value = '';
                })
                .catch(function(err) {
                    console.error('Newsletter error:', err);
                    showToast('Something went wrong. Please try again later.', 'error');
                })
                .finally(function() {
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = originalHtml;
                    }
                });
            });
        });
    })();
    </script>
    <style>
    @keyframes toastIn {
        from { opacity: 0; transform: translateX(100%); }
        to { opacity: 1; transform: translateX(0); }
    }
    @keyframes toastOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100%); }
    }
    /* Rung icon chat khi có tin nhắn mới (như điện thoại đổ chuông) */
    @keyframes liveChatRing {
        0%, 100% { transform: translateX(0) rotate(0deg); }
        10% { transform: translateX(-2px) rotate(-8deg); }
        20% { transform: translateX(2px) rotate(8deg); }
        30% { transform: translateX(-2px) rotate(-6deg); }
        40% { transform: translateX(2px) rotate(6deg); }
        50% { transform: translateX(-1px) rotate(-4deg); }
        60% { transform: translateX(1px) rotate(4deg); }
        70% { transform: translateX(-1px) rotate(-2deg); }
        80% { transform: translateX(1px) rotate(2deg); }
        90% { transform: translateX(0) rotate(0deg); }
    }
    #live-chat-toggle-wrap.live-chat-ring .live-chat-ring-target {
        animation: liveChatRing 0.5s ease-in-out 6 forwards;
    }
    /* Live chat responsive: bottom sheet trên mobile, tránh safe area */
    @media (max-width: 639px) {
        #live-chat-panel.live-chat-panel {
            bottom: max(5rem, calc(env(safe-area-inset-bottom, 0px) + 3.5rem));
            max-height: min(85vh, calc(100vh - 6rem - env(safe-area-inset-bottom, 0px)));
        }
    }
    </style>

    <!-- Live Chat widget: khách hàng chat với seller -->
    <script>
    (function() {
        var startUrl = '{{ route("live-chat.start") }}';
        var sendUrl = '{{ route("live-chat.send") }}';
        var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
        var conversationId = null;
        var pollTimer = null;
        var lastSeenMessageId = 0;
        var unreadCount = 0;

        var panel = document.getElementById('live-chat-panel');
        var unreadBadge = document.getElementById('live-chat-unread-badge');
        var startBlock = document.getElementById('live-chat-start');
        var boxBlock = document.getElementById('live-chat-box');
        var messagesEl = document.getElementById('live-chat-messages');
        var STORAGE_KEY_NAME = 'liveChatGuestName';
        var STORAGE_KEY_EMAIL = 'liveChatGuestEmail';

        function messagesUrl() { return '{{ url("/live-chat/conversations") }}/' + conversationId + '/messages'; }
        function getHoneypotValue() { var el = document.getElementById('live-chat-website'); return (el && el.value) ? el.value : ''; }

        function renderMessages(messages) {
            if (!messages || !messages.length) { messagesEl.innerHTML = '<p class="text-sm text-slate-500 text-center py-4">No messages yet.</p>'; return; }
            messagesEl.innerHTML = messages.map(function(m) {
                var isMe = m.is_from_customer;
                var align = isMe ? 'justify-end' : 'justify-start';
                var bg = isMe ? 'text-white' : 'bg-slate-100 text-slate-900';
                var style = isMe ? ' style="background:#7BC5ED;color:#fff"' : '';
                var sender = isMe ? 'You' : 'Blulavelle';
                var time = (function() { var d = new Date(m.created_at); return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }); })();
                return '<div class="flex ' + align + '"><div class="max-w-[85%] rounded-xl px-3 py-2 text-sm ' + bg + '"' + style + '><p class="text-xs font-semibold opacity-90 mb-1">' + sender + '</p><p class="whitespace-pre-wrap">' + (m.body || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p><p class="text-xs mt-1 opacity-80">' + time + '</p></div></div>';
            }).join('');
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function playNewMessageSound() {
            try {
                var C = window.AudioContext || window.webkitAudioContext;
                if (!C) return;
                var ctx = new C();
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.frequency.value = 800; osc.type = 'sine';
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
                osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.15);
            } catch (e) {}
        }
        function updateUnreadBadge() {
            if (!unreadBadge) return;
            unreadBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            unreadBadge.classList.toggle('hidden', unreadCount <= 0);
            unreadBadge.setAttribute('aria-hidden', unreadCount <= 0);
        }
        function triggerChatRing() {
            var wrap = document.getElementById('live-chat-toggle-wrap');
            if (!wrap) return;
            wrap.classList.remove('live-chat-ring');
            wrap.offsetHeight;
            wrap.classList.add('live-chat-ring');
            setTimeout(function() { wrap.classList.remove('live-chat-ring'); }, 3200);
        }
        function fetchMessages() {
            if (!conversationId) return;
            fetch(messagesUrl(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success || !data.messages || !data.messages.length) return;
                    var maxId = Math.max.apply(null, data.messages.map(function(m) { return m.id; }));
                    var newFromSeller = data.messages.filter(function(m) { return !m.is_from_customer && m.id > lastSeenMessageId; });
                    if (panel.classList.contains('hidden') && lastSeenMessageId > 0 && newFromSeller.length > 0) {
                        unreadCount += newFromSeller.length;
                        updateUnreadBadge();
                        triggerChatRing();
                        playNewMessageSound();
                    }
                    lastSeenMessageId = Math.max(lastSeenMessageId, maxId);
                    renderMessages(data.messages);
                })
                .catch(function() {});
        }

        function startPolling() {
            if (pollTimer) clearInterval(pollTimer);
            pollTimer = setInterval(fetchMessages, 3000);
        }

        function stopPolling() {
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        }

        function isMobileChat() { return typeof window !== 'undefined' && window.innerWidth < 640; }
        function setChatPanelBodyScroll(open) {
            if (isMobileChat()) document.body.style.overflow = open ? 'hidden' : '';
        }
        document.getElementById('live-chat-toggle').addEventListener('click', function() {
            panel.classList.toggle('hidden');
            var isOpen = !panel.classList.contains('hidden');
            setChatPanelBodyScroll(isOpen);
            if (isOpen && conversationId) {
                unreadCount = 0;
                updateUnreadBadge();
                fetchMessages();
            }
            if (conversationId) startPolling();
        });
        document.getElementById('live-chat-close').addEventListener('click', function() {
            panel.classList.add('hidden');
            setChatPanelBodyScroll(false);
        });
        window.addEventListener('resize', function() {
            if (!isMobileChat() && document.body.style.overflow === 'hidden') document.body.style.overflow = '';
        });

        function tryResumeThenStart(payload, btn) {
            var body = payload || {};
            body.website = getHoneypotValue();
            if (btn) btn.disabled = true;
            fetch(startUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(body)
            })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.conversation) {
                        conversationId = data.conversation.id;
                        startBlock.classList.add('hidden');
                        boxBlock.classList.remove('hidden');
                        fetchMessages();
                        startPolling();
                    } else {
                        var msg = data.message || '';
                        if (msg.indexOf('name and email') !== -1) {
                            var guestForm = document.getElementById('live-chat-guest-form');
                            if (guestForm && !guestForm.classList.contains('hidden')) {
                                return;
                            }
                        }
                        if (window.showToast) showToast(msg || 'Could not start chat.', 'error');
                    }
                })
                .catch(function() { if (window.showToast) showToast('Connection error.', 'error'); })
                .finally(function() { if (btn) btn.disabled = false; });
        }

        document.getElementById('live-chat-start-btn').addEventListener('click', function() {
            var isGuest = !document.getElementById('live-chat-guest-form').classList.contains('hidden');
            var payload = {};
            if (isGuest) {
                var name = (document.getElementById('live-chat-guest-name').value || '').trim();
                var email = (document.getElementById('live-chat-guest-email').value || '').trim();
                if (!name || !email) {
                    if (window.showToast) showToast('Please enter your name and email.', 'error');
                    return;
                }
                payload.name = name;
                payload.email = email;
                tryResumeThenStart(payload, this);
                try {
                    localStorage.setItem(STORAGE_KEY_NAME, name);
                    localStorage.setItem(STORAGE_KEY_EMAIL, email);
                } catch (e) {}
            } else {
                tryResumeThenStart(payload, this);
            }
        });

        (function prefillAndResume() {
            var guestForm = document.getElementById('live-chat-guest-form');
            if (guestForm && !guestForm.classList.contains('hidden')) {
                try {
                    var savedName = localStorage.getItem(STORAGE_KEY_NAME);
                    var savedEmail = localStorage.getItem(STORAGE_KEY_EMAIL);
                    if (savedName) document.getElementById('live-chat-guest-name').value = savedName;
                    if (savedEmail) document.getElementById('live-chat-guest-email').value = savedEmail;
                } catch (e) {}
                var nameEl = document.getElementById('live-chat-guest-name');
                var emailEl = document.getElementById('live-chat-guest-email');
                var name = (nameEl && nameEl.value) ? nameEl.value.trim() : '';
                var email = (emailEl && emailEl.value) ? emailEl.value.trim() : '';
                tryResumeThenStart(name && email ? { name: name, email: email } : {});
            } else {
                tryResumeThenStart({});
            }
        })();

        document.getElementById('live-chat-send-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var input = document.getElementById('live-chat-input');
            var body = (input && input.value) ? input.value.trim() : '';
            if (!body || !conversationId) return;
            var sendBtn = document.getElementById('live-chat-send-btn');
            sendBtn.disabled = true;
            fetch(sendUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ conversation_id: conversationId, body: body })
            })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.message) {
                        input.value = '';
                        fetchMessages();
                    }
                })
                .catch(function() {})
                .finally(function() { sendBtn.disabled = false; });
        });
    })();
    </script>

    <!-- Promo popup: hiển thị sau Add to Cart / Wishlist -->
    <script>
    (function() {
        var offerUrl = '{{ route("promo.offer") }}';
        var claimUrl = '{{ route("promo.claim") }}';
        var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;

        function getPopup() { return document.getElementById('promo-popup'); }
        function getBackdrop() { return document.getElementById('promo-popup-backdrop'); }
        function getFormWrap() { return document.getElementById('promo-popup-form-wrap'); }
        function getSuccessWrap() { return document.getElementById('promo-popup-success'); }

        function showPopup(data, trigger) {
            var popup = getPopup();
            popup.setAttribute('data-trigger', trigger || 'add_to_cart');
            document.getElementById('promo-popup-headline').textContent = data.headline || 'Get 10% OFF!';
            document.getElementById('promo-popup-subline').textContent = data.subline || 'Enter your email to receive your discount code.';
            var desc = document.getElementById('promo-popup-description');
            if (data.description) { desc.textContent = data.description; desc.classList.remove('hidden'); } else { desc.classList.add('hidden'); }
            getFormWrap().classList.remove('hidden');
            getSuccessWrap().classList.add('hidden');
            document.getElementById('promo-popup-email').value = '';
            popup.classList.remove('hidden');
            getBackdrop().classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function hidePopup() {
            getPopup().classList.add('hidden');
            getBackdrop().classList.add('hidden');
            document.body.style.overflow = '';
        }

        window.addEventListener('promoPopupShow', function(e) {
            var trigger = (e.detail && e.detail.trigger) ? e.detail.trigger : 'add_to_cart';
            fetch(offerUrl + '?trigger=' + encodeURIComponent(trigger), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.available) showPopup(data, trigger);
                })
                .catch(function() {});
        });

        getBackdrop().addEventListener('click', hidePopup);
        document.getElementById('promo-popup-close').addEventListener('click', hidePopup);
        document.getElementById('promo-popup-success-close').addEventListener('click', hidePopup);

        document.getElementById('promo-popup-form').addEventListener('submit', function(ev) {
            ev.preventDefault();
            var emailInput = document.getElementById('promo-popup-email');
            var email = (emailInput && emailInput.value) ? emailInput.value.trim() : '';
            if (!email || !/\S+@\S+\.\S+/.test(email)) return;
            var trigger = (document.getElementById('promo-popup').getAttribute('data-trigger')) || 'add_to_cart';
            var btn = document.getElementById('promo-popup-submit');
            var origText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Sending...';
            fetch(claimUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ email: email, trigger: trigger })
            })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        getFormWrap().classList.add('hidden');
                        getSuccessWrap().classList.remove('hidden');
                    } else {
                        alert(data.message || 'Something went wrong. Please try again.');
                    }
                })
                .catch(function() { alert('Something went wrong. Please try again.'); })
                .finally(function() { btn.disabled = false; btn.textContent = origText; });
        });

        window.promoPopupShow = function(trigger) {
            var el = getPopup();
            if (el) el.setAttribute('data-trigger', trigger || 'add_to_cart');
            window.dispatchEvent(new CustomEvent('promoPopupShow', { detail: { trigger: trigger || 'add_to_cart' } }));
        };
    })();
    </script>
    
    <!-- Wishlist JavaScript -->
    <script src="{{ asset('js/wishlist.js') }}"></script>
</body>
</html>
