{{-- Compact contextual CTA (Try-On / order success). --}}
@props([
    'campaign' => 'website',
    'title' => 'Get the BluLavelle app',
    'text' => 'Better on your phone.',
])

@php
    $iosUrl = \App\Support\MobileAppLinks::iosUrl($campaign);
@endphp

@if($iosUrl !== '')
<div {{ $attributes->class(['rounded-2xl border border-sky-100 bg-gradient-to-r from-sky-50 to-white p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-4']) }}>
    <div class="flex-1 min-w-0">
        <p class="text-sm sm:text-base font-bold text-slate-900">{{ $title }}</p>
        <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">{{ $text }}</p>
    </div>
    <div class="shrink-0">
        <x-app-store-badge :campaign="$campaign" :height="40" />
    </div>
</div>
@endif
