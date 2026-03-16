@extends('layouts.admin')

@section('title', 'Post Tags Management')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Post Tags</h1>
            <p class="text-gray-600 mt-2">Quản lý tags cho bài viết</p>
        </div>
        <a href="{{ route('admin.post-tags.create') }}" 
           class="bg-[#F0427C] hover:bg-[#d6386a] text-white px-6 py-3 rounded-lg font-semibold transition-colors flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>Add New Tag</span>
        </a>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
            <div class="flex">
                <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p>{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <!-- Tags Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($tags as $tag)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        @if($tag->color)
                            <span class="w-8 h-8 rounded-full border-2 border-gray-200" style="background-color: {{ $tag->color }}"></span>
                        @endif
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $tag->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $tag->slug }}</p>
                        </div>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ $tag->posts_count }}
                    </span>
                </div>

                @if($tag->description)
                    <p class="text-sm text-gray-600 mb-4">{{ Str::limit($tag->description, 100) }}</p>
                @endif

                <div class="flex space-x-2 pt-4 border-t">
                    <a href="{{ route('admin.post-tags.edit', $tag) }}" 
                       class="flex-1 text-center px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors text-sm font-medium">
                        Edit
                    </a>
                    <form action="{{ route('admin.post-tags.destroy', $tag) }}" method="POST" 
                          onsubmit="return confirm('Are you sure?');" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors text-sm font-medium">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl shadow-sm p-12 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <p class="text-lg font-medium text-gray-900">No tags found</p>
                <p class="text-gray-600 mt-2">Create your first post tag to get started!</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($tags->hasPages())
        <div class="mt-6">
            {{ $tags->links() }}
        </div>
    @endif
</div>
@endsection

