@extends('layouts.auth')

@section('title', 'Forgot Password')

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
    <h1 class="text-2xl font-bold text-slate-900">Forgot password</h1>
    <p class="text-slate-500 mt-2">Enter your email and we'll send you a reset link</p>
</div>

@if (session('status'))
    <div class="mx-8 mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="px-8 pb-10 space-y-5">
    @csrf

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com"
               class="w-full h-12 px-4 rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary focus:border-primary text-slate-900 placeholder:text-slate-400"
               required autofocus autocomplete="username">
        @error('email')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" class="w-full h-12 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg shadow-md shadow-primary/20 transition-all flex items-center justify-center">
        Email password reset link
    </button>

    <p class="text-center text-sm text-slate-600">
        <a href="{{ route('login') }}" class="font-semibold text-primary hover:text-primary/80">Back to login</a>
    </p>
</form>
@endsection
