@props([
    'eyebrow' => 'BluLavelle Community',
    'heading' => 'Customer',
    'headingHighlight' => 'Favorites',
    'viewAllLabel' => 'View All',
    'viewAllUrl' => '#',
    'items' => [],
    'canEdit' => false,
    'editMode' => false,
    'bgColor' => null,
])

@php
    $cardLayouts = ['cf-card--1', 'cf-card--2', 'cf-card--3', 'cf-card--4', 'cf-card--5'];
    $placeholders = [
        'linear-gradient(135deg, #0195FE 0%, #5eb8ff 100%)',
        'linear-gradient(135deg, #7ab8e8 0%, #0195FE 85%)',
        'linear-gradient(160deg, #b8d9f0 0%, #0195FE 100%)',
        'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)',
        'linear-gradient(135deg, #e0f2fe 0%, #7dd3fc 100%)',
    ];
@endphp

<section {{ $attributes->merge([
    'class' => 'px-4 sm:px-6 lg:px-20 py-16 sm:py-20 md:py-24 bg-slate-50 overflow-hidden',
    'style' => $bgColor ? 'background-color: ' . $bgColor . ';' : null,
]) }} data-content-block="home.customer_favorites">
    <div class="max-w-7xl mx-auto">
        @if($canEdit && $editMode)
        <div class="flex justify-end mb-2">
            <button type="button" class="inline-edit-trigger px-3 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg hover:opacity-90" data-block="home.customer_favorites">Chỉnh sửa</button>
        </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6 mb-10 sm:mb-12">
            <div>
                <p class="flex items-center gap-2 text-[11px] sm:text-xs font-bold uppercase tracking-[0.22em] text-[#0195FE] mb-3">
                    <span class="w-4 h-px bg-[#0195FE]"></span>
                    <span data-content-field="eyebrow">{{ $eyebrow }}</span>
                </p>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl leading-tight">
                    <span class="font-black text-slate-900" data-content-field="heading">{{ $heading }}</span>
                    <span class="cf-heading-highlight font-normal italic" data-content-field="heading_highlight">{{ $headingHighlight }}</span>
                </h2>
            </div>
            <a href="{{ $viewAllUrl }}" class="inline-flex items-center gap-2 text-[11px] sm:text-xs font-bold uppercase tracking-[0.18em] text-[#0195FE] hover:underline underline-offset-4 shrink-0 self-start sm:self-auto">
                <span data-content-field="view_all_label">{{ $viewAllLabel }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>

        <div class="cf-grid">
            @foreach($items as $idx => $item)
                @php
                    $username = trim((string) ($item['username'] ?? ''));
                    if ($username !== '' && !str_starts_with($username, '@')) {
                        $username = '@'.$username;
                    }
                    $imageUrl = trim((string) ($item['image_url'] ?? ''));
                    $avatarUrl = trim((string) ($item['avatar_url'] ?? ''));
                    $hasGif = $imageUrl !== '';
                    $badge = $hasGif ? 'GIF' : '';
                    $initial = $username !== '' ? strtoupper(substr(ltrim($username, '@'), 0, 1)) : '?';
                    $layoutClass = $cardLayouts[$idx] ?? 'cf-card--1';
                    $placeholderBg = $placeholders[$idx] ?? $placeholders[0];
                @endphp
                <article class="cf-card {{ $layoutClass }} group relative overflow-hidden rounded-2xl bg-slate-200 shadow-md shadow-slate-200/60 ring-1 ring-slate-900/5">
                    @if($hasGif)
                        <img class="cf-card-media absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                             src="{{ $imageUrl }}"
                             alt="{{ $username ?: 'Community favorite' }}"
                             loading="lazy"
                             decoding="async">
                    @else
                        <div class="absolute inset-0 transition-transform duration-700 group-hover:scale-105" style="background: {{ $placeholderBg }};"></div>
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-black/5 pointer-events-none"></div>

                    @if($badge !== '')
                    <span class="absolute top-3 left-3 z-10 px-2 py-1 rounded-md bg-black/45 backdrop-blur-sm text-[10px] font-bold uppercase tracking-wider text-white">{{ $badge }}</span>
                    @endif

                    <div class="absolute bottom-0 left-0 right-0 z-10 p-3 sm:p-4 flex items-center gap-2.5">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="" class="w-8 h-8 rounded-full object-cover ring-2 ring-white/80 shrink-0" loading="lazy">
                        @else
                            <span class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-sm ring-2 ring-white/60 flex items-center justify-center text-xs font-bold text-white shrink-0">{{ $initial }}</span>
                        @endif
                        <span class="text-sm font-semibold text-white truncate drop-shadow-sm">{{ $username ?: 'BluLavelle' }}</span>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<style>
.cf-heading-highlight {
    font-family: 'Fraunces', Georgia, serif;
    background: linear-gradient(100deg, #0195FE, #5eb8ff 70%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.cf-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    grid-template-rows: repeat(2, minmax(170px, 210px));
    gap: 1rem;
}
.cf-card--1 { grid-column: 1; grid-row: 1; }
.cf-card--2 { grid-column: 1; grid-row: 2; }
.cf-card--3 { grid-column: 2; grid-row: 1 / span 2; min-height: 100%; }
.cf-card--4 { grid-column: 3; grid-row: 1; }
.cf-card--5 { grid-column: 3; grid-row: 2; }
@media (max-width: 767px) {
    .cf-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: auto;
        gap: 0.75rem;
    }
    .cf-card--1, .cf-card--2, .cf-card--4, .cf-card--5 { min-height: 180px; }
    .cf-card--3 { grid-column: 1 / -1; grid-row: auto; min-height: 260px; }
    .cf-card--1 { grid-column: 1; grid-row: 1; }
    .cf-card--2 { grid-column: 2; grid-row: 1; }
    .cf-card--4 { grid-column: 1; grid-row: 3; }
    .cf-card--5 { grid-column: 2; grid-row: 3; }
}
</style>
