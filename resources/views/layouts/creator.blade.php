<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $__pressOnNailGtagEnabled = false;
        $googleTagManagerId = \App\Support\Settings::get('analytics.google_tag_manager_id', config('services.google.tag_manager_id'));
        $__gtmId = $googleTagManagerId ? trim((string) $googleTagManagerId) : '';
    @endphp
    @if($__pressOnNailGtagEnabled)
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('consent', 'default', {
          'ad_storage': 'denied',
          'ad_user_data': 'denied',
          'ad_personalization': 'denied',
          'analytics_storage': 'denied',
          'regions': ['US', 'GB']
        });
        gtag('consent', 'default', {
          'ad_storage': 'denied',
          'ad_user_data': 'denied',
          'ad_personalization': 'denied',
          'analytics_storage': 'denied'
        });
    </script>
    @else
    <script>window.dataLayer = window.dataLayer || [];</script>
    @endif

    @if($__gtmId !== '')
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',@json($__gtmId));</script>
    @endif

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    @php
        $defaultMetaDescription = 'Creator & affiliate portal — ' . config('app.name');
        $pageTitle = trim((string) $__env->yieldContent('title', config('creator.portal_name')));
        $metaDescription = trim((string) $__env->yieldContent('meta_description', $defaultMetaDescription));
        $metaTitle = trim((string) $__env->yieldContent('meta_title', config('app.name') . ' — ' . $pageTitle));
        $ogType = trim((string) $__env->yieldContent('og_type', 'website'));
        $ogUrl = trim((string) $__env->yieldContent('og_url', url()->current()));
        $ogImage = trim((string) $__env->yieldContent('og_image', asset('favicon.png')));
    @endphp
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ $ogUrl }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <title>{{ $metaTitle }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@500;600&family=Manrope:wght@400;500;600&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
    [x-cloak] { display: none !important; }
    html, body { margin: 0 !important; padding: 0 !important; }
    .creator-portal {
        font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;
        --creator-surface: #f8f9ff;
        --creator-on-surface: #0b1c30;
        --creator-on-surface-variant: #404753;
        --creator-outline-variant: #bfc7d5;
        --creator-surface-container-low: #eff4ff;
        --creator-primary-deep: #0060a7;
    }
    .creator-font-headline { font-family: 'Playfair Display', ui-serif, Georgia, serif; }
    .creator-font-label { font-family: 'Hanken Grotesk', ui-sans-serif, system-ui, sans-serif; }
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
    }
    .creator-btn-primary {
        background: #0195fe;
        color: #ffffff;
        transition: background-color 0.2s ease, transform 0.15s ease;
    }
    .creator-btn-primary:hover {
        background: #0060a7;
    }
    .creator-btn-primary:active {
        transform: scale(0.98);
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 10px 40px rgba(0, 96, 167, 0.08);
    }
    .btn-primary {
        background: #0195fe;
        color: #ffffff;
        transition: background-color 0.3s ease, transform 0.3s ease;
    }
    .btn-primary:hover {
        background: #0060a7;
        transform: translateY(-1px);
    }
    @keyframes creator-bounce-slow {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6px); }
    }
    .animate-creator-bounce-slow {
        animation: creator-bounce-slow 3s ease-in-out infinite;
    }
    .creator-faq details > summary {
        list-style: none;
    }
    .creator-faq details > summary::-webkit-details-marker {
        display: none;
    }
    </style>
    @stack('styles')
</head>
<body class="creator-portal min-h-screen overflow-x-hidden bg-[#f8f9ff] text-[#0b1c30] antialiased">
    @if($__gtmId !== '')
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $__gtmId }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    @php
        $creatorFooterPolicyPages = \App\Models\Page::query()
            ->where('status', 'published')
            ->whereIn('slug', config('creator.affiliate_policy_slugs', []))
            ->orderBy('sort_order')
            ->get();
        $creatorSupportPages = \App\Models\Page::query()
            ->where('status', 'published')
            ->whereIn('slug', ['faqs', 'contact-us', 'help-center'])
            ->orderBy('sort_order')
            ->get();

        $creatorLogoUrl = asset('images/logo/logo(3).png');
        $creatorLogo200 = optimized_local_img($creatorLogoUrl, 200);
        $creatorLogo360 = optimized_local_img($creatorLogoUrl, 360);
        $creatorLogoAlt = trim((string) \App\Support\Settings::get('mail.brand_name', config('theme.mail_brand_name', config('app.name'))));
        if ($creatorLogoAlt === '') {
            $creatorLogoAlt = 'Baby Blue';
        }

        $creatorUser = auth()->user();
        $creatorHasDashboard = $creatorUser && $creatorUser->canAccessCreatorAffiliateFeatures();
        $creatorHasPendingApplication = $creatorUser && ! $creatorHasDashboard && $creatorUser->hasPendingAffiliateApplication();
        $creatorHelpPage = $creatorSupportPages->firstWhere('slug', 'help-center')
            ?? $creatorSupportPages->firstWhere('slug', 'faqs')
            ?? $creatorSupportPages->first();
        $creatorHelpUrl = $creatorHelpPage
            ? route('page.show', ['slug' => $creatorHelpPage->slug])
            : route('creator.home');
    @endphp

    <div x-data="{ creatorMenuOpen: false }" class="flex min-h-screen flex-col">
        {{-- Top app bar (Stitch / Blulavelle creator portal) --}}
        <header class="fixed top-0 z-50 w-full border-b border-[#bfc7d5] bg-[#f8f9ff]/85 shadow-sm backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 md:px-16">
                <div class="flex min-w-0 flex-1 items-center gap-3 md:flex-none">
                    <button type="button"
                            class="inline-flex rounded-lg p-2 text-primary hover:bg-primary/10 md:hidden"
                            @click="creatorMenuOpen = !creatorMenuOpen"
                            :aria-expanded="creatorMenuOpen"
                            aria-controls="creator-mobile-nav"
                            aria-label="Menu">
                        <span class="material-symbols-outlined text-[26px]">menu</span>
                    </button>
                    <a href="{{ route('creator.home') }}" class="flex shrink-0 items-center min-w-0" aria-label="{{ $creatorLogoAlt }}">
                        <span class="flex max-h-14 max-w-[140px] items-center justify-center overflow-hidden rounded sm:max-h-16 sm:max-w-[160px] md:max-h-[72px] md:max-w-[180px]">
                            <img src="{{ $creatorLogo200 }}" alt="{{ $creatorLogoAlt }}"
                                 @if(storage_image_resize_url($creatorLogoUrl, 200)) srcset="{{ optimized_local_img($creatorLogoUrl, 140) }} 140w, {{ $creatorLogo200 }} 200w, {{ $creatorLogo360 }} 360w" sizes="(max-width: 768px) 120px, 160px" @endif
                                 width="200" height="70" decoding="async"
                                 class="h-auto max-h-full w-auto max-w-full object-contain"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <span class="flex h-full w-full items-center justify-center text-primary" style="display: none;" aria-hidden="true">
                                <svg class="h-8 w-8 sm:h-9 sm:w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                            </span>
                        </span>
                    </a>
                </div>

                <nav class="hidden items-center gap-6 lg:gap-8 md:flex" aria-label="Creator portal">
                    <a href="{{ route('creator.home') }}"
                       class="creator-font-label text-sm font-semibold tracking-wide transition-colors duration-300 {{ request()->routeIs('creator.home') ? 'border-b-2 border-primary pb-1 text-primary' : 'text-[#404753] hover:text-primary' }}">
                        Creator
                    </a>
                    @if ($creatorHasDashboard)
                        <a href="{{ route('creator.dashboard') }}"
                           class="creator-font-label text-sm font-semibold tracking-wide transition-colors duration-300 {{ request()->routeIs('creator.dashboard') || request()->routeIs('creator.analytics.*') ? 'border-b-2 border-primary pb-1 text-primary' : 'text-[#404753] hover:text-primary' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('creator.product-links.index') }}"
                           class="creator-font-label text-sm font-semibold tracking-wide transition-colors duration-300 {{ request()->routeIs('creator.product-links.*') ? 'border-b-2 border-primary pb-1 text-primary' : 'text-[#404753] hover:text-primary' }}">
                            Product links
                        </a>
                        <a href="{{ route('creator.promo-codes.index') }}"
                           class="creator-font-label text-sm font-semibold tracking-wide transition-colors duration-300 {{ request()->routeIs('creator.promo-codes.*') ? 'border-b-2 border-primary pb-1 text-primary' : 'text-[#404753] hover:text-primary' }}">
                            Coupons
                        </a>
                        <a href="{{ route('creator.sample-requests.index') }}"
                           class="creator-font-label text-sm font-semibold tracking-wide transition-colors duration-300 {{ request()->routeIs('creator.sample-requests.*') ? 'border-b-2 border-primary pb-1 text-primary' : 'text-[#404753] hover:text-primary' }}">
                            Samples
                        </a>
                        <a href="{{ $creatorHelpUrl }}"
                           class="creator-font-label text-sm font-semibold tracking-wide transition-colors duration-300 text-[#404753] hover:text-primary">
                            Help
                        </a>
                    @elseif ($creatorUser)
                        <a href="{{ route('creator.affiliate.status') }}"
                           class="creator-font-label inline-flex items-center gap-1.5 text-sm font-semibold tracking-wide transition-colors duration-300 {{ request()->routeIs('creator.affiliate.status') ? 'border-b-2 border-primary pb-1 text-primary' : 'text-[#404753] hover:text-primary' }}">
                            My application
                            @if ($creatorHasPendingApplication)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-900">Pending</span>
                            @endif
                        </a>
                        <a href="{{ $creatorHelpUrl }}"
                           class="creator-font-label text-sm font-semibold tracking-wide text-[#404753] transition-colors duration-300 hover:text-primary">
                            Help
                        </a>
                    @else
                        <a href="{{ route('creator.affiliate.apply') }}"
                           class="creator-font-label text-sm font-semibold tracking-wide transition-colors duration-300 {{ request()->routeIs('creator.affiliate.apply') || request()->routeIs('creator.affiliate.apply.*') ? 'border-b-2 border-primary pb-1 text-primary' : 'text-[#404753] hover:text-primary' }}">
                            Apply
                        </a>
                        <a href="{{ $creatorHelpUrl }}"
                           class="creator-font-label text-sm font-semibold tracking-wide text-[#404753] transition-colors duration-300 hover:text-primary">
                            Help
                        </a>
                    @endif
                </nav>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    @if ($creatorUser)
                        @if ($creatorHasDashboard && ($creatorSetupIncomplete ?? false))
                            <a href="{{ route('creator.setup.index') }}"
                               class="creator-font-label rounded-lg px-3 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50 {{ request()->routeIs('creator.setup.*') ? 'underline' : '' }}">
                                Account setup
                            </a>
                        @endif
                        <form method="post" action="{{ route('creator.logout') }}">
                            @csrf
                            <button type="submit" class="creator-font-label rounded-lg px-3 py-2 text-sm font-semibold text-[#404753] hover:text-primary">
                                Sign out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('creator.login') }}"
                           class="creator-font-label rounded-lg px-3 py-2 text-sm font-semibold text-primary hover:bg-primary/5 sm:border sm:border-primary sm:px-4 {{ request()->routeIs('creator.login') ? 'bg-primary/5' : '' }}">
                            Sign in
                        </a>
                    @endif
                </div>
            </div>

            {{-- Mobile nav --}}
            <div id="creator-mobile-nav"
                 x-show="creatorMenuOpen"
                 x-cloak
                 class="border-t border-[#bfc7d5] bg-[#f8f9ff] px-5 py-4 md:hidden"
                 @click.outside="creatorMenuOpen = false"
                 @keydown.escape.window="creatorMenuOpen = false">
                <nav class="flex flex-col gap-1" aria-label="Creator portal mobile">
                    <a href="{{ route('creator.home') }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-[#404753] hover:bg-primary/5 hover:text-primary" @click="creatorMenuOpen = false">Creator</a>
                    @if ($creatorHasDashboard)
                        <a href="{{ route('creator.dashboard') }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-[#404753] hover:bg-primary/5 hover:text-primary" @click="creatorMenuOpen = false">Dashboard</a>
                        <a href="{{ route('creator.product-links.index') }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-[#404753] hover:bg-primary/5 hover:text-primary" @click="creatorMenuOpen = false">Product links</a>
                        <a href="{{ route('creator.promo-codes.index') }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-[#404753] hover:bg-primary/5 hover:text-primary" @click="creatorMenuOpen = false">Coupons</a>
                        <a href="{{ route('creator.sample-requests.index') }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-[#404753] hover:bg-primary/5 hover:text-primary" @click="creatorMenuOpen = false">Samples</a>
                        <a href="{{ $creatorHelpUrl }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-[#404753] hover:bg-primary/5 hover:text-primary" @click="creatorMenuOpen = false">Help</a>
                    @elseif ($creatorUser)
                        <a href="{{ route('creator.affiliate.status') }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold text-[#404753] hover:bg-primary/5 hover:text-primary" @click="creatorMenuOpen = false">
                            <span>My application</span>
                            @if ($creatorHasPendingApplication)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-900">Pending</span>
                            @endif
                        </a>
                        <a href="{{ $creatorHelpUrl }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-[#404753] hover:bg-primary/5 hover:text-primary" @click="creatorMenuOpen = false">Help</a>
                    @else
                        <a href="{{ route('creator.affiliate.apply') }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-[#404753] hover:bg-primary/5 hover:text-primary" @click="creatorMenuOpen = false">Apply</a>
                        <a href="{{ $creatorHelpUrl }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-[#404753] hover:bg-primary/5 hover:text-primary" @click="creatorMenuOpen = false">Help</a>
                    @endif
                    @if ($creatorUser)
                        <div class="mt-1 flex flex-col gap-1 border-t border-[#bfc7d5] pt-2">
                            @if ($creatorHasDashboard && ($creatorSetupIncomplete ?? false))
                                <a href="{{ route('creator.setup.index') }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-amber-900 hover:bg-primary/5" @click="creatorMenuOpen = false">Account setup</a>
                            @endif
                            <form method="post" action="{{ route('creator.logout') }}">
                                @csrf
                                <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-[#404753] hover:bg-primary/5 hover:text-primary" @click="creatorMenuOpen = false">
                                    Sign out
                                </button>
                            </form>
                        </div>
                    @endif
                </nav>
            </div>
        </header>

        <main class="flex-1 pt-[5.25rem] md:pt-24">
            @if(!empty($canEdit) && !empty($editMode))
            <div class="mx-auto max-w-7xl px-5 pt-4 md:px-16">
                <div class="rounded-xl border border-primary/40 bg-primary/10 px-4 py-3 text-sm text-[#0b1c30]">
                    <span class="font-semibold text-primary">Chế độ chỉnh sửa.</span>
                    Tìm các nút <span class="font-semibold">Chỉnh …</span> trên từng section (Hero, Lợi ích, Các bước, Hạng, Sample, Spotlight, Dashboard, FAQ, CTA) và <span class="font-semibold">Chỉnh footer</span> cuối trang.
                </div>
            </div>
            @endif
            @if (session('success'))
                <div class="mx-auto mb-4 max-w-7xl px-5 pt-6 md:px-16">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 shadow-sm">
                        {{ session('success') }}
                    </div>
                </div>
            @endif
            @if (session('error'))
                <div class="mx-auto mb-4 max-w-7xl px-5 pt-6 md:px-16">
                    <div class="rounded-xl border border-red-200 bg-red-50/90 px-4 py-3 text-sm text-red-800 shadow-sm">
                        {{ session('error') }}
                    </div>
                </div>
            @endif
            @yield('content')
        </main>

        <footer class="mt-auto w-full border-t border-[#bfc7d5] py-12 {{ empty($creatorLayoutFooter['bg_color'] ?? null) ? 'bg-[#eff4ff]' : '' }}" data-content-block="creator.layout.footer"
            data-content-bg-color="{{ $creatorLayoutFooter['bg_color'] ?? '' }}"
            @if($__footerBg = content_block_section_bg_style($creatorLayoutFooter['bg_color'] ?? null)) style="{{ $__footerBg }}" @endif>
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-5 md:grid-cols-2 md:px-16 lg:flex lg:justify-between lg:gap-8">
                <div class="max-w-sm">
                    @if(!empty($canEdit) && !empty($editMode))
                    <div class="mb-2 flex justify-end">
                        <button type="button" class="inline-edit-trigger rounded-lg bg-primary px-3 py-2 text-sm font-bold text-white shadow-lg hover:opacity-90" data-block="creator.layout.footer">Chỉnh footer</button>
                    </div>
                    @endif
                    <a href="{{ route('creator.home') }}" class="inline-block" aria-label="{{ $creatorLogoAlt }}">
                        <span class="flex max-h-20 max-w-[200px] items-center justify-center overflow-hidden">
                            <img src="{{ $creatorLogo200 }}" alt="{{ $creatorLogoAlt }}"
                                 @if(storage_image_resize_url($creatorLogoUrl, 200)) srcset="{{ optimized_local_img($creatorLogoUrl, 140) }} 140w, {{ $creatorLogo200 }} 200w, {{ $creatorLogo360 }} 360w" sizes="180px" @endif
                                 width="200" height="70" decoding="async" loading="lazy"
                                 class="h-auto max-h-full w-auto max-w-full object-contain">
                        </span>
                    </a>
                    <p class="mt-3 text-base leading-relaxed text-[#404753]" data-content-field="tagline">
                        {{ $creatorLayoutFooter['tagline'] ?? '' }}
                    </p>
                </div>
                <div>
                    <h5 class="creator-font-label mb-4 text-sm font-semibold uppercase tracking-wide text-[#0b1c30]">Portal</h5>
                    <ul class="space-y-3 text-base text-[#404753]">
                        <li><a href="{{ route('creator.home') }}" class="transition-colors duration-200 hover:text-primary">Creator</a></li>
                        <li><a href="{{ route('creator.affiliate.apply') }}" class="transition-colors duration-200 hover:text-primary">Apply to program</a></li>
                        @if ($creatorHasDashboard)
                            <li><a href="{{ route('creator.dashboard') }}" class="transition-colors duration-200 hover:text-primary">Creator dashboard</a></li>
                        @elseif ($creatorUser)
                            <li><a href="{{ route('creator.affiliate.status') }}" class="transition-colors duration-200 hover:text-primary">My application</a></li>
                        @else
                            <li><a href="{{ route('creator.login') }}" class="transition-colors duration-200 hover:text-primary">Sign in</a></li>
                        @endif
                        <li><a href="{{ rtrim(config('creator.shop_url', config('app.url')), '/') }}" class="transition-colors duration-200 hover:text-primary">Shop</a></li>
                    </ul>
                </div>
                @if($creatorSupportPages->isNotEmpty())
                    <div>
                        <h5 class="creator-font-label mb-4 text-sm font-semibold uppercase tracking-wide text-[#0b1c30]">Support</h5>
                        <ul class="space-y-3 text-base text-[#404753]">
                            @foreach($creatorSupportPages as $p)
                                <li>
                                    <a href="{{ route('page.show', ['slug' => $p->slug]) }}" class="transition-colors duration-200 hover:text-primary">{{ $p->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($creatorFooterPolicyPages->isNotEmpty())
                    <div>
                        <h5 class="creator-font-label mb-4 text-sm font-semibold uppercase tracking-wide text-[#0b1c30]">Legal</h5>
                        <ul class="space-y-3 text-base text-[#404753]">
                            @foreach($creatorFooterPolicyPages as $p)
                                <li>
                                    <a href="{{ route('creator.policies.show', $p->slug) }}" class="transition-colors duration-200 hover:text-primary">{{ $p->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <div class="mx-auto mt-10 flex max-w-7xl flex-col items-center justify-between gap-4 border-t border-[#bfc7d5] px-5 pt-8 text-xs text-[#707884] md:flex-row md:px-16">
                <span>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved. · Creator portal</span>
                <a href="{{ rtrim(config('creator.shop_url', config('app.url')), '/') }}" class="inline-flex items-center gap-1 text-[#707884] transition-colors hover:text-primary" title="Main store">
                    <span class="material-symbols-outlined text-[20px]">storefront</span>
                    <span class="creator-font-label font-medium">Store</span>
                </a>
            </div>
        </footer>
    </div>

    @include('partials.inline-edit-toolbar')

    @push('inline_edit_config')
    <script>
    Object.assign(window.CONTENT_BLOCK_SCHEMAS, {
        'creator.layout.footer': @json(creator_layout_footer_block_schema()),
    });
    Object.assign(window.CONTENT_BLOCK_DATA, {
        'creator.layout.footer': @json($creatorLayoutFooter ?? creator_layout_footer_block_defaults()),
    });
    </script>
    @endpush

    @include('partials.inline-edit-assets')
    @stack('scripts')
</body>
</html>
