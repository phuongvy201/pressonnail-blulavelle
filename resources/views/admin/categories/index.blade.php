@extends('layouts.admin')

@section('title', 'Categories Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Categories Management</h1>
            <p class="mt-1 text-sm text-gray-600">Manage product categories and subcategories</p>
        </div>
        <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-3">
            <a href="{{ route('admin.category-cross-sells.index') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                Cross-Sell Rules
            </a>
            <a href="{{ route('admin.categories.featured') }}" 
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
                Manage Featured
            </a>
            <a href="{{ route('admin.categories.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add New Category
            </a>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-1/12">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-2/5">Category</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-1/6">Parent</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-1/6">Templates</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-1/6">Created</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider w-1/12">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($categories as $category)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $category->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    @if($category->image)
                                        <img class="h-10 w-10 rounded-xl object-cover" src="{{ $category->image }}" alt="{{ $category->name }}">
                                    @else
                                        <div class="h-10 w-10 rounded-xl bg-gradient-to-r from-green-500 to-blue-600 flex items-center justify-center shadow-sm">
                                            <span class="text-sm font-bold text-white">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900">{{ $category->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $category->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($category->parent)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                    {{ $category->parent->name }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                    Root Category
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                {{ $category->templates_count ?? 0 }} templates
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $category->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-1">
                                <a href="{{ route('admin.categories.edit', $category) }}" 
                                   class="inline-flex items-center px-3 py-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
                                   title="Edit category">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="inline-flex items-center px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete category">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                                    <span class="text-2xl text-gray-400">📁</span>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">No categories found</h3>
                                <p class="text-gray-500 mb-6">Get started by creating a new category.</p>
                                <a href="{{ route('admin.categories.create') }}" 
                                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition-colors shadow-sm">
                                    Add First Category
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($categories->hasPages())
    <div class="bg-white px-6 py-4 flex items-center justify-between border-t border-gray-200 rounded-b-xl">
        <div class="flex-1 flex justify-between sm:hidden">
            @if($categories->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-500 bg-white cursor-not-allowed">
                    Previous
                </span>
            @else
                <a href="{{ $categories->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    Previous
                </a>
            @endif

            @if($categories->hasMorePages())
                <a href="{{ $categories->nextPageUrl() }}" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    Next
                </a>
            @else
                <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-500 bg-white cursor-not-allowed">
                    Next
                </span>
            @endif
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-600">
                    Showing
                    <span class="font-semibold text-gray-900">{{ $categories->firstItem() }}</span>
                    to
                    <span class="font-semibold text-gray-900">{{ $categories->lastItem() }}</span>
                    of
                    <span class="font-semibold text-gray-900">{{ $categories->total() }}</span>
                    results
                </p>
            </div>
            <div>
                {{ $categories->links() }}
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
