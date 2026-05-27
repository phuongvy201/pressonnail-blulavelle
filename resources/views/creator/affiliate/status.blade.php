@extends('layouts.creator')

@section('title', 'Application status')

@section('content')
    <div class="mx-auto max-w-xl px-5 py-12 md:px-16">
        <h1 class="creator-font-headline text-2xl font-bold text-[#0b1c30]">Creator application</h1>

        @if (session('success'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                {{ session('error') }}
            </div>
        @endif

        @if ($application?->isPending())
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-amber-900">Pending review</p>
                        <p class="mt-2 text-sm text-amber-800">
                            We received your application on {{ $application->created_at->format('M d, Y') }}.
                            You will be emailed when a decision is made.
                        </p>
                        <dl class="mt-4 space-y-2 text-sm text-amber-900">
                            <div class="flex flex-wrap gap-x-2">
                                <dt class="font-semibold">Ref code requested</dt>
                                <dd class="font-mono">{{ $application->proposed_ref_code }}</dd>
                            </div>
                            @if ($application->primary_platform)
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-semibold">Platform</dt>
                                    <dd>{{ ucfirst(str_replace('_', ' ', $application->primary_platform)) }}</dd>
                                </div>
                            @endif
                            @if ($application->full_name)
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-semibold">Name</dt>
                                    <dd>{{ $application->full_name }}</dd>
                                </div>
                            @endif
                        </dl>
                        <p class="mt-4 text-xs text-amber-800">
                            While your application is pending, creator dashboard features stay locked. Check back here anytime for updates.
                        </p>
                    </div>
                </div>
            </div>
        @elseif (! $application)
            <p class="mt-4 text-[#404753]">You have not submitted an application yet.</p>
            <a href="{{ route('creator.affiliate.apply') }}" class="btn-primary mt-6 inline-block rounded-xl px-6 py-3 text-sm font-semibold">Start application</a>
        @elseif ($application->status === \App\Models\AffiliateApplication::STATUS_REJECTED)
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-6">
                <p class="font-semibold text-red-900">Not approved</p>
                @if ($application->admin_note)
                    <p class="mt-2 text-sm text-red-800">{{ $application->admin_note }}</p>
                @endif
                <a href="{{ route('creator.affiliate.apply') }}" class="mt-4 inline-block text-sm font-semibold text-primary underline">Submit a new application</a>
            </div>
        @else
            @if (! empty($affiliateMissing))
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
                    <p class="font-semibold">Approved, but dashboard is not linked yet</p>
                    <p class="mt-2">Your application is approved. We could not match an active affiliate profile to this account.</p>
                    <p class="mt-2">Ask admin to set <strong>User ID</strong> on the affiliate row to your account (#{{ auth()->id() }}) and check <strong>Active</strong>, or use
                        <strong>Approve & create affiliate</strong> on the application (not only manual create).</p>
                </div>
            @else
                <p class="mt-4 text-[#404753]">Your application was approved.</p>
                <a href="{{ route('creator.dashboard') }}" class="btn-primary mt-6 inline-block rounded-xl px-6 py-3 text-sm font-semibold">Open creator dashboard</a>
            @endif
        @endif
    </div>
@endsection
