@extends('layouts.admin')

@section('title', 'Affiliate application')

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <a href="{{ route('admin.affiliate-applications.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Back to list</a>

    @if (session('success'))
        <div class="mt-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Application: {{ $application->full_name }}</h1>
        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold
            @if($application->status === \App\Models\AffiliateApplication::STATUS_APPROVED) bg-green-100 text-green-800
            @elseif($application->status === \App\Models\AffiliateApplication::STATUS_REJECTED) bg-red-100 text-red-800
            @else bg-amber-100 text-amber-900 @endif">
            {{ ucfirst($application->status) }}
        </span>
    </div>
    <p class="text-gray-500 text-sm mt-1">Submitted {{ $application->created_at?->format('Y-m-d H:i') }}</p>

    <dl class="mt-8 bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
        <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
            <dt class="text-sm font-medium text-gray-500">Email</dt>
            <dd class="sm:col-span-2 text-sm text-gray-900">{{ $application->email }}</dd>
        </div>
        <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
            <dt class="text-sm font-medium text-gray-500">Phone</dt>
            <dd class="sm:col-span-2 text-sm text-gray-900">{{ $application->phone ?: '—' }}</dd>
        </div>
        @if($application->primary_platform)
        <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
            <dt class="text-sm font-medium text-gray-500">Primary platform</dt>
            <dd class="sm:col-span-2 text-sm text-gray-900">{{ ucfirst($application->primary_platform) }}</dd>
        </div>
        @endif
        @if($application->follower_range)
        <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
            <dt class="text-sm font-medium text-gray-500">Followers</dt>
            <dd class="sm:col-span-2 text-sm text-gray-900">
                {{ str_replace('_', ' ', $application->follower_range) }}
                @if($application->follower_count)
                    ({{ number_format($application->follower_count) }} reported)
                @endif
            </dd>
        </div>
        @endif
        @if($application->content_niche)
        <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
            <dt class="text-sm font-medium text-gray-500">Content niche</dt>
            <dd class="sm:col-span-2 text-sm text-gray-900">{{ $application->content_niche }}</dd>
        </div>
        @endif
        <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
            <dt class="text-sm font-medium text-gray-500">Proposed ref code</dt>
            <dd class="sm:col-span-2 text-sm font-mono text-gray-900">{{ $application->proposed_ref_code }}</dd>
        </div>
        @if($application->user)
            <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                <dt class="text-sm font-medium text-gray-500">Linked user</dt>
                <dd class="sm:col-span-2 text-sm text-gray-900">#{{ $application->user_id }} {{ $application->user->name }} ({{ $application->user->email }})</dd>
            </div>
        @else
            <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                <dt class="text-sm font-medium text-gray-500">Linked user</dt>
                <dd class="sm:col-span-2 text-sm text-amber-700">No user linked — applicant must complete Step 2 before approval.</dd>
            </div>
        @endif
        @if($application->social_links)
            <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                <dt class="text-sm font-medium text-gray-500">Social links</dt>
                <dd class="sm:col-span-2 text-sm text-gray-900 whitespace-pre-wrap">{{ $application->social_links }}</dd>
            </div>
        @endif
        @if($application->portfolio_links)
            <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                <dt class="text-sm font-medium text-gray-500">Portfolio / videos</dt>
                <dd class="sm:col-span-2 text-sm text-gray-900 whitespace-pre-wrap">{{ $application->portfolio_links }}</dd>
            </div>
        @endif
        @if($application->message)
            <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                <dt class="text-sm font-medium text-gray-500">Audience / bio</dt>
                <dd class="sm:col-span-2 text-sm text-gray-900 whitespace-pre-wrap">{{ $application->message }}</dd>
            </div>
        @endif
        @if($application->admin_note)
            <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                <dt class="text-sm font-medium text-gray-500">Admin note</dt>
                <dd class="sm:col-span-2 text-sm text-gray-900 whitespace-pre-wrap">{{ $application->admin_note }}</dd>
            </div>
        @endif
    </dl>

    @if($application->status === \App\Models\AffiliateApplication::STATUS_PENDING)
        <div class="mt-8 flex flex-wrap gap-3">
            <form method="post" action="{{ route('admin.affiliate-applications.approve', $application) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700">
                    Approve & create affiliate
                </button>
            </form>
            <form method="post" action="{{ route('admin.affiliate-applications.reject', $application) }}" class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label for="admin_note" class="block text-xs font-medium text-gray-500">Rejection note (optional)</label>
                    <input id="admin_note" name="admin_note" type="text" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-lg border border-red-300 bg-white px-5 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">
                    Reject
                </button>
            </form>
        </div>
    @elseif($application->user?->affiliate)
        <p class="mt-8 text-sm text-gray-600">
            Affiliate profile:
            <a href="{{ route('admin.affiliates.edit', $application->user->affiliate) }}" class="font-semibold text-primary underline">{{ $application->user->affiliate->code }}</a>
        </p>
    @endif
</div>
@endsection
