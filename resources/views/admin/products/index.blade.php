@extends('layouts.admin')

@section('title', 'Products Management')

@section('content')
<div class="space-y-6 w-full max-w-full overflow-x-hidden">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Products</h1>
            <p class="mt-1 text-sm text-gray-600">Manage products created from templates</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center space-x-3">
            <!-- Bulk Delete Button (Hidden by default) -->
            <button id="bulkDeleteBtn" onclick="confirmBulkDelete()" 
                    style="display: none;"
                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors shadow-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Delete Selected (<span id="selectedCount">0</span>)
            </button>
            
            @if(($availableGmcConfigs ?? collect())->isNotEmpty())
            <!-- Feed to GMC Button (Hidden by default) -->
            <button id="feedToGMCBtn" onclick="feedToGMC()" 
                    style="display: none;"
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors shadow-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                Feed to GMC (<span id="gmcSelectedCount">0</span>)
            </button>
            @endif
            
            <!-- Export to Meta Button (Hidden by default) -->
            <button id="exportToMetaBtn" onclick="exportToMeta()" 
                    style="display: none;"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors shadow-md"
                    title="Export selected products to Meta Commerce Catalog format (CSV)">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export to Meta (<span id="metaSelectedCount">0</span>)
            </button>
            
            <a href="{{ route('admin.products.import') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors shadow-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                Import Products
            </a>
            <a href="{{ route('admin.products.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors shadow-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add New Product
            </a>
        </div>
    </div>

    <!-- Filters Section - Compact -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <form method="GET" action="{{ route('admin.products.index') }}" id="filterForm">
            <div class="flex items-center gap-3 p-3">
                <!-- Search -->
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Search..."
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Per Page -->
                <div class="w-28">
                    <select name="per_page" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @foreach([12,25,50,100] as $size)
                            <option value="{{ $size }}" {{ (int)request('per_page', $perPage ?? 12) === $size ? 'selected' : '' }}>
                                {{ $size }}/page
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Category -->
                <div class="w-48">
                    <select name="category_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Template -->
                <div class="w-48">
                    <select name="template_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Templates</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}" {{ request('template_id') == $template->id ? 'selected' : '' }}>
                                {{ Str::limit($template->name, 25) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Shop (Admin only) -->
                @if(auth()->user()->hasRole('admin') && $shops)
                <div class="w-48">
                    <select name="shop_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Shops</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                                {{ Str::limit($shop->shop_name, 25) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Collection -->
                <div class="w-48">
                    <select name="collection_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Collections</option>
                        @foreach($collections as $collection)
                            <option value="{{ $collection->id }}" {{ request('collection_id') == $collection->id ? 'selected' : '' }}>
                                {{ Str::limit($collection->name, 25) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Buttons -->
                <div class="flex items-center gap-2">
                    <button type="submit" 
                            class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Filter
                    </button>
                    @if(request()->anyFilled(['category_id', 'template_id', 'shop_id', 'collection_id', 'search']))
                        <a href="{{ route('admin.products.index') }}" 
                           class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Active Filters Display - Compact -->
    @if(request()->anyFilled(['category_id', 'template_id', 'shop_id', 'collection_id', 'search']))
    <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center flex-wrap gap-2">
                <span class="text-xs font-semibold text-blue-900">Active:</span>
                @if(request('search'))
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-200 text-blue-800">
                        Search: "{{ Str::limit(request('search'), 20) }}"
                        <button onclick="removeFilter('search')" class="ml-1.5 text-blue-600 hover:text-blue-900">×</button>
                    </span>
                @endif
                @if(request('category_id'))
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-200 text-blue-800">
                        {{ $categories->firstWhere('id', request('category_id'))->name ?? 'N/A' }}
                        <button onclick="removeFilter('category_id')" class="ml-1.5 text-blue-600 hover:text-blue-900">×</button>
                    </span>
                @endif
                @if(request('template_id'))
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-200 text-blue-800">
                        {{ Str::limit($templates->firstWhere('id', request('template_id'))->name ?? 'N/A', 20) }}
                        <button onclick="removeFilter('template_id')" class="ml-1.5 text-blue-600 hover:text-blue-900">×</button>
                    </span>
                @endif
                @if(request('shop_id') && $shops)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-200 text-blue-800">
                        {{ Str::limit($shops->firstWhere('id', request('shop_id'))->shop_name ?? 'N/A', 20) }}
                        <button onclick="removeFilter('shop_id')" class="ml-1.5 text-blue-600 hover:text-blue-900">×</button>
                    </span>
                @endif
                @if(request('collection_id'))
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-200 text-blue-800">
                        {{ Str::limit($collections->firstWhere('id', request('collection_id'))->name ?? 'N/A', 20) }}
                        <button onclick="removeFilter('collection_id')" class="ml-1.5 text-blue-600 hover:text-blue-900">×</button>
                    </span>
                @endif
            </div>
            <span class="text-xs text-blue-700 font-medium">{{ $products->total() }} found</span>
        </div>
    </div>
    @endif

    <!-- Products Table -->
    @if($products->count() > 0)
    <div class="bg-white shadow-md rounded-xl border border-gray-200 overflow-hidden w-full max-w-full">
        <!-- Scroll Hint -->
        <div class="bg-gradient-to-r from-blue-50 to-green-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-2 text-sm text-gray-600">
                <svg class="w-4 h-4 text-blue-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium">⬅️ Drag horizontally to see more columns ➡️</span>
            </div>
            <div class="text-xs text-gray-500">
                <span class="font-semibold">{{ $products->total() }}</span> products
            </div>
        </div>
        
        <!-- Table Container with Horizontal & Vertical Scroll -->
        <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-280px)] scrollbar-custom" 
             id="productsTableContainer"
             style="overscroll-behavior: contain;">
            <table class="min-w-[1400px] w-full divide-y divide-gray-200" style="table-layout: auto;">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-4 text-center" style="min-width: 60px;">
                            <input type="checkbox" id="selectAll" 
                                   class="w-5 h-5 text-green-600 focus:ring-green-500 border-gray-300 rounded cursor-pointer"
                                   onchange="toggleSelectAll(this)">
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider" style="min-width: 100px;">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                </svg>
                                <span>ID</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider" style="min-width: 300px;">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span>Product</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider" style="min-width: 200px;">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Template</span>
                            </div>
                        </th>
                        @if(auth()->user()->hasRole('admin'))
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider" style="min-width: 200px;">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                <span>Shop</span>
                            </div>
                        </th>
                        @endif
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider" style="min-width: 120px;">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                <span>Price</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider" style="min-width: 120px;">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                <span>Quantity</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider" style="min-width: 120px;">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                <span>Variants</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider" style="min-width: 120px;">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Status</span>
                            </div>
                        </th>
                        @if(auth()->user()->hasRole('admin'))
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider" style="min-width: 200px;">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Owner</span>
                            </div>
                        </th>
                        @endif
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider" style="min-width: 150px;">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($products as $product)
                    @php
                        $canEdit = auth()->user()->hasRole('admin') || $product->user_id === auth()->id();
                    @endphp
                    <tr class="hover:bg-green-50 transition-colors duration-150 {{ !$canEdit ? 'opacity-60' : '' }}" data-product-id="{{ $product->id }}">
                        <!-- Checkbox -->
                        <td class="px-6 py-4 text-center">
                            @if($canEdit)
                                <input type="checkbox" class="product-checkbox w-5 h-5 text-green-600 focus:ring-green-500 border-gray-300 rounded cursor-pointer" 
                                       value="{{ $product->id }}" onchange="updateBulkDeleteButton()">
                            @endif
                        </td>
                        
                        <!-- ID -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold text-sm shadow-sm">
                                #{{ $product->id }}
                            </span>
                        </td>
                        
                        <!-- Product Info -->
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-4">
                                <!-- Thumbnail -->
                                <div class="flex-shrink-0">
                                    @php
                                        // Get first media URL safely
                                        $mediaItem = $product->media && is_array($product->media) && count($product->media) > 0 
                                            ? $product->media[0] 
                                            : ($product->template->media && is_array($product->template->media) && count($product->template->media) > 0 
                                                ? $product->template->media[0] 
                                                : null);
                                        
                                        // Convert to URL string
                                        if (is_string($mediaItem)) {
                                            $mediaUrl = $mediaItem;
                                        } elseif (is_array($mediaItem) && !empty($mediaItem)) {
                                            $mediaUrl = $mediaItem['url'] ?? $mediaItem['path'] ?? reset($mediaItem) ?? null;
                                        } else {
                                            $mediaUrl = null;
                                        }
                                    @endphp
                                    
                                    @if($mediaUrl)
                                        @if(str_contains($mediaUrl, '.mp4') || str_contains($mediaUrl, '.mov') || str_contains($mediaUrl, '.avi'))
                                            <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <img src="{{ $mediaUrl }}" alt="{{ $product->name }}" class="w-16 h-16 rounded-lg object-cover border-2 border-gray-200">
                                        @endif
                                    @else
                                        <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-green-100 to-teal-100 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Info -->
                                <div class="min-w-0 flex-1" style="max-width: 250px;">
                                    <p class="text-sm font-bold text-gray-900 truncate" title="{{ $product->name }}">
                                        {{ Str::limit($product->name, 30) }}
                                    </p>
                                    <p class="text-xs text-gray-500 truncate" title="{{ $product->description ?? $product->template->description }}">
                                        {{ Str::limit($product->description ?? $product->template->description, 40) }}
                                    </p>
                                    @if($product->sku)
                                    <p class="text-xs text-gray-400 mt-1 truncate font-medium" title="{{ $product->sku }}">
                                        SKU: <span class="text-blue-600 font-semibold">{{ $product->sku }}</span>
                                    </p>
                                    @else
                                    <p class="text-xs text-gray-400 mt-1 italic">
                                        SKU: <span class="text-gray-400">Not assigned</span>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        
                        <!-- Template -->
                        <td class="px-6 py-4">
                            <div style="max-width: 200px;">
                                <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $product->template->name }}">
                                    {{ Str::limit($product->template->name, 25) }}
                                </p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        #{{ $product->template->id }}
                                    </span>
                                    <span class="text-xs text-gray-500 truncate" title="{{ $product->template->category->name }}">
                                        {{ Str::limit($product->template->category->name, 15) }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Shop (Admin only) -->
                        @if(auth()->user()->hasRole('admin'))
                        <td class="px-6 py-4">
                            @if($product->shop)
                            <div class="flex items-center space-x-2">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-pink-500 to-rose-600 flex items-center justify-center text-white font-bold shadow-sm">
                                    @if($product->shop->shop_logo)
                                        <img src="{{ $product->shop->shop_logo }}" alt="{{ $product->shop->shop_name }}" class="w-full h-full rounded-lg object-cover">
                                    @else
                                        {{ substr($product->shop->shop_name, 0, 2) }}
                                    @endif
                                </div>
                                <div style="max-width: 150px;">
                                    <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $product->shop->shop_name }}">
                                        {{ Str::limit($product->shop->shop_name, 20) }}
                                    </p>
                                    <div class="flex items-center space-x-1 mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                            {{ $product->shop->shop_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $product->shop->shop_status === 'active' ? '✓ Active' : '⚠ ' . ucfirst($product->shop->shop_status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @else
                                <div class="flex items-center space-x-2 text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <span class="text-xs italic">Chưa gán shop</span>
                                </div>
                            @endif
                        </td>
                        @endif
                        
                        <!-- Price -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $displayPrice = (float) ($product->price ?? $product->template->base_price);
                                $displayListPrice = (float) ($product->list_price ?? $product->template->list_price ?? 0);
                                $showListPrice = $displayListPrice > 0 && $displayListPrice > $displayPrice;
                            @endphp
                            <div class="text-lg font-bold text-green-600">
                                ${{ number_format($displayPrice, 2) }}
                            </div>
                            @if($showListPrice)
                                <p class="text-xs text-gray-500 line-through">
                                    ${{ number_format($displayListPrice, 2) }}
                                </p>
                            @endif
                        </td>
                        
                        <!-- Quantity -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $product->quantity > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $product->quantity }}
                                </span>
                            </div>
                        </td>
                        
                        <!-- Variants -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    {{ $product->variants->count() }} variants
                                </span>
                            </div>
                        </td>
                        
                        <!-- Status -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase
                                {{ $product->status === 'active' ? 'bg-green-100 text-green-800 border border-green-300' : ($product->status === 'draft' ? 'bg-gray-100 text-gray-800 border border-gray-300' : 'bg-red-100 text-red-800 border border-red-300') }}">
                                @if($product->status === 'active')
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                @elseif($product->status === 'draft')
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                                {{ ucfirst($product->status) }}
                            </span>
                        </td>
                        
                        <!-- Owner (Admin only) -->
                        @if(auth()->user()->hasRole('admin'))
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($product->user)
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs">
                                    {{ substr($product->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $product->user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $product->user->email }}</p>
                                </div>
                            </div>
                            @else
                                <span class="text-xs text-gray-400 italic">No owner</span>
                            @endif
                        </td>
                        @endif
                        
                        <!-- Actions -->
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <!-- View -->
                                <a href="{{ route('admin.products.show', $product) }}" 
                                   class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                   title="View Details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                
                                @if($canEdit)
                                <!-- Duplicate -->
                                <form method="POST" action="{{ route('admin.products.duplicate', $product) }}" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-purple-600 hover:text-purple-700 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors"
                                            title="Duplicate Product">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </form>
                                
                                <!-- Edit -->
                                <a href="{{ route('admin.products.edit', $product) }}" 
                                   class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 hover:text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors"
                                   title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                
                                <!-- Delete -->
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 hover:text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors"
                                            title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                                @else
                                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed" title="Locked">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Table Footer with Stats -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6 text-sm">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-gray-600">Active: <span class="font-semibold text-gray-900">{{ $products->where('status', 'active')->count() }}</span></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-gray-500"></div>
                        <span class="text-gray-600">Draft: <span class="font-semibold text-gray-900">{{ $products->where('status', 'draft')->count() }}</span></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <span class="text-gray-600">Inactive: <span class="font-semibold text-gray-900">{{ $products->where('status', 'inactive')->count() }}</span></span>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    Total: <span class="font-bold text-gray-900">{{ $products->total() }}</span> products
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Empty State -->
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center">
        <div class="w-20 h-20 bg-gradient-to-br from-green-100 to-teal-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">No products found</h3>
        <p class="text-gray-500 mb-6">Get started by creating a new product from a template.</p>
        <a href="{{ route('admin.products.create') }}" 
           class="inline-flex items-center px-6 py-3 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700 transition-colors shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add First Product
        </a>
    </div>
    @endif

    <!-- Pagination -->
    @if($products->hasPages())
    <div class="bg-white px-6 py-4 flex items-center justify-between border-t border-gray-200 rounded-b-xl shadow-md">
        <div class="flex-1 flex justify-between sm:hidden">
            @if($products->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-500 bg-white cursor-not-allowed">
                    Previous
                </span>
            @else
                <a href="{{ $products->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    Previous
                </a>
            @endif

            @if($products->hasMorePages())
                <a href="{{ $products->nextPageUrl() }}" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
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
                    <span class="font-semibold text-gray-900">{{ $products->firstItem() }}</span>
                    to
                    <span class="font-semibold text-gray-900">{{ $products->lastItem() }}</span>
                    of
                    <span class="font-semibold text-gray-900">{{ $products->total() }}</span>
                    results
                </p>
            </div>
            <div>
                {{ $products->links() }}
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Bulk Delete Confirmation Modal -->
<div id="bulkDeleteModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden z-50 transition-opacity">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-t-2xl p-6">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Delete Products</h3>
                        <p class="text-red-100 text-sm">This action cannot be undone</p>
                    </div>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6">
                <div class="mb-6">
                    <div class="flex items-center justify-center mb-4">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-center text-gray-700 text-lg mb-2">
                        Are you sure you want to delete <span id="modalDeleteCount" class="font-bold text-red-600"></span> selected product(s)?
                    </p>
                    <p class="text-center text-gray-500 text-sm">
                        All product data, variants, and media will be permanently removed from the system.
                    </p>
                </div>
                
                <!-- Selected Products Preview (Optional) -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4 max-h-32 overflow-y-auto">
                    <p class="text-xs font-semibold text-gray-600 mb-2">Selected Products:</p>
                    <div id="selectedProductsList" class="space-y-1 text-sm text-gray-700">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 rounded-b-2xl flex items-center justify-end space-x-3">
                <button type="button" onclick="closeBulkDeleteModal()" 
                        class="px-6 py-2.5 bg-white text-gray-700 font-semibold rounded-lg border-2 border-gray-300 hover:bg-gray-50 hover:border-gray-400 transition-all duration-200">
                    <span class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Cancel</span>
                    </span>
                </button>
                <button type="button" onclick="submitBulkDelete()" 
                        class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white font-semibold rounded-lg hover:from-red-700 hover:to-red-800 shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                    <span class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Delete Products</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Animation */
@keyframes bounce-in {
    0% {
        transform: scale(0.9);
        opacity: 0;
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-bounce-in {
    animation: bounce-in 0.3s ease-out;
}

/* Custom Scrollbar Styles - Always Visible */
.scrollbar-custom {
    scrollbar-width: thin;
    scrollbar-color: #10b981 #e5e7eb;
}

/* Webkit browsers (Chrome, Safari, Edge) */
.scrollbar-custom::-webkit-scrollbar {
    width: 12px;           /* Vertical scrollbar width */
    height: 12px;          /* Horizontal scrollbar height */
}

.scrollbar-custom::-webkit-scrollbar-track {
    background: #e5e7eb;   /* Gray track */
    border-radius: 10px;
    margin: 4px;
}

/* Horizontal scrollbar thumb - Green gradient */
.scrollbar-custom::-webkit-scrollbar-thumb:horizontal {
    background: linear-gradient(to right, #10b981, #14b8a6);
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    min-width: 40px;       /* Minimum thumb width for visibility */
}

/* Vertical scrollbar thumb - Green gradient */
.scrollbar-custom::-webkit-scrollbar-thumb:vertical {
    background: linear-gradient(to bottom, #10b981, #14b8a6);
    border-radius: 10px;
    border: 2px solid #e5e7eb;
}

.scrollbar-custom::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to right, #059669, #0d9488);
    box-shadow: 0 0 6px rgba(16, 185, 129, 0.5);
}

.scrollbar-custom::-webkit-scrollbar-corner {
    background: #e5e7eb;
    border-radius: 4px;
}

/* Make scrollbar always visible */
.scrollbar-custom::-webkit-scrollbar-thumb {
    visibility: visible;
}

/* Firefox */
.scrollbar-custom {
    scrollbar-width: auto;  /* Make sure scrollbar is visible */
}
</style>

<script>
// Prevent page scroll when scrolling table
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('productsTableContainer');
    
    if (tableContainer) {
        // Prevent scroll propagation when scrolling in table
        tableContainer.addEventListener('wheel', function(e) {
            const isScrollable = tableContainer.scrollHeight > tableContainer.clientHeight || 
                                tableContainer.scrollWidth > tableContainer.clientWidth;
            
            if (isScrollable) {
                e.stopPropagation();
            }
        }, { passive: false });
    }
});

// Bulk Delete Functions
function toggleSelectAll(checkbox) {
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    productCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkDeleteButton();
}

function updateBulkDeleteButton() {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const feedToGMCBtn = document.getElementById('feedToGMCBtn');
    const exportToMetaBtn = document.getElementById('exportToMetaBtn');
    const selectedCount = document.getElementById('selectedCount');
    const gmcSelectedCount = document.getElementById('gmcSelectedCount');
    const metaSelectedCount = document.getElementById('metaSelectedCount');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    if (checkedBoxes.length > 0) {
        bulkDeleteBtn.style.display = 'inline-flex';
        if (feedToGMCBtn) feedToGMCBtn.style.display = 'inline-flex';
        exportToMetaBtn.style.display = 'inline-flex';
        selectedCount.textContent = checkedBoxes.length;
        gmcSelectedCount.textContent = checkedBoxes.length;
        metaSelectedCount.textContent = checkedBoxes.length;
    } else {
        bulkDeleteBtn.style.display = 'none';
        if (feedToGMCBtn) feedToGMCBtn.style.display = 'none';
        exportToMetaBtn.style.display = 'none';
    }
    
    // Update "Select All" checkbox state
    const allCheckboxes = document.querySelectorAll('.product-checkbox');
    selectAllCheckbox.checked = allCheckboxes.length > 0 && checkedBoxes.length === allCheckboxes.length;
}

function confirmBulkDelete() {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    const count = checkedBoxes.length;
    
    if (count === 0) {
        showNoSelectionModal();
        return;
    }
    
    // Update modal content
    document.getElementById('modalDeleteCount').textContent = count;
    
    // Populate selected products list
    const productsList = document.getElementById('selectedProductsList');
    productsList.innerHTML = '';
    
    checkedBoxes.forEach((checkbox, index) => {
        const row = checkbox.closest('tr');
        const productName = row.querySelector('td:nth-child(3) p').textContent.trim();
        const productId = checkbox.value;
        
        const item = document.createElement('div');
        item.className = 'flex items-center space-x-2';
        item.innerHTML = `
            <span class="w-5 h-5 flex items-center justify-center bg-red-100 text-red-600 rounded-full text-xs font-bold">${index + 1}</span>
            <span class="truncate">ID #${productId}: ${productName}</span>
        `;
        productsList.appendChild(item);
    });
    
    // Show modal with animation
    const modal = document.getElementById('bulkDeleteModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('opacity-100');
    }, 10);
}

function closeBulkDeleteModal() {
    const modal = document.getElementById('bulkDeleteModal');
    modal.classList.remove('opacity-100');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

async function submitBulkDelete() {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    const productIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (productIds.length === 0) {
        alert('Please select products to delete.');
        return;
    }
    
    // Show loading state
    const submitBtn = document.querySelector('#bulkDeleteModal button[onclick="submitBulkDelete()"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Deleting...';
    
    try {
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        
        console.log('Sending bulk delete request:', {
            url: '{{ route("admin.products.bulk-delete") }}',
            productIds: productIds,
            csrfToken: csrfToken ? 'present' : 'missing'
        });
        
        // Use fetch API instead of form submission to avoid browser security warning
        const response = await fetch('{{ route("admin.products.bulk-delete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                product_ids: productIds
            })
        });
        
        let data;
        try {
            data = await response.json();
        } catch (parseError) {
            // If response is not JSON, handle as error
            console.error('Failed to parse JSON response:', parseError);
            throw new Error('Invalid response from server');
        }
        
        console.log('Bulk delete response:', data);
        
        if (response.ok && data.success) {
            // Show success message
            if (data.message) {
                alert(data.message);
            }
            
            // Close modal and reload page
            closeBulkDeleteModal();
            window.location.reload();
        } else {
            // Show detailed error message
            let errorMessage = data.message || data.error || 'An error occurred while deleting products.';
            
            // Add validation errors if present
            if (data.errors) {
                if (typeof data.errors === 'object') {
                    const errorList = Object.values(data.errors).flat();
                    errorMessage += '\n\nDetails:\n' + errorList.join('\n');
                } else if (Array.isArray(data.errors)) {
                    errorMessage += '\n\nErrors:\n' + data.errors.join('\n');
                }
            }
            
            console.error('Bulk delete failed:', {
                status: response.status,
                statusText: response.statusText,
                data: data
            });
            
            alert(errorMessage);
        }
    } catch (error) {
        console.error('Bulk delete error:', error);
        alert('An error occurred while deleting products. Please try again.');
    } finally {
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

function showNoSelectionModal() {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full mx-4 transform transition-all animate-bounce-in">
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-t-2xl p-6">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">No Selection</h3>
                </div>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-center text-gray-700 text-lg mb-4">
                    Please select at least one product to delete.
                </p>
            </div>
            <div class="bg-gray-50 px-6 py-4 rounded-b-2xl flex justify-center">
                <button onclick="this.closest('.fixed').remove()" 
                        class="px-6 py-2.5 bg-gradient-to-r from-orange-600 to-orange-700 text-white font-semibold rounded-lg hover:from-orange-700 hover:to-orange-800 shadow-lg transition-all duration-200 transform hover:scale-105">
                    Got it
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Auto close after 3 seconds
    setTimeout(() => {
        modal.remove();
    }, 3000);
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('bulkDeleteModal');
    if (modal && event.target === modal) {
        closeBulkDeleteModal();
    }
});

// Filter Functions
function removeFilter(filterName) {
    const url = new URL(window.location.href);
    url.searchParams.delete(filterName);
    if (filterName === 'price_min' || filterName === 'price_max') {
        url.searchParams.delete('price_min');
        url.searchParams.delete('price_max');
    }
    window.location.href = url.toString();
}

// Auto-submit on filter change (optional - can be enabled)
// document.addEventListener('DOMContentLoaded', function() {
//     const filterInputs = document.querySelectorAll('#filterForm select, #filterForm input[type="number"]');
//     filterInputs.forEach(input => {
//         input.addEventListener('change', function() {
//             document.getElementById('filterForm').submit();
//         });
//     });
// });

// Feed to Google Merchant Center
async function feedToGMC() {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    const productIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (productIds.length === 0) {
        alert('Vui lòng chọn ít nhất một sản phẩm để feed lên Google Merchant Center.');
        return;
    }
    
    // Show modal to select target country
    const targetCountry = await showTargetCountryModal();
    if (!targetCountry) {
        return; // User cancelled
    }
    
    // Show loading state
    const feedBtn = document.getElementById('feedToGMCBtn');
    const originalHTML = feedBtn.innerHTML;
    feedBtn.disabled = true;
    feedBtn.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Đang upload...';
    
    try {
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        
        // Use fetch API to upload via API
        const response = await fetch('{{ route("admin.products.feed-to-gmc") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                product_ids: productIds,
                method: 'api',
                target_country: targetCountry
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            // Show success message with details
            let message = data.message || 'Upload thành công!';
            if (data.results) {
                const results = data.results;
                message += `\n\nChi tiết:\n- Thành công: ${results.success_count}/${results.total}`;
                if (results.failed_count > 0) {
                    message += `\n- Thất bại: ${results.failed_count}`;
                }
            }
            alert(message);
            
            // Reload page to show updated status
            window.location.reload();
        } else {
            // If API fails, try XML download as fallback
            if (data.error && (data.error.includes('not configured') || data.error.includes('credentials'))) {
                const useXML = confirm('API chưa được cấu hình. Bạn có muốn tải file XML thay thế không?');
                if (useXML) {
                    // Download XML instead
                    downloadGMCXML(productIds);
                }
            } else {
                alert('Lỗi: ' + (data.message || data.error || 'Có lỗi xảy ra khi upload lên Google Merchant Center.'));
            }
        }
    } catch (error) {
        console.error('GMC Feed error:', error);
        alert('Có lỗi xảy ra khi upload. Vui lòng thử lại hoặc tải file XML.');
    } finally {
        feedBtn.disabled = false;
        feedBtn.innerHTML = originalHTML;
    }
}

// Show modal to select target country
function showTargetCountryModal() {
    return new Promise((resolve) => {
        // Get available GMC configs from server (only active configs for current domain)
        const availableConfigs = @json($availableGmcConfigs ?? []);
        
        if (availableConfigs.length === 0) {
            alert('Chưa có cấu hình GMC nào cho domain này.');
            resolve(null);
            return;
        }

        // Country labels mapping
        const countryLabels = {
            'US': 'United States (USD)',
            'GB': 'United Kingdom (GBP)',
            'VN': 'Vietnam (VND)',
            'CA': 'Canada (CAD)',
            'AU': 'Australia (AUD)',
            'DE': 'Germany (EUR)',
            'FR': 'France (EUR)',
            'IT': 'Italy (EUR)',
            'ES': 'Spain (EUR)',
        };

        // Get current domain from window location
        const currentDomain = window.location.hostname.replace(/^www\./, '');

        // Build options from available configs
        const options = availableConfigs.map(config => {
            const countryCode = config.target_country;
            const label = countryLabels[countryCode] || `${countryCode} (${config.currency})`;
            return `<option value="${countryCode}">${label} - ${config.name}</option>`;
        }).join('');

        // Create modal
        const modal = document.createElement('div');
        modal.id = 'gmcTargetCountryModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Chọn thị trường</h3>
                <p class="text-sm text-gray-600 mb-2">Vui lòng chọn thị trường để gửi sản phẩm lên Google Merchant Center:</p>
                <div class="mb-3 p-2 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-xs text-blue-800">
                        <strong>Domain sẽ sử dụng:</strong> <span class="font-mono">${currentDomain}</span>
                    </p>
                    <p class="text-xs text-blue-600 mt-1">
                        Chỉ hiển thị các thị trường đã được cấu hình GMC cho domain này.
                    </p>
                </div>
                <select id="targetCountrySelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 mb-4">
                    <option value="">-- Chọn thị trường --</option>
                    ${options}
                </select>
                <div class="flex justify-end gap-3">
                    <button onclick="closeGMCModal(null)" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Hủy
                    </button>
                    <button onclick="confirmGMCModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Xác nhận
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Close modal function
        window.closeGMCModal = function(result) {
            document.body.removeChild(modal);
            resolve(result);
        };

        // Confirm function
        window.confirmGMCModal = function() {
            const select = document.getElementById('targetCountrySelect');
            const value = select.value;
            if (!value) {
                alert('Vui lòng chọn thị trường!');
                return;
            }
            closeGMCModal(value);
        };
    });
}

// Download XML feed as fallback
async function downloadGMCXML(productIds) {
    const targetCountry = await showTargetCountryModal();
    if (!targetCountry) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.products.feed-to-gmc") }}';
    form.style.display = 'none';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
    
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = 'method';
    methodInput.value = 'xml';
    form.appendChild(methodInput);

    const targetCountryInput = document.createElement('input');
    targetCountryInput.type = 'hidden';
    targetCountryInput.name = 'target_country';
    targetCountryInput.value = targetCountry;
    form.appendChild(targetCountryInput);
    
    productIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'product_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    
    setTimeout(() => {
        document.body.removeChild(form);
    }, 1000);
}

// Export to Meta function
function exportToMeta() {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    const productIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (productIds.length === 0) {
        alert('Vui lòng chọn ít nhất một sản phẩm để export.');
        return;
    }
    
    // Build URL with product_ids
    const baseUrl = '{{ route("admin.products.export.meta") }}';
    const params = new URLSearchParams();
    productIds.forEach(id => {
        params.append('product_ids[]', id);
    });
    
    // Open in new window to download CSV
    window.location.href = baseUrl + '?' + params.toString();
}
</script>
@endsection
