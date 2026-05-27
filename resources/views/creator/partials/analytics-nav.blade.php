@php
    $period = $period ?? request('period', '30d');
    $tabs = [
        ['route' => 'creator.analytics.overview', 'label' => 'Overview'],
        ['route' => 'creator.analytics.links', 'label' => 'Link clicks'],
        ['route' => 'creator.analytics.traffic', 'label' => 'Traffic sources'],
        ['route' => 'creator.analytics.products', 'label' => 'Products sold'],
        ['route' => 'creator.analytics.commissions', 'label' => 'Commissions'],
    ];
@endphp
<nav class="mt-6 flex flex-wrap gap-2 border-b border-[#bfc7d5]/60 pb-1" aria-label="Analytics sections">
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route'], ['period' => $period]) }}"
           class="rounded-t-lg px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs($tab['route']) ? 'bg-white text-primary border border-[#bfc7d5] border-b-white -mb-px' : 'text-[#707884] hover:text-primary hover:bg-white/60' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
