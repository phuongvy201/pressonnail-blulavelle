@extends('layouts.app')

@section('title', 'Press-On Nail Guides')
@section('meta_description', 'Guides for handmade press-on nails: how to size, apply, and care for reusable sets, plus stories from BluLavelle artists.')

@section('content')
<main class="bg-background-light">
    <nav class="max-w-7xl mx-auto px-6 lg:px-20 py-4 flex items-center gap-2 text-xs font-medium text-slate-400 uppercase tracking-widest">
        <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-600">Blog</span>
    </nav>

    <section class="bg-primary/5 py-12 sm:py-16 mb-10">
        <div class="max-w-7xl mx-auto px-6 lg:px-20 text-center">
            <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 mb-4">Press-On Nail Guides</h1>
            <p class="text-slate-600 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed">
                How to size, apply, and care for handmade sets, plus stories from BluLavelle artists.
            </p>
            <form method="GET" class="mt-8 max-w-3xl mx-auto">
                <div class="flex flex-col sm:flex-row gap-3 items-stretch">
                    <label class="relative flex-1">
                        <span class="sr-only">Search articles</span>
                        <svg class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search articles" class="w-full pl-12 pr-4 py-3 rounded-full border border-slate-200 bg-white shadow-sm focus:ring-2 focus:ring-primary focus:border-transparent text-sm">
                    </label>
                    <label class="sm:w-48">
                        <span class="sr-only">Sort articles</span>
                        <select name="sort" class="w-full py-3 px-4 rounded-full border border-slate-200 bg-white shadow-sm focus:ring-2 focus:ring-primary focus:border-transparent text-sm font-semibold text-slate-900">
                            <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest</option>
                            <option value="popular" @selected(request('sort') === 'popular')>Most viewed</option>
                            <option value="trending" @selected(request('sort') === 'trending')>Trending</option>
                        </select>
                    </label>
                    <button type="submit" class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-primary text-white font-extrabold shadow-sm hover:opacity-95 transition">Search</button>
                </div>
            </form>
        </div>
    </section>

    @if($categories->isNotEmpty())
        <section class="max-w-7xl mx-auto px-6 lg:px-20 mb-12">
            <div class="flex flex-wrap justify-center gap-3 py-6 border-y border-primary/10">
                <a href="{{ route('blog.index') }}" class="inline-flex items-center px-4 py-2 rounded-full bg-primary text-white text-xs sm:text-sm font-extrabold uppercase tracking-widest">All</a>
                @foreach($categories as $category)
                    <a href="{{ route('blog.category', $category->slug) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-primary/10 text-slate-900 shadow-sm hover:bg-primary hover:text-white transition">
                        <span class="text-xs sm:text-sm font-extrabold uppercase tracking-widest">{{ $category->name }}</span>
                        <span class="text-[11px] font-bold opacity-70">({{ $category->posts_count }})</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="max-w-7xl mx-auto px-6 lg:px-20 pb-24">
        @if($posts->isEmpty())
            <div class="text-center py-16">
                <h2 class="font-display text-2xl font-extrabold text-slate-900">No articles found</h2>
                <p class="mt-2 text-slate-600">Try another search, or come back for a new guide.</p>
            </div>
        @else
            @if($featuredPosts->isNotEmpty() && !request()->filled('search'))
                <h2 class="font-display text-sm font-extrabold uppercase tracking-widest text-slate-400 mb-6">Featured</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10 mb-14">
                    @foreach($featuredPosts->take(3) as $post)
                        @include('posts.partials.card', ['post' => $post])
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10">
                @foreach($posts as $post)
                    @include('posts.partials.card', ['post' => $post])
                @endforeach
            </div>

            <div class="mt-12">
                {{ $posts->links() }}
            </div>
        @endif

        @if($popularTags->isNotEmpty())
            <div class="mt-16 pt-8 border-t border-primary/10">
                <h2 class="font-display text-sm font-extrabold uppercase tracking-widest text-slate-400 mb-4">Tags</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($popularTags as $tag)
                        <a href="{{ route('blog.tag', $tag->slug) }}" class="inline-flex px-3 py-1.5 rounded-full bg-white border border-slate-200 text-sm text-slate-700 hover:border-primary hover:text-primary transition">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</main>
@endsection
