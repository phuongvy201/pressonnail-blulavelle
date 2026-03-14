@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<div class="px-8 pt-10 pb-6 flex flex-col items-center text-center">
    <div class="flex items-center gap-2 mb-6 text-primary">
        <div class="size-8 shrink-0">
            <svg class="w-full h-full" fill="currentColor" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <path clip-rule="evenodd" fill-rule="evenodd" d="M24 4H6V17.3333V30.6667H24V44H42V30.6667V17.3333H24V4Z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold tracking-tight">{{ config('app.name') }}</h2>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Create an account</h1>
    <p class="text-slate-500 mt-2">Join us to get started</p>
</div>

@if (session('status'))
    <div class="mx-8 mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">
        {{ session('status') }}
    </div>
@endif

{{-- Social --}}
<div class="px-8 flex flex-col gap-3">
    <a href="{{ route('google.login') }}" class="w-full flex items-center justify-center gap-3 px-4 py-3 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        <span class="text-sm font-semibold">Continue with Google</span>
    </a>
    <a href="{{ route('facebook.login') }}" class="w-full flex items-center justify-center gap-3 px-4 py-3 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
        <svg class="w-5 h-5 text-[#1877F2] shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        <span class="text-sm font-semibold">Continue with Facebook</span>
    </a>
</div>

<div class="px-8 py-6 flex items-center gap-4">
    <div class="h-px grow bg-slate-200"></div>
    <span class="text-xs text-slate-400 uppercase tracking-widest font-medium">or</span>
    <div class="h-px grow bg-slate-200"></div>
</div>

<form id="register-form" method="POST" action="{{ route('register') }}" class="px-8 pb-10 space-y-5">
    @csrf

    <div>
        <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Full name</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Enter your full name"
               class="w-full h-12 px-4 rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary focus:border-primary text-slate-900 placeholder:text-slate-400"
               required autofocus autocomplete="name">
        @error('name')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com"
               class="w-full h-12 px-4 rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary focus:border-primary text-slate-900 placeholder:text-slate-400"
               required autocomplete="username">
        @error('email')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
        <div class="relative">
            <input id="password" type="password" name="password" placeholder="••••••••"
                   class="w-full h-12 px-4 pr-12 rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary focus:border-primary text-slate-900 placeholder:text-slate-400"
                   required autocomplete="new-password">
            <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" aria-label="Toggle password">
                <svg id="eye-icon-password" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </button>
        </div>
        @error('password')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm password</label>
        <div class="relative">
            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="••••••••"
                   class="w-full h-12 px-4 pr-12 rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary focus:border-primary text-slate-900 placeholder:text-slate-400"
                   required autocomplete="new-password">
            <button type="button" onclick="togglePassword('password_confirmation')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" aria-label="Toggle password">
                <svg id="eye-icon-password_confirmation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </button>
        </div>
        @error('password_confirmation')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @error('captcha')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror

    @if(config('services.recaptcha.site_key'))
        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
    @endif

    <button type="submit" class="w-full h-12 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg shadow-md shadow-primary/20 transition-all flex items-center justify-center">
        Create account
    </button>

    <p class="text-center text-sm text-slate-600 mt-6">
        Already have an account?
        <a href="{{ route('login') }}" class="font-bold text-primary hover:text-primary/80">Sign in</a>
    </p>
</form>

@push('scripts')
<script>
function togglePassword(fieldId) {
    var el = document.getElementById(fieldId);
    var icon = document.getElementById('eye-icon-' + fieldId);
    if (!el || !icon) return;
    if (el.type === 'password') {
        el.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>';
    } else {
        el.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
    }
}
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('register-form');
    if (!form || window.tiktokRegistrationTracked) return;
    form.addEventListener('submit', function () {
        window.tiktokRegistrationTracked = true;
        if (typeof window.ttq !== 'undefined') {
            window.ttq.track('CompleteRegistration', { contents: [{ content_id: 'account_registration', content_type: 'user', content_name: 'Account Registration' }], value: 0, currency: 'USD' });
        }
    });
});
</script>
@endpush

@if(config('services.recaptcha.site_key'))
    @push('scripts')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endpush
@endif
@endsection
