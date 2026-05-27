@php
    $action = $action ?? route('admin.affiliates.analytics.index', $affiliate ?? null);
    $period = $period ?? request('period', '30d');
@endphp
<form method="get" action="{{ $action }}" class="flex items-center gap-2">
    @foreach (request()->except(['period', 'page', 'tab']) as $key => $value)
        @if (is_array($value))
            @foreach ($value as $item)
                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
            @endforeach
        @elseif ($value !== null && $value !== '')
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <label for="admin_analytics_period" class="text-sm font-medium text-gray-700">Period</label>
    <select id="admin_analytics_period" name="period" onchange="this.form.submit()"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
        @foreach (['7d' => 'Last 7 days', '30d' => 'Last 30 days', '90d' => 'Last 90 days', 'all' => 'All time'] as $val => $label)
            <option value="{{ $val }}" @selected($period === $val)>{{ $label }}</option>
        @endforeach
    </select>
</form>
