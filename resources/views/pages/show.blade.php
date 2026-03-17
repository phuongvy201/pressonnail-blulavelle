@extends('layouts.app')

@section('title', $page->meta_title ?? $page->title)
@section('meta_description', $page->meta_description ?? $page->excerpt)

@section('content')
@php
    // If page content already includes its own Tailwind layout (seeded pages),
    // don't wrap it in an extra narrow container/card/prose.
    $content = (string) ($page->content ?? '');
    $hasOwnLayout =
        str_contains($content, 'max-w-') ||
        str_contains($content, 'mx-auto') ||
        str_contains($content, 'bg-gradient') ||
        str_contains($content, 'rounded-') ||
        str_contains($content, 'shadow-');
@endphp
<!-- Page Header -->
<div class="bg-gradient-to-r from-[#0297FE] to-[#d6386a] text-white py-16">
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-12 2xl:px-20 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $page->title }}</h1>
        @if($page->excerpt)
            <p class="text-xl text-gray-100">{{ $page->excerpt }}</p>
        @endif
        <div class="flex items-center justify-center space-x-4 text-sm text-gray-200 mt-6">
            <span class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ $page->updated_at->format('M d, Y') }}
            </span>
            <span class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                {{ number_format($page->views) }} views
            </span>
        </div>
    </div>
</div>

<!-- Page Content -->
<div class="bg-gray-50 py-12">
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-12 2xl:px-20">
        @if(!$hasOwnLayout)
            <div class="bg-white rounded-2xl shadow-sm p-8 md:p-12">
        @endif
            @if($page->featured_image)
                <div class="mb-8 rounded-xl overflow-hidden">
                    <img src="{{ Storage::url($page->featured_image) }}" alt="{{ $page->title }}" class="w-full h-auto">
                </div>
            @endif

            @if($hasOwnLayout)
                {!! $page->content !!}
            @else
                <div class="prose prose-lg max-w-none">
                    {!! $page->content !!}
                </div>
            @endif
        @if(!$hasOwnLayout)
            </div>
        @endif

        <!-- Child Pages -->
        @if($childPages->isNotEmpty())
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Pages</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($childPages as $child)
                        <a href="{{ route('page.show', $child->slug) }}" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-all">
                            <h3 class="text-lg font-bold text-gray-900 mb-2 hover:text-[#005366]">{{ $child->title }}</h3>
                            @if($child->excerpt)
                                <p class="text-gray-600 text-sm">{{ Str::limit($child->excerpt, 120) }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

