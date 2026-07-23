@props([
    'products' => null,
    'limit' => 5,
    'excludeId' => null,
    'wrapperClass' => 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-20',
])
@php
    $mountId = 'recently-viewed-' . \Illuminate\Support\Str::random(8);
@endphp

<div class="{{ $wrapperClass }}">
    <div id="{{ $mountId }}" data-exclude-id="{{ (int) ($excludeId ?? 0) }}">
        @if(isset($products) && $products instanceof \Illuminate\Support\Collection && $products->isNotEmpty())
            <x-recently-viewed-carousel :products="$products" :limit="$limit" />
        @endif
    </div>
</div>

@once
<style>
.rv-heading-highlight {
    font-family: 'Fraunces', Georgia, serif;
    background: linear-gradient(100deg, #0195FE, #5eb8ff 70%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.rv-nav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 9999px;
    background: #fff;
    color: #334155;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
    transition: opacity 0.2s, background-color 0.2s, border-color 0.2s;
}
.rv-nav-btn:hover:not(:disabled) {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.rv-nav-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.rv-track {
    transition: transform 0.45s cubic-bezier(0.4, 0, 0.2, 1);
}
.rv-slide {
    min-width: 0;
}
</style>
<script>
(function () {
    function rvPerView() {
        if (window.innerWidth >= 1280) return 4;
        if (window.innerWidth >= 1024) return 3;
        if (window.innerWidth >= 640) return 2;
        return 2;
    }

    function initRvCarousel(root) {
        if (!root || root.dataset.rvReady === '1') return;

        var carouselId = root.getAttribute('data-rv-root');
        var track = document.getElementById(carouselId);
        var viewport = root.querySelector('[data-rv-viewport="' + carouselId + '"]');
        var prevBtn = root.querySelector('[data-rv-prev="' + carouselId + '"]');
        var nextBtn = root.querySelector('[data-rv-next="' + carouselId + '"]');
        if (!track || !viewport) return;

        var slides = Array.from(track.querySelectorAll('[data-rv-slide]'));
        if (!slides.length) return;

        var index = 0;
        var gap = 16;

        function layout() {
            // Always size cards for the intended grid (2/3/4 cols), even if fewer items —
            // otherwise 1 product stretches to full width and looks "zoomed".
            var desiredPerView = rvPerView();
            var perView = Math.min(desiredPerView, slides.length);
            var viewportWidth = viewport.clientWidth;
            if (viewportWidth <= 0) return;

            gap = window.innerWidth >= 640 ? 20 : 12;
            var slideWidth = (viewportWidth - gap * (desiredPerView - 1)) / desiredPerView;

            slides.forEach(function (slide) {
                slide.style.width = slideWidth + 'px';
                slide.style.maxWidth = slideWidth + 'px';
            });
            track.style.gap = gap + 'px';

            var maxIndex = Math.max(0, slides.length - perView);
            if (index > maxIndex) index = maxIndex;

            track.style.transform = 'translateX(-' + (index * (slideWidth + gap)) + 'px)';

            var nav = root.querySelector('.rv-nav');
            if (nav) nav.style.display = slides.length > perView ? '' : 'none';
            if (prevBtn) prevBtn.disabled = index <= 0;
            if (nextBtn) nextBtn.disabled = index >= maxIndex;
        }

        function step(dir) {
            var desiredPerView = rvPerView();
            var perView = Math.min(desiredPerView, slides.length);
            var maxIndex = Math.max(0, slides.length - perView);
            index = Math.min(maxIndex, Math.max(0, index + dir));
            layout();
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { step(-1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { step(1); });

        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(layout, 120);
        });

        root.dataset.rvReady = '1';
        layout();
        requestAnimationFrame(layout);
    }

    window.initRecentlyViewedCarousels = function (scope) {
        if (!scope) {
            document.querySelectorAll('[data-rv-root]').forEach(initRvCarousel);
            return;
        }
        if (scope.matches && scope.matches('[data-rv-root]')) {
            initRvCarousel(scope);
            return;
        }
        scope.querySelectorAll('[data-rv-root]').forEach(initRvCarousel);
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initRecentlyViewedCarousels();
    });
})();
</script>
@endonce

<script>
document.addEventListener('DOMContentLoaded', function () {
    var mount = document.getElementById(@json($mountId));
    if (!mount) return;

    if (mount.querySelector('[data-rv-root]')) {
        window.initRecentlyViewedCarousels && window.initRecentlyViewedCarousels(mount);
        return;
    }

    try {
        var excludeId = parseInt(mount.getAttribute('data-exclude-id') || '0', 10) || 0;
        var raw = localStorage.getItem('recentlyViewedIds');
        var ids = raw ? JSON.parse(raw) : [];
        if (!Array.isArray(ids)) ids = [];
        ids = ids.map(function (x) { return parseInt(x, 10); }).filter(function (x) { return x && x > 0; });
        if (excludeId) ids = ids.filter(function (id) { return id !== excludeId; });
        ids = ids.slice(0, 10);
        var url = @json(route('products.recently-viewed')) + '?limit=' + {{ (int) $limit }} + (excludeId ? ('&exclude_id=' + excludeId) : '');
        if (ids.length > 0) url += '&ids=' + ids.join(',');
        if (ids.length === 0 && !@json(auth()->check())) return;
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                if (!html || !html.trim()) return;
                mount.innerHTML = html;
                window.initRecentlyViewedCarousels && window.initRecentlyViewedCarousels(mount);
            })
            .catch(function () {});
    } catch (e) {}
});
</script>
