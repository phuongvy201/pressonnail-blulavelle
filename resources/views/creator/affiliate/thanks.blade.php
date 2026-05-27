@extends('layouts.creator')

@section('title', 'Application received')

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-8 md:px-16 md:py-10">
    <div class="mx-auto max-w-lg text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <h1 class="mt-6 text-2xl font-bold text-slate-900">Application received</h1>
        <p class="mt-3 text-slate-600">
            Thank you. We will email you after your application is reviewed and your affiliate code is activated.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            @auth
                <a href="{{ route('creator.affiliate.status') }}" class="text-sm font-semibold text-primary hover:underline">View application status</a>
            @endauth
            <a href="{{ route('creator.home') }}" class="text-sm font-semibold text-primary hover:underline">Back to Creator home</a>
            <a href="{{ rtrim(config('creator.shop_url', config('app.url')), '/') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Go to shop</a>
        </div>
    </div>
    </div>
@endsection
