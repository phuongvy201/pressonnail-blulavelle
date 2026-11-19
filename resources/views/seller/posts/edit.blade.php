@extends('layouts.admin')

@section('title', 'Edit Post')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Edit Blog Post</h1>
        <p class="text-gray-600">Edit: {{ $post->title }}</p>
    </div>

    <form id="blog-post-form" action="{{ route('admin.posts.update', $post) }}" method="POST" enctype="multipart/form-data" class="max-w-5xl">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-3 gap-6">
            <!-- Main Content (2/3) -->
            <div class="col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow p-6 space-y-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Post Title *</label>
                        <input type="text" name="title" value="{{ old('title', $post->title) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366]">
                        @error('title')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Content -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content *</label>
                        <textarea name="content" id="content" rows="20"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366]">{{ old('content', $post->content) }}</textarea>
                        @error('content')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Excerpt -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
                        <textarea name="excerpt" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg">{{ old('excerpt', $post->excerpt) }}</textarea>
                    </div>
                </div>

                <!-- SEO Section -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">SEO Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                            <input type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                            <textarea name="meta_description" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg">{{ old('meta_description', $post->meta_description) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Keywords</label>
                            <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $post->meta_keywords) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar (1/3) -->
            <div class="col-span-1 space-y-6">
                <!-- Publish Settings -->
                <div class="bg-white rounded-lg shadow p-6 space-y-4">
                    <h3 class="font-semibold text-gray-900">Publish</h3>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="draft" {{ old('status', $post->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $post->status) == 'published' ? 'selected' : '' }}>Publish</option>
                            <option value="scheduled" {{ old('status', $post->status) == 'scheduled' ? 'selected' : '' }}>Schedule</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Publish Date</label>
                        <input type="datetime-local" name="published_at" 
                               value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-[#0297FE] text-white rounded-lg hover:bg-[#d6386a] text-sm">
                            Update Post
                        </button>
                        <a href="{{ route('admin.posts.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                            Cancel
                        </a>
                    </div>
                </div>

                <!-- Category & Tags -->
                <div class="bg-white rounded-lg shadow p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="post_category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">No Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('post_category_id', $post->post_category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                        <select name="tags[]" multiple class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm" size="5">
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', $post->tags->pluck('id')->toArray())) ? 'selected' : '' }}>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Ctrl/Cmd + click for multiple</p>
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="bg-white rounded-lg shadow p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
                    @if($post->featured_image)
                        <img src="{{ $post->featured_image_url }}" alt="" class="w-full h-32 object-cover rounded mb-2">
                    @endif
                    <input type="file" name="featured_image" accept="image/*" class="w-full text-sm">
                </div>

                <!-- Gallery -->
                <div class="bg-white rounded-lg shadow p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gallery</label>
                    @if($post->gallery && count($post->gallery) > 0)
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            @foreach($post->gallery_urls as $imageUrl)
                                <img src="{{ $imageUrl }}" alt="" class="w-full h-20 object-cover rounded">
                            @endforeach
                        </div>
                    @endif
                    <input type="file" name="gallery[]" accept="image/*" multiple class="w-full text-sm">
                </div>

                <!-- Post Type -->
                <div class="bg-white rounded-lg shadow p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Post Type</label>
                    <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="article" {{ old('type', $post->type) == 'article' ? 'selected' : '' }}>Article</option>
                        <option value="video" {{ old('type', $post->type) == 'video' ? 'selected' : '' }}>Video</option>
                        <option value="gallery" {{ old('type', $post->type) == 'gallery' ? 'selected' : '' }}>Gallery</option>
                        <option value="product_review" {{ old('type', $post->type) == 'product_review' ? 'selected' : '' }}>Product Review</option>
                    </select>
                </div>

                <!-- Options -->
                <div class="bg-white rounded-lg shadow p-6 space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="featured" value="1" {{ old('featured', $post->featured) ? 'checked' : '' }}
                               class="w-4 h-4 text-[#005366] rounded">
                        <span class="ml-2 text-sm text-gray-700">Featured Post</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="allow_comments" value="1" {{ old('allow_comments', $post->allow_comments) ? 'checked' : '' }}
                               class="w-4 h-4 text-[#005366] rounded">
                        <span class="ml-2 text-sm text-gray-700">Allow Comments</span>
                    </label>
                </div>

                <!-- Stats -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 mb-3">Statistics</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Views:</span>
                            <span class="font-semibold">{{ number_format($post->views) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Likes:</span>
                            <span class="font-semibold">{{ number_format($post->likes) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Comments:</span>
                            <span class="font-semibold">{{ number_format($post->comments_count) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Reading Time:</span>
                            <span class="font-semibold">{{ $post->reading_time }} min</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@include('seller.posts.partials.editor')
