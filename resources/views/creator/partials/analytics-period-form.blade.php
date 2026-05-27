@props(['period', 'action'])

<form method="get" action="{{ $action }}" class="flex items-center gap-2">
    <label for="analytics-period" class="creator-font-label text-xs font-semibold uppercase tracking-wide text-[#707884]">Period</label>
    <select id="analytics-period" name="period" onchange="this.form.submit()"
            class="rounded-lg border border-[#bfc7d5] bg-white px-3 py-2 text-sm font-medium text-[#0b1c30] focus:border-primary focus:ring-1 focus:ring-primary">
        @foreach (['7d' => 'Last 7 days', '30d' => 'Last 30 days', '90d' => 'Last 90 days', 'all' => 'All time'] as $val => $label)
            <option value="{{ $val }}" @selected($period === $val)>{{ $label }}</option>
        @endforeach
    </select>
</form>
