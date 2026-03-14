{{-- Sidebar navigation cho trang profile (theo code.html BleameNails) --}}
@php
    $currentRoute = request()->routeIs('customer.profile.*') ? request()->route()->getName() : null;
@endphp
<aside class="w-full lg:w-64 flex-shrink-0">
    <nav class="space-y-1">
        <a href="{{ route('customer.profile.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors {{ request()->routeIs('customer.profile.index') ? 'bg-primary text-white' : 'text-slate-600 hover:bg-primary/10' }}">
            <span class="material-symbols-outlined">person</span>
            <span>{{ __('My Profile') }}</span>
        </a>
        <a href="{{ route('customer.profile.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors {{ request()->routeIs('customer.profile.edit') ? 'bg-primary text-white' : 'text-slate-600 hover:bg-primary/10' }}">
            <span class="material-symbols-outlined">edit_square</span>
            <span>{{ __('Edit Profile') }}</span>
        </a>
        <a href="{{ route('customer.orders.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-primary/10 transition-colors font-medium">
            <span class="material-symbols-outlined">package</span>
            <span>{{ __('My Orders') }}</span>
        </a>
        <a href="{{ route('customer.profile.edit') }}#address" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-primary/10 transition-colors font-medium">
            <span class="material-symbols-outlined">location_on</span>
            <span>{{ __('Address Book') }}</span>
        </a>
        <a href="{{ route('wishlist.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-primary/10 transition-colors font-medium">
            <span class="material-symbols-outlined">favorite</span>
            <span>{{ __('Wishlist') }}</span>
        </a>
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
