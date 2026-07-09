@props([
    'videoSrc' => null,
    'heading' => 'See It In',
    'headingHighlight' => null,
    'subheading' => 'No matter the style, your nails always fit perfectly.',
    'panelTitle' => 'Not sure about your size?',
    'panelBody' => 'Use our Sizing Kit to measure accurately before ordering, ensuring a perfect fit for all 10 nails.',
    'panelCtaLabel' => 'View Sizing Kit',
    'panelCtaUrl' => null,
    'feature1' => '10 minutes for a full application',
    'feature2' => 'No filing, no extra glue required',
    'feature3' => 'Reusable 20+ times with proper care',
    'canEdit' => false,
    'editMode' => false,
])
@php
    $videoSrc = $videoSrc ?? asset('storage/images/grok-video-c34824a0-ea8a-47d9-ac98-c1b5aa3cf99e.mp4');
    $panelCtaUrl = $panelCtaUrl ?? route('sizing-kit.index');

    $block = function_exists('content_block')
        ? content_block('home.see_it_in_action', [])
        : [];

    if (empty($headingHighlight)) {
        $rawHeading = trim((string) $heading);
        if (preg_match('/^(.+?)\s+(action\.?)$/i', $rawHeading, $m)) {
            $heading = trim($m[1]);
            $headingHighlight = rtrim(ucfirst(strtolower($m[2])), '.') . '.';
        } else {
            $headingHighlight = 'Action.';
        }
    }

    $defaultTabs = [
        ['key' => 'nail', 'label' => 'Nail', 'image_url' => null, 'video_url' => $videoSrc],
        ['key' => 'box', 'label' => 'Box', 'image_url' => null, 'video_url' => $videoSrc],
        ['key' => 'arm', 'label' => 'Arm', 'image_url' => null, 'video_url' => $videoSrc],
        ['key' => 'back', 'label' => 'Back', 'image_url' => null, 'video_url' => $videoSrc],
    ];

    $legacyTabMap = [
        'bikini' => 'nail',
        'leg' => 'box',
    ];

    $tabGradients = [
        'nail' => 'linear-gradient(165deg, #7ec8f7 0%, #0195FE 100%)',
        'box' => 'linear-gradient(165deg, #0195FE 0%, #004f8c 100%)',
        'arm' => 'linear-gradient(165deg, #5eb8ff 0%, #0195FE 88%)',
        'back' => 'linear-gradient(165deg, #d9efff 0%, #8ecfff 100%)',
    ];

    $tabs = $block['tabs'] ?? null;
    if (!is_array($tabs) || count($tabs) === 0) {
        $tabs = $defaultTabs;
    } else {
        $tabs = collect($tabs)->map(function ($t, $i) use ($defaultTabs, $legacyTabMap, $videoSrc) {
            $t = is_array($t) ? $t : [];
            $key = $t['key'] ?? null;
            $key = is_string($key) && $key !== '' ? $key : null;
            if ($key && isset($legacyTabMap[$key])) {
                $key = $legacyTabMap[$key];
                $t['key'] = $key;
            }
            $fallback = collect($defaultTabs)->firstWhere('key', $key) ?? ($defaultTabs[$i] ?? ['key' => $key ?? \Illuminate\Support\Str::random(6), 'label' => 'Tab', 'image_url' => null, 'video_url' => $videoSrc]);
            $merged = array_merge($fallback, $t);
            if (empty($merged['video_url']) && empty($merged['image_url'])) {
                $merged['video_url'] = $videoSrc;
            }
            return $merged;
        })->values()->all();
    }

    while (count($tabs) < 4) {
        $i = count($tabs);
        $tabs[] = $defaultTabs[$i] ?? ['key' => 'tab' . ($i + 1), 'label' => 'Tab', 'image_url' => null, 'video_url' => $videoSrc];
    }
    $tabs = array_slice($tabs, 0, 4);

    $activeTabKey = $tabs[0]['key'] ?? 'nail';
    $firstTab = $tabs[0] ?? [];
    $firstImg = $firstTab['image_url'] ?? null;
    $firstVid = $firstTab['video_url'] ?? $videoSrc;
    $firstLabel = $firstTab['label'] ?? 'Nail';
@endphp

<section {{ $attributes->merge(['class' => 'px-4 sm:px-6 lg:px-20 py-16 sm:py-20 md:py-24 bg-white overflow-hidden']) }} data-content-block="home.see_it_in_action">
    <div class="max-w-7xl mx-auto">
        @if($canEdit && $editMode)
        <div class="flex justify-end mb-2">
            <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.see_it_in_action">
                Edit
            </button>
        </div>
        @endif

        {{-- Header --}}
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6 lg:gap-10 mb-8 sm:mb-10">
            <div class="max-w-2xl">
                <h2 class="text-3xl sm:text-4xl lg:text-[2.75rem] leading-tight mb-3">
                    <span class="font-black text-slate-900" data-content-field="heading">{{ $heading }}</span>
                    <span class="seeit-heading-highlight font-normal italic" data-content-field="heading_highlight">{{ $headingHighlight }}</span>
                </h2>
                <p class="text-slate-600 text-sm sm:text-base leading-relaxed max-w-xl" data-content-field="subheading">{{ $subheading }}</p>
            </div>

            <div class="seeit-style-picker flex flex-wrap items-center gap-2 shrink-0" role="group" aria-label="Choose a preview angle">
                @foreach($tabs as $tab)
                    @php
                        $isActive = ($tab['key'] ?? '') === $activeTabKey;
                        $img = $tab['image_url'] ?? '';
                        $vid = $tab['video_url'] ?? '';
                        $label = $tab['label'] ?? 'Tab';
                    @endphp
                    <button type="button"
                            class="seeit-pill-btn seeit-tab-btn select-none focus:outline-none px-5 sm:px-6 py-2 rounded-full text-sm font-semibold border transition-all duration-200 {{ $isActive ? 'bg-primary text-white border-primary shadow-md shadow-primary/20' : 'bg-white text-slate-800 border-slate-200 hover:border-primary/40 hover:text-primary' }}"
                            data-tab="{{ $tab['key'] }}"
                            data-label="{{ $label }}"
                            data-image="{{ $img }}"
                            data-video="{{ $vid }}"
                            aria-pressed="{{ $isActive ? 'true' : 'false' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- 3-column layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-5 items-stretch">
            {{-- Left thumbnails --}}
            <div class="hidden lg:flex lg:col-span-2 flex-col gap-3">
                @foreach($tabs as $tab)
                    @php
                        $isActive = ($tab['key'] ?? '') === $activeTabKey;
                        $key = $tab['key'] ?? '';
                        $img = $tab['image_url'] ?? null;
                        $vid = $tab['video_url'] ?? '';
                        $label = $tab['label'] ?? 'Tab';
                        $gradient = $tabGradients[$key] ?? $tabGradients['nail'];
                    @endphp
                    <button type="button"
                            class="seeit-thumb-btn seeit-tab-btn select-none focus:outline-none group text-left rounded-2xl overflow-hidden transition-all duration-300 {{ $isActive ? 'ring-2 ring-primary ring-offset-2 shadow-lg shadow-primary/15' : 'hover:ring-2 hover:ring-primary/30 hover:ring-offset-1' }}"
                            data-tab="{{ $key }}"
                            data-label="{{ $label }}"
                            data-image="{{ $img ?? '' }}"
                            data-video="{{ $vid }}"
                            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                            aria-label="Show {{ $label }}">
                        <div class="relative aspect-[4/5] overflow-hidden">
                            @if($img)
                                <img src="{{ $img }}" alt="" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                            @else
                                <div class="absolute inset-0 transition-transform duration-500 group-hover:scale-105" style="background: {{ $gradient }};"></div>
                            @endif
                            <div class="absolute inset-x-0 bottom-0 bg-[#0195FE]/95 text-white text-[10px] font-extrabold uppercase tracking-[0.18em] text-center py-2">
                                {{ strtoupper($label) }}
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>

            {{-- Main media --}}
            <div class="lg:col-span-7">
                <div class="seeit-media-region relative rounded-3xl overflow-hidden shadow-xl shadow-slate-200/80 ring-1 ring-slate-900/5 bg-gradient-to-br from-[#d9efff] via-[#b8e0fc] to-[#8ecfff] h-full min-h-[220px] sm:min-h-[280px] lg:min-h-[420px]"
                     role="region"
                     aria-label="Selected look preview">
                    <video preload="none" autoplay loop muted playsinline
                           class="seeit-main-video absolute inset-0 w-full h-full object-cover transition-transform duration-700 {{ $firstVid ? '' : 'hidden' }}"
                           @if($firstVid) data-seeit-src="{{ $firstVid }}" @endif
                           onerror="this.classList.add('hidden');">
                    </video>
                    <img class="seeit-main-image absolute inset-0 w-full h-full object-cover {{ $firstVid ? 'hidden' : '' }}"
                         src="{{ $firstImg ?: '' }}"
                         alt="See it in action"
                         loading="lazy"
                         decoding="async"
                         onerror="this.style.display='none';" />

                    <div class="absolute bottom-4 left-4 sm:bottom-5 sm:left-5 pointer-events-none">
                        <span class="seeit-media-label inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-900/75 backdrop-blur-sm text-white text-[10px] sm:text-xs font-extrabold uppercase tracking-[0.2em]">
                            {{ strtoupper($firstLabel) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Right info panel --}}
            <div class="lg:col-span-3">
                <div class="h-full rounded-3xl bg-[#e8f5fd] border border-[#c5e4f7] p-5 sm:p-6 flex flex-col">
                    <div class="w-10 h-10 rounded-xl bg-white/80 flex items-center justify-center text-primary mb-4 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 10h18M3 14h12M3 18h8"/>
                        </svg>
                    </div>

                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-2 leading-snug" data-content-field="panel_title">{{ $panelTitle }}</h3>
                    <p class="text-sm text-slate-600 leading-relaxed mb-5" data-content-field="panel_body">{{ $panelBody }}</p>

                    <ul class="space-y-2.5 mb-6 flex-1">
                        <li class="flex items-center gap-3 bg-white rounded-xl px-3.5 py-3 shadow-sm border border-white/80">
                            <span class="w-8 h-8 rounded-lg bg-[#e8f5fd] flex items-center justify-center text-primary shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <span class="text-sm text-slate-700 leading-snug" data-content-field="feature1">{{ $feature1 }}</span>
                        </li>
                        <li class="flex items-center gap-3 bg-white rounded-xl px-3.5 py-3 shadow-sm border border-white/80">
                            <span class="w-8 h-8 rounded-lg bg-[#e8f5fd] flex items-center justify-center text-primary shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/></svg>
                            </span>
                            <span class="text-sm text-slate-700 leading-snug" data-content-field="feature2">{{ $feature2 }}</span>
                        </li>
                        <li class="flex items-center gap-3 bg-white rounded-xl px-3.5 py-3 shadow-sm border border-white/80">
                            <span class="w-8 h-8 rounded-lg bg-[#e8f5fd] flex items-center justify-center text-primary shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </span>
                            <span class="text-sm text-slate-700 leading-snug" data-content-field="feature3">{{ $feature3 }}</span>
                        </li>
                    </ul>

                    <a href="{{ $panelCtaUrl }}"
                       class="inline-flex items-center gap-2 text-[11px] sm:text-xs font-extrabold uppercase tracking-[0.18em] text-[#0195FE] hover:underline underline-offset-4 mt-auto">
                        <span data-content-field="panel_cta_label">{{ $panelCtaLabel }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <span class="hidden" data-content-field="panel_cta_url">{{ $panelCtaUrl }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

@once
<style>
.seeit-heading-highlight {
    font-family: 'Fraunces', Georgia, serif;
    background: linear-gradient(100deg, #0195FE, #5eb8ff 70%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.seeit-tab-btn {
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}
.seeit-tab-btn:focus {
    outline: none;
}
</style>
@endonce

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('section[data-content-block="home.see_it_in_action"]').forEach(function(section) {
        var video = section.querySelector('.seeit-main-video');
        var image = section.querySelector('.seeit-main-image');
        var mediaLabel = section.querySelector('.seeit-media-label');

        function playVideoUrl(url) {
            if (!video || !url) return;
            video.classList.remove('hidden');
            if (image) image.classList.add('hidden');
            if (video.getAttribute('src') !== url) {
                video.setAttribute('src', url);
                video.load();
            }
            video.play().catch(function () {});
            video.dataset.seeItHydrated = '1';
        }

        function hydrateFromDataAttr() {
            if (!video || video.dataset.seeItHydrated === '1') return;
            var u = video.getAttribute('data-seeit-src');
            if (u) playVideoUrl(u);
        }

        if (video && video.getAttribute('data-seeit-src')) {
            if ('IntersectionObserver' in window) {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) {
                            hydrateFromDataAttr();
                            io.disconnect();
                        }
                    });
                }, { rootMargin: '200px', threshold: 0.01 });
                io.observe(section);
            } else {
                hydrateFromDataAttr();
            }
        }

        function setPillActive(btn, active) {
            if (active) {
                btn.classList.add('bg-primary', 'text-white', 'border-primary', 'shadow-md', 'shadow-primary/20');
                btn.classList.remove('bg-white', 'text-slate-800', 'border-slate-200');
            } else {
                btn.classList.remove('bg-primary', 'text-white', 'border-primary', 'shadow-md', 'shadow-primary/20');
                btn.classList.add('bg-white', 'text-slate-800', 'border-slate-200');
            }
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        }

        function setThumbActive(btn, active) {
            if (active) {
                btn.classList.add('ring-2', 'ring-primary', 'ring-offset-2', 'shadow-lg', 'shadow-primary/15');
            } else {
                btn.classList.remove('ring-2', 'ring-primary', 'ring-offset-2', 'shadow-lg', 'shadow-primary/15');
            }
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        }

        function activateTab(btn) {
            var tabKey = btn.getAttribute('data-tab');
            var label = btn.getAttribute('data-label') || '';

            section.querySelectorAll('.seeit-pill-btn').forEach(function (b) {
                setPillActive(b, b.getAttribute('data-tab') === tabKey);
            });
            section.querySelectorAll('.seeit-thumb-btn').forEach(function (b) {
                setThumbActive(b, b.getAttribute('data-tab') === tabKey);
            });

            if (mediaLabel && label) {
                mediaLabel.textContent = label.toUpperCase();
            }

            var imgUrl = btn.getAttribute('data-image') || '';
            var vidUrl = btn.getAttribute('data-video') || '';
            if (vidUrl) {
                playVideoUrl(vidUrl);
            } else if (imgUrl) {
                if (video) {
                    video.pause();
                    video.removeAttribute('src');
                    video.classList.add('hidden');
                }
                if (image) {
                    image.setAttribute('src', imgUrl);
                    image.classList.remove('hidden');
                    image.style.display = '';
                }
            }
        }

        var stylePicker = section.querySelector('.seeit-style-picker');
        if (stylePicker) {
            stylePicker.addEventListener('keydown', function (e) {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                var tabs = Array.prototype.slice.call(stylePicker.querySelectorAll('.seeit-pill-btn'));
                if (!tabs.length) return;
                var idx = tabs.indexOf(document.activeElement);
                if (idx < 0) return;
                e.preventDefault();
                var next = e.key === 'ArrowRight' ? (idx + 1) % tabs.length : (idx - 1 + tabs.length) % tabs.length;
                tabs[next].click();
                tabs[next].focus();
            });
        }

        section.querySelectorAll('.seeit-tab-btn').forEach(function(btn) {
            if (btn.dataset.tabListen === '1') return;
            btn.dataset.tabListen = '1';
            btn.addEventListener('click', function() {
                activateTab(this);
            });
        });
    });
});
</script>
