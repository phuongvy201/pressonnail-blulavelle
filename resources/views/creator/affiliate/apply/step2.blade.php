@extends('layouts.creator')

@section('title', 'Apply — Your account')

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-8 md:px-16 md:py-10">
        <div class="mx-auto max-w-2xl">
            @include('creator.affiliate.partials.onboarding-steps', ['current' => 2])

            <h1 class="creator-font-headline text-2xl font-bold text-[#0b1c30] md:text-3xl">Link your store account</h1>
            <p class="mt-2 text-sm text-[#404753]">
                Your creator application is saved. Sign in or create a free customer account. New accounts must verify email (step 3) before submitting.
            </p>

            @if (session('success'))
                <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mt-6 rounded-xl border border-[#bfc7d5] bg-[#eff4ff] p-5 text-sm text-[#404753]">
                <p class="font-semibold text-[#0b1c30]">{{ $draft['full_name'] ?? 'Creator' }}</p>
                <p class="mt-1">Ref code: <span class="font-mono text-primary">{{ $draft['proposed_ref_code'] ?? '—' }}</span></p>
                <p class="mt-1">{{ $draft['content_niche'] ?? '' }}</p>
                <a href="{{ route('creator.affiliate.apply') }}" class="mt-3 inline-block text-sm font-semibold text-primary underline">Edit application</a>
            </div>

            @if ($isLoggedIn)
                <div class="mt-8 space-y-4 rounded-2xl border border-[#bfc7d5] bg-white p-6 shadow-sm">
                    <p class="text-sm text-[#404753]">
                        Signed in as <strong>{{ auth()->user()->email }}</strong>. Submit your application for review.
                    </p>
                    <form method="post" action="{{ route('creator.affiliate.apply.submit') }}">
                        @csrf
                        <button type="submit" class="btn-primary creator-font-label w-full rounded-xl py-3 text-sm font-semibold tracking-wide">
                            Submit application
                        </button>
                    </form>
                    <form method="post" action="{{ route('creator.affiliate.apply.logout') }}" class="text-center">
                        @csrf
                        <button type="submit" class="text-sm text-[#707884] underline hover:text-primary">Use a different account</button>
                    </form>
                </div>
            @else
                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <a href="{{ route('creator.affiliate.apply.register-account') }}"
                       class="flex flex-col items-center justify-center rounded-2xl border-2 border-primary bg-white p-8 text-center shadow-sm transition hover:bg-primary/5">
                        <span class="material-symbols-outlined mb-2 text-4xl text-primary">person_add</span>
                        <span class="font-semibold text-[#0b1c30]">Create account</span>
                        <span class="mt-1 text-xs text-[#404753]">New to {{ config('app.name') }}</span>
                    </a>
                    <a href="{{ route('creator.affiliate.apply.login') }}"
                       class="flex flex-col items-center justify-center rounded-2xl border border-[#bfc7d5] bg-white p-8 text-center shadow-sm transition hover:border-primary">
                        <span class="material-symbols-outlined mb-2 text-4xl text-primary">login</span>
                        <span class="font-semibold text-[#0b1c30]">Sign in</span>
                        <span class="mt-1 text-xs text-[#404753]">Already have an account</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
