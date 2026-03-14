@extends('layouts.auth')

@section('title', 'Data Deletion Status')

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
    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Data deletion request received</h1>
    <p class="text-slate-500 mt-2">Your request to delete your Facebook data has been processed successfully.</p>
</div>

<div class="mx-8 mb-6 p-4 bg-slate-50 border border-slate-200 rounded-lg">
    <p class="text-sm font-medium text-slate-700 mb-2">Confirmation code</p>
    <p class="text-sm font-mono text-slate-900 bg-white p-3 rounded-lg border border-slate-200">{{ $confirmation_code }}</p>
    <p class="text-xs text-slate-500 mt-2">Please save this code for your records.</p>
</div>

<div class="mx-8 mb-8 border-t border-slate-200 pt-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-3">What happens next?</h2>
    <ul class="text-sm text-slate-600 space-y-2 text-left">
        <li class="flex items-start gap-2">
            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            Your personal data associated with your Facebook account has been removed or anonymized.
        </li>
        <li class="flex items-start gap-2">
            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            The connection between your Facebook account and {{ config('app.name') }} has been removed.
        </li>
        <li class="flex items-start gap-2">
            <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            You can still use your account with email login if you have an email registered.
        </li>
    </ul>
</div>

<div class="px-8 pb-10 text-center">
    <a href="{{ route('home') }}" class="text-sm font-semibold text-primary hover:text-primary/80">Return to home →</a>
</div>
@endsection
