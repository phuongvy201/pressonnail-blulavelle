@extends('layouts.creator')

@section('title', 'Apply — Creator profile')

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-8 md:px-16 md:py-10">
        <div class="mx-auto max-w-2xl">
            @include('creator.affiliate.partials.onboarding-steps', ['current' => 1])

            <h1 class="creator-font-headline text-2xl font-bold text-[#0b1c30] md:text-3xl">Creator application</h1>
            <p class="mt-2 text-sm text-[#404753]">
                Tell us about your content and audience. You will create or sign in to a store account in the next step — no purchase required.
            </p>

            <form method="post" action="{{ route('creator.affiliate.apply.store') }}" class="mt-8 space-y-5 rounded-2xl border border-[#bfc7d5] bg-white p-6 shadow-sm">
                @csrf

                <div>
                    <label for="full_name" class="block text-sm font-medium text-slate-700">Full name <span class="text-red-500">*</span></label>
                    <input id="full_name" name="full_name" type="text" required value="{{ old('full_name', $draft['full_name'] ?? '') }}"
                           class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                    @error('full_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="contact_email" class="block text-sm font-medium text-slate-700">Contact email <span class="text-red-500">*</span></label>
                    <input id="contact_email" name="contact_email" type="email" required value="{{ old('contact_email', $draft['contact_email'] ?? auth()->user()?->email) }}"
                           class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                    <p class="mt-1 text-xs text-slate-500">Used to reach you about your application. Step 2 will link your store login.</p>
                    @error('contact_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700">Phone</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone', $draft['phone'] ?? '') }}"
                           class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                    @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="primary_platform" class="block text-sm font-medium text-slate-700">Primary platform <span class="text-red-500">*</span></label>
                        <select id="primary_platform" name="primary_platform" required
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                            <option value="">Select…</option>
                            @foreach ($platforms as $value => $label)
                                <option value="{{ $value }}" @selected(old('primary_platform', $draft['primary_platform'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('primary_platform')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="follower_range" class="block text-sm font-medium text-slate-700">Followers (range) <span class="text-red-500">*</span></label>
                        <select id="follower_range" name="follower_range" required
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                            <option value="">Select…</option>
                            @foreach ($followerRanges as $value => $label)
                                <option value="{{ $value }}" @selected(old('follower_range', $draft['follower_range'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('follower_range')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="follower_count" class="block text-sm font-medium text-slate-700">Exact follower count (optional)</label>
                    <input id="follower_count" name="follower_count" type="number" min="0"
                           value="{{ old('follower_count', $draft['follower_count'] ?? '') }}"
                           class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary"
                           placeholder="e.g. 24500">
                    @error('follower_count')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="content_niche" class="block text-sm font-medium text-slate-700">Content niche <span class="text-red-500">*</span></label>
                    <input id="content_niche" name="content_niche" type="text" required
                           value="{{ old('content_niche', $draft['content_niche'] ?? '') }}"
                           placeholder="e.g. press-on nails, clean girl aesthetic"
                           class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                    @error('content_niche')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="social_links" class="block text-sm font-medium text-slate-700">Social profile links <span class="text-red-500">*</span></label>
                    <textarea id="social_links" name="social_links" rows="3" required
                              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary"
                              placeholder="One URL per line (TikTok, Instagram, …)">{{ old('social_links', $draft['social_links'] ?? '') }}</textarea>
                    @error('social_links')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="portfolio_links" class="block text-sm font-medium text-slate-700">Portfolio / sample videos</label>
                    <textarea id="portfolio_links" name="portfolio_links" rows="3"
                              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary"
                              placeholder="Links to reels, TikToks, or drive folders">{{ old('portfolio_links', $draft['portfolio_links'] ?? '') }}</textarea>
                    @error('portfolio_links')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-slate-700">About your audience</label>
                    <textarea id="message" name="message" rows="3"
                              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary"
                              placeholder="Demographics, engagement, why you want to partner">{{ old('message', $draft['message'] ?? '') }}</textarea>
                    @error('message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="proposed_ref_code" class="block text-sm font-medium text-slate-700">Desired ref code <span class="text-red-500">*</span></label>
                    <input id="proposed_ref_code" name="proposed_ref_code" type="text" required
                           value="{{ old('proposed_ref_code', $draft['proposed_ref_code'] ?? '') }}"
                           pattern="[a-zA-Z0-9]+(-[a-zA-Z0-9]+)*"
                           class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 font-mono focus:border-primary focus:ring-1 focus:ring-primary"
                           placeholder="e.g. anna-nails">
                    <p class="mt-1 text-xs text-slate-500">Letters, numbers, hyphens only — used as <span class="font-mono">?ref=</span> on the shop.</p>
                    @error('proposed_ref_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                @include('creator.affiliate.partials.terms-checkbox', ['draft' => $draft])

                <button type="submit" class="btn-primary creator-font-label w-full rounded-xl py-3 text-sm font-semibold tracking-wide">
                    Continue to account
                </button>
            </form>
        </div>
    </div>
@endsection
