@extends('layouts.creator')

@section('title', 'Apply — Verify email')

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-8 md:px-16 md:py-10">
        <div class="mx-auto max-w-2xl">
            @include('creator.affiliate.partials.onboarding-steps', ['current' => 3])

            <h1 class="creator-font-headline text-2xl font-bold text-[#0b1c30] md:text-3xl">
                @if ($emailVerified)
                    Submit your application
                @else
                    Verify your email
                @endif
            </h1>
            <p class="mt-2 text-sm text-[#404753]">
                @if ($emailVerified)
                    Your email is confirmed. Review your details and submit for review.
                @else
                    We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Click the link in your inbox, then return here to submit.
                @endif
            </p>

            @if (session('success'))
                <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @if (session('status') === 'verification-link-sent')
                <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    A new verification link has been sent to your email.
                </div>
            @endif

            <div class="mt-6 rounded-xl border border-[#bfc7d5] bg-[#eff4ff] p-5 text-sm text-[#404753]">
                <p class="font-semibold text-[#0b1c30]">{{ $draft['full_name'] ?? 'Creator' }}</p>
                <p class="mt-1">Ref code: <span class="font-mono text-primary">{{ $draft['proposed_ref_code'] ?? '—' }}</span></p>
                <p class="mt-1">{{ $draft['content_niche'] ?? '' }}</p>
                <a href="{{ route('creator.affiliate.apply') }}" class="mt-3 inline-block text-sm font-semibold text-primary underline">Edit application</a>
            </div>

            @if (! $emailVerified)
                <div class="mt-8 space-y-4 rounded-2xl border border-[#bfc7d5] bg-white p-6 shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-3xl text-primary">mark_email_unread</span>
                        <div class="text-sm text-[#404753]">
                            <p class="font-semibold text-[#0b1c30]">Check your inbox</p>
                            <p class="mt-1">The link may take a few minutes. Check spam or promotions if you do not see it.</p>
                        </div>
                    </div>
                    <form method="post" action="{{ route('creator.affiliate.apply.verify-email.resend') }}">
                        @csrf
                        <button type="submit" class="btn-primary creator-font-label w-full rounded-xl py-3 text-sm font-semibold tracking-wide">
                            Resend verification email
                        </button>
                    </form>
                    <p class="text-center text-xs text-[#707884]">
                        Wrong email?
                        <form method="post" action="{{ route('creator.affiliate.apply.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="font-semibold text-primary underline">Use a different account</button>
                        </form>
                    </p>
                </div>
            @else
                <div class="mt-8 space-y-4 rounded-2xl border border-[#bfc7d5] bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-2 text-sm text-green-800">
                        <span class="material-symbols-outlined text-xl">verified</span>
                        <span>Email verified</span>
                    </div>
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
            @endif
        </div>
    </div>
@endsection
