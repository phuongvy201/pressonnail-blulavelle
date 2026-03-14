@extends('layouts.auth')

@section('title', 'Reset Password')

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
    <h1 class="text-2xl font-bold text-slate-900">Reset password</h1>
    <p class="text-slate-500 mt-2">Enter your new password below</p>
</div>

<form method="POST" action="{{ route('password.store') }}" class="px-8 pb-10 space-y-5">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" placeholder="name@example.com"
               class="w-full h-12 px-4 rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary focus:border-primary text-slate-900 placeholder:text-slate-400"
               required autofocus autocomplete="username">
        @error('email')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">New password</label>
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
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm new password</label>
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

    <button type="submit" class="w-full h-12 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg shadow-md shadow-primary/20 transition-all flex items-center justify-center">
        Reset password
    </button>

    <p class="text-center text-sm text-slate-600 mt-6">
        Remember your password?
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
</script>
@endpush
@endsection
