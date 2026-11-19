<a href="{{ route('blog.show', $post->slug) }}" class="group block">
    <div class="relative overflow-hidden rounded-2xl aspect-[4/3] mb-4 bg-slate-100">
        @if($post->featured_image)
            <img
                src="{{ $post->featured_image_url }}"
                alt="{{ $post->title }}"
                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                loading="lazy"
                decoding="async"
            >
        @else
            <div class="w-full h-full bg-gradient-to-br from-primary/25 to-primary/5"></div>
        @endif
    </div>
    <p class="text-[11px] font-extrabold uppercase tracking-widest text-primary mb-2">
        @if($post->category)
            {{ $post->category->name }}
            <span class="text-slate-300"> · </span>
        @endif
        {{ $post->published_at ? $post->published_at->format('M j, Y') : 'Draft' }}
    </p>
    <h2 class="font-display text-xl font-extrabold text-slate-900 group-hover:text-primary transition-colors line-clamp-2">{{ $post->title }}</h2>
    @if($post->excerpt)
        <p class="mt-2 text-sm text-slate-600 leading-relaxed line-clamp-3">{{ $post->excerpt }}</p>
    @endif
    <p class="mt-3 text-xs font-bold uppercase tracking-widest text-slate-400">{{ $post->reading_time ?? 1 }} min read</p>
</a>
