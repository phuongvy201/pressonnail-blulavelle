@extends('layouts.admin')

@section('title', 'Edit Post Tag')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-4 mb-4">
            <a href="{{ route('admin.post-tags.index') }}" 
               class="text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Edit Post Tag</h1>
        </div>
        <p class="text-gray-600">Chỉnh sửa tag: {{ $postTag->name }}</p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.post-tags.update', $postTag) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-lg p-6 space-y-6">
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                    Tag Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $postTag->name) }}" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent @error('name') border-red-500 @enderror"
                       placeholder="e.g., Tutorial, News, Tips">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                    Description
                </label>
                <textarea id="description" name="description" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent @error('description') border-red-500 @enderror"
                          placeholder="Optional description for this tag">{{ old('description', $postTag->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Color -->
            <div>
                <label for="color" class="block text-sm font-semibold text-gray-700 mb-2">
                    Tag Color (Hex)
                </label>
                <div class="flex space-x-2">
                    <input type="color" id="color" name="color" value="{{ old('color', $postTag->color ?? '#005366') }}"
                           class="w-16 h-12 border border-gray-300 rounded-lg cursor-pointer">
                    <input type="text" id="color-text" name="color" value="{{ old('color', $postTag->color ?? '#005366') }}"
                           class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent"
                           placeholder="#005366">
                </div>
                <p class="text-sm text-gray-500 mt-1">This color will be used to display the tag</p>
            </div>

            <!-- Stats -->
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-600">
                    <span class="font-semibold">Posts using this tag:</span> 
                    <span class="text-blue-600">{{ $postTag->posts_count }}</span>
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('admin.post-tags.index') }}" 
               class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit" 
                    class="bg-[#F0427C] hover:bg-[#d6386a] text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                Update Tag
            </button>
        </div>
    </form>
</div>

<script>
// Sync color picker with text input
document.getElementById('color').addEventListener('input', function(e) {
    document.getElementById('color-text').value = e.target.value;
});
document.getElementById('color-text').addEventListener('input', function(e) {
    if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
        document.getElementById('color').value = e.target.value;
    }
});
</script>
@endsection

