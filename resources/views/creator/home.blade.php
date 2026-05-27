@extends('layouts.creator')

@section('title', 'Creator home')

@php
    $affiliateRegister = route('creator.affiliate.apply');
    $shopUrl = rtrim(config('creator.shop_url', config('app.url')), '/');
    $shopHost = parse_url($shopUrl, PHP_URL_HOST) ?: config('app.name');
    $creatorHero = content_block('creator.home.hero', creator_home_hero_block_defaults());
    $creatorFaq = content_block('creator.home.faq', creator_home_faq_block_defaults());
    $creatorElevate = creator_home_page_block('creator.home.elevate');
    $creatorSteps = creator_home_page_block('creator.home.steps');
    $creatorTiers = creator_home_tiers_block();
    $creatorSampleRequestUrl = creator_home_sample_request_url();
    $creatorSample = creator_home_page_block('creator.home.sample');
    $creatorSpotlight = creator_home_page_block('creator.home.spotlight');
    $creatorDashboard = creator_home_page_block('creator.home.dashboard');
    $creatorCta = creator_home_page_block('creator.home.cta');
    $creatorHeroImage = content_block_asset_url($creatorHero['hero_image'] ?? '');
    $creatorStepsImage = content_block_asset_url($creatorSteps['step_image'] ?? '');
    $creatorSampleProductImage = content_block_asset_url($creatorSample['product_image'] ?? '');
    $creatorSampleReel1Image = content_block_asset_url($creatorSample['reel1_image'] ?? '');
    $creatorSampleReel2Image = content_block_asset_url($creatorSample['reel2_image'] ?? '');
    $creatorDashboardLink = filled($creatorDashboard['link_example'] ?? null)
        ? $creatorDashboard['link_example']
        : $shopHost.'/join/your-code';
@endphp

@push('inline_edit_config')
<script>
Object.assign(window.CONTENT_BLOCK_SCHEMAS, {
    'creator.home.hero': @json(creator_home_hero_block_schema()),
    'creator.home.elevate': @json(creator_home_page_schema('creator.home.elevate')),
    'creator.home.steps': @json(creator_home_page_schema('creator.home.steps')),
    'creator.home.tiers': @json(creator_home_page_schema('creator.home.tiers')),
    'creator.home.sample': @json(creator_home_page_schema('creator.home.sample')),
    'creator.home.spotlight': @json(creator_home_page_schema('creator.home.spotlight')),
    'creator.home.dashboard': @json(creator_home_page_schema('creator.home.dashboard')),
    'creator.home.faq': @json(creator_home_faq_block_schema()),
    'creator.home.cta': @json(creator_home_page_schema('creator.home.cta')),
});
Object.assign(window.CONTENT_BLOCK_DATA, {
    'creator.home.faq': @json($creatorFaq),
});
</script>
@endpush

@section('content')
    {{-- Nội dung landing theo mẫu Stitch (code.html) --}}
    <section class="relative mx-auto max-w-7xl overflow-visible px-5 py-16 md:px-16 md:py-24" data-content-block="creator.home.hero"
        data-content-bg-color="{{ $creatorHero['bg_color'] ?? '' }}"
        @if($__heroBg = content_block_section_bg_style($creatorHero['bg_color'] ?? null)) style="{{ $__heroBg }}" @endif>
        @if(!empty($canEdit) && !empty($editMode))
        <div class="mb-2 flex justify-end">
            <button type="button" class="inline-edit-trigger rounded-lg bg-primary px-3 py-2 text-sm font-bold text-white shadow-lg hover:opacity-90" data-block="creator.home.hero">Chỉnh Hero</button>
        </div>
        @endif
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
            <div class="z-10">
                <span class="creator-font-label mb-6 inline-block rounded-full bg-primary px-4 py-1 text-xs font-semibold uppercase tracking-wider text-primary-fg" data-content-field="badge">{{ $creatorHero['badge'] ?? '' }}</span>
                <h1 class="creator-font-headline mb-6 text-3xl font-bold leading-tight tracking-tight text-[#0b1c30] md:text-5xl md:leading-[56px]">
                    Join the <span data-content-field="heading_brand">{{ $creatorHero['heading_brand'] ?? config('app.name') }}</span><br />
                    <span class="font-normal italic" data-content-field="heading_highlight">{{ $creatorHero['heading_highlight'] ?? '' }}</span>
                </h1>
                <p class="mb-8 max-w-lg text-lg leading-7 text-[#404753]" data-content-field="subheading">{{ $creatorHero['subheading'] ?? '' }}</p>
                <div class="flex flex-col gap-4 sm:flex-row">
                    <a href="{{ $affiliateRegister }}" class="btn-primary creator-font-label inline-flex items-center justify-center rounded-lg px-8 py-4 text-center text-sm font-semibold tracking-wide shadow-lg shadow-primary/20" data-content-field="cta_primary_label">{{ $creatorHero['cta_primary_label'] ?? 'Become a Creator' }}</a>
                    <a href="#elevate-content" class="creator-font-label inline-flex items-center justify-center rounded-lg border border-primary px-8 py-4 text-center text-sm font-semibold tracking-wide text-primary transition-colors hover:bg-primary/5" data-content-field="cta_secondary_label">{{ $creatorHero['cta_secondary_label'] ?? 'Explore Benefits' }}</a>
                </div>
                <p class="mt-4 text-sm text-[#404753]">
                    Already applied or approved?
                    <a href="{{ route('creator.login') }}" class="font-semibold text-primary underline">Sign in to the creator portal</a>
                    @auth
                        @if (auth()->user()->canAccessCreatorAffiliateFeatures())
                            · <a href="{{ route('creator.dashboard') }}" class="font-semibold text-primary underline">Open dashboard</a>
                        @endif
                    @endauth
                </p>
            </div>
            <div class="relative min-h-[450px]">
                <div class="absolute inset-0 rotate-2 overflow-hidden rounded-xl shadow-2xl">
                    <img alt="Beauty Creator" class="h-full w-full object-cover" src="{{ $creatorHeroImage }}" data-content-field="hero_image" loading="lazy" decoding="async" />
                </div>
                <div class="glass-card animate-creator-bounce-slow absolute -left-4 top-10 w-44 rounded-xl p-4 sm:-left-8 sm:w-48">
                    <div class="mb-2 flex items-center gap-3">
                        <span class="material-symbols-outlined rounded-lg bg-[#d2e4ff] p-2 text-primary">ads_click</span>
                        <span class="creator-font-label text-[10px] font-medium uppercase tracking-wide text-[#404753] sm:text-xs">Clicks</span>
                    </div>
                    <p class="creator-font-headline text-3xl font-semibold text-primary" data-content-field="stat_clicks_value">{{ $creatorHero['stat_clicks_value'] ?? '12.4k' }}</p>
                    <div class="creator-font-label mt-1 text-[10px] font-medium text-green-600" data-content-field="stat_clicks_change">{{ $creatorHero['stat_clicks_change'] ?? '+14% this week' }}</div>
                </div>
                <div class="glass-card absolute -right-2 bottom-20 w-52 rounded-xl p-4 sm:-right-4 sm:w-56">
                    <div class="mb-2 flex items-center gap-3">
                        <span class="material-symbols-outlined rounded-lg bg-[#d2e4ff] p-2 text-primary">payments</span>
                        <span class="creator-font-label text-[10px] font-medium uppercase tracking-wide text-[#404753] sm:text-xs">Commission</span>
                    </div>
                    <p class="creator-font-headline text-3xl font-semibold text-primary" data-content-field="stat_commission_value">{{ $creatorHero['stat_commission_value'] ?? '$2,840.50' }}</p>
                    <div class="mt-3 h-1 overflow-hidden rounded-full bg-[#e5eeff]">
                        @php
                            $commissionBar = (int) preg_replace('/\D/', '', (string) ($creatorHero['stat_commission_bar'] ?? '75'));
                            $commissionBar = max(0, min(100, $commissionBar));
                        @endphp
                        <div class="h-full bg-primary" data-content-field="stat_commission_bar" data-content-style="width" style="width: {{ $commissionBar }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="elevate-content" class="px-5 py-24 md:px-16" data-content-block="creator.home.elevate"
        data-content-bg-color="{{ $creatorElevate['bg_color'] ?? '' }}"
        @if($__elevateBg = content_block_section_bg_style($creatorElevate['bg_color'] ?? null)) style="{{ $__elevateBg }}" @endif>
        @include('creator.partials.inline-edit-section-btn', ['block' => 'creator.home.elevate', 'label' => 'Lợi ích'])
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="creator-font-headline mb-4 text-3xl font-bold text-[#0b1c30] md:text-5xl md:leading-[56px]" data-content-field="heading">{{ $creatorElevate['heading'] ?? '' }}</h2>
                <p class="mx-auto max-w-2xl text-base leading-6 text-[#404753]" data-content-field="subheading">{{ $creatorElevate['subheading'] ?? '' }}</p>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3 lg:grid-cols-5">
                @for ($bi = 1; $bi <= 5; $bi++)
                    <div class="rounded-xl border border-[#bfc7d5] bg-white p-8 text-center transition-all hover:border-primary">
                        <span class="material-symbols-outlined mb-4 block text-4xl text-primary" data-content-field="b{{ $bi }}_icon">{{ $creatorElevate['b'.$bi.'_icon'] ?? '' }}</span>
                        <h3 class="creator-font-label mb-2 text-sm font-semibold tracking-wide text-[#0b1c30]" data-content-field="b{{ $bi }}_title">{{ $creatorElevate['b'.$bi.'_title'] ?? '' }}</h3>
                        <p class="creator-font-label text-xs font-medium text-[#404753]" data-content-field="b{{ $bi }}_desc">{{ $creatorElevate['b'.$bi.'_desc'] ?? '' }}</p>
                    </div>
                @endfor
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-24 md:px-16" data-content-block="creator.home.steps"
        data-content-bg-color="{{ $creatorSteps['bg_color'] ?? '' }}"
        @if($__stepsBg = content_block_section_bg_style($creatorSteps['bg_color'] ?? null)) style="{{ $__stepsBg }}" @endif>
        @include('creator.partials.inline-edit-section-btn', ['block' => 'creator.home.steps', 'label' => 'Các bước'])
        <div class="grid grid-cols-1 items-center gap-16 lg:grid-cols-2">
            <div class="order-2 space-y-12 lg:order-1">
                @for ($si = 1; $si <= 4; $si++)
                    <div class="flex items-start gap-6">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#0060a7] text-sm font-bold text-white">{{ $si }}</div>
                        <div>
                            <h3 class="creator-font-headline mb-2 text-3xl font-semibold text-[#0b1c30]" data-content-field="step{{ $si }}_title">{{ $creatorSteps['step'.$si.'_title'] ?? '' }}</h3>
                            <p class="text-[#404753]" data-content-field="step{{ $si }}_body">{{ $creatorSteps['step'.$si.'_body'] ?? '' }}</p>
                        </div>
                    </div>
                @endfor
            </div>
            <div class="order-1 overflow-hidden rounded-xl shadow-2xl lg:order-2">
                <img alt="Creator Workspace" class="min-h-[500px] h-full w-full object-cover" src="{{ $creatorStepsImage }}" data-content-field="step_image" loading="lazy" decoding="async" />
            </div>
        </div>
    </section>

    <section id="commission-tiers" class="px-5 py-24 md:px-16" data-content-block="creator.home.tiers"
        data-content-bg-color="{{ $creatorTiers['bg_color'] ?? '' }}"
        @if($__tiersBg = content_block_section_bg_style($creatorTiers['bg_color'] ?? null)) style="{{ $__tiersBg }}" @endif>
        @include('creator.partials.inline-edit-section-btn', ['block' => 'creator.home.tiers', 'label' => 'Hạng'])
        <div class="mx-auto max-w-7xl">
            <div class="mb-16 text-center">
                <h2 class="creator-font-headline mb-4 text-3xl font-bold text-[#0b1c30] md:text-5xl md:leading-[56px]" data-content-field="heading">{{ $creatorTiers['heading'] ?? '' }}</h2>
                <p class="text-base text-[#404753]" data-content-field="subheading">{{ $creatorTiers['subheading'] ?? '' }}</p>
            </div>
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['slug' => 'basic', 'popular' => false, 'starter' => true],
                    ['slug' => 'silver', 'popular' => false, 'starter' => false],
                    ['slug' => 'gold', 'popular' => true, 'starter' => false],
                    ['slug' => 'diamond', 'popular' => false, 'starter' => false],
                ] as $tierCard)
                    @php
                        $tierSlug = $tierCard['slug'];
                        $tierCta = trim((string) ($creatorTiers[$tierSlug.'_cta'] ?? ''));
                    @endphp
                    <div @class([
                        'flex flex-col items-center rounded-xl bg-white p-8 text-center sm:p-10',
                        'relative scale-100 border-2 border-primary shadow-xl md:scale-105' => $tierCard['popular'],
                        'border border-[#bfc7d5]' => ! $tierCard['popular'],
                    ])>
                        @if ($tierCard['popular'])
                            <div class="absolute -top-4 rounded-full bg-[#0060a7] px-4 py-1 text-[10px] font-bold uppercase tracking-widest text-white">Most Popular</div>
                        @endif
                        <span class="creator-font-label mb-4 text-xs font-medium uppercase tracking-widest text-[#404753]" data-content-field="{{ $tierSlug }}_label">{{ $creatorTiers[$tierSlug.'_label'] ?? \App\Support\AffiliateTier::label($tierSlug) }}</span>
                        <h3 class="creator-font-headline mb-6 text-4xl font-bold text-primary lg:text-5xl" data-content-field="{{ $tierSlug }}_rate">{{ $creatorTiers[$tierSlug.'_rate'] ?? '' }}</h3>
                        <ul class="creator-font-label mb-8 w-full space-y-3 text-sm font-medium text-[#404753]" data-features-list="{{ $tierSlug }}_features">
                            @foreach (creator_home_features_list($creatorTiers[$tierSlug.'_features'] ?? null) as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                        @if ($tierCard['starter'])
                            <span class="mt-auto w-full rounded-lg border border-primary px-6 py-3 text-center text-sm font-semibold tracking-wide text-primary">Current Level</span>
                        @elseif ($tierCta !== '')
                            <a href="{{ $affiliateRegister }}" @class([
                                'creator-font-label mt-auto w-full rounded-lg px-6 py-3 text-center text-sm font-semibold tracking-wide',
                                'btn-primary' => $tierCard['popular'],
                                'border border-primary text-primary transition-colors hover:bg-primary/5' => ! $tierCard['popular'],
                            ]) data-content-field="{{ $tierSlug }}_cta">{{ $tierCta }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl overflow-hidden px-5 py-24 md:px-16" data-content-block="creator.home.sample"
        data-content-bg-color="{{ $creatorSample['bg_color'] ?? '' }}"
        @if($__sampleBg = content_block_section_bg_style($creatorSample['bg_color'] ?? null)) style="{{ $__sampleBg }}" @endif>
        @include('creator.partials.inline-edit-section-btn', ['block' => 'creator.home.sample', 'label' => 'Sample'])
        <div class="grid grid-cols-1 items-center gap-16 lg:grid-cols-2">
            <div>
                <h2 class="creator-font-headline mb-6 text-3xl font-bold text-[#0b1c30] md:text-5xl md:leading-[56px]">
                    <span data-content-field="heading">{{ $creatorSample['heading'] ?? '' }}</span>
                    <span class="italic" data-content-field="heading_italic">{{ $creatorSample['heading_italic'] ?? '' }}</span>
                </h2>
                <p class="creator-font-label mb-8 text-lg leading-7 text-[#404753]" data-content-field="subheading">{{ $creatorSample['subheading'] ?? '' }}</p>
                <div class="mb-8 rounded-xl border border-[#bfc7d5] bg-[#e5eeff] p-8">
                    <div class="mb-6 flex items-center gap-6">
                        <img alt="Product" class="h-24 w-24 rounded-lg object-cover" src="{{ $creatorSampleProductImage }}" data-content-field="product_image" loading="lazy" decoding="async" />
                        <div>
                            <h4 class="creator-font-label text-sm font-semibold tracking-wide text-[#0b1c30]" data-content-field="product_name">{{ $creatorSample['product_name'] ?? '' }}</h4>
                            <p class="creator-font-label text-xs font-medium text-[#404753]" data-content-field="product_value">{{ $creatorSample['product_value'] ?? '' }}</p>
                            <span class="mt-2 inline-block rounded-full bg-green-100 px-3 py-1 text-[10px] font-bold text-green-700" data-content-field="product_badge">{{ $creatorSample['product_badge'] ?? '' }}</span>
                        </div>
                    </div>
                    <a href="{{ $creatorSampleRequestUrl }}" class="btn-primary creator-font-label mb-4 block w-full rounded-lg px-8 py-3 text-center text-sm font-semibold tracking-wide" data-content-field="btn_label">{{ $creatorSample['btn_label'] ?? '' }}</a>
                    <p class="text-center text-[10px] uppercase tracking-widest text-[#404753]" data-content-field="footnote">{{ $creatorSample['footnote'] ?? '' }}</p>
                </div>
            </div>
            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="w-full pt-0 sm:w-1/2 sm:pt-12">
                    <div class="group relative mb-4 aspect-[9/16] overflow-hidden rounded-xl shadow-xl">
                        <img alt="Reel Preview" class="h-full w-full object-cover transition-all duration-500 group-hover:scale-105" src="{{ $creatorSampleReel1Image }}" data-content-field="reel1_image" loading="lazy" decoding="async" />
                        <div class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-primary/80 to-transparent p-4 text-white">
                            <p class="creator-font-label text-sm font-medium" data-content-field="reel1_handle">{{ $creatorSample['reel1_handle'] ?? '' }}</p>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">favorite</span>
                                <span class="text-[10px]" data-content-field="reel1_likes">{{ $creatorSample['reel1_likes'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-full sm:w-1/2">
                    <div class="group relative aspect-[9/16] overflow-hidden rounded-xl shadow-xl">
                        <img alt="TikTok Preview" class="h-full w-full object-cover transition-all duration-500 group-hover:scale-105" src="{{ $creatorSampleReel2Image }}" data-content-field="reel2_image" loading="lazy" decoding="async" />
                        <div class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-primary/80 to-transparent p-4 text-white">
                            <p class="creator-font-label text-sm font-medium" data-content-field="reel2_handle">{{ $creatorSample['reel2_handle'] ?? '' }}</p>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">favorite</span>
                                <span class="text-[10px]" data-content-field="reel2_likes">{{ $creatorSample['reel2_likes'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 py-24 md:px-16" data-content-block="creator.home.spotlight"
        data-content-bg-color="{{ $creatorSpotlight['bg_color'] ?? '' }}"
        @if($__spotlightBg = content_block_section_bg_style($creatorSpotlight['bg_color'] ?? null)) style="{{ $__spotlightBg }}" @endif>
        @include('creator.partials.inline-edit-section-btn', ['block' => 'creator.home.spotlight', 'label' => 'Spotlight'])
        <div class="mx-auto max-w-7xl">
            <div class="mb-12">
                <h2 class="creator-font-headline text-3xl font-bold text-[#0b1c30] md:text-5xl md:leading-[56px]" data-content-field="heading">{{ $creatorSpotlight['heading'] ?? '' }}</h2>
                <p class="mt-2 text-[#404753]" data-content-field="subheading">{{ $creatorSpotlight['subheading'] ?? '' }}</p>
            </div>
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @for ($ci = 1; $ci <= 4; $ci++)
                    @php $creatorCardImage = content_block_asset_url($creatorSpotlight['c'.$ci.'_image'] ?? ''); @endphp
                    <div class="group">
                        <div class="relative mb-4 aspect-square overflow-hidden rounded-xl shadow-md">
                            <img alt="Creator" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110" src="{{ $creatorCardImage }}" data-content-field="c{{ $ci }}_image" loading="lazy" decoding="async" />
                        </div>
                        <h4 class="creator-font-label text-sm font-semibold tracking-wide text-[#0b1c30]" data-content-field="c{{ $ci }}_handle">{{ $creatorSpotlight['c'.$ci.'_handle'] ?? '' }}</h4>
                        <div class="creator-font-label mt-1 flex justify-between text-xs font-medium text-[#404753]">
                            <span data-content-field="c{{ $ci }}_left">{{ $creatorSpotlight['c'.$ci.'_left'] ?? '' }}</span>
                            <span data-content-field="c{{ $ci }}_right">{{ $creatorSpotlight['c'.$ci.'_right'] ?? '' }}</span>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-24 md:px-16" data-content-block="creator.home.dashboard"
        data-content-bg-color="{{ $creatorDashboard['bg_color'] ?? '' }}"
        @if($__dashboardBg = content_block_section_bg_style($creatorDashboard['bg_color'] ?? null)) style="{{ $__dashboardBg }}" @endif>
        @include('creator.partials.inline-edit-section-btn', ['block' => 'creator.home.dashboard', 'label' => 'Dashboard'])
        <div class="mb-16 text-center">
            <h2 class="creator-font-headline text-3xl font-bold text-[#0b1c30] md:text-5xl md:leading-[56px]" data-content-field="heading">{{ $creatorDashboard['heading'] ?? '' }}</h2>
            <p class="text-[#404753]" data-content-field="subheading">{{ $creatorDashboard['subheading'] ?? '' }}</p>
        </div>
        <div class="relative rounded-xl border border-[#bfc7d5] bg-white p-8 shadow-2xl">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4 lg:grid-cols-6">
                <div class="rounded-xl border border-[#bfc7d5] bg-[#eff4ff] p-6 md:col-span-2 lg:col-span-2">
                    <div class="mb-6 flex items-center justify-between">
                        <h4 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Total Revenue</h4>
                        <span class="material-symbols-outlined text-primary">trending_up</span>
                    </div>
                    <p class="mb-2 text-[32px] font-bold text-primary" data-content-field="revenue_value">{{ $creatorDashboard['revenue_value'] ?? '' }}</p>
                    <p class="creator-font-label text-xs font-medium text-green-600" data-content-field="revenue_change">{{ $creatorDashboard['revenue_change'] ?? '' }}</p>
                </div>
                <div class="rounded-xl border border-[#bfc7d5] bg-[#eff4ff] p-6 md:col-span-2 lg:col-span-2">
                    <div class="mb-6 flex items-center justify-between">
                        <h4 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Active Orders</h4>
                        <span class="material-symbols-outlined text-primary">shopping_bag</span>
                    </div>
                    <p class="mb-2 text-[32px] font-bold text-primary" data-content-field="orders_value">{{ $creatorDashboard['orders_value'] ?? '' }}</p>
                    <p class="creator-font-label text-xs font-medium text-[#404753]" data-content-field="orders_note">{{ $creatorDashboard['orders_note'] ?? '' }}</p>
                </div>
                <div class="rounded-xl border border-[#bfc7d5] bg-[#eff4ff] p-6 md:col-span-2 lg:col-span-2">
                    <div class="mb-6 flex items-center justify-between">
                        <h4 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Payout Status</h4>
                        <span class="material-symbols-outlined text-primary">verified</span>
                    </div>
                    <p class="mb-2 text-[32px] font-bold text-primary" data-content-field="payout_value">{{ $creatorDashboard['payout_value'] ?? '' }}</p>
                    <p class="creator-font-label text-xs font-medium text-[#404753]" data-content-field="payout_note">{{ $creatorDashboard['payout_note'] ?? '' }}</p>
                </div>
                <div class="h-64 rounded-xl border border-[#bfc7d5] bg-white p-6 md:col-span-4 lg:col-span-4">
                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="creator-font-label text-sm font-semibold tracking-wide text-[#0b1c30]">Performance Over Time</h4>
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-primary"></span>
                            <span class="text-[10px] uppercase text-[#404753]">Sales</span>
                        </div>
                    </div>
                    <div class="flex h-full w-full items-end gap-2 pb-8">
                        <div class="h-[40%] w-full rounded-t-lg bg-primary/20"></div>
                        <div class="h-[60%] w-full rounded-t-lg bg-primary/20"></div>
                        <div class="h-[45%] w-full rounded-t-lg bg-primary/40"></div>
                        <div class="h-[80%] w-full rounded-t-lg bg-primary/20"></div>
                        <div class="h-[55%] w-full rounded-t-lg bg-primary/60"></div>
                        <div class="h-[95%] w-full rounded-t-lg bg-primary"></div>
                        <div class="h-[70%] w-full rounded-t-lg bg-primary/30"></div>
                    </div>
                </div>
                <div class="flex flex-col justify-center rounded-xl bg-[#0060a7] p-8 text-white md:col-span-4 lg:col-span-2">
                    <h4 class="creator-font-headline mb-4 text-3xl font-semibold leading-tight" data-content-field="link_heading">{{ $creatorDashboard['link_heading'] ?? '' }}</h4>
                    <div class="mb-4 flex items-center justify-between rounded-lg border border-white/20 bg-white/10 p-3">
                        <span class="creator-font-label truncate text-xs font-medium" data-content-field="link_example">{{ $creatorDashboardLink }}</span>
                        <span class="material-symbols-outlined shrink-0 cursor-pointer text-sm">content_copy</span>
                    </div>
                    <p class="text-[10px] uppercase tracking-widest opacity-70" data-content-field="link_footnote">{{ $creatorDashboard['link_footnote'] ?? '' }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="creator-faq mx-auto max-w-3xl px-5 py-24 md:px-16" data-content-block="creator.home.faq"
        data-content-bg-color="{{ $creatorFaq['bg_color'] ?? '' }}"
        @if($__faqBg = content_block_section_bg_style($creatorFaq['bg_color'] ?? null)) style="{{ $__faqBg }}" @endif>
        @if(!empty($canEdit) && !empty($editMode))
        <div class="mb-2 flex justify-end">
            <button type="button" class="inline-edit-trigger rounded-lg bg-primary px-3 py-2 text-sm font-bold text-white shadow-lg hover:opacity-90" data-block="creator.home.faq">Chỉnh FAQ</button>
        </div>
        @endif
        <h2 class="creator-font-headline mb-12 text-center text-3xl font-bold text-[#0b1c30] md:text-5xl md:leading-[56px]" data-content-field="section_heading">{{ $creatorFaq['section_heading'] ?? '' }}</h2>
        <div class="space-y-4">
            <details class="group rounded-xl border border-[#bfc7d5] bg-white p-6" open>
                <summary class="flex cursor-pointer list-none items-center justify-between text-left">
                    <span class="creator-font-label text-sm font-semibold tracking-wide text-[#0b1c30]" data-content-field="q1">{{ $creatorFaq['q1'] ?? '' }}</span>
                    <span class="material-symbols-outlined text-primary transition-transform group-open:rotate-180">expand_more</span>
                </summary>
                <p class="mt-4 text-base leading-6 text-[#404753]" data-content-field="a1">{{ $creatorFaq['a1'] ?? '' }}</p>
            </details>
            <details class="group rounded-xl border border-[#bfc7d5] bg-white p-6">
                <summary class="flex cursor-pointer list-none items-center justify-between text-left">
                    <span class="creator-font-label text-sm font-semibold tracking-wide text-[#0b1c30]" data-content-field="q2">{{ $creatorFaq['q2'] ?? '' }}</span>
                    <span class="material-symbols-outlined text-primary transition-transform group-open:rotate-180">expand_more</span>
                </summary>
                <p class="mt-4 text-base leading-6 text-[#404753]" data-content-field="a2">{{ $creatorFaq['a2'] ?? '' }}</p>
            </details>
            <details class="group rounded-xl border border-[#bfc7d5] bg-white p-6">
                <summary class="flex cursor-pointer list-none items-center justify-between text-left">
                    <span class="creator-font-label text-sm font-semibold tracking-wide text-[#0b1c30]" data-content-field="q3">{{ $creatorFaq['q3'] ?? '' }}</span>
                    <span class="material-symbols-outlined text-primary transition-transform group-open:rotate-180">expand_more</span>
                </summary>
                <p class="mt-4 text-base leading-6 text-[#404753]" data-content-field="a3">{{ $creatorFaq['a3'] ?? '' }}</p>
            </details>
            <details class="group rounded-xl border border-[#bfc7d5] bg-white p-6">
                <summary class="flex cursor-pointer list-none items-center justify-between text-left">
                    <span class="creator-font-label text-sm font-semibold tracking-wide text-[#0b1c30]" data-content-field="q4">{{ $creatorFaq['q4'] ?? '' }}</span>
                    <span class="material-symbols-outlined text-primary transition-transform group-open:rotate-180">expand_more</span>
                </summary>
                <p class="mt-4 text-base leading-6 text-[#404753]" data-content-field="a4">{{ $creatorFaq['a4'] ?? '' }}</p>
            </details>
        </div>
    </section>

    <section class="px-5 py-24 text-center md:px-16 @if(empty($creatorCta['bg_color'])) bg-primary/10 @endif" data-content-block="creator.home.cta"
        data-content-bg-color="{{ $creatorCta['bg_color'] ?? '' }}"
        @if($__ctaBg = content_block_section_bg_style($creatorCta['bg_color'] ?? null)) style="{{ $__ctaBg }}" @endif>
        @include('creator.partials.inline-edit-section-btn', ['block' => 'creator.home.cta', 'label' => 'CTA'])
        <div class="mx-auto max-w-4xl">
            <h2 class="creator-font-headline mb-6 text-3xl font-bold text-[#0b1c30] md:text-5xl md:leading-[56px]">
                <span data-content-field="heading">{{ $creatorCta['heading'] ?? '' }}</span>
                <span class="italic" data-content-field="heading_brand">{{ $creatorCta['heading_brand'] ?? config('app.name') }}</span>?
            </h2>
            <p class="creator-font-label mb-10 text-lg leading-7 text-[#404753]" data-content-field="subheading">{{ $creatorCta['subheading'] ?? '' }}</p>
            <a href="{{ $affiliateRegister }}" class="btn-primary creator-font-label inline-flex items-center justify-center rounded-lg px-12 py-5 text-sm font-semibold tracking-wide shadow-xl transition-transform hover:scale-105 active:scale-95" data-content-field="btn_label">{{ $creatorCta['btn_label'] ?? '' }}</a>
        </div>
    </section>
@endsection
