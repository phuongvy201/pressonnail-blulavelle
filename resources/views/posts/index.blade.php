@extends('layouts.app')

@section('title', 'Blog')

@section('content')
<!-- Blog Header -->
<div class="bg-gradient-to-r from-[#F0427C] to-[#d6386a] text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Our Blog</h1>
            <p class="text-xl text-gray-100 max-w-2xl mx-auto">
                Stories, tips, and insights from our community
            </p>
        </div>
    </div>
</div>

<div class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Filter Bar -->
                <div class="bg-white rounded-2xl shadow-sm p-4 mb-8">
                    <form method="GET" class="flex flex-col md:flex-row gap-4">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="Search articles..." 
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366]">
                        <select name="sort" 
                                onchange="this.form.submit()"
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366]">
                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                            <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Most Viewed</option>
                            <option value="trending" {{ request('sort') == 'trending' ? 'selected' : '' }}>Trending</option>
                        </select>
                        <button type="submit" class="px-6 py-2 bg-[#F0427C] text-white rounded-lg hover:bg-[#d6386a] transition">
                            Apply
                        </button>
                    </form>
                </div>

                <!-- Posts Grid -->
                @if($posts->isEmpty())
                    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
                        <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">No posts found</h2>
                        <p class="text-gray-600">Check back later for new content!</p>
                    </div>
                @else
                    <div class="space-y-8">
                        @foreach($posts as $post)
                            <article class="bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-xl transition-all">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- Post Image -->
                                    @if($post->featured_image)
                                        <a href="{{ route('blog.show', $post->slug) }}" class="md:col-span-1">
                                            <div class="aspect-[4/3] overflow-hidden bg-gray-100">
                                                <img src="{{ $post->featured_image_url }}" 
                                                     alt="{{ $post->title }}"
                                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                            </div>
                                        </a>
                                    @endif

                                    <!-- Post Content -->
                                    <div class="md:col-span-2 p-6">
                                        @if($post->sticky)
                                            <span class="inline-block px-3 py-1 bg-yellow-400 text-gray-900 text-xs font-semibold rounded-full mb-3">
                                                📌 Pinned
                                            </span>
                                        @endif

                                        <div class="flex items-center space-x-3 text-sm text-gray-500 mb-3">
                                            @if($post->category)
                                                <a href="{{ route('blog.category', $post->category->slug) }}" class="text-[#F0427C] hover:text-[#d6386a] font-semibold">
                                                    {{ $post->category->name }}
                                                </a>
                                            @endif
                                            @if($post->published_at)
                                                <span>•</span>
                                                <span>{{ $post->published_at->format('M d, Y') }}</span>
                                            @endif
                                            <span>•</span>
                                            <span>{{ $post->reading_time ?? 1 }} min read</span>
                                        </div>

                                        <h2 class="text-2xl font-bold text-gray-900 mb-3 hover:text-[#005366] transition">
                                            <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                                        </h2>

                                        @if($post->excerpt)
                                            <p class="text-gray-600 mb-4">{{ $post->excerpt }}</p>
                                        @endif

                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                                @if($post->shop)
                                                    <a href="{{ route('shops.show', $post->shop->shop_slug) }}" class="flex items-center hover:text-[#005366]">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                        </svg>
                                                        {{ $post->shop->shop_name }}
                                                    </a>
                                                @endif
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    {{ number_format($post->views) }}
                                                </span>
                                            </div>

                                            <a href="{{ route('blog.show', $post->slug) }}" class="text-[#F0427C] hover:text-[#d6386a] font-semibold text-sm flex items-center">
                                                Read More
                                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $posts->links() }}
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Featured Posts -->
                @if($featuredPosts->isNotEmpty())
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            Featured
                        </h3>
                        <div class="space-y-4">
                            @foreach($featuredPosts as $featured)
                                <a href="{{ route('blog.show', $featured->slug) }}" class="block group">
                                    <h4 class="font-semibold text-gray-900 group-hover:text-[#005366] mb-1 line-clamp-2">
                                        {{ $featured->title }}
                                    </h4>
                                    <p class="text-xs text-gray-500">{{ $featured->published_at ? $featured->published_at->format('M d, Y') : 'Draft' }}</p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Categories -->
                @if($categories->isNotEmpty())
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Categories</h3>
                        <div class="space-y-2">
                            @foreach($categories as $category)
                                <a href="{{ route('blog.category', $category->slug) }}" class="flex items-center justify-between text-gray-700 hover:text-[#005366] transition">
                                    <span>{{ $category->name }}</span>
                                    <span class="text-sm text-gray-500">({{ $category->posts_count }})</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Tags -->
                @if($popularTags->isNotEmpty())
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Popular Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($popularTags as $tag)
                                <a href="{{ route('blog.tag', $tag->slug) }}" class="inline-block px-3 py-1 bg-gray-100 hover:bg-[#005366] hover:text-white text-gray-700 text-sm rounded-full transition">
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

