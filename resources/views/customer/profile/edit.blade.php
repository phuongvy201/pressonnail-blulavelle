@extends('layouts.app')

@section('title', __('Edit Profile'))

@section('content')
<div class="min-h-screen bg-background-light font-display text-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <p class="text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-red-600 shrink-0">error</span>
                    <div>
                        <h3 class="font-semibold text-red-800">{{ __('Please fix the following errors:') }}</h3>
                        <ul class="list-disc list-inside text-sm text-red-700 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8">
            @include('customer.profile.partials.sidebar')

            <div class="flex-1 space-y-8">
                {{-- Profile Header (theo code.html) --}}
                <section class="bg-white border border-primary/10 rounded-xl p-6 flex flex-col sm:flex-row items-center gap-6 shadow-sm">
                    <div class="relative group">
                        <div class="h-24 w-24 rounded-full overflow-hidden border-4 border-primary/10 bg-slate-100">
                            @if($user->avatar)
                                <img id="avatar-preview" src="{{ $user->avatar }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                            @else
                                <div id="avatar-preview" class="h-full w-full flex items-center justify-center bg-primary/20 text-primary text-3xl font-bold">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <label class="absolute bottom-0 right-0 bg-primary text-white p-1.5 rounded-full border-2 border-white shadow-lg cursor-pointer hover:bg-primary/90 transition-colors">
                            <span class="material-symbols-outlined text-sm">photo_camera</span>
                            <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/jpg,image/png,image/webp" class="hidden">
                        </label>
                    </div>
                    <div class="text-center sm:text-left">
                        <h1 class="text-2xl font-bold text-slate-900">{{ $user->name }}</h1>
                        <p class="text-slate-500">{{ __('Member since') }} {{ $user->created_at->translatedFormat('F Y') }}</p>
                        <div class="mt-2 flex gap-2 flex-wrap justify-center sm:justify-start">
                            @if($user->email_verified_at)
                                <span class="px-3 py-1 bg-primary/10 text-primary text-xs font-bold rounded-full uppercase tracking-wider">{{ __('Verified User') }}</span>
                            @else
                                <span class="px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full uppercase tracking-wider">{{ __('Unverified') }}</span>
                            @endif
                        </div>
                    </div>
                </section>

                {{-- Personal Information Form --}}
                <form action="{{ route('customer.profile.update') }}" method="POST" enctype="multipart/form-data" id="profile-form">
                    @csrf
                    @method('PUT')

                    <section class="bg-white border border-primary/10 rounded-xl p-8 shadow-sm">
                        <h3 class="text-lg font-bold mb-6 text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">edit_square</span>
                            {{ __('Personal Information') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700" for="name">{{ __('Full Name') }}</label>
                                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                                    class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-900">
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700" for="email">{{ __('Email Address') }}</label>
                                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                                    class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-900">
                                @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                                    <p class="text-sm text-amber-600 mt-1">
                                        {{ __('Your email is unverified.') }}
                                        <button form="send-verification" type="submit" class="underline hover:no-underline">{{ __('Resend verification email') }}</button>
                                    </p>
                                @endif
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700" for="phone">{{ __('Phone Number') }}</label>
                                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}"
                                    class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-900">
                            </div>
                        </div>
                    </section>

                    {{-- Address (anchor #address for sidebar) --}}
                    <section id="address" class="bg-white border border-primary/10 rounded-xl p-8 shadow-sm scroll-mt-24">
                        <h3 class="text-lg font-bold mb-6 text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">location_on</span>
                            {{ __('Address') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2 flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700" for="address">{{ __('Street Address') }}</label>
                                <input id="address" name="address" type="text" value="{{ old('address', $user->address) }}"
                                    class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-900">
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700" for="city">{{ __('City') }}</label>
                                <input id="city" name="city" type="text" value="{{ old('city', $user->city) }}"
                                    class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-900">
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700" for="state">{{ __('State / Province') }}</label>
                                <input id="state" name="state" type="text" value="{{ old('state', $user->state) }}"
                                    class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-900">
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700" for="postal_code">{{ __('Postal Code') }}</label>
                                <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $user->postal_code) }}"
                                    class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-900">
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700" for="country">{{ __('Country') }}</label>
                                <input id="country" name="country" type="text" value="{{ old('country', $user->country) }}"
                                    class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-900">
                            </div>
                        </div>
                    </section>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-4">
                        <a href="{{ route('customer.profile.index') }}" class="px-6 py-3 rounded-lg border border-primary text-primary font-bold hover:bg-primary/5 transition-colors">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="px-10 py-3 rounded-lg bg-primary text-white font-bold shadow-lg shadow-primary/30 hover:bg-primary/90 transition-all active:scale-95">
                            {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>

                {{-- Change Password Section (theo code.html) --}}
                <section class="bg-white border border-primary/10 rounded-xl p-8 shadow-sm">
                    <h3 class="text-lg font-bold mb-6 text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">lock_reset</span>
                        {{ __('Change Password') }}
                    </h3>
                    <form action="{{ route('customer.profile.password') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700" for="current_password">{{ __('Current Password') }}</label>
                                <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                                    class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-900" placeholder="••••••••">
                                @error('current_password')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex flex-col gap-2"></div>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700" for="password">{{ __('New Password') }}</label>
                                <input id="password" name="password" type="password" required autocomplete="new-password"
                                    class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-900" placeholder="••••••••">
                                @error('password')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700" for="password_confirmation">{{ __('Confirm New Password') }}</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                                    class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-slate-900" placeholder="••••••••">
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 italic">{{ __('Password must be at least 8 characters long and include a symbol.') }}</p>
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90 transition-colors">
                                {{ __('Update Password') }}
                            </button>
                        </div>
                    </form>
                </section>

                {{-- Delete Account --}}
                <section class="bg-white border border-red-200 rounded-xl p-8 shadow-sm">
                    <h3 class="text-lg font-bold mb-2 text-slate-900">{{ __('Delete Account') }}</h3>
                    <p class="text-sm text-slate-600 mb-4">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}</p>
                    <button type="button" id="delete-account-btn" class="px-6 py-3 rounded-lg border border-red-500 text-red-500 font-bold hover:bg-red-50 transition-colors">
                        {{ __('Delete Account') }}
                    </button>

                    <div id="delete-modal" class="fixed inset-0 bg-black/50 z-50 p-4 hidden" aria-hidden="true" role="dialog" aria-modal="true">
                        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200">
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-slate-900 mb-4">{{ __('Are you sure you want to delete your account?') }}</h3>
                                <p class="text-slate-600 mb-6">{{ __('This action cannot be undone. Please enter your password to confirm.') }}</p>
                                <form action="{{ route('customer.profile.destroy') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <div class="mb-6">
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">{{ __('Password') }}</label>
                                        <input type="password" name="password" required class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-primary/5 text-slate-900">
                                        @error('password')
                                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex justify-end gap-3">
                                        <button type="button" id="delete-modal-cancel" class="px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50">
                                            {{ __('Cancel') }}
                                        </button>
                                        <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                                            {{ __('Delete Account') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<form id="send-verification" method="post" action="{{ route('verification.send') }}" class="hidden">
    @csrf
</form>

<script>
document.getElementById('avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const preview = document.getElementById('avatar-preview');
            preview.outerHTML = '<img id="avatar-preview" src="' + ev.target.result + '" alt="Preview" class="h-full w-full object-cover">';
        };
        reader.readAsDataURL(file);
    }
});

document.querySelector('form#profile-form').addEventListener('submit', function() {
    var btn = this.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = '{{ __("Saving...") }}'; }
});

var deleteModal = document.getElementById('delete-modal');
document.getElementById('delete-account-btn').addEventListener('click', function() {
    deleteModal.classList.remove('hidden');
    deleteModal.classList.add('flex', 'items-center', 'justify-center');
    deleteModal.setAttribute('aria-hidden', 'false');
});
document.getElementById('delete-modal-cancel').addEventListener('click', function() {
    deleteModal.classList.add('hidden');
    deleteModal.classList.remove('flex', 'items-center', 'justify-center');
    deleteModal.setAttribute('aria-hidden', 'true');
});
</script>
@endsection
