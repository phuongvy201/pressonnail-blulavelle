@extends('layouts.app')

@section('title', $post->meta_title ?? $post->title)
@section('meta_description', $post->meta_description ?? $post->excerpt)

@section('content')
<!-- Post Header -->
@if($post->featured_image)
    <div class="relative h-96 bg-gray-900">
        <img src="{{ $post->featured_image_url }}" 
             alt="{{ $post->title }}"
             class="w-full h-full object-cover opacity-60">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                @if($post->category)
                    <a href="{{ route('blog.category', $post->category->slug) }}" class="inline-block px-3 py-1 bg-[#005366] text-white text-sm font-semibold rounded-full mb-4">
                        {{ $post->category->name }}
                    </a>
                @endif
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">{{ $post->title }}</h1>
                <div class="flex items-center space-x-4 text-sm text-gray-200">
                    @if($post->published_at)
                        <span>{{ $post->published_at ? $post->published_at->format('M d, Y') : 'Draft' }}</span>
                        <span>•</span>
                    @endif
                    <span>{{ $post->reading_time ?? 1 }} min read</span>
                    <span>•</span>
                    <span>{{ number_format($post->views ?? 0) }} views</span>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="bg-gradient-to-r from-[#F0427C] to-[#d6386a] text-white py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($post->category)
                <a href="{{ route('blog.category', $post->category->slug) }}" class="inline-block px-3 py-1 bg-white/20 text-white text-sm font-semibold rounded-full mb-4">
                    {{ $post->category->name }}
                </a>
            @endif
            <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $post->title }}</h1>
            <div class="flex items-center space-x-4 text-sm text-gray-200">
                @if($post->published_at)
                    <span>{{ $post->published_at ? $post->published_at->format('M d, Y') : 'Draft' }}</span>
                    <span>•</span>
                @endif
                <span>{{ $post->reading_time ?? 1 }} min read</span>
                <span>•</span>
                <span>{{ number_format($post->views ?? 0) }} views</span>
            </div>
        </div>
    </div>
@endif

<!-- Post Content -->
<div class="bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-sm p-8 md:p-12">
            <!-- Author & Shop Info -->
            @if($post->shop)
                <div class="flex items-center space-x-4 pb-6 mb-6 border-b border-gray-200">
                    <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-100">
                        @if($post->shop->shop_logo)
                            <img src="{{ $post->shop->shop_logo }}" alt="{{ $post->shop->shop_name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-[#005366] text-white font-bold">
                                {{ substr($post->shop->shop_name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('shops.show', $post->shop->shop_slug) }}" class="font-bold text-gray-900 hover:text-[#005366]">
                            {{ $post->shop->shop_name }}
                        </a>
                        <p class="text-sm text-gray-500">Posted by {{ $post->user->name }}</p>
                    </div>
                </div>
            @endif

            <!-- Post Content -->
            <div class="prose prose-lg max-w-none mb-8">
                {!! $post->content !!}
            </div>

            <!-- Gallery -->
            @if($post->gallery && count($post->gallery) > 0)
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Gallery</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($post->gallery_urls as $imageUrl)
                            <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                <img src="{{ $imageUrl }}" alt="Gallery image" class="w-full h-full object-cover">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Tags -->
            @if($post->tags->isNotEmpty())
                <div class="flex flex-wrap gap-2 pt-6 border-t border-gray-200">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('blog.tag', $tag->slug) }}" class="inline-block px-3 py-1 bg-gray-100 hover:bg-[#005366] hover:text-white text-gray-700 text-sm rounded-full transition">
                            #{{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            <!-- Social Share -->
            <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200">
                <div class="flex items-center space-x-4 text-gray-500">
                    <button class="flex items-center space-x-1 hover:text-red-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        <span>{{ number_format($post->likes) }}</span>
                    </button>
                    <span class="flex items-center space-x-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <span>{{ number_format($post->comments_count) }}</span>
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">Share:</span>
                    <button class="p-2 hover:text-blue-600 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </button>
                    <button class="p-2 hover:text-blue-400 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Related Posts -->
        @if($relatedPosts->isNotEmpty())
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Articles</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($relatedPosts as $related)
                        <a href="{{ route('blog.show', $related->slug) }}" class="group">
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-lg transition">
                                @if($related->featured_image)
                                    <div class="aspect-video overflow-hidden bg-gray-100">
                                        <img src="{{ Storage::url($related->featured_image) }}" 
                                             alt="{{ $related->title }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform">
                                    </div>
                                @endif
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-900 group-hover:text-[#005366] line-clamp-2 mb-2">
                                        {{ $related->title }}
                                    </h3>
                                    <p class="text-sm text-gray-500">{{ $related->published_at ? $related->published_at->format('M d, Y') : 'Draft' }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

