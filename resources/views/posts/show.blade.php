@extends('layouts.app')

@section('title', $post->meta_title ?? $post->title)
@section('meta_description', $post->meta_description ?? $post->excerpt)

@section('content')
@php
    $featuredUrl = $post->featured_image_url;
    $body = (string) $post->content;
    $showLeadImage = $featuredUrl && ! str_contains($body, $featuredUrl);
    $shareUrl = route('blog.show', $post->slug);
    $gallery = collect($post->gallery_urls ?? [])->reject(fn (string $url): bool => str_contains($body, $url))->values();
@endphp
<main class="bg-background-light">
    <article>
        <nav class="max-w-3xl mx-auto px-6 py-4 flex flex-wrap items-center gap-2 text-xs font-medium text-slate-400 uppercase tracking-widest">
            <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
            <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a class="hover:text-primary transition-colors" href="{{ route('blog.index') }}">Blog</a>
            @if($post->category)
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a class="hover:text-primary transition-colors" href="{{ route('blog.category', $post->category->slug) }}">{{ $post->category->name }}</a>
            @endif
        </nav>

        <header class="max-w-3xl mx-auto px-6 pt-6 pb-8">
            @if($post->category)
                <a href="{{ route('blog.category', $post->category->slug) }}" class="inline-flex px-3 py-1 rounded-full bg-primary/10 text-primary text-[11px] font-extrabold uppercase tracking-widest">{{ $post->category->name }}</a>
            @endif
            <h1 class="font-display text-3xl sm:text-5xl font-extrabold text-slate-900 leading-tight mt-4">{{ $post->title }}</h1>
            <p class="mt-4 text-sm text-slate-500">
                @if($post->shop)
                    <a href="{{ route('shops.show', $post->shop->shop_slug) }}" class="font-semibold text-slate-700 hover:text-primary">{{ $post->shop->shop_name }}</a>
                    <span> · </span>
                @else
                    <span class="font-semibold text-slate-700">BluLavelle</span>
                    <span> · </span>
                @endif
                {{ $post->published_at ? $post->published_at->format('M j, Y') : 'Draft' }}
                <span> · </span>
                {{ $post->reading_time ?? 1 }} min read
            </p>
        </header>

        @if($showLeadImage)
            <div class="max-w-3xl mx-auto px-6 mb-8">
                <img src="{{ $featuredUrl }}" alt="{{ $post->title }}" class="w-full rounded-2xl object-cover max-h-[32rem] bg-slate-100" width="1200" height="800">
            </div>
        @endif

        <div class="max-w-3xl mx-auto px-6 pb-16">
            <div class="blog-article bg-white rounded-3xl border border-primary/10 shadow-sm px-6 py-8 sm:px-10 sm:py-12">
                {!! \App\Support\Seo::rootRelativeHrefs(\App\Support\Seo::demoteExtraHeadings($body)) !!}
            </div>

            @if($gallery->isNotEmpty())
                <div class="grid grid-cols-2 gap-4 mt-8">
                    @foreach($gallery as $imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $post->title }}" class="w-full aspect-square object-cover rounded-2xl bg-slate-100" loading="lazy" decoding="async">
                    @endforeach
                </div>
            @endif

            @if($post->tags->isNotEmpty())
                <div class="flex flex-wrap gap-2 mt-8">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('blog.tag', $tag->slug) }}" class="inline-flex px-3 py-1.5 rounded-full bg-white border border-slate-200 text-sm text-slate-700 hover:border-primary hover:text-primary transition">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-3 mt-8 text-sm">
                <span class="font-extrabold uppercase tracking-widest text-[11px] text-slate-400">Share</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener noreferrer" class="px-4 py-2 rounded-full bg-white border border-slate-200 font-semibold text-slate-700 hover:border-primary hover:text-primary">Facebook</a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener noreferrer" class="px-4 py-2 rounded-full bg-white border border-slate-200 font-semibold text-slate-700 hover:border-primary hover:text-primary">X</a>
            </div>
        </div>
    </article>

    @if($relatedPosts->isNotEmpty())
        <section class="max-w-7xl mx-auto px-6 lg:px-20 pb-24">
            <h2 class="font-display text-2xl font-extrabold text-slate-900 mb-8">Related articles</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($relatedPosts as $post)
                    @include('posts.partials.card', ['post' => $post])
                @endforeach
            </div>
        </section>
    @endif
</main>

<style>
    .blog-article { color: #334155; font-size: 1.05rem; line-height: 1.75; }
    .blog-article > :first-child { margin-top: 0; }
    .blog-article p { margin: 0 0 1rem; }
    .blog-article h2 { font-family: "Plus Jakarta Sans", sans-serif; color: #0f172a; font-size: 1.5rem; font-weight: 800; line-height: 1.3; margin: 2rem 0 0.75rem; }
    .blog-article ul { margin: 0 0 1.25rem; padding-left: 1.25rem; list-style: disc; }
    .blog-article li { margin: 0.35rem 0; }
    .blog-article a { color: #0195FE; font-weight: 700; text-decoration: underline; text-underline-offset: 3px; }
    .blog-article figure { margin: 1.5rem 0; }
    .blog-article img { width: 100%; border-radius: 1rem; background: #f1f5f9; }
    .blog-article figcaption { margin-top: 0.5rem; font-size: 0.8rem; color: #64748b; }
</style>
@endsection
