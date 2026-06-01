@php
    $setup = $setup ?? null;
@endphp
@if ($setup && ! $setup->allComplete())
    <p class="mt-3 text-sm text-[#404753]">
        <a href="{{ route('creator.setup.index') }}" class="font-semibold text-primary underline hover:text-[#0060a7]">Complete your account setup</a><span class="text-[#707884]"> — payouts require profile, social links, and payout details on file. Payment is sent {{ \App\Support\AffiliateSettings::payoutDelayDaysAfterDelivery() }} days after successful delivery.</span>
    </p>
@endif
