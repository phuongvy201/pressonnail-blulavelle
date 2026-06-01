@extends('layouts.creator')

@section('title', 'Account setup')

@section('content')
    <div class="mx-auto max-w-3xl px-5 py-12 md:px-16" x-data="{ payoutMethod: @js($defaultPayoutMethod) }">
        <h1 class="creator-font-headline text-3xl font-bold text-[#0b1c30]">Account setup</h1>
        <p class="mt-2 text-[#404753]">Finish these steps so we can pay you commissions. Your referral links work anytime.</p>
        <div class="mt-4 rounded-xl border border-[#bfc7d5]/80 bg-[#eff4ff]/50 px-4 py-3">
            @include('creator.partials.payout-timing-note', ['class' => 'text-sm text-[#404753]'])
        </div>

        @include('creator.partials.setup-checklist', ['setup' => $setup])

        @if ($setup->allComplete())
            <div class="mt-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/80 px-5 py-4 text-sm text-emerald-900">
                <span class="material-symbols-outlined text-[24px]">verified</span>
                <span>Your account setup is complete. You can update any section below at any time.</span>
            </div>
        @endif

        {{-- Profile --}}
        <section id="profile" class="mt-8 scroll-mt-28 rounded-xl border border-[#bfc7d5] bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Profile</h2>
                @if ($setup->profileComplete)
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span> Complete
                    </span>
                @endif
            </div>
            <form method="post" action="{{ route('creator.setup.profile') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="display_name" class="block text-sm font-medium text-slate-700">Display name <span class="text-red-500">*</span></label>
                    <input id="display_name" name="display_name" type="text" required
                           value="{{ old('display_name', $affiliate->display_name) }}"
                           class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                    @error('display_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700">Phone <span class="text-red-500">*</span></label>
                    <input id="phone" name="phone" type="text" required
                           value="{{ old('phone', $affiliate->phone) }}"
                           class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                    @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="primary_platform" class="block text-sm font-medium text-slate-700">Primary platform <span class="text-red-500">*</span></label>
                        <select id="primary_platform" name="primary_platform" required
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                            <option value="">Select…</option>
                            @foreach ($platforms as $value => $label)
                                <option value="{{ $value }}" @selected(old('primary_platform', $affiliate->primary_platform) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('primary_platform')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="follower_range" class="block text-sm font-medium text-slate-700">Followers (range)</label>
                        <select id="follower_range" name="follower_range"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                            <option value="">Select…</option>
                            @foreach ($followerRanges as $value => $label)
                                <option value="{{ $value }}" @selected(old('follower_range', $affiliate->follower_range) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('follower_range')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="content_niche" class="block text-sm font-medium text-slate-700">Content niche <span class="text-red-500">*</span></label>
                    <input id="content_niche" name="content_niche" type="text" required
                           value="{{ old('content_niche', $affiliate->content_niche) }}"
                           class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                    @error('content_niche')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="creator-btn-primary creator-font-label rounded-lg px-5 py-2.5 text-sm font-semibold tracking-wide">
                    Save profile
                </button>
            </form>
        </section>

        {{-- Social --}}
        <section id="social" class="mt-6 scroll-mt-28 rounded-xl border border-[#bfc7d5] bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Social links</h2>
                @if ($setup->socialComplete)
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span> Complete
                    </span>
                @endif
            </div>
            <form method="post" action="{{ route('creator.setup.social') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="social_links" class="block text-sm font-medium text-slate-700">Social profile URLs <span class="text-red-500">*</span></label>
                    <textarea id="social_links" name="social_links" rows="4" required
                              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary"
                              placeholder="One URL per line">{{ old('social_links', $affiliate->social_links) }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">TikTok, Instagram, YouTube, etc. — at least one valid URL.</p>
                    @error('social_links')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="portfolio_links" class="block text-sm font-medium text-slate-700">Portfolio / sample content</label>
                    <textarea id="portfolio_links" name="portfolio_links" rows="3"
                              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary"
                              placeholder="Optional — reels, drive folders">{{ old('portfolio_links', $affiliate->portfolio_links) }}</textarea>
                    @error('portfolio_links')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="creator-btn-primary creator-font-label rounded-lg px-5 py-2.5 text-sm font-semibold tracking-wide">
                    Save social links
                </button>
            </form>
        </section>

        {{-- Payout --}}
        <section id="payout" class="mt-6 scroll-mt-28 rounded-xl border border-rose-200 bg-rose-50/30 p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-rose-900">Payout information</h2>
                @if ($setup->payoutComplete)
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span> Complete
                    </span>
                @else
                    <span class="text-xs font-semibold text-rose-800">Required for payouts</span>
                @endif
            </div>
            <p class="mt-2 text-sm text-[#404753]">
                We store payout details securely (encrypted) so we can send commissions. PayPal or US bank transfer (ACH) only.
            </p>
            <div class="mt-3">
                @include('creator.partials.payout-timing-note')
            </div>
            <form method="post" action="{{ route('creator.setup.payout') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="payout_method" class="block text-sm font-medium text-slate-700">Payout method <span class="text-red-500">*</span></label>
                    <select id="payout_method" name="payout_method" required x-model="payoutMethod"
                            class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                        @foreach ($payoutMethods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payout_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="payout_legal_name" class="block text-sm font-medium text-slate-700">Legal name (payee) <span class="text-red-500">*</span></label>
                    <input id="payout_legal_name" name="payout_legal_name" type="text" required
                           value="{{ old('payout_legal_name', $affiliate->payout_legal_name) }}"
                           class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                    @error('payout_legal_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div x-show="payoutMethod === 'paypal'" x-cloak>
                    <label for="payout_paypal_email" class="block text-sm font-medium text-slate-700">PayPal email <span class="text-red-500">*</span></label>
                    <input id="payout_paypal_email" name="payout_paypal_email" type="email"
                           value="{{ old('payout_paypal_email', $affiliate->payout_paypal_email) }}"
                           class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                    @error('payout_paypal_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div x-show="payoutMethod === 'bank_transfer'" x-cloak class="space-y-4">
                    <div>
                        <label for="payout_bank_name" class="block text-sm font-medium text-slate-700">Bank name <span class="text-red-500">*</span></label>
                        <input id="payout_bank_name" name="payout_bank_name" type="text"
                               value="{{ old('payout_bank_name', $affiliate->payout_bank_name) }}"
                               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                        @error('payout_bank_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="payout_account_holder" class="block text-sm font-medium text-slate-700">Account holder name <span class="text-red-500">*</span></label>
                        <input id="payout_account_holder" name="payout_account_holder" type="text"
                               value="{{ old('payout_account_holder', $affiliate->payout_account_holder) }}"
                               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                        @error('payout_account_holder')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="payout_routing_number" class="block text-sm font-medium text-slate-700">Routing number (9 digits) <span class="text-red-500">*</span></label>
                        <input id="payout_routing_number" name="payout_routing_number" type="text" inputmode="numeric" autocomplete="off"
                               maxlength="9" pattern="\d{9}" placeholder="123456789"
                               value="{{ old('payout_routing_number') }}"
                               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 font-mono focus:border-primary focus:ring-1 focus:ring-primary">
                        @if ($affiliate->payout_method === 'bank_transfer' && filled($affiliate->payout_routing_last4))
                            <p class="mt-1 text-xs text-slate-500">On file: routing ending {{ $affiliate->payout_routing_last4 }}. Enter full number to update.</p>
                        @endif
                        @error('payout_routing_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="payout_account_number" class="block text-sm font-medium text-slate-700">Account number <span class="text-red-500">*</span></label>
                        <input id="payout_account_number" name="payout_account_number" type="text" inputmode="numeric" autocomplete="off"
                               maxlength="17" pattern="\d{4,17}" placeholder="Checking or savings account"
                               value="{{ old('payout_account_number') }}"
                               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 font-mono focus:border-primary focus:ring-1 focus:ring-primary">
                        @if ($affiliate->payout_method === 'bank_transfer' && filled($affiliate->payout_account_last4))
                            <p class="mt-1 text-xs text-slate-500">On file: account ending {{ $affiliate->payout_account_last4 }}. Enter full number to update.</p>
                        @endif
                        @error('payout_account_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <p class="text-xs text-slate-500">US ACH only. Numbers are stored encrypted and never shown again in the portal.</p>
                </div>
                <p class="text-xs text-[#707884]">
                    See our
                    <a href="{{ route('creator.policies.show', 'affiliate-commission-payout-policy') }}" target="_blank" rel="noopener" class="font-semibold text-primary underline">Commission &amp; Payout Policy</a>.
                </p>
                <button type="submit" class="creator-btn-primary creator-font-label rounded-lg px-5 py-2.5 text-sm font-semibold tracking-wide">
                    Save payout information
                </button>
            </form>
        </section>

        <p class="mt-8 text-center text-sm text-[#404753]">
            <a href="{{ route('creator.dashboard') }}" class="font-semibold text-primary underline">Back to dashboard</a>
        </p>
    </div>
@endsection
