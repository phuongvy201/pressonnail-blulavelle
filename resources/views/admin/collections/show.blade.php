@extends('layouts.admin')

@section('title', $collection->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-lg p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center">
                    📚 {{ $collection->name }}
                    @if($collection->featured)
                        <span class="ml-3 inline-flex items-center px-3 py-1 bg-white text-purple-600 rounded-lg text-sm font-semibold">
                            ⭐ Featured
                        </span>
                    @endif
                </h1>
                <p class="text-purple-100 mt-2">{{ $collection->description ?: 'No description provided.' }}</p>
                <div class="flex items-center space-x-4 mt-4">
                    <span class="text-sm bg-white bg-opacity-20 px-3 py-1 rounded-lg">
                        {{ $collection->active_products_count }} products
                    </span>
                    <span class="text-sm bg-white bg-opacity-20 px-3 py-1 rounded-lg">
                        {{ $collection->type === 'manual' ? '📝 Manual' : '🤖 Automatic' }}
                    </span>
                    <span class="text-sm bg-white bg-opacity-20 px-3 py-1 rounded-lg">
                        {{ ucfirst($collection->status) }}
                    </span>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                @if($collection->canEdit())
                    <a href="{{ route('admin.collections.edit', $collection) }}" 
                       class="px-6 py-3 bg-white text-purple-600 font-semibold rounded-lg hover:bg-purple-50 transition shadow-lg">
                        ✏️ Edit Collection
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Collection Image -->
    @if($collection->image)
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <img src="{{ $collection->image }}" alt="{{ $collection->name }}" class="w-full h-64 object-cover">
        </div>
    @endif

    <!-- Collection Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Collection Info -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Collection Details</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Slug</label>
                        <p class="text-sm text-gray-900 font-mono">{{ $collection->slug }}</p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Created</label>
                        <p class="text-sm text-gray-900">{{ $collection->created_at->format('M d, Y') }}</p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Last Updated</label>
                        <p class="text-sm text-gray-900">{{ $collection->updated_at->format('M d, Y') }}</p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Owner</label>
                        <div class="flex items-center space-x-2 mt-1">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs">
                                {{ substr($collection->user->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $collection->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $collection->user->email }}</p>
                            </div>
                        </div>
                    </div>
                    
                    @if($collection->meta_title || $collection->meta_description)
                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">SEO Information</h4>
                            
                            @if($collection->meta_title)
                                <div class="mb-3">
                                    <label class="text-sm font-medium text-gray-500">Meta Title</label>
                                    <p class="text-sm text-gray-900">{{ $collection->meta_title }}</p>
                                </div>
                            @endif
                            
                            @if($collection->meta_description)
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Meta Description</label>
                                    <p class="text-sm text-gray-900">{{ $collection->meta_description }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Products in Collection -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Products in Collection</h3>
                    @if($collection->canEdit())
                        <a href="{{ route('admin.collections.edit', $collection) }}" 
                           class="text-purple-600 hover:text-purple-700 font-semibold text-sm">
                            Manage Products →
                        </a>
                    @endif
                </div>

                @if($collection->products->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($collection->products as $product)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-start space-x-4">
                                <!-- Product Image -->
                                <div class="w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center text-2xl flex-shrink-0">
                                    📦
                                </div>
                                
                                <!-- Product Info -->
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-900 truncate">{{ $product->name }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">SKU: {{ $product->slug }}</p>
                                    <p class="text-sm font-bold text-purple-600 mt-1">${{ number_format($product->getEffectivePrice(), 2) }}</p>
                                    
                                    <!-- Product Status -->
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold mt-2
                                        {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : 
                                           ($product->status === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800') }}">
                                        {{ ucfirst($product->status) }}
                                    </span>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex flex-col space-y-1">
                                    <a href="{{ route('admin.products.show', $product) }}" 
                                       class="text-xs text-purple-600 hover:text-purple-700 font-semibold">
                                        View
                                    </a>
                                    @if($product->canEdit())
                                        <a href="{{ route('admin.products.edit', $product) }}" 
                                           class="text-xs text-blue-600 hover:text-blue-700 font-semibold">
                                            Edit
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">No products in collection</h4>
                        <p class="text-gray-500 mb-4">This collection doesn't have any products yet.</p>
                        @if($collection->canEdit())
                            <a href="{{ route('admin.collections.edit', $collection) }}" 
                               class="inline-flex items-center px-4 py-2 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Products
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions -->
    @if($collection->canEdit())
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Collection Actions</h3>
            
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.collections.edit', $collection) }}" 
                   class="inline-flex items-center px-4 py-2 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition">
                    ✏️ Edit Collection
                </a>
                
                <form action="{{ route('admin.collections.toggle-featured', $collection) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 {{ $collection->featured ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} font-semibold rounded-lg transition">
                        {{ $collection->featured ? '⭐ Remove Featured' : '⭐ Make Featured' }}
                    </button>
                </form>
                
                @if($collection->canDelete())
                    <form action="{{ route('admin.collections.destroy', $collection) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this collection? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition">
                            🗑️ Delete Collection
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
