@props(['route', 'period' => '30d', 'label' => 'View details →'])

<a href="{{ route($route, ['period' => $period]) }}" class="creator-font-label shrink-0 text-xs font-semibold text-primary hover:underline sm:text-sm">
    {{ $label }}
</a>
