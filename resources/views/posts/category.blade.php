@extends('layouts.app')

@section('title', \Illuminate\Support\Str::contains(strtolower($category->name), 'guide') ? $category->name.' for Press-On Nails' : $category->name.' Press-On Articles')
@section('meta_description', $category->description ?: 'BluLavelle articles in '.$category->name.'.')

@section('content')
<main class="bg-background-light">
    <nav class="max-w-7xl mx-auto px-6 lg:px-20 py-4 flex items-center gap-2 text-xs font-medium text-slate-400 uppercase tracking-widest">
        <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a class="hover:text-primary transition-colors" href="{{ route('blog.index') }}">Blog</a>
        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-600">{{ $category->name }}</span>
    </nav>

    <section class="bg-primary/5 py-12 sm:py-16 mb-10">
        <div class="max-w-7xl mx-auto px-6 lg:px-20 text-center">
            <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 mb-4">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-slate-600 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed">{{ $category->description }}</p>
            @endif
            <p class="mt-4 text-xs font-extrabold uppercase tracking-widest text-slate-400">{{ $posts->total() }} articles</p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 lg:px-20 pb-24">
        @if($posts->isEmpty())
            <div class="text-center py-16">
                <h2 class="font-display text-2xl font-extrabold text-slate-900">No articles in this category yet</h2>
                <a href="{{ route('blog.index') }}" class="inline-flex mt-6 px-6 py-3 rounded-full bg-primary text-white font-extrabold">Back to the blog</a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10">
                @foreach($posts as $post)
                    @include('posts.partials.card', ['post' => $post])
                @endforeach
            </div>
            <div class="mt-12">{{ $posts->links() }}</div>
        @endif
    </section>
</main>
@endsection
