@extends('layouts.auth')

@section('title', 'Verify Email')

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
    <div class="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Verify your email</h1>
    <p class="text-slate-500 mt-2">Check your inbox for the verification link</p>
</div>

<div class="mx-8 mb-6 p-4 bg-slate-50 border border-slate-200 rounded-lg">
    <p class="text-sm text-slate-700">Thanks for signing up! Please verify your email address by clicking the link we sent you. Didn't receive it? We can send another.</p>
</div>

@if (session('status') == 'verification-link-sent')
    <div class="mx-8 mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2">
        <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <p class="text-sm text-green-700">A new verification link has been sent to your email.</p>
    </div>
@endif

<div class="px-8 pb-10 space-y-4">
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="w-full h-12 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg shadow-md shadow-primary/20 transition-all flex items-center justify-center">
            Resend verification email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full h-12 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg transition-all flex items-center justify-center">
            Log out
        </button>
    </form>
</div>

<p class="text-center text-sm text-slate-500 px-8 pb-8">Check your spam folder or try resending.</p>
@endsection
