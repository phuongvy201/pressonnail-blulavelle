@php
    $setup = $setup ?? null;
@endphp
@if ($setup && ! $setup->allComplete())
    <div class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50/90 to-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-amber-900">Complete your account setup</h2>
                <p class="mt-2 text-sm text-[#404753]">
                    You can use referral links and earn commissions right away. Payouts require completed payout information below.
                </p>
                <div class="mt-3">
                    @include('creator.partials.payout-timing-note')
                </div>
            </div>
            <a href="{{ route('creator.setup.index') }}"
               class="creator-btn-primary creator-font-label shrink-0 rounded-lg px-4 py-2 text-sm font-semibold tracking-wide">
                Finish setup
            </a>
        </div>
        <ul class="mt-5 space-y-3">
            @foreach ($setup->checklistItems() as $item)
                <li class="flex items-center gap-3 rounded-lg border border-[#bfc7d5]/80 bg-white/80 px-4 py-3">
                    @if ($item['done'])
                        <span class="material-symbols-outlined text-[22px] text-emerald-600" aria-hidden="true">check_circle</span>
                    @else
                        <span class="material-symbols-outlined text-[22px] text-amber-600" aria-hidden="true">radio_button_unchecked</span>
                    @endif
                    <span class="flex-1 text-sm font-medium {{ $item['done'] ? 'text-[#707884]' : 'text-[#0b1c30]' }}">
                        {{ $item['label'] }}
                    </span>
                    @if (! $item['done'])
                        <a href="{{ route($item['route']) }}#{{ $item['anchor'] }}"
                           class="creator-font-label text-sm font-semibold text-primary hover:underline">
                            Complete
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
        @if (! $setup->payoutComplete)
            <p class="mt-4 text-xs text-[#707884]">
                <span class="material-symbols-outlined align-middle text-[16px]">info</span>
                Commissions stay <strong>pending</strong> until payout details are on file. Payment is sent after the delivery hold period in our policy.
            </p>
        @endif
    </div>
@endif
