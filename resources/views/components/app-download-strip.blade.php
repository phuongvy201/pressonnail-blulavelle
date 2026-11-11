@props([
    'campaign' => 'website_hero',
    'qrCampaign' => 'website_hero_qr',
    'headline' => 'BluLavelle in your pocket',
    'body' => 'Try nails on virtually, track orders, and get new drops first.',
])

@php
    $iosUrl = \App\Support\MobileAppLinks::iosUrl($campaign);
    $qrUrl = \App\Support\MobileAppLinks::qrImageUrl($qrCampaign, 148);
@endphp

@if($iosUrl !== '')
<section {{ $attributes->class(['w-full bg-gradient-to-r from-slate-50 via-white to-sky-50 border-y border-slate-200/80']) }}>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 py-8 sm:py-10">
        <div class="flex flex-col md:flex-row items-center gap-6 md:gap-10">
            <div class="relative shrink-0 w-28 h-44 sm:w-32 sm:h-52 rounded-[1.6rem] border-[3px] border-slate-900 bg-slate-900 shadow-xl shadow-slate-900/20 overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-5 bg-slate-900 z-10 flex justify-center pt-1.5">
                    <span class="w-12 h-1.5 rounded-full bg-slate-700"></span>
                </div>
                <div class="absolute inset-[3px] top-5 rounded-[1.2rem] bg-gradient-to-b from-[#0195FE] to-[#015a9e] flex flex-col items-center justify-center px-3 text-center text-white">
                    <svg class="w-8 h-8 mb-2 opacity-95" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <p class="text-[10px] font-bold leading-tight">Virtual Try-On</p>
                    <p class="text-[9px] text-white/80 mt-1 leading-snug">Order tracking &amp; drops</p>
                </div>
            </div>

            <div class="flex-1 text-center md:text-left min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-[#0195FE] mb-2">Mobile app</p>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $headline }}</h2>
                <p class="mt-2 text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl mx-auto md:mx-0">{{ $body }}</p>

                {{-- Phone / tablet: official badge --}}
                <div class="mt-4 flex justify-center md:justify-start lg:hidden">
                    <x-app-store-badge :campaign="$campaign" :height="44" />
                </div>
            </div>

            {{-- Desktop: QR to App Store --}}
            <div class="hidden lg:flex flex-col items-center gap-2 shrink-0 rounded-2xl bg-white border border-slate-200 shadow-sm px-4 py-3">
                @if($qrUrl !== '')
                    <img src="{{ $qrUrl }}" alt="QR code to download BluLavelle on the App Store" width="148" height="148" class="rounded-lg" loading="lazy" decoding="async">
                @endif
                <p class="text-[11px] font-semibold text-slate-500 text-center leading-snug">Scan with iPhone<br>to download</p>
                <x-app-store-badge :campaign="$qrCampaign" :height="36" />
            </div>
        </div>
    </div>
</section>
@endif
