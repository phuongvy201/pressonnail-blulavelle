@php
    $payoutDelayDays = $payoutDelayDays ?? \App\Support\AffiliateSettings::payoutDelayDaysAfterDelivery();
@endphp
<p class="{{ $class ?? 'text-xs text-[#707884]' }}">
    <span class="material-symbols-outlined align-middle text-[16px]">schedule</span>
    Commissions are paid approximately <strong>{{ $payoutDelayDays }} days</strong> after each attributed order is
    <strong>successfully delivered</strong> (see
    <a href="{{ route('creator.policies.show', 'affiliate-commission-payout-policy') }}" target="_blank" rel="noopener" class="font-semibold text-primary underline">Commission &amp; Payout Policy</a>).
</p>
