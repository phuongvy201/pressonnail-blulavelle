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
            <div class="flex items-center bg-slate-100 p-1.5 rounded-xl" role="tablist">
                @foreach($tabs as $i => $tab)
                    @php
                        $isActive = ($tab['key'] ?? '') === $activeTabKey;
                        $img = $tab['image_url'] ?? null;
                        $vid = $tab['video_url'] ?? null;
                    @endphp
                    <button type="button"
                            class="tab-btn px-6 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive ? 'bg-primary text-white shadow-md' : 'bg-transparent text-slate-600 hover:text-slate-900' }}"
                            data-tab="{{ $tab['key'] }}"
                            data-image="{{ $img ?? '' }}"
                            data-video="{{ $vid ?? '' }}"
                            aria-selected="{{ $isActive ? 'true' : 'false' }}">
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
        <div class="relative rounded-3xl overflow-hidden shadow-2xl group">
            <video id="seeit-video" autoplay loop muted playsinline
                   class="w-full aspect-video lg:aspect-[21/9] object-cover transition-transform duration-700 group-hover:scale-105 {{ $firstVid ? '' : 'hidden' }}"
                   @if($firstVid) src="{{ $firstVid }}" @endif
                   onerror="this.classList.add('hidden');">
            </video>
            <img id="seeit-image"
                 class="w-full aspect-video lg:aspect-[21/9] object-cover {{ $firstVid ? 'hidden' : '' }}"
                 src="{{ $firstImg ?: '' }}"
                 alt="See it in action"
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
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        if (btn.dataset.tabListen === '1') return;
        btn.dataset.tabListen = '1';
        btn.addEventListener('click', function() {
            var wrapper = this.closest('section');
            if (wrapper)             wrapper.querySelectorAll('.tab-btn').forEach(function(b) {
                b.classList.remove('bg-primary', 'text-white', 'shadow-md');
                b.classList.add('bg-transparent', 'text-slate-600');
                b.setAttribute('aria-selected', 'false');
            });
            this.classList.remove('bg-transparent', 'text-slate-600');
            this.classList.add('bg-primary', 'text-white', 'shadow-md');
            this.setAttribute('aria-selected', 'true');

            // Swap media (image/video)
            var imgUrl = this.getAttribute('data-image') || '';
            var vidUrl = this.getAttribute('data-video') || '';
            var video = wrapper ? wrapper.querySelector('#seeit-video') : null;
            var image = wrapper ? wrapper.querySelector('#seeit-image') : null;
            if (vidUrl) {
                if (image) image.classList.add('hidden');
                if (video) {
                    video.classList.remove('hidden');
                    if (video.getAttribute('src') !== vidUrl) {
                        video.setAttribute('src', vidUrl);
                        video.load();
                    }
                    video.play().catch(function(){});
                }
            } else if (imgUrl) {
                if (video) {
                    video.pause();
                    video.classList.add('hidden');
                }
                if (image) {
                    image.setAttribute('src', imgUrl);
                    image.classList.remove('hidden');
                }
            }
        });
    });
});
</script>
