@extends('layouts.admin')

@section('title', 'Edit Post Category')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-4 mb-4">
            <a href="{{ route('admin.post-categories.index') }}" 
               class="text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Edit Post Category</h1>
        </div>
        <p class="text-gray-600">Chỉnh sửa danh mục: {{ $postCategory->name }}</p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.post-categories.update', $postCategory) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-lg p-6 space-y-6">
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                    Category Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $postCategory->name) }}" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                    Description
                </label>
                <textarea id="description" name="description" rows="4"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description', $postCategory->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Parent Category -->
            <div>
                <label for="parent_id" class="block text-sm font-semibold text-gray-700 mb-2">
                    Parent Category (Optional)
                </label>
                <select id="parent_id" name="parent_id"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent">
                    <option value="">None (Main Category)</option>
                    @foreach($parentCategories as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id', $postCategory->parent_id) == $parent->id ? 'selected' : '' }}>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Current Image -->
            @if($postCategory->image)
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Current Image</label>
                    <img src="{{ $postCategory->image }}" alt="{{ $postCategory->name }}" class="w-32 h-32 object-cover rounded-lg">
                </div>
            @endif

            <!-- Image Upload -->
            <div>
                <label for="image" class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ $postCategory->image ? 'Change Image' : 'Category Image' }}
                </label>
                <input type="file" id="image" name="image" accept="image/*"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent">
                <p class="text-sm text-gray-500 mt-1">Recommended: 400x400px, Max 5MB</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Color -->
                <div>
                    <label for="color" class="block text-sm font-semibold text-gray-700 mb-2">
                        Color (Hex)
                    </label>
                    <div class="flex space-x-2">
                        <input type="color" id="color" name="color" value="{{ old('color', $postCategory->color ?? '#005366') }}"
                               class="w-16 h-12 border border-gray-300 rounded-lg cursor-pointer">
                        <input type="text" id="color-text" name="color" value="{{ old('color', $postCategory->color ?? '#005366') }}"
                               class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent"
                               placeholder="#005366">
                    </div>
                </div>

                <!-- Icon -->
                <div>
                    <label for="icon" class="block text-sm font-semibold text-gray-700 mb-2">
                        Icon (Emoji hoặc class)
                    </label>
                    <input type="text" id="icon" name="icon" value="{{ old('icon', $postCategory->icon) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent"
                           placeholder="📝 hoặc fa-blog">
                </div>
            </div>

            <!-- Sort Order -->
            <div>
                <label for="sort_order" class="block text-sm font-semibold text-gray-700 mb-2">
                    Sort Order
                </label>
                <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $postCategory->sort_order ?? 0) }}" min="0"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent">
            </div>

            <!-- SEO Fields -->
            <div class="border-t pt-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">SEO Settings</h3>
                
                <div>
                    <label for="meta_title" class="block text-sm font-semibold text-gray-700 mb-2">
                        Meta Title
                    </label>
                    <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $postCategory->meta_title) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent">
                </div>

                <div>
                    <label for="meta_description" class="block text-sm font-semibold text-gray-700 mb-2">
                        Meta Description
                    </label>
                    <textarea id="meta_description" name="meta_description" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent">{{ old('meta_description', $postCategory->meta_description) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('admin.post-categories.index') }}" 
               class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit" 
                    class="bg-[#F0427C] hover:bg-[#d6386a] text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                Update Category
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

