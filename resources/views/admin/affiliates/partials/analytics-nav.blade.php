@php
    $period = $period ?? request('period', '30d');
    $tabs = [
        ['key' => 'overview', 'label' => 'Overview'],
        ['key' => 'links', 'label' => 'Link clicks'],
        ['key' => 'traffic', 'label' => 'Traffic sources'],
        ['key' => 'products', 'label' => 'Products sold'],
        ['key' => 'samples', 'label' => 'Samples'],
        ['key' => 'commissions', 'label' => 'Commissions'],
    ];
    $currentTab = $tab ?? 'overview';
@endphp
<nav class="mt-6 flex flex-wrap gap-1 border-b border-gray-200" aria-label="Creator analytics sections">
    @foreach ($tabs as $t)
        <a href="{{ route('admin.affiliates.analytics.show', ['affiliate' => $affiliate, 'period' => $period, 'tab' => $t['key']]) }}"
           class="rounded-t-lg px-4 py-2 text-sm font-medium transition-colors {{ $currentTab === $t['key'] ? 'border border-b-white border-gray-200 bg-white text-blue-600 -mb-px' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600' }}">
            {{ $t['label'] }}
        </a>
    @endforeach
</nav>
