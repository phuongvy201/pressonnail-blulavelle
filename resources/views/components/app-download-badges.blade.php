@props([
    'campaign' => 'website',
    'layout' => 'row', // row | stack
    'showAndroid' => null,
])

@php
    $iosUrl = \App\Support\MobileAppLinks::iosUrl($campaign);
    $androidUrl = \App\Support\MobileAppLinks::androidUrl($campaign);
    $showAndroid = $showAndroid === null
        ? \App\Support\MobileAppLinks::androidEnabled()
        : (bool) $showAndroid;
    $wrap = $layout === 'stack' ? 'flex flex-col gap-2.5' : 'flex flex-wrap items-center gap-2.5';
@endphp

<div {{ $attributes->class([$wrap]) }}>
    @if($iosUrl !== '')
        <x-app-store-badge :campaign="$campaign" :height="40" />
    @endif

    @if($showAndroid && $androidUrl !== '')
        <a href="{{ $androidUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl bg-[#111827] border border-white/10 text-white px-3 py-2 hover:bg-black transition-colors" aria-label="Get it on Google Play">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#EA4335" d="M3.6 2.2 13.5 12 3.6 21.8c-.4-.2-.6-.6-.6-1V3.2c0-.4.2-.8.6-1z"/><path fill="#FBBC04" d="m13.5 12 2.8-2.8 4.7 2.7c.6.3.6 1.2 0 1.5l-4.7 2.7L13.5 12z"/><path fill="#4285F4" d="M13.5 12 3.6 2.2c.3-.2.6-.2.9 0l12.4 7.1L13.5 12z"/><path fill="#34A853" d="M13.5 12 17 15.4 4.5 22.5c-.3.2-.6.1-.9 0L13.5 12z"/></svg>
            <span class="text-left leading-tight">
                <span class="block text-[9px] text-slate-300 uppercase tracking-wide">Get it on</span>
                <span class="block text-xs font-semibold text-white">Google Play</span>
            </span>
        </a>
    @elseif(! $showAndroid)
        <span class="inline-flex items-center gap-2 rounded-xl bg-slate-800/80 border border-white/10 text-white/80 px-3 py-2" title="Android coming soon" aria-label="Google Play coming soon">
            <svg class="w-5 h-5 shrink-0 opacity-70" viewBox="0 0 24 24" aria-hidden="true"><path fill="#EA4335" d="M3.6 2.2 13.5 12 3.6 21.8c-.4-.2-.6-.6-.6-1V3.2c0-.4.2-.8.6-1z"/><path fill="#FBBC04" d="m13.5 12 2.8-2.8 4.7 2.7c.6.3.6 1.2 0 1.5l-4.7 2.7L13.5 12z"/><path fill="#4285F4" d="M13.5 12 3.6 2.2c.3-.2.6-.2.9 0l12.4 7.1L13.5 12z"/><path fill="#34A853" d="M13.5 12 17 15.4 4.5 22.5c-.3.2-.6.1-.9 0L13.5 12z"/></svg>
            <span class="text-left leading-tight">
                <span class="block text-[9px] text-slate-400 uppercase tracking-wide">Coming soon</span>
                <span class="block text-xs font-semibold text-white">Google Play</span>
            </span>
        </span>
    @endif
</div>
