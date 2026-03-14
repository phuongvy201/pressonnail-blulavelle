@extends('layouts.admin')

@section('title', 'Create Shipping Zone')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Create Shipping Zone</h1>
            <p class="mt-1 text-sm text-gray-600">Tạo vùng shipping mới theo quốc gia</p>
        </div>
        <a href="{{ route('admin.shipping-zones.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Zones
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.shipping-zones.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
            <!-- Zone Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                    Zone Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                       placeholder="e.g., United States, Europe, Asia Pacific"
                       required>
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Countries -->
            <div>
                <label for="countries" class="block text-sm font-semibold text-gray-700 mb-2">
                    Country Codes <span class="text-red-500">*</span>
                </label>
                <textarea name="countries" 
                          id="countries" 
                          rows="4"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('countries') border-red-500 @enderror"
                          placeholder="US, CA, GB, AT (mã quốc gia ISO 2 chữ, phân cách bằng dấu phẩy)"
                          required>{{ old('countries') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Nhập mã quốc gia ISO 2 chữ (VD: US, VN, GB), phân cách bằng dấu phẩy
                </p>
                @error('countries')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                    Description
                </label>
                <textarea name="description" 
                          id="description" 
                          rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Shipping zone description...">{{ old('description') }}</textarea>
            </div>

            <!-- Sort Order -->
            <div>
                <label for="sort_order" class="block text-sm font-semibold text-gray-700 mb-2">
                    Sort Order
                </label>
                <input type="number" 
                       name="sort_order" 
                       id="sort_order" 
                       value="{{ old('sort_order', 0) }}"
                       min="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="0">
                <p class="mt-1 text-xs text-gray-500">Lower numbers appear first</p>
            </div>

            <!-- Is Active -->
            <div class="flex items-center">
                <input type="checkbox" 
                       name="is_active" 
                       id="is_active" 
                       value="1"
                       {{ old('is_active', true) ? 'checked' : '' }}
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-700">
                    Active (zone có thể sử dụng)
                </label>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('admin.shipping-zones.index') }}" 
               class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Create Zone
            </button>
        </div>
    </form>
</div>
@endsection

