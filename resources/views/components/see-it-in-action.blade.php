@props([
    'videoSrc' => null,
    'heading' => 'See it in action.',
    'subheading' => 'No matter the style, our nails fit perfectly.',
    'nowPlayingLabel' => 'Now Playing',
    'nowPlayingTitle' => 'How to Apply Press-on Nails',
    'canEdit' => false,
    'editMode' => false,
])
@php
    $videoSrc = $videoSrc ?? asset('storage/images/grok-video-c34824a0-ea8a-47d9-ac98-c1b5aa3cf99e.mp4');

    // Tabs có thể custom từ ContentBlock: home.see_it_in_action
    // Format gợi ý:
    // tabs: [
    //   { key: 'bikini', label: 'Bikini', image_url: '...', video_url: '...' },
    //   ...
    // ]
    $block = function_exists('content_block')
        ? content_block('home.see_it_in_action', [])
        : [];

    $defaultTabs = [
        ['key' => 'bikini', 'label' => 'Bikini', 'image_url' => null, 'video_url' => $videoSrc],
        ['key' => 'leg', 'label' => 'Leg', 'image_url' => null, 'video_url' => $videoSrc],
        ['key' => 'arm', 'label' => 'Arm', 'image_url' => null, 'video_url' => $videoSrc],
        ['key' => 'back', 'label' => 'Back', 'image_url' => null, 'video_url' => $videoSrc],
    ];

    $tabs = $block['tabs'] ?? null;
    if (!is_array($tabs) || count($tabs) === 0) {
        $tabs = $defaultTabs;
    } else {
        // Normalize + merge defaults per key (để không thiếu field)
        $tabs = collect($tabs)->map(function ($t) use ($defaultTabs) {
            $t = is_array($t) ? $t : [];
            $key = $t['key'] ?? null;
            $key = is_string($key) && $key !== '' ? $key : null;
            $fallback = collect($defaultTabs)->firstWhere('key', $key) ?? ['key' => $key ?? \Illuminate\Support\Str::random(6), 'label' => 'Tab', 'image_url' => null, 'video_url' => null];
            return array_merge($fallback, $t);
        })->values()->all();
    }

    $activeTabKey = $tabs[0]['key'] ?? 'bikini';
@endphp
<section {{ $attributes->merge(['class' => 'px-4 sm:px-6 lg:px-20 py-16 sm:py-20 md:py-24 bg-white overflow-hidden']) }} data-content-block="home.see_it_in_action">
    <div class="max-w-7xl mx-auto">
        @if($canEdit && $editMode)
        <div class="flex justify-end mb-2">
            <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.see_it_in_action">
                Chỉnh sửa
            </button>
        </div>
        @endif
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-12 gap-8">
            <div class="max-w-xl">
                <h2 class="text-3xl lg:text-4xl font-black text-slate-900 leading-tight mb-2">
                    <span data-content-field="heading">{{ $heading }}</span> <span class="font-normal italic text-slate-700" data-content-field="subheading">{{ $subheading }}</span>
                </h2>
            </div>
            {{-- role="group" + aria-pressed: tránh tablist bắt buộc con role="tab" (một số engine không gán tab đúng cho <button>). --}}
            <div class="seeit-style-picker flex items-center bg-slate-100 p-1.5 rounded-xl" role="group" aria-label="Choose a look to preview">
                @foreach($tabs as $i => $tab)
                    @php
                        $isActive = ($tab['key'] ?? '') === $activeTabKey;
                        $img = $tab['image_url'] ?? null;
                        $vid = $tab['video_url'] ?? null;
                    @endphp
                    <button type="button"
                            class="tab-btn px-6 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive ? 'bg-primary text-white shadow-md' : 'bg-transparent text-slate-800 hover:text-slate-950' }}"
                            data-tab="{{ $tab['key'] }}"
                            data-image="{{ $img ?? '' }}"
                            data-video="{{ $vid ?? '' }}"
                            aria-pressed="{{ $isActive ? 'true' : 'false' }}">
                        {{ $tab['label'] ?? 'Tab' }}
                    </button>
                @endforeach
            </div>
        </div>
        @php
            $firstTab = $tabs[0] ?? [];
            $firstImg = $firstTab['image_url'] ?? null;
            $firstVid = $firstTab['video_url'] ?? $videoSrc;
        @endphp
        <div class="seeit-media-region relative rounded-3xl overflow-hidden shadow-2xl group bg-slate-200"
             role="region"
             aria-label="Selected look preview">
            {{-- Không gắn src ban đầu: tránh tải MP4 lớn (~MB) trước khi section vào viewport (PageSpeed payload). --}}
            <video preload="none" autoplay loop muted playsinline
                   class="seeit-main-video w-full aspect-video lg:aspect-[21/9] object-cover transition-transform duration-700 group-hover:scale-105 {{ $firstVid ? '' : 'hidden' }}"
                   @if($firstVid) data-seeit-src="{{ $firstVid }}" @endif
                   onerror="this.classList.add('hidden');">
            </video>
            <img class="seeit-main-image w-full aspect-video lg:aspect-[21/9] object-cover {{ $firstVid ? 'hidden' : '' }}"
                 src="{{ $firstImg ?: '' }}"
                 alt="See it in action"
                 loading="lazy"
                 decoding="async"
                 onerror="this.style.display='none';" />

            <div class="absolute bottom-8 left-8 right-8 flex justify-between items-end text-white pointer-events-none">
                <div class="bg-black/20 backdrop-blur-md px-4 py-2 rounded-lg border border-white/20">
                    <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-1">{{ $nowPlayingLabel }}</p>
                    <p class="text-lg font-bold">{{ $nowPlayingTitle }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('section[data-content-block="home.see_it_in_action"]').forEach(function(section) {
        var video = section.querySelector('.seeit-main-video');
        var image = section.querySelector('.seeit-main-image');

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

        var stylePicker = section.querySelector('.seeit-style-picker');
        if (stylePicker) {
            stylePicker.addEventListener('keydown', function (e) {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                var tabs = Array.prototype.slice.call(stylePicker.querySelectorAll('.tab-btn'));
                if (!tabs.length) return;
                var idx = tabs.indexOf(document.activeElement);
                if (idx < 0) return;
                e.preventDefault();
                var next = e.key === 'ArrowRight' ? (idx + 1) % tabs.length : (idx - 1 + tabs.length) % tabs.length;
                tabs[next].click();
                tabs[next].focus();
            });
        }

        section.querySelectorAll('.tab-btn').forEach(function(btn) {
            if (btn.dataset.tabListen === '1') return;
            btn.dataset.tabListen = '1';
            btn.addEventListener('click', function() {
                var wrapper = this.closest('section');
                if (!wrapper) return;
                var picker = wrapper.querySelector('.seeit-style-picker');
                var buttons = picker ? picker.querySelectorAll('.tab-btn') : wrapper.querySelectorAll('.tab-btn');
                buttons.forEach(function(b) {
                    b.classList.remove('bg-primary', 'text-white', 'shadow-md');
                    b.classList.add('bg-transparent', 'text-slate-800');
                    b.setAttribute('aria-pressed', 'false');
                });
                this.classList.remove('bg-transparent', 'text-slate-800');
                this.classList.add('bg-primary', 'text-white', 'shadow-md');
                this.setAttribute('aria-pressed', 'true');

                var imgUrl = this.getAttribute('data-image') || '';
                var vidUrl = this.getAttribute('data-video') || '';
                var vid = wrapper.querySelector('.seeit-main-video');
                var img = wrapper.querySelector('.seeit-main-image');
                if (vidUrl) {
                    if (img) img.classList.add('hidden');
                    if (vid) {
                        vid.classList.remove('hidden');
                        if (vid.getAttribute('src') !== vidUrl) {
                            vid.setAttribute('src', vidUrl);
                            vid.load();
                        }
                        vid.play().catch(function () {});
                        vid.dataset.seeItHydrated = '1';
                    }
                } else if (imgUrl) {
                    if (vid) {
                        vid.pause();
                        vid.removeAttribute('src');
                        vid.classList.add('hidden');
                    }
                    if (img) {
                        img.setAttribute('src', imgUrl);
                        img.classList.remove('hidden');
                    }
                }
            });
        });
    });
});
</script>
