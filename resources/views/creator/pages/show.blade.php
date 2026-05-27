@extends('layouts.creator')

@section('title', $page->meta_title ?? $page->title)
@section('meta_description', $page->meta_description ?? $page->excerpt)

@section('content')
    <article class="mx-auto max-w-4xl px-5 py-12 md:px-16 md:py-16">
        <p class="creator-font-label mb-6">
            <a href="{{ route('creator.home') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                Creator home
            </a>
        </p>

        @if (! str_contains((string) $page->content, 'creator-font-headline'))
            <header class="mb-10 border-b border-[#bfc7d5] pb-8">
                <h1 class="creator-font-headline text-3xl font-bold text-[#0b1c30] md:text-4xl">{{ $page->title }}</h1>
                @if ($page->excerpt)
                    <p class="mt-3 text-lg text-[#404753]">{{ $page->excerpt }}</p>
                @endif
                <p class="creator-font-label mt-4 text-xs font-medium uppercase tracking-widest text-[#707884]">
                    Last updated {{ $page->updated_at->format('F d, Y') }}
                </p>
            </header>
        @endif

        <div class="affiliate-policy-content text-[#404753]">
            {!! $page->content !!}
        </div>

        <footer class="mt-12 border-t border-[#bfc7d5] pt-8">
            <p class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#0b1c30]">Related policies</p>
            <ul class="mt-4 flex flex-wrap gap-3">
                @foreach (\App\Models\Page::query()->where('status', 'published')->whereIn('slug', config('creator.affiliate_policy_slugs', []))->orderBy('sort_order')->get() as $related)
                    @if ($related->slug !== $page->slug)
                        <li>
                            <a href="{{ route('creator.policies.show', $related->slug) }}"
                               class="rounded-lg border border-[#bfc7d5] bg-white px-4 py-2 text-sm font-medium text-primary transition-colors hover:border-primary hover:bg-primary/5">
                                {{ $related->title }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </footer>
    </article>
@endsection
