@props([
    'products' => null,
    'limit' => 5,
    'excludeId' => null,
    // Dùng ở các trang full-width (vd products index). Ở trang show đã có container riêng thì truyền wrapperClass = ''
    'wrapperClass' => 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-20',
])
@php
    $mountId = 'recently-viewed-' . \Illuminate\Support\Str::random(8);
@endphp

<div class="{{ $wrapperClass }}">
    <div id="{{ $mountId }}" data-exclude-id="{{ (int) ($excludeId ?? 0) }}">
        @if(isset($products) && $products instanceof \Illuminate\Support\Collection && $products->isNotEmpty())
            <x-related-products :products="$products" title="Recently Viewed" :limit="$limit" />
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var mount = document.getElementById(@json($mountId));
    if (!mount) return;
    if (mount.querySelector('.pt-8')) return; // đã render server-side

    try {
        var excludeId = parseInt(mount.getAttribute('data-exclude-id') || '0', 10) || 0;
        var raw = localStorage.getItem('recentlyViewedIds');
        var ids = raw ? JSON.parse(raw) : [];
        if (!Array.isArray(ids)) ids = [];
        ids = ids.map(function (x) { return parseInt(x, 10); }).filter(function (x) { return x && x > 0; });
        if (excludeId) ids = ids.filter(function (id) { return id !== excludeId; });
        ids = ids.slice(0, 10);
        // Guest: cần ids; User login: có thể load từ DB dù ids rỗng
        var url = @json(route('products.recently-viewed')) + '?limit=' + {{ (int) $limit }} + (excludeId ? ('&exclude_id=' + excludeId) : '');
        if (ids.length > 0) url += '&ids=' + ids.join(',');
        if (ids.length === 0 && !@json(auth()->check())) return;
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) { if (html && html.trim()) mount.innerHTML = html; })
            .catch(function () {});
    } catch (e) {}
});
</script>

