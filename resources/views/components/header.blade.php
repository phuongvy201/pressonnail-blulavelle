<!-- Header Component - Responsive: logo + nav + search + cart + user -->
@php
    $headerBg = \App\Support\Settings::get('theme.header_bg', config('theme.header_bg'));
    $headerBorder = \App\Support\Settings::get('theme.header_border', config('theme.header_border'));
    $headerBgCustom = (is_string($headerBg) && (str_starts_with(trim($headerBg), '#') || str_starts_with(trim($headerBg), 'rgb'))) ? trim($headerBg) : null;
    $headerBorderCustom = (is_string($headerBorder) && (str_starts_with(trim($headerBorder), '#') || str_starts_with(trim($headerBorder), 'rgb'))) ? trim($headerBorder) : null;
    $headerStyle = '';
    if ($headerBgCustom) $headerStyle .= "background-color: {$headerBgCustom};";
    if ($headerBorderCustom) $headerStyle .= "border-bottom-color: {$headerBorderCustom};";
@endphp
<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-primary/10 px-3 sm:px-6 lg:px-20 py-1 sm:py-1.5 bg-background-light sticky top-0 z-50" @if($headerStyle !== '') style="{{ $headerStyle }}" @endif>
    <div class="flex items-center gap-4 sm:gap-6 lg:gap-12 w-full min-w-0">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="flex items-center flex-shrink-0">
            <div class="max-w-[160px] max-h-[72px] sm:max-w-[190px] sm:max-h-[80px] md:max-w-[220px] md:max-h-[88px] lg:max-w-[260px] lg:max-h-[96px] overflow-hidden rounded flex items-center justify-center">
                <img src="{{ asset('images/logo/logo(3).png') }}" alt="Baby Blue" class="w-auto h-auto max-w-full max-h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="w-full h-full flex items-center justify-center text-primary" style="display: none;">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8 md:w-9 md:h-9 lg:w-10 lg:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
            </div>
        </a>

        <!-- Desktop/MD Nav -->
        <nav class="hidden md:flex items-center gap-5 lg:gap-10 flex-shrink-0">
            <a href="{{ route('products.index') }}" class="text-slate-700 text-base font-semibold hover:text-primary transition-colors {{ request()->routeIs('products.*') ? 'text-primary' : '' }}">All Nails</a>
            <a href="{{ route('collections.index') }}" class="text-slate-700 text-base font-semibold hover:text-primary transition-colors {{ request()->routeIs('collections.*') ? 'text-primary' : '' }}">Collections</a>
            <a href="{{ route('sizing-kit.index') }}" class="text-slate-700 text-base font-semibold hover:text-primary transition-colors {{ request()->routeIs('sizing-kit.*') ? 'text-primary' : '' }}">Sizing Kit</a>
            <div class="relative group">
                <button type="button" class="text-slate-700 text-base font-semibold hover:text-primary transition-colors">Help Center</button>
                <div class="absolute left-0 mt-2 w-56 sm:w-60 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-2.5">
                    @php
                        $helpPages = \App\Models\Page::where('status', 'published')->whereIn('slug', ['faqs', 'about-us', 'contact-us', 'refund-policy', 'returns-exchanges-policy'])->orderBy('sort_order')->get();
                    @endphp
                    @foreach($helpPages as $page)
                        <a href="{{ '/page/' . $page->slug }}" class="block px-4 py-2.5 text-base text-gray-700 hover:bg-gray-50">{{ $page->title }}</a>
                    @endforeach
                </div>
            </div>
        </nav>
    </div>

    <!-- Right: Desktop = Search + Wishlist + Cart + User | Mobile = Search + Cart + Menu -->
    <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0">
        <!-- Search desktop: từ sm trở lên -->
        <div class="hidden sm:block relative min-w-0">
            <form action="{{ route('search') }}" method="GET" class="relative">
                <label class="relative flex items-center">
                    <svg class="absolute left-3 sm:left-4 w-4 h-4 sm:w-5 sm:h-5 text-slate-400 pointer-events-none flex-shrink-0 z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <div class="relative flex-1 min-w-0 w-36 sm:w-48 md:w-56 lg:w-72 rounded-full bg-slate-200/50">
                        <div id="search-placeholder-wrap" class="absolute top-0 bottom-0 left-9 sm:left-12 right-0 flex items-center pointer-events-none overflow-hidden rounded-r-full pr-3 sm:pr-5 transition-opacity duration-200">
                            <span id="search-typewriter-text" class="search-typewriter-text"></span><span class="search-typewriter-cursor">|</span>
                        </div>
                        <input type="text" name="q" id="search-input" placeholder=" " value="{{ request('q') }}"
                               class="relative z-[1] pl-9 sm:pl-12 pr-3 sm:pr-5 py-2 sm:py-2.5 rounded-full border-none bg-transparent focus:ring-2 focus:ring-primary text-sm sm:text-base w-full text-slate-900 min-w-0">
                    </div>
                </label>
                <div id="search-suggestions" class="hidden absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-xl border border-gray-200 max-h-[70vh] sm:max-h-96 overflow-y-auto z-50 min-w-[16rem] max-w-[min(24rem,100vw-2rem)]">
                    <div id="suggestions-content" class="p-2"></div>
                </div>
            </form>
        </div>

        <!-- Search mobile: nút mở thanh search bên dưới header -->
        <button id="mobile-search-btn" type="button" class="sm:hidden p-2.5 hover:bg-primary/10 rounded-full transition-colors flex-shrink-0" aria-label="Search">
            <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </button>

        <!-- Wishlist - ẩn trên mobile (có trong menu), từ sm trở lên hiện icon trên header -->
        <a href="{{ route('wishlist.index') }}" class="hidden sm:inline-flex p-2 sm:p-2.5 hover:bg-primary/10 rounded-full transition-colors relative flex-shrink-0" aria-label="Wishlist">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <span id="mobile-wishlist-count" class="wishlist-count absolute -top-0.5 -right-0.5 bg-primary text-white text-[10px] sm:text-xs rounded-full min-w-[1.125rem] h-4.5 flex items-center justify-center font-bold px-1" style="display: none;">0</span>
            <span id="desktop-wishlist-count" class="wishlist-count absolute -top-0.5 -right-0.5 bg-primary text-white text-[10px] sm:text-xs rounded-full min-w-[1.125rem] h-4.5 flex items-center justify-center font-bold px-1" style="display: none;">0</span>
        </a>

        <!-- Cart -->
        <a href="{{ route('cart.index') }}" id="header-cart-trigger" class="p-2 sm:p-2.5 hover:bg-primary/10 rounded-full transition-colors relative inline-flex items-center justify-center flex-shrink-0" aria-label="Giỏ hàng">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span id="mobile-cart-count" class="cart-count absolute -top-0.5 -right-0.5 bg-primary text-white text-[10px] sm:text-xs rounded-full min-w-[1.125rem] h-4.5 flex items-center justify-center font-bold px-1" style="display: none;">0</span>
            <span id="desktop-cart-count" class="cart-count absolute -top-0.5 -right-0.5 bg-primary text-white text-[10px] sm:text-xs rounded-full min-w-[1.125rem] h-4.5 flex items-center justify-center font-bold px-1" style="display: none;">0</span>
            <span id="cart-tooltip" class="hidden sm:block absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2.5 py-1.5 bg-slate-800 text-white text-xs sm:text-sm rounded opacity-0 group-hover/cart:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">Cart</span>
        </a>

        <!-- User - chỉ từ md (dưới md dùng link trong menu) -->
        <div class="hidden md:block">
            @auth
                <div class="relative group">
                    <button type="button" class="p-2 sm:p-2.5 hover:bg-primary/10 rounded-full transition-colors flex items-center justify-center flex-shrink-0">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full object-cover">
                        @else
                            <div class="w-8 h-8 sm:w-10 sm:h-10 bg-primary rounded-full flex items-center justify-center text-white font-bold text-sm sm:text-base">{{ substr(auth()->user()->name, 0, 1) }}</div>
                        @endif
                    </button>
                    <div class="absolute right-0 mt-2 w-56 sm:w-60 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-2.5">
                        <div class="px-4 py-3 border-b border-gray-100 min-w-0">
                            <p class="text-sm sm:text-base font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs sm:text-sm text-gray-500 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        @if(auth()->user()->hasAnyRole(['admin', 'seller', 'ad-partner']))
                            <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2.5 text-base text-gray-700 hover:bg-gray-50">Dashboard</a>
                        @endif
                        @if(!auth()->user()->hasVerifiedEmail())
                            <a href="{{ route('verification.notice') }}" class="flex items-center px-4 py-2.5 text-base text-orange-600 hover:bg-orange-50">Verify Email</a>
                        @endif
                        <a href="{{ route('customer.orders.index') }}" class="flex items-center px-4 py-2.5 text-base text-gray-700 hover:bg-gray-50">My Orders</a>
                        <a href="{{ route('customer.profile.index') }}" class="flex items-center px-4 py-2.5 text-base text-gray-700 hover:bg-gray-50">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex items-center w-full px-4 py-2.5 text-base text-red-600 hover:bg-red-50">Logout</button></form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="p-2 sm:p-2.5 hover:bg-primary/10 rounded-full transition-colors flex-shrink-0 inline-flex" aria-label="Login">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </a>
            @endauth
        </div>

        <!-- Mobile/Tablet < md: Cart + Menu (Search/Wishlist/Account trong menu) -->
        <button id="mobile-menu-btn" type="button" class="md:hidden p-2.5 hover:bg-primary/10 rounded-full transition-colors flex-shrink-0" aria-label="Menu">
            <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>
</header>

<!-- Mobile Search Overlay (popup đè lên màn hình, chỉ mobile) -->
<div id="mobile-search-backdrop" class="fixed inset-0 bg-black/30 z-40 hidden sm:hidden transition-opacity" aria-hidden="true"></div>
<div id="mobile-search" class="fixed top-0 left-0 right-0 z-50 hidden sm:hidden bg-white shadow-lg border-b border-primary/10 transform transition-transform duration-300 ease-out">
    <div class="px-4 py-4 flex items-center gap-3">
        <form action="{{ route('search') }}" method="GET" class="flex-1 relative">
            <label class="relative flex items-center">
                <svg class="absolute left-3 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" id="mobile-search-input" placeholder="Search styles..." value="{{ request('q') }}"
                       class="w-full pl-10 pr-4 py-2.5 rounded-full border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary focus:border-primary text-sm text-slate-900">
            </label>
        </form>
        <button type="button" id="mobile-search-close" class="p-2 hover:bg-primary/10 rounded-full transition-colors flex-shrink-0" aria-label="Đóng">
            <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>

<!-- Mobile Menu Overlay (drawer đè lên màn hình như cart popup, chỉ < lg) -->
<div id="mobile-menu-backdrop" class="fixed inset-0 bg-black/30 z-40 hidden lg:hidden transition-opacity" aria-hidden="true"></div>
<div id="mobile-menu" class="fixed top-0 right-0 h-full w-full max-w-[min(100vw,24rem)] bg-white shadow-2xl z-50 flex flex-col border-l border-primary/10 transform translate-x-full transition-transform duration-300 ease-out lg:hidden" role="dialog" aria-label="Menu">
    <div class="flex items-center justify-between px-4 py-4 border-b border-primary/10 flex-shrink-0">
        <span class="text-lg font-bold text-slate-900">Menu</span>
        <button type="button" id="mobile-menu-close" class="p-2 hover:bg-primary/10 rounded-full transition-colors" aria-label="Đóng">
            <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="flex-1 overflow-y-auto px-4 py-4">
        {{-- 1. Wishlist --}}
        <div class="mb-5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600 px-2 mb-2">Wishlist</p>
            <a href="{{ route('wishlist.index') }}" class="flex items-center justify-between py-3 px-3 rounded-xl bg-primary/5 hover:bg-primary/10 active:bg-primary/15 transition-colors">
                <span class="flex items-center gap-3 text-slate-800 font-semibold text-sm">
                    <span class="w-9 h-9 rounded-full bg-white flex items-center justify-center shadow-sm text-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </span>
                    My Wishlist
                </span>
                <span id="mobile-menu-wishlist-count" class="wishlist-count bg-primary text-white text-xs rounded-full min-w-[1.25rem] h-5 flex items-center justify-center font-bold px-1.5" style="display: none;">0</span>
            </a>
        </div>

        {{-- 2. Account --}}
        <div class="mb-5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600 px-2 mb-2">Account</p>
            @auth
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 overflow-hidden">
                <a href="{{ route('customer.profile.index') }}" class="flex items-center gap-3 py-3.5 px-3 hover:bg-white/60 transition-colors">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" alt="" class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow">
                    @else
                        <span class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm shadow">{{ substr(auth()->user()->name, 0, 1) }}</span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-slate-900 truncate text-sm">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </a>
                <div class="border-t border-slate-200/80 divide-y divide-slate-200/80">
                    <a href="{{ route('customer.orders.index') }}" class="flex items-center gap-3 py-2.5 px-3 text-slate-700 text-sm hover:bg-white/60 hover:text-primary transition-colors">
                        <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-slate-500 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg></span>
                        My Orders
                    </a>
                    <a href="{{ route('customer.profile.index') }}" class="flex items-center gap-3 py-2.5 px-3 text-slate-700 text-sm hover:bg-white/60 hover:text-primary transition-colors">
                        <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-slate-500 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></span>
                        Profile
                    </a>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-200/80">@csrf
                    <button type="submit" class="flex items-center gap-3 w-full py-2.5 px-3 text-left text-red-600 text-sm font-semibold hover:bg-red-50 transition-colors">
                        <span class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg></span>
                        Logout
                    </button>
                </form>
            </div>
            @else
            <a href="{{ route('login') }}" class="flex items-center gap-3 py-3 px-3 rounded-xl border border-slate-200/80 bg-slate-50/80 hover:bg-primary/5 hover:border-primary/20 transition-colors">
                <span class="w-9 h-9 rounded-full bg-white flex items-center justify-center shadow-sm text-slate-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
                <span class="font-semibold text-slate-800 text-sm">Login / Register</span>
            </a>
            @endauth
        </div>

        {{-- 3. Menu (Browse) --}}
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600 px-2 mb-2">Menu</p>
            <nav class="rounded-xl border border-slate-200/80 bg-white overflow-hidden">
                <a href="{{ route('home') }}" class="flex items-center gap-3 py-3 px-3 text-slate-700 text-sm font-medium hover:bg-primary/5 hover:text-primary border-b border-slate-100 transition-colors">
                    <span class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></span>
                    Home
                </a>
                <a href="{{ route('products.index') }}" class="flex items-center gap-3 py-3 px-3 text-slate-700 text-sm font-medium hover:bg-primary/5 hover:text-primary border-b border-slate-100 transition-colors">
                    <span class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg></span>
                    All Nails
                </a>
                <a href="{{ route('products.index', ['new' => 1]) }}" class="flex items-center gap-3 py-3 px-3 text-slate-700 text-sm font-medium hover:bg-primary/5 hover:text-primary border-b border-slate-100 transition-colors">
                    <span class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg></span>
                    New Arrivals
                </a>
                <a href="{{ route('sizing-kit.index') }}" class="flex items-center gap-3 py-3 px-3 text-slate-700 text-sm font-medium hover:bg-primary/5 hover:text-primary border-b border-slate-100 transition-colors">
                    <span class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg></span>
                    Sizing Kit
                </a>
                <a href="{{ route('collections.index') }}" class="flex items-center gap-3 py-3 px-3 text-slate-700 text-sm font-medium hover:bg-primary/5 hover:text-primary border-b border-slate-100 transition-colors">
                    <span class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg></span>
                    Collections
                </a>
                <a href="{{ route('blog.index') }}" class="flex items-center gap-3 py-3 px-3 text-slate-700 text-sm font-medium hover:bg-primary/5 hover:text-primary border-b border-slate-100 transition-colors">
                    <span class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg></span>
                    Blog
                </a>
                <a href="{{ url('/page/faqs') }}" class="flex items-center gap-3 py-3 px-3 text-slate-700 text-sm font-medium hover:bg-primary/5 hover:text-primary transition-colors">
                    <span class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                    Help Center
                </a>
            </nav>
        </div>
    </div>
</div>

    <style>
        .search-typewriter-text {
            color: rgb(100 116 139);
            font-size: 0.875rem;
        }
        @media (min-width: 640px) {
            .search-typewriter-text { font-size: 1rem; }
        }
        .search-typewriter-cursor {
            color: rgb(100 116 139);
            animation: search-cursor-blink 1s step-end infinite;
        }
        @keyframes search-cursor-blink {
            50% { opacity: 0; }
        }
        #search-placeholder-wrap.search-placeholder-hidden {
            opacity: 0;
        }
    </style>

    <!-- JavaScript for Mobile Menu and Search (overlay popup như cart drawer) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuBackdrop = document.getElementById('mobile-menu-backdrop');
            const mobileMenuClose = document.getElementById('mobile-menu-close');
            const mobileSearchBtn = document.getElementById('mobile-search-btn');
            const mobileSearch = document.getElementById('mobile-search');
            const mobileSearchBackdrop = document.getElementById('mobile-search-backdrop');
            const mobileSearchClose = document.getElementById('mobile-search-close');

            function closeMobileMenu() {
                if (mobileMenuBackdrop) mobileMenuBackdrop.classList.add('hidden');
                if (mobileMenu) mobileMenu.classList.add('translate-x-full');
            }
            function openMobileMenu() {
                if (mobileSearchBackdrop) mobileSearchBackdrop.classList.add('hidden');
                if (mobileSearch) mobileSearch.classList.add('hidden');
                if (mobileMenuBackdrop) mobileMenuBackdrop.classList.remove('hidden');
                if (mobileMenu) mobileMenu.classList.remove('translate-x-full');
            }
            function closeMobileSearch() {
                if (mobileSearchBackdrop) mobileSearchBackdrop.classList.add('hidden');
                if (mobileSearch) mobileSearch.classList.add('hidden');
            }
            function openMobileSearch() {
                if (mobileMenuBackdrop) mobileMenuBackdrop.classList.add('hidden');
                if (mobileMenu) mobileMenu.classList.add('translate-x-full');
                if (mobileSearchBackdrop) mobileSearchBackdrop.classList.remove('hidden');
                if (mobileSearch) mobileSearch.classList.remove('hidden');
            }

            if (mobileMenuBtn && mobileMenu) {
                mobileMenuBtn.addEventListener('click', openMobileMenu);
            }
            if (mobileMenuClose) mobileMenuClose.addEventListener('click', closeMobileMenu);
            if (mobileMenuBackdrop) mobileMenuBackdrop.addEventListener('click', closeMobileMenu);

            if (mobileSearchBtn && mobileSearch) {
                mobileSearchBtn.addEventListener('click', openMobileSearch);
            }
            if (mobileSearchClose) mobileSearchClose.addEventListener('click', closeMobileSearch);
            if (mobileSearchBackdrop) mobileSearchBackdrop.addEventListener('click', closeMobileSearch);

            document.addEventListener('click', function(event) {
                if (mobileMenu && !mobileMenu.classList.contains('translate-x-full') && !mobileMenuBtn.contains(event.target) && !mobileMenu.contains(event.target) && !mobileMenuBackdrop.contains(event.target)) {
                    closeMobileMenu();
                }
                if (mobileSearch && !mobileSearch.classList.contains('hidden') && !mobileSearchBtn.contains(event.target) && !mobileSearch.contains(event.target) && !mobileSearchBackdrop.contains(event.target)) {
                    closeMobileSearch();
                }
            });

            const desktopSearchInput = document.getElementById('search-input');
            const mobileSearchInput = document.getElementById('mobile-search-input');
            const searchPlaceholderWrap = document.getElementById('search-placeholder-wrap');
            function toggleSearchPlaceholder() {
                if (!searchPlaceholderWrap || !desktopSearchInput) return;
                const hide = desktopSearchInput.value.trim() !== '' || document.activeElement === desktopSearchInput;
                searchPlaceholderWrap.classList.toggle('search-placeholder-hidden', hide);
            }
            if (desktopSearchInput) {
                desktopSearchInput.addEventListener('focus', toggleSearchPlaceholder);
                desktopSearchInput.addEventListener('blur', toggleSearchPlaceholder);
                desktopSearchInput.addEventListener('input', toggleSearchPlaceholder);
                toggleSearchPlaceholder();
            }

            // Typewriter: gõ từng chữ rồi xóa, lần lượt các cụm
            (function() {
                const el = document.getElementById('search-typewriter-text');
                if (!el) return;
                const phrases = ['Search style', 'search nail', 'collections', 'sizing kit'];
                let phraseIndex = 0;
                let charIndex = 0;
                let isDeleting = false;
                const typeDelay = 90;
                const deleteDelay = 50;
                const pauseAfterType = 1800;
                const pauseAfterDelete = 400;
                let timeoutId = null;
                function tick() {
                    const phrase = phrases[phraseIndex];
                    if (isDeleting) {
                        if (charIndex === 0) {
                            isDeleting = false;
                            phraseIndex = (phraseIndex + 1) % phrases.length;
                            timeoutId = setTimeout(tick, pauseAfterDelete);
                            return;
                        }
                        charIndex--;
                        el.textContent = phrase.slice(0, charIndex);
                        timeoutId = setTimeout(tick, deleteDelay);
                    } else {
                        el.textContent = phrase.slice(0, charIndex + 1);
                        charIndex++;
                        if (charIndex >= phrase.length) {
                            isDeleting = true;
                            timeoutId = setTimeout(tick, pauseAfterType);
                        } else {
                            timeoutId = setTimeout(tick, typeDelay);
                        }
                    }
                }
                timeoutId = setTimeout(tick, 400);
            })();

            if (mobileSearchInput) {
                mobileSearchInput.addEventListener('focus', function() { this.setAttribute('placeholder', 'Search styles...'); });
            }

            updateHeaderCartCount();
            window.addEventListener('load', function () {
                setTimeout(syncHeaderWithBackend, 1400);
            }, { once: true });
        });

        // Function to update cart count in header
        function updateHeaderCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            // Update both mobile and desktop cart counts
            const mobileCartCount = document.getElementById('mobile-cart-count');
            const desktopCartCount = document.getElementById('desktop-cart-count');
            
            [mobileCartCount, desktopCartCount].forEach(element => {
                if (element) {
                    element.textContent = totalItems;
                    element.style.display = totalItems > 0 ? 'flex' : 'none';
                }
            });

            // Update tooltip
            const cartTooltip = document.getElementById('cart-tooltip');
            if (cartTooltip && totalItems > 0) {
                const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                cartTooltip.textContent = `Cart (${totalItems} items - $${totalPrice.toFixed(2)})`;
            }
        }

        // Listen for storage changes (when cart is updated in another tab)
        window.addEventListener('storage', function(e) {
            if (e.key === 'cart') {
                updateHeaderCartCount();
            }
        });

        // Listen for custom cart update event
        window.addEventListener('cartUpdated', function() {
            updateHeaderCartCount();
        });
        
        // Function to sync header with backend
        function syncHeaderWithBackend() {
            fetch('/api/cart/get', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.cart_items) {
                    // Convert backend cart items to localStorage format
                    const backendCart = data.cart_items.map(item => ({
                        id: item.product_id,
                        name: item.product.name,
                        price: parseFloat(item.price),
                        quantity: item.quantity,
                        selectedVariant: item.selected_variant,
                        customizations: item.customizations,
                        addedAt: Date.now()
                    }));
                    
                    // Update localStorage to match backend
                    localStorage.setItem('cart', JSON.stringify(backendCart));
                    
                    // Update header count
                    updateHeaderCartCount();
                    
                    console.log('Header synced with backend');
                }
            })
            .catch(error => {
                console.error('Failed to sync header with backend:', error);
            });
        }

        // Search Suggestions/Autocomplete
        const searchInput = document.getElementById('search-input');
        const suggestionsContainer = document.getElementById('search-suggestions');
        const suggestionsContent = document.getElementById('suggestions-content');
        let searchTimeout;

        if (searchInput && suggestionsContainer) {
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.trim();
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    suggestionsContainer.classList.add('hidden');
                    return;
                }
                
                // Debounce search
                searchTimeout = setTimeout(() => {
                    fetch(`{{ route('search.suggestions') }}?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            const items = Array.isArray(data) ? data : (data.items || []);
                            const phrases = data.phrases || [];
                            if (items.length === 0 && phrases.length === 0) {
                                suggestionsContainer.classList.add('hidden');
                                return;
                            }
                            
                            let html = '';
                            if (phrases.length > 0) {
                                html += '<div class="px-2 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">Suggested</div>';
                                phrases.forEach(phrase => {
                                    html += `<a href="{{ route('search') }}?q=${encodeURIComponent(phrase)}" class="flex items-center p-2.5 hover:bg-gray-50 rounded-xl transition-colors text-sm text-slate-700">${phrase}</a>`;
                                });
                                html += '<div class="border-t border-gray-200 my-2"></div>';
                            }
                            
                            items.forEach(item => {
                                if (item.type === 'product') {
                                    html += `
                                        <a href="${item.url}" class="flex items-center space-x-3 p-3 hover:bg-gray-50 rounded-xl transition-colors">
                                            <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                                ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">` : '<div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">No img</div>'}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">${item.name}</p>
                                                <p class="text-xs text-[#005366] font-bold">$${parseFloat(item.price).toFixed(2)}</p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                                                    Product
                                                </span>
                                            </div>
                                        </a>
                                    `;
                                } else if (item.type === 'collection') {
                                    html += `
                                        <a href="${item.url}" class="flex items-center space-x-3 p-3 hover:bg-gray-50 rounded-xl transition-colors">
                                            <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                                ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">` : '<div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">No img</div>'}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">${item.name}</p>
                                                <p class="text-xs text-gray-500">${item.products_count} products</p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800">
                                                    Collection
                                                </span>
                                            </div>
                                        </a>
                                    `;
                                } else if (item.type === 'shop') {
                                    html += `
                                        <a href="${item.url}" class="flex items-center space-x-3 p-3 hover:bg-gray-50 rounded-xl transition-colors">
                                            <div class="w-12 h-12 bg-gray-100 rounded-full overflow-hidden flex-shrink-0">
                                                ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">` : `<div class="w-full h-full flex items-center justify-center bg-[#005366] text-white font-bold">${item.name.charAt(0)}</div>`}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">${item.name}</p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800">
                                                    Shop
                                                </span>
                                            </div>
                                        </a>
                                    `;
                                }
                            });
                            
                            // Add "View all results" link
                            html += `
                                <div class="border-t border-gray-200 mt-2 pt-2">
                                    <a href="{{ route('search') }}?q=${encodeURIComponent(query)}" class="block text-center text-sm text-[#0297FE] hover:text-[#d6386a] font-semibold p-3 hover:bg-gray-50 rounded-xl transition-colors">
                                        View all results for "${query}"
                                    </a>
                                </div>
                            `;
                            
                            suggestionsContent.innerHTML = html;
                            suggestionsContainer.classList.remove('hidden');
                        })
                        .catch(error => {
                            console.error('Search suggestions error:', error);
                            suggestionsContainer.classList.add('hidden');
                        });
                }, 300); // 300ms debounce
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                    suggestionsContainer.classList.add('hidden');
                }
            });

            // Show suggestions when focusing search input if it has value
            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length >= 2 && suggestionsContent.innerHTML !== '') {
                    suggestionsContainer.classList.remove('hidden');
                }
            });
        }

        // Update wishlist count
        function updateWishlistCount() {
            // Always fetch from server (no localStorage)
            fetch('{{ route("wishlist.count") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const count = data.count;
                        
                        // Update header badge + menu wishlist count
                        const mobileWishlistCount = document.getElementById('mobile-wishlist-count');
                        const desktopWishlistCount = document.getElementById('desktop-wishlist-count');
                        const menuWishlistCount = document.getElementById('mobile-menu-wishlist-count');
                        
                        [mobileWishlistCount, desktopWishlistCount, menuWishlistCount].forEach(element => {
                            if (element) {
                                element.textContent = count;
                                element.style.display = count > 0 ? 'flex' : 'none';
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Failed to fetch wishlist count:', error);
                });
        }

        // Số wishlist: do public/js/wishlist.js tải sau idle (tránh chuỗi request quan trọng / trùng fetch).
        // Listen for custom wishlist update event
        window.addEventListener('wishlistUpdated', function() {
            updateWishlistCount();
        });
    </script>