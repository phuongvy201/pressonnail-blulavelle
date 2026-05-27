{{-- Sidebar account: profile, orders, seller (shared with customer profile & seller orders) --}}
@php
    $navUser = auth()->user();
    $isSeller = $navUser && $navUser->hasRole('seller');
    $navActive = fn (bool $active): string => $active
        ? 'bg-primary text-white'
        : 'text-slate-600 hover:bg-primary/10';
@endphp
<aside class="w-full lg:w-64 flex-shrink-0">
    <nav class="space-y-1">
        <a href="{{ route('customer.profile.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors {{ $navActive(request()->routeIs('customer.profile.index')) }}">
            <span class="material-symbols-outlined">person</span>
            <span>{{ __('My Profile') }}</span>
        </a>
        <a href="{{ route('customer.profile.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors {{ $navActive(request()->routeIs('customer.profile.edit')) }}">
            <span class="material-symbols-outlined">edit_square</span>
            <span>{{ __('Edit Profile') }}</span>
        </a>
        <a href="{{ route('customer.orders.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors {{ $navActive(request()->routeIs('customer.orders.*')) }}">
            <span class="material-symbols-outlined">shopping_bag</span>
            <span>{{ __('My Orders') }}</span>
        </a>
        <a href="{{ route('customer.profile.edit') }}#address" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-primary/10 transition-colors font-medium">
            <span class="material-symbols-outlined">location_on</span>
            <span>{{ __('Address Book') }}</span>
        </a>
        <a href="{{ route('wishlist.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors {{ $navActive(request()->routeIs('wishlist.*')) }}">
            <span class="material-symbols-outlined">favorite</span>
            <span>{{ __('Wishlist') }}</span>
        </a>

        @if ($navUser && $navUser->canAccessCreatorAffiliateFeatures())
            <div class="pt-4 mt-2 border-t border-primary/10">
                <p class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Creator') }}</p>
                <a href="{{ \App\Support\CreatorPortal::dashboardUrl() }}" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors text-slate-600 hover:bg-primary/10">
                    <span class="material-symbols-outlined">campaign</span>
                    <span>{{ __('Creator dashboard') }}</span>
                </a>
            </div>
        @endif

        @if ($isSeller && Route::has('seller.orders.index'))
            <div class="pt-4 mt-2 border-t border-primary/10">
                <p class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Seller') }}</p>
                @if (Route::has('seller.shop.dashboard'))
                    <a href="{{ route('seller.shop.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors {{ $navActive(request()->routeIs('seller.shop.*')) }}">
                        <span class="material-symbols-outlined">storefront</span>
                        <span>{{ __('My Shop') }}</span>
                    </a>
                @endif
                <a href="{{ route('seller.orders.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors {{ $navActive(request()->routeIs('seller.orders.*')) }}">
                    <span class="material-symbols-outlined">inventory_2</span>
                    <span>{{ __('Shop Orders') }}</span>
                </a>
            </div>
        @endif

        <div class="pt-4 mt-4 border-t border-primary/10">
            <form method="POST" action="{{ route('logout') }}" class="block">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 px-4 py-3 rounded-lg text-red-500 hover:bg-red-50 transition-colors font-medium">
                    <span class="material-symbols-outlined">logout</span>
                    <span>{{ __('Sign Out') }}</span>
                </button>
            </form>
        </div>
    </nav>
</aside>
