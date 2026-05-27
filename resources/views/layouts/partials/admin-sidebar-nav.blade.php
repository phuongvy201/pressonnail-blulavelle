@php
    $m = !empty($mobile);
    $sub = $m ? 'mt-0.5 space-y-0.5 border-l-2 border-gray-600/40 ml-2 pl-2 pb-1' : 'mt-0.5 space-y-0.5 border-l-2 border-gray-200 ml-2 pl-2 pb-1';
    $ac = $navLinkClass;
    $sm = $navSummaryClass;
    $clk = $m ? '@click="sidebarOpen = false"' : '';
@endphp

@if ($__na)
    {{-- Overview --}}
    <details class="admin-nav-details mb-1" @if($navOpenOverview) open @endif>
        <summary class="{{ $sm($m, $navOpenOverview) }}">
            <span>Overview</span>
            <svg class="admin-nav-chevron w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="{{ $sub }}">
            <a href="{{ route('admin.dashboard') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.dashboard'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                </svg>
                Dashboard
            </a>
            <a href="{{ route('admin.analytics.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.analytics.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Analytics
            </a>
            <a href="{{ route('admin.settings.analytics.edit') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.settings.analytics.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Analytics Settings
            </a>
            <a href="{{ route('admin.site.home-preview') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.site.home-preview'), $m) }}" target="_blank" rel="noopener">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                Homepage
            </a>
        </div>
    </details>

    {{-- Users & Shops --}}
    <details class="admin-nav-details mb-1" @if($navOpenUsers) open @endif>
        <summary class="{{ $sm($m, $navOpenUsers) }}">
            <span>Users &amp; Shops</span>
            <svg class="admin-nav-chevron w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="{{ $sub }}">
            <a href="{{ route('admin.users.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.users.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>
                Users
            </a>
            <a href="{{ route('admin.roles.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.roles.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                Roles
            </a>
            <a href="{{ route('admin.seller-applications.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.seller-applications.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7M5 7h6m-6 4h3"></path></svg>
                Seller Applications
            </a>
            <a href="{{ route('admin.shops.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.shops.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Shops
            </a>
        </div>
    </details>
@elseif ($__ns)
    <a href="{{ route('admin.seller.dashboard') }}" {!! $clk !!}
       class="{{ $ac(request()->routeIs('admin.seller.dashboard'), $m) }} mb-1">
        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
        </svg>
        Dashboard
    </a>
@endif

@if ($__na || $__ns)
    <details class="admin-nav-details mb-1" @if($navOpenProducts) open @endif>
        <summary class="{{ $sm($m, $navOpenProducts) }}">
            <span>Products</span>
            <svg class="admin-nav-chevron w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="{{ $sub }}">
            @if ($__na)
                <a href="{{ route('admin.categories.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.categories.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Categories
                </a>
            @elseif ($__ns)
                <a href="{{ route('seller.shop.dashboard') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('seller.shop.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Shop Profile
                </a>
            @endif
            <a href="{{ route('admin.product-templates.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.product-templates.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Templates
            </a>
            <a href="{{ route('admin.products.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.products.*') && !request()->routeIs('admin.products.show-delete-from-gmc') && !request()->routeIs('admin.products.import*') && !request()->routeIs('admin.reviews.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                Products
            </a>
            <a href="{{ route('admin.collections.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.collections.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                Collections
            </a>
            <a href="{{ route('admin.reviews.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.reviews.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                Reviews
            </a>
            @if ($__na)
                <a href="{{ route('admin.products.show-delete-from-gmc') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.products.show-delete-from-gmc'), $m, 'danger') }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Remove Product GMC
                </a>
            @endif
        </div>
    </details>
@endif

@if ($__na)
    <details class="admin-nav-details mb-1" @if($navOpenAffiliate) open @endif>
        <summary class="{{ $sm($m, $navOpenAffiliate) }}">
            <span>Affiliate</span>
            <svg class="admin-nav-chevron w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="{{ $sub }}">
            <a href="{{ route('admin.affiliate-applications.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.affiliate-applications.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="flex items-center">
                    Applications
                    @if (isset($sidebarPendingAffiliateApplications) && $sidebarPendingAffiliateApplications > 0)
                        <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $m ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-900' }}">{{ $sidebarPendingAffiliateApplications }}</span>
                    @endif
                </span>
            </a>
            <a href="{{ route('admin.sample-requests.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.sample-requests.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <span class="flex items-center">
                    Sample requests
                    @if (isset($sidebarPendingSampleRequests) && $sidebarPendingSampleRequests > 0)
                        <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $m ? 'bg-violet-500 text-white' : 'bg-violet-100 text-violet-900' }}">{{ $sidebarPendingSampleRequests }}</span>
                    @endif
                </span>
            </a>
            @if (Route::has('admin.affiliates.analytics.index'))
                <a href="{{ route('admin.affiliates.analytics.index') }}" {!! $clk !!} class="{{ $ac($navActiveAffiliateAnalytics ?? false, $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Creator analytics
                </a>
            @endif
            @if (Route::has('admin.affiliates.index'))
                <a href="{{ route('admin.affiliates.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.affiliates.index') || request()->routeIs('admin.affiliates.create') || request()->routeIs('admin.affiliates.edit'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Affiliates
                </a>
            @endif
            <a href="{{ route('admin.settings.affiliate-program.edit') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.settings.affiliate-program.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Program Settings
            </a>
        </div>
    </details>
@endif

@if ($__na || $__nad || $__ns)
    <details class="admin-nav-details mb-1" @if($navOpenSales) open @endif>
        <summary class="{{ $sm($m, $navOpenSales) }}">
            <span>Sales</span>
            <svg class="admin-nav-chevron w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="{{ $sub }}">
            @if ($__na || $__nad)
                <a href="{{ route('admin.orders.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.orders.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span class="flex items-center">
                        Orders
                        @if (isset($sidebarPendingOrders) && $sidebarPendingOrders > 0)
                            <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $m ? 'bg-red-500 text-white' : 'bg-red-100 text-red-700' }}">{{ $sidebarPendingOrders }}</span>
                        @endif
                    </span>
                </a>
                <a href="{{ route('admin.returns.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.returns.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-7 7l3 3 4-6"></path></svg>
                    <span class="flex items-center">
                        Returns
                        @if (isset($sidebarPendingReturns) && $sidebarPendingReturns > 0)
                            <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $m ? 'bg-red-500 text-white' : 'bg-red-100 text-red-700' }}">{{ $sidebarPendingReturns }}</span>
                        @endif
                    </span>
                </a>
            @endif
            @if ($__na)
                <a href="{{ route('admin.promo-codes.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.promo-codes.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    Promo Codes
                </a>
                <a href="{{ route('admin.gift-cards.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.gift-cards.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12v7a2 2 0 01-2 2H6a2 2 0 01-2-2v-7m16 0V8a2 2 0 00-2-2h-3l-1-2h-4L9 6H6a2 2 0 00-2 2v4m16 0H4m4-2v4m8-4v4"></path></svg>
                    Gift Cards
                </a>
                <a href="{{ route('admin.settings.bulk-discounts.edit') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.settings.bulk-discounts.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V6m0 12v-2m-7-5a7 7 0 1114 0 7 7 0 01-14 0z"></path></svg>
                    Bulk Discounts
                </a>
            @endif
            @if ($__ns)
                <a href="{{ route('seller.orders.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('seller.orders.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Orders
                </a>
            @endif
        </div>
    </details>
@endif

@if ($__na)
    <details class="admin-nav-details mb-1" @if($navOpenShipping) open @endif>
        <summary class="{{ $sm($m, $navOpenShipping) }}">
            <span>Shipping</span>
            <svg class="admin-nav-chevron w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="{{ $sub }}">
            <a href="{{ route('admin.shipping-zones.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.shipping-zones.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Shipping Zones
            </a>
            <a href="{{ route('admin.shipping-rates.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.shipping-rates.*'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Shipping Rates
            </a>
        </div>
    </details>
@endif

@if ($__na || $__ns)
    <details class="admin-nav-details mb-1" @if($navOpenContent) open @endif>
        <summary class="{{ $sm($m, $navOpenContent) }}">
            <span>Content</span>
            <svg class="admin-nav-chevron w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="{{ $sub }}">
            @if ($__na)
                <a href="{{ route('admin.pages.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.pages.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    Pages
                </a>
            @endif
            @if ($__na || $__ns)
                <a href="{{ route('admin.posts.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.posts.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Blog Posts
                </a>
            @endif
            @if ($__na)
                <a href="{{ route('admin.post-categories.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.post-categories.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    Post Categories
                </a>
                <a href="{{ route('admin.post-tags.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.post-tags.*'), $m) }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    Post Tags
                </a>
            @endif
        </div>
    </details>
@endif

@if ($__na)
    <details class="admin-nav-details mb-1" @if($navOpenTools) open @endif>
        <summary class="{{ $sm($m, $navOpenTools) }}">
            <span>Tools</span>
            <svg class="admin-nav-chevron w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="{{ $sub }}">
            <a href="{{ route('admin.live-chat.index') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.live-chat.*'), $m) }}">
                <span class="relative inline-flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    Live Chat
                    @if (($liveChatUnreadCount ?? 0) > 0)
                        <span class="ml-1 min-w-[18px] h-[18px] px-1 inline-flex items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold">{{ $liveChatUnreadCount > 99 ? '99+' : $liveChatUnreadCount }}</span>
                    @endif
                </span>
            </a>
            <a href="{{ route('admin.api-token') }}" {!! $clk !!} class="{{ $ac(request()->routeIs('admin.api-token'), $m) }}">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                API Token
            </a>
        </div>
    </details>
@endif
