@extends('layouts.app')

@section('title', __('My Profile'))

@section('content')
<div class="min-h-screen bg-background-light font-display text-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <p class="text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8">
            @include('customer.profile.partials.sidebar')

            <div class="flex-1 space-y-8">
                {{-- Profile Header (theo code.html) --}}
                <section class="bg-white border border-primary/10 rounded-xl p-6 flex flex-col sm:flex-row items-center gap-6 shadow-sm">
                    <div class="h-24 w-24 rounded-full overflow-hidden border-4 border-primary/10 bg-slate-100 shrink-0">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full flex items-center justify-center bg-primary/20 text-primary text-3xl font-bold">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="text-center sm:text-left">
                        <h1 class="text-2xl font-bold text-slate-900">{{ $user->name }}</h1>
                        <p class="text-slate-500">{{ $user->email }}</p>
                        <p class="text-slate-500 mt-1">{{ __('Member since') }} {{ $user->created_at->translatedFormat('F Y') }}</p>
                        <div class="mt-2 flex gap-2 flex-wrap justify-center sm:justify-start">
                            @if($user->email_verified_at)
                                <span class="px-3 py-1 bg-primary/10 text-primary text-xs font-bold rounded-full uppercase tracking-wider">{{ __('Verified User') }}</span>
                            @else
                                <span class="px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full uppercase tracking-wider">{{ __('Unverified') }}</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('customer.profile.edit') }}" class="ml-auto px-6 py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90 transition-colors shadow-lg shadow-primary/25 flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">edit</span>
                        {{ __('Edit Profile') }}
                    </a>
                </section>

                {{-- Account Statistics (primary style) --}}
                <section class="bg-white border border-primary/10 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">analytics</span>
                        {{ __('Account Statistics') }}
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="flex items-center justify-between p-4 rounded-xl bg-primary/5 border border-primary/10">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                    <span class="material-symbols-outlined">shopping_bag</span>
                                </div>
                                <span class="text-slate-600">{{ __('Total Orders') }}</span>
                            </div>
                            <span class="text-xl font-bold text-slate-900">{{ $stats['total_orders'] }}</span>
                        </div>
                        <div class="flex items-center justify-between p-4 rounded-xl bg-primary/5 border border-primary/10">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                    <span class="material-symbols-outlined">payments</span>
                                </div>
                                <span class="text-slate-600">{{ __('Total Spent') }}</span>
                            </div>
                            <span class="text-xl font-bold text-slate-900">{{ currency_symbol() }}{{ number_format($stats['total_spent'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between p-4 rounded-xl bg-primary/5 border border-primary/10">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                    <span class="material-symbols-outlined">favorite</span>
                                </div>
                                <span class="text-slate-600">{{ __('Wishlist') }}</span>
                            </div>
                            <span class="text-xl font-bold text-slate-900">{{ $stats['wishlist_items'] }}</span>
                        </div>
                    </div>
                </section>

                {{-- Personal Information (read-only) --}}
                <section class="bg-white border border-primary/10 rounded-xl p-8 shadow-sm">
                    <h3 class="text-lg font-bold mb-6 text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">person</span>
                        {{ __('Personal Information') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">{{ __('Full Name') }}</label>
                            <p class="text-slate-900 font-medium">{{ $user->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">{{ __('Email') }}</label>
                            <p class="text-slate-900 font-medium">{{ $user->email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">{{ __('Phone') }}</label>
                            <p class="text-slate-900 font-medium">{{ $user->phone ?? '—' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">{{ __('Status') }}</label>
                            <span class="inline-flex px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">{{ __('Active') }}</span>
                        </div>
                    </div>
                </section>

                {{-- Address (read-only) --}}
                <section class="bg-white border border-primary/10 rounded-xl p-8 shadow-sm">
                    <h3 class="text-lg font-bold mb-6 text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">location_on</span>
                        {{ __('Address') }}
                    </h3>
                    @if($user->address || $user->city || $user->state || $user->postal_code || $user->country)
                        <div class="space-y-3 text-slate-700">
                            @if($user->address)<p>{{ $user->address }}</p>@endif
                            <p>
                                @if($user->city){{ $user->city }}@endif
                                @if($user->state){{ $user->state ? ', ' : '' }}{{ $user->state }}@endif
                                @if($user->postal_code) {{ $user->postal_code }}@endif
                                @if($user->country)<br>{{ $user->country }}@endif
                            </p>
                        </div>
                    @else
                        <p class="text-slate-500">{{ __('No address on file.') }}</p>
                    @endif
                    <a href="{{ route('customer.profile.edit') }}#address" class="inline-flex items-center gap-2 mt-4 text-primary font-bold hover:underline">
                        <span class="material-symbols-outlined text-lg">edit</span>
                        {{ __('Edit Address') }}
                    </a>
                </section>

                {{-- Quick Links (theo code.html style) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route('customer.orders.index') }}" class="flex items-center gap-4 p-6 rounded-xl bg-white border border-primary/10 hover:border-primary/30 hover:bg-primary/5 transition-all shadow-sm">
                        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-2xl">package</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900">{{ __('My Orders') }}</h4>
                            <p class="text-sm text-slate-500">{{ $stats['total_orders'] }} {{ __('orders') }}</p>
                        </div>
                    </a>
                    <a href="{{ route('wishlist.index') }}" class="flex items-center gap-4 p-6 rounded-xl bg-white border border-primary/10 hover:border-primary/30 hover:bg-primary/5 transition-all shadow-sm">
                        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-2xl">favorite</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900">{{ __('Wishlist') }}</h4>
                            <p class="text-sm text-slate-500">{{ $stats['wishlist_items'] }} {{ __('items') }}</p>
                        </div>
                    </a>
                    <a href="{{ route('customer.profile.edit') }}" class="flex items-center gap-4 p-6 rounded-xl bg-white border border-primary/10 hover:border-primary/30 hover:bg-primary/5 transition-all shadow-sm">
                        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-2xl">settings</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900">{{ __('Settings') }}</h4>
                            <p class="text-sm text-slate-500">{{ __('Edit profile') }}</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
