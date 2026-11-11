@props([
    'campaign' => 'website',
    'height' => 40,
])

@php
    $url = \App\Support\MobileAppLinks::iosUrl($campaign);
    $badge = asset('images/app-badges/app-store-black.svg');
    $h = max(32, (int) $height);
@endphp

@if($url !== '')
    <a
        href="{{ $url }}"
        target="_blank"
        rel="noopener noreferrer"
        {{ $attributes->class(['inline-block leading-none transition-opacity hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded']) }}
        aria-label="Download on the App Store"
    >
        <img
            src="{{ $badge }}"
            alt="Download on the App Store"
            height="{{ $h }}"
            style="height: {{ $h }}px; width: auto;"
            loading="lazy"
            decoding="async"
        >
    </a>
@endif
