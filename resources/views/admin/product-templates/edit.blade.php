@extends('layouts.admin')

@section('title', 'Edit Product Template')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-3">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Edit Product Template</h1>
                <span class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-bold shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                    </svg>
                    ID: {{ $productTemplate->id }}
                </span>
            </div>
            <p class="mt-2 text-sm text-gray-600">Update template configuration and attributes • Template ID for bulk product creation</p>
        </div>
        <a href="{{ route('admin.product-templates.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Templates
        </a>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('admin.product-templates.update', $productTemplate->id) }}" enctype="multipart/form-data" class="space-y-6" onsubmit="logFormData(event)">
        @csrf
        @method('PUT')
        
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
            </div>
            <div class="p-6 space-y-6">
                <!-- Template Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Template Name *</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $productTemplate->name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                           placeholder="Enter template name"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <select id="category_id" 
                            name="category_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('category_id') border-red-500 @enderror"
                            required>
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $productTemplate->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Base Price -->
                <div>
                    <label for="base_price" class="block text-sm font-medium text-gray-700 mb-2">Base Price *</label>
                        <input type="number" 
                               id="base_price" 
                               name="base_price" 
                           value="{{ old('base_price', $productTemplate->base_price) }}"
                               step="0.01" 
                               min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('base_price') border-red-500 @enderror"
                               placeholder="0.00"
                               required>
                    @error('base_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- List Price -->
                <div>
                    <label for="list_price" class="block text-sm font-medium text-gray-700 mb-2">List Price (giá niêm yết)</label>
                    <input type="number" 
                           id="list_price" 
                           name="list_price" 
                           value="{{ old('list_price', $productTemplate->list_price) }}"
                           step="0.01" 
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('list_price') border-red-500 @enderror"
                           placeholder="0.00 (để trống = không hiển thị gạch ngang)"
                           onchange="applyListPriceToAllVariants()"
                           oninput="applyListPriceToAllVariants()">
                    @error('list_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Giá niêm yết; hiển thị gạch ngang khi giảm giá.</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="description" 
                              name="description" 
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                              placeholder="Enter template description">{{ old('description', $productTemplate->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Media Upload -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Template Media Files
                </h3>
                <p class="text-sm text-gray-600">Upload multiple images or videos for this template</p>
            </div>
            <div class="p-6">
                <!-- Current Media -->
                @if($productTemplate->media && count($productTemplate->media) > 0)
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Current Media:</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach($productTemplate->media as $mediaItem)
                            @php
                                // Get media URL safely
                                if (is_string($mediaItem)) {
                                    $mediaUrl = $mediaItem;
                                } elseif (is_array($mediaItem) && !empty($mediaItem)) {
                                    $mediaUrl = $mediaItem['url'] ?? $mediaItem['path'] ?? reset($mediaItem) ?? null;
                                } else {
                                    $mediaUrl = null;
                                }
                            @endphp
                            
                            @if($mediaUrl)
                            <div class="relative bg-white rounded-lg border-2 border-gray-200 p-2 shadow-sm">
                                @if(str_contains($mediaUrl, '.mp4') || str_contains($mediaUrl, '.mov') || str_contains($mediaUrl, '.avi'))
                                    <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                                        <video class="w-full h-full object-cover" controls>
                                            <source src="{{ $mediaUrl }}" type="video/mp4">
                                        </video>
                                    </div>
                                    <div class="absolute top-4 left-4 bg-purple-500 text-white text-xs px-2 py-1 rounded-full font-semibold">
                                        Video
                                    </div>
                                @else
                                    <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gray-100">
                                        <img src="{{ $mediaUrl }}" alt="Media" class="w-full h-full object-cover">
                                    </div>
                                    <div class="absolute top-4 left-4 bg-green-500 text-white text-xs px-2 py-1 rounded-full font-semibold">
                                        Image
                                    </div>
                                @endif
                            </div>
                            @endif
                    @endforeach
            </div>
        </div>
        @endif

                <!-- File Upload Area -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors bg-gray-50" 
                     id="template-media-drop-zone"
                     ondrop="handleTemplateMediaDrop(event)" 
                     ondragover="handleTemplateMediaDragOver(event)"
                     ondragleave="handleTemplateMediaDragLeave(event)">
                        <input type="file" 
                               id="media" 
                               name="media[]" 
                               multiple
                               accept="image/*,video/*"
                           class="hidden"
                           onchange="handleTemplateMediaFiles(this.files)">
                    
                    <div class="space-y-4">
                        <div class="flex flex-col items-center">
                            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <p class="text-xl font-semibold text-gray-700">Upload New Media</p>
                            <p class="text-sm text-gray-500 mt-2">Drag and drop files here, or click to browse</p>
                            <p class="text-xs text-gray-400 mt-1">Supports: JPG, PNG, GIF, MP4, MOV (Max 10MB each)</p>
                        </div>
                        
                        <button type="button" 
                                onclick="document.getElementById('media').click()"
                                class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Choose Files
                        </button>
                    </div>
                </div>
                
                        @error('media.*')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                
                <!-- Template Media Preview -->
                <div id="template-media-preview" class="mt-6 hidden">
                    <h5 class="text-sm font-semibold text-gray-700 mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        New Files Selected (<span id="template-file-count">0</span>):
                    </h5>
                    <div id="template-media-preview-list" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <!-- Preview items will be added here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Attributes -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Template Attributes</h3>
                <p class="text-sm text-gray-600">Add attributes that will be used to generate variants (e.g., Color, Size)</p>
            </div>
            <div class="p-6">
                <div id="attributes-container">
                    @php
                        $existingAttributes = [];
                        if($productTemplate->attributes && count($productTemplate->attributes) > 0) {
                            foreach($productTemplate->attributes as $attr) {
                                if (!isset($existingAttributes[$attr->attribute_name])) {
                                    $existingAttributes[$attr->attribute_name] = [];
                                }
                                $existingAttributes[$attr->attribute_name][] = $attr->attribute_value;
                            }
                        }
                        $attrIndex = 0;
                        
                        // Debug logging
                        \Log::info('Edit page - Existing attributes:', [
                            'attributes_count' => $productTemplate->attributes ? count($productTemplate->attributes) : 0,
                            'grouped_attributes' => $existingAttributes
                        ]);
                    @endphp
                    
                    @if(count($existingAttributes) > 0)
                        @foreach($existingAttributes as $attrName => $attrValues)
                            <div class="attribute-row border border-gray-200 rounded-lg p-4 mb-4">
                                <div class="flex space-x-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Attribute Name</label>
                                    <input type="text" 
                                               name="attributes[{{ $attrIndex }}][name]" 
                                               value="{{ old('attributes.'.$attrIndex.'.name', $attrName) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="e.g., Color"
                                               onchange="generateVariants()">
                                </div>
                                <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Attribute Values (comma separated)</label>
                                    <input type="text" 
                                               name="attributes[{{ $attrIndex }}][values]" 
                                               value="{{ old('attributes.'.$attrIndex.'.values', implode(', ', $attrValues)) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="e.g., Black, Blue, Red"
                                               onchange="generateVariants()">
                                </div>
                                <div class="flex items-end">
                                    <button type="button" 
                                                onclick="console.log('Button clicked!'); removeAttribute(this)" 
                                                class="px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                                style="pointer-events: auto; z-index: 10; position: relative;">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            </div>
                            @php $attrIndex++; @endphp
                        @endforeach
                    @else
                        <div class="attribute-row border border-gray-200 rounded-lg p-4 mb-4">
                            <div class="flex space-x-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Attribute Name</label>
                                <input type="text" 
                                       name="attributes[0][name]" 
                                           value="{{ old('attributes.0.name') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="e.g., Color"
                                           onchange="generateVariants()">
                            </div>
                            <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Attribute Values (comma separated)</label>
                                <input type="text" 
                                           name="attributes[0][values]" 
                                           value="{{ old('attributes.0.values') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="e.g., Black, Blue, Red"
                                           onchange="generateVariants()">
                            </div>
                            <div class="flex items-end">
                                <button type="button" 
                                            onclick="console.log('Button clicked!'); removeAttribute(this)" 
                                            class="px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                            style="pointer-events: auto; z-index: 10; position: relative;">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <button type="button" 
                        onclick="addAttribute()" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Attribute
                </button>
            </div>
        </div>

        <!-- Product Customization -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Product Customization
                </h3>
                <p class="text-sm text-gray-600">Allow customers to customize products with custom content</p>
            </div>
            <div class="p-6">
                <!-- Enable Customization -->
                <div class="mb-6">
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" 
                               id="allow_customization" 
                               name="allow_customization" 
                               value="1"
                               {{ old('allow_customization', $productTemplate->allow_customization) ? 'checked' : '' }}
                               class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                               onchange="toggleCustomizationSection()">
                        <div>
                            <span class="text-lg font-semibold text-gray-900">Allow customers to customize products</span>
                            <p class="text-sm text-gray-600">When this feature is enabled, customers can enter custom content for the product (e.g.: name, wishes, etc.)</p>
                        </div>
                    </label>
                </div>

                <!-- Customization Types -->
                <div id="customization-section" class="{{ $productTemplate->allow_customization ? '' : 'hidden' }}">
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-md font-semibold text-gray-800">Customization Type</h4>
                            <button type="button" 
                                    onclick="addCustomizationType()" 
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Customization Type
                            </button>
                        </div>
                        
                        <!-- Total Customization Price Banner -->
                        <div class="bg-teal-500 text-white p-4 rounded-lg mb-4">
                            <div class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                <span class="text-lg font-bold">Total Customization Price: <span id="total-customization-price">${{ $productTemplate->customizations ? collect($productTemplate->customizations)->sum('price') : 0 }}</span></span>
                            </div>
                        </div>
                        
                        <!-- Customization Types Container -->
                        <div id="customization-types-container">
                            @if($productTemplate->customizations && count($productTemplate->customizations) > 0)
                                @foreach($productTemplate->customizations as $index => $customization)
                                    <div class="customization-type-row border border-gray-200 rounded-lg p-4 mb-4 bg-white shadow-sm">
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                                                <select name="customizations[{{ $index }}][type]" 
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                        onchange="updateCustomizationType(this)">
                                                    <option value="text" {{ $customization['type'] == 'text' ? 'selected' : '' }}>Text</option>
                                                    <option value="number" {{ $customization['type'] == 'number' ? 'selected' : '' }}>Number</option>
                                                    <option value="textarea" {{ $customization['type'] == 'textarea' ? 'selected' : '' }}>Textarea</option>
                                                    <option value="select" {{ $customization['type'] == 'select' ? 'selected' : '' }}>Select</option>
                                                    <option value="checkbox" {{ $customization['type'] == 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                                                    <option value="file" {{ $customization['type'] == 'file' ? 'selected' : '' }}>File Upload</option>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Price (USD)</label>
                                                <input type="number" 
                                                       name="customizations[{{ $index }}][price]" 
                                                       value="{{ $customization['price'] ?? 0 }}"
                                                       step="0.01" 
                                                       min="0"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                       placeholder="0.00"
                                                       onchange="updateTotalCustomizationPrice()">
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Display Label</label>
                                                <input type="text" 
                                                       name="customizations[{{ $index }}][label]" 
                                                       value="{{ $customization['label'] ?? '' }}"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                       placeholder="e.g.: Name, Number, Logo..."
                                                       required>
                                            </div>
                                            
                                            <div class="flex justify-end">
                                                <button type="button" 
                                                        onclick="removeCustomizationType(this)" 
                                                        class="px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4 space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Placeholder</label>
                                                <input type="text" 
                                                       name="customizations[{{ $index }}][placeholder]" 
                                                       value="{{ $customization['placeholder'] ?? '' }}"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                       placeholder="Suggested text...">
                                            </div>
                                            
                                            <div id="select-options-{{ $index }}" class="{{ $customization['type'] == 'select' ? '' : 'hidden' }}">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Options (one per line)</label>
                                                <textarea name="customizations[{{ $index }}][options]" 
                                                          rows="3"
                                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                          placeholder="Option 1&#10;Option 2&#10;Option 3">{{ $customization['options'] ?? '' }}</textarea>
                                            </div>
                                            
                                            <div class="flex items-center space-x-2">
                                                <input type="checkbox" 
                                                       name="customizations[{{ $index }}][required]" 
                                                       value="1"
                                                       {{ isset($customization['required']) && $customization['required'] ? 'checked' : '' }}
                                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                                <label class="text-sm font-medium text-gray-700">Required</label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generated Variants -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Generated Variants</h3>
                <p class="text-sm text-gray-600">Variants will be automatically generated from your attributes</p>
            </div>
            <div class="p-6">
                <!-- Bulk Actions Buttons -->
                <div class="mb-6">
                    <div class="flex flex-wrap gap-3">
                        <button type="button" 
                                onclick="openBulkModal('all')" 
                                class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                            </svg>
                            <span class="text-lg">Bulk Actions</span>
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">Click to open bulk editing tools for faster variant management</p>
                </div>
                
                <div id="variants-container">
                    <div class="text-center text-gray-500 py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-2">Add attributes above to generate variants</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('admin.product-templates.index') }}" 
               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Update Template
            </button>
        </div>
    </form>
</div>

<!-- Bulk Actions Modal -->
<div id="bulk-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Bulk Actions</h3>
                <button onclick="closeBulkModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Attribute Selection -->
            <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-800 mb-4">Select Variants by Attributes</h4>
                <div id="attribute-selection" class="space-y-4 max-h-64 overflow-y-auto">
                    <!-- Attributes will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Value Inputs -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-semibold text-gray-800">Set Values</h4>
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" 
                                   id="clear-existing-values" 
                                   checked
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="text-sm text-gray-700">Clear existing values first</span>
                        </label>
                        <select id="clear-mode" 
                                class="text-sm border border-gray-300 rounded px-2 py-1">
                            <option value="selected">Clear selected variants only</option>
                            <option value="all">Clear all variants</option>
                        </select>
                    </div>
                </div>
                
                <!-- Logic Selection -->
                <div class="mb-4">
                    <div class="flex items-center space-x-4">
                        <label class="text-sm font-medium text-gray-700">Matching Logic:</label>
                        <select id="matching-logic" 
                                class="text-sm border border-gray-300 rounded px-3 py-2">
                            <option value="or">OR - Match any selected attribute</option>
                            <option value="and">AND - Match all selected attributes</option>
                        </select>
                        <div class="text-xs text-gray-500">
                            <span id="logic-description">OR: Apply to variants that have ANY of the selected attributes</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Price Input -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            Price
                        </label>
                        <input type="number" 
                               id="bulk-price-input" 
                               step="0.01" 
                               min="0"
                               class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors font-medium"
                               placeholder="0.00">
                    </div>
                    
                    <!-- List Price Input -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            List Price
                        </label>
                        <input type="number" 
                               id="bulk-list-price-input" 
                               step="0.01" 
                               min="0"
                               class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors font-medium"
                               placeholder="0.00">
                    </div>
                    
                    <!-- Quantity Input -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            Quantity
                        </label>
                        <input type="number" 
                               id="bulk-quantity-input" 
                               min="0"
                               class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors font-medium"
                               placeholder="0">
                    </div>
                    
                    <!-- Media Input -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Media Files
                        </label>
                        
                        <!-- File Upload Area -->
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-purple-400 transition-colors" 
                             id="media-drop-zone"
                             ondrop="handleMediaDrop(event)" 
                             ondragover="handleMediaDragOver(event)"
                             ondragleave="handleMediaDragLeave(event)">
                            <input type="file" 
                                   id="bulk-media-input" 
                                   accept="image/*,video/*"
                                   multiple
                                   class="hidden"
                                   onchange="handleMediaFiles(this.files)">
                            
                            <div class="space-y-4">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-700">Upload Media Files</p>
                                    <p class="text-sm text-gray-500">Drag and drop files here, or click to select</p>
                                    <p class="text-xs text-gray-400">Supports: JPG, PNG, GIF, MP4, MOV (Max 10MB each)</p>
                                </div>
                                
                                <button type="button" 
                                        onclick="document.getElementById('bulk-media-input').click()"
                                        class="px-6 py-2 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors">
                                    Choose Files
                                </button>
                            </div>
                        </div>
                        
                        <!-- Media Preview -->
                        <div id="media-preview" class="mt-4 hidden">
                            <h5 class="text-sm font-semibold text-gray-700 mb-3">Selected Files:</h5>
                            <div id="media-preview-list" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <!-- Preview items will be added here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Actions -->
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeBulkModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="applyBulkValue()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                    Apply
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let attributeIndex = {{ $attrIndex }};
let customizationIndex = {{ $productTemplate->customizations ? count($productTemplate->customizations) : 0 }};

// Add event listeners when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, adding event listeners');
    
    // Add click listeners to all remove buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('button[onclick*="removeAttribute"]')) {
            console.log('Remove button clicked via event delegation');
            e.preventDefault();
            e.stopPropagation();
            removeAttribute(e.target.closest('button'));
        }
    });
    
    // Add listener for matching logic change
    const matchingLogicSelect = document.getElementById('matching-logic');
    if (matchingLogicSelect) {
        matchingLogicSelect.addEventListener('change', updateLogicDescription);
    }
    
    // Debug: Check initial state
    const existingVariantsData = @json($productTemplate->variants ?? []);
    console.log('=== PAGE LOAD DEBUG ===');
    console.log('Template ID:', {{ $productTemplate->id }});
    console.log('Existing attributes loaded:', {{ $productTemplate->attributes ? count($productTemplate->attributes) : 0 }});
    console.log('Existing variants loaded:', existingVariantsData.length);
    console.log('Variants data:', existingVariantsData);
    
    // Generate variants preview on load
    generateVariants();
    
    // Restore variant data after generating
    setTimeout(() => {
        restoreVariantData();
    }, 500);
    
    // Update total customization price on load
    updateTotalCustomizationPrice();
});

function addAttribute() {
    const container = document.getElementById('attributes-container');
    const newRow = document.createElement('div');
    newRow.className = 'attribute-row border border-gray-200 rounded-lg p-4 mb-4';
    newRow.innerHTML = `
        <div class="flex space-x-4">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Attribute Name</label>
            <input type="text" 
                   name="attributes[${attributeIndex}][name]" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="e.g., Color"
                       onchange="generateVariants()">
        </div>
        <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Attribute Values (comma separated)</label>
            <input type="text" 
                       name="attributes[${attributeIndex}][values]" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="e.g., Black, Blue, Red"
                       onchange="generateVariants()">
        </div>
        <div class="flex items-end">
            <button type="button" 
                        onclick="console.log('Button clicked!'); removeAttribute(this)" 
                        class="px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                        style="pointer-events: auto; z-index: 10; position: relative;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    attributeIndex++;
}

function removeAttribute(button) {
    console.log('removeAttribute called with button:', button);
    const attributeRow = button.closest('.attribute-row');
    console.log('Found attribute row:', attributeRow);
    
    if (attributeRow) {
        attributeRow.remove();
        console.log('Attribute row removed');
        generateVariants();
    } else {
        console.log('No attribute row found');
    }
}

function generateVariants() {
    const attributes = [];
    const attributeRows = document.querySelectorAll('.attribute-row');
    
    console.log('=== GENERATE VARIANTS DEBUG ===');
    console.log('Attribute rows found:', attributeRows.length);
    
    attributeRows.forEach((row, idx) => {
        const nameInput = row.querySelector('input[name*="[name]"]');
        const valuesInput = row.querySelector('input[name*="[values]"]');
        
        console.log(`Row ${idx}:`, {
            nameInput: nameInput ? nameInput.value : 'null',
            valuesInput: valuesInput ? valuesInput.value : 'null'
        });
        
        if (nameInput && valuesInput && nameInput.value.trim() && valuesInput.value.trim()) {
            const values = valuesInput.value.split(',').map(v => v.trim()).filter(v => v);
            if (values.length > 0) {
                attributes.push({
                    name: nameInput.value.trim(),
                    values: values
                });
                console.log(`Added attribute: ${nameInput.value.trim()} with ${values.length} values`);
            }
        }
    });
    
    console.log('Total attributes collected:', attributes.length);
    console.log('Attributes:', attributes);
    
    const container = document.getElementById('variants-container');
    if (!container) return;
    
    if (attributes.length === 0) {
        console.log('No attributes - showing empty state');
        container.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="mt-2">Add attributes above to generate variants</p>
            </div>
        `;
        return;
    }
    
    // Generate all combinations
    const combinations = generateCombinations(attributes);
    console.log('Combinations generated:', combinations.length);
    displayVariants(combinations);
}

function displayVariants(combinations) {
    const container = document.getElementById('variants-container');
    
    console.log('=== DISPLAY VARIANTS DEBUG ===');
    console.log('Combinations to display:', combinations.length);
    console.log('Combinations data:', combinations);
    
    if (combinations.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="mt-2">Add attributes above to generate variants</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Generated Variants</h4>
                            <p class="text-sm text-gray-600">${combinations.length} variants created from your attributes</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">${combinations.length} variants</span>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                    Variant
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Image
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    Price
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                    List Price
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    Quantity
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Actions
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    const listPriceInput = document.getElementById('list_price');
    const baseListPrice = listPriceInput ? listPriceInput.value : '';
    
    combinations.forEach((combination, index) => {
        const variantName = combination.map(c => c.value).join('/');
        const variantKey = combination.map(c => `${c.name}:${c.value}`).join(',');
        
        html += `
            <tr class="hover:bg-blue-50 transition-colors duration-200 group" data-variant-index="${index}">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 flex items-center justify-center">
                                <span class="text-white font-semibold text-sm">${index + 1}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-semibold text-gray-900">${variantName}</div>
                            <div class="text-sm text-gray-500">${variantKey}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="relative">
                        <input type="file" 
                               name="variants[${index}][media]" 
                               accept="image/*,video/*" 
                               class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100"
                               onchange="previewVariantImage(this)">
                        <p class="text-xs text-gray-500 mt-1">Upload new file or keep existing</p>
                        <div class="mt-2 variant-image-preview hidden">
                            <img src="" alt="Preview" class="w-20 h-20 object-cover rounded border border-gray-300">
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="number" 
                           name="variants[${index}][price]" 
                           step="0.01" 
                           min="0" 
                           class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors font-medium"
                           placeholder="0.00"
                           onchange="highlightVariant(this)">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="number" 
                           name="variants[${index}][list_price]" 
                           step="0.01" 
                           min="0" 
                           value="${baseListPrice}"
                           class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors font-medium"
                           placeholder="0.00"
                           onchange="highlightVariant(this)">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="number" 
                           name="variants[${index}][quantity]" 
                           min="0" 
                           class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors font-medium"
                           placeholder="0"
                           onchange="highlightVariant(this)">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <button type="button" 
                            onclick="removeVariant(this)" 
                            class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all transform hover:scale-105 shadow-md">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Remove
                    </button>
                </td>
            </tr>
        `;
        
        // Add hidden inputs for variant data
        html += `<input type="hidden" name="variants[${index}][variant_name]" value="${variantName}">`;
        
        // Generate attributes object for this variant
        const attributesObj = {};
        combination.forEach(attr => {
            attributesObj[attr.name] = attr.value;
        });
        html += `<input type="hidden" name="variants[${index}][attributes]" value='${JSON.stringify(attributesObj)}'>`;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

function removeVariant(button) {
    button.closest('tr').remove();
}

function highlightVariant(input) {
    const row = input.closest('tr');
    row.classList.add('bg-yellow-50', 'border-yellow-200');
    row.style.borderLeft = '4px solid #f59e0b';
    
    setTimeout(() => {
        row.classList.remove('bg-yellow-50', 'border-yellow-200');
        row.style.borderLeft = '';
    }, 2000);
}

function applyListPriceToAllVariants() {
    const listPriceInput = document.getElementById('list_price');
    if (!listPriceInput) return;
    
    const listPrice = listPriceInput.value;
    const variantListPriceInputs = document.querySelectorAll('input[name*="variants"][name*="[list_price]"]');
    
    if (variantListPriceInputs.length === 0) return;
    
    variantListPriceInputs.forEach(input => {
        input.value = listPrice;
        highlightVariant(input);
    });
}

function restoreVariantData() {
    const existingVariants = @json($productTemplate->variants ?? []);
    
    if (!existingVariants || existingVariants.length === 0) {
        console.log('No existing variants to restore');
        return;
    }
    
    console.log('Restoring variant data:', existingVariants);
    
    // Loop through existing variants and find matching rows in the table
    existingVariants.forEach(variant => {
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const variantNameElement = row.querySelector('.text-sm.font-semibold.text-gray-900');
            
            if (variantNameElement && variantNameElement.textContent === variant.variant_name) {
                console.log(`Restoring variant: ${variant.variant_name}`);
                
                // Restore price
                if (variant.price) {
                    const priceInput = row.querySelector('input[name*="[price]"]');
                    if (priceInput) {
                        priceInput.value = variant.price;
                        console.log(`- Price: ${variant.price}`);
                    }
                }
                
                // Restore list_price
                if (variant.list_price != null && variant.list_price !== '') {
                    const listPriceInput = row.querySelector('input[name*="[list_price]"]');
                    if (listPriceInput) {
                        listPriceInput.value = variant.list_price;
                        console.log(`- List Price: ${variant.list_price}`);
                    }
                }
                
                // Restore quantity
                if (variant.quantity) {
                    const quantityInput = row.querySelector('input[name*="[quantity]"]');
                    if (quantityInput) {
                        quantityInput.value = variant.quantity;
                        console.log(`- Quantity: ${variant.quantity}`);
                    }
                }
                
                // Show existing media preview
                if (variant.media && variant.media.length > 0) {
                    const mediaInput = row.querySelector('input[name*="[media]"]');
                    if (mediaInput && mediaInput.parentElement) {
                        const previewContainer = mediaInput.parentElement.querySelector('.variant-image-preview');
                        const previewImg = previewContainer ? previewContainer.querySelector('img') : null;
                        
                        if (previewContainer && previewImg) {
                            const mediaUrl = variant.media[0]; // Get first media
                            
                            // Check if it's a video
                            const isVideo = mediaUrl.includes('.mp4') || mediaUrl.includes('.mov') || mediaUrl.includes('.avi');
                            
                            if (isVideo) {
                                // Show video preview
                                const video = document.createElement('video');
                                video.src = mediaUrl;
                                video.controls = true;
                                video.className = 'w-20 h-20 object-cover rounded border border-gray-300';
                                previewContainer.appendChild(video);
                                previewImg.style.display = 'none';
                            } else {
                                // Show image preview
                                previewImg.src = mediaUrl;
                                previewImg.style.display = 'block';
                            }
                            
                            previewContainer.classList.remove('hidden');
                            console.log(`- Restored media: ${mediaUrl}`);
                        }
                    }
                }
            }
        });
    });
    
    console.log('Variant data restoration complete');
}

function generateCombinations(attributes) {
    if (attributes.length === 0) return [];
    
    let combinations = [];
    
    function generateRecursive(index, current) {
        if (index === attributes.length) {
            combinations.push([...current]);
            return;
        }
        
        const attribute = attributes[index];
        for (const value of attribute.values) {
            current.push({ name: attribute.name, value: value });
            generateRecursive(index + 1, current);
            current.pop();
        }
    }
    
    generateRecursive(0, []);
    return combinations;
}

// Template Media Upload Functions
let templateMediaFiles = [];

function handleTemplateMediaDragOver(event) {
    event.preventDefault();
    event.currentTarget.classList.add('border-blue-400', 'bg-blue-50');
}

function handleTemplateMediaDragLeave(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('border-blue-400', 'bg-blue-50');
}

function handleTemplateMediaDrop(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('border-blue-400', 'bg-blue-50');
    
    const files = Array.from(event.dataTransfer.files);
    handleTemplateMediaFiles(files);
}

function handleTemplateMediaFiles(files) {
    templateMediaFiles = Array.from(files);
    
    // Update the actual file input
    const input = document.getElementById('media');
    const dt = new DataTransfer();
    templateMediaFiles.forEach(file => dt.items.add(file));
    input.files = dt.files;
    
    displayTemplateMediaPreview();
}

function displayTemplateMediaPreview() {
    const previewContainer = document.getElementById('template-media-preview');
    const previewList = document.getElementById('template-media-preview-list');
    const fileCount = document.getElementById('template-file-count');
    
    if (templateMediaFiles.length === 0) {
        previewContainer.classList.add('hidden');
        return;
    }
    
    previewContainer.classList.remove('hidden');
    fileCount.textContent = templateMediaFiles.length;
    previewList.innerHTML = '';
    
    templateMediaFiles.forEach((file, index) => {
        const previewItem = document.createElement('div');
        previewItem.className = 'relative bg-white rounded-lg border-2 border-gray-200 p-2 shadow-sm hover:shadow-md transition-shadow';
        
        const isVideo = file.type.startsWith('video/');
        const isImage = file.type.startsWith('image/');
        
        if (isImage) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewItem.innerHTML = `
                    <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gray-100">
                        <img src="${e.target.result}" alt="${file.name}" class="w-full h-full object-cover">
                    </div>
                    <div class="text-center px-1">
                        <p class="text-xs font-medium text-gray-700 truncate" title="${file.name}">${file.name}</p>
                        <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                    </div>
                    <button type="button" onclick="removeTemplateMediaFile(${index})" 
                            class="absolute -top-2 -right-2 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full font-semibold">
                        Image
                    </div>
                `;
                previewList.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        } else if (isVideo) {
            previewItem.innerHTML = `
                <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                    <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-center px-1">
                    <p class="text-xs font-medium text-gray-700 truncate" title="${file.name}">${file.name}</p>
                    <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                </div>
                <button type="button" onclick="removeTemplateMediaFile(${index})" 
                        class="absolute -top-2 -right-2 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div class="absolute top-2 left-2 bg-purple-500 text-white text-xs px-2 py-1 rounded-full font-semibold">
                    Video
                </div>
            `;
            previewList.appendChild(previewItem);
        }
    });
}

function removeTemplateMediaFile(index) {
    templateMediaFiles.splice(index, 1);
    
    // Update the actual file input
    const input = document.getElementById('media');
    const dt = new DataTransfer();
    templateMediaFiles.forEach(file => dt.items.add(file));
    input.files = dt.files;
    
    displayTemplateMediaPreview();
}

// Customization Functions
function toggleCustomizationSection() {
    const checkbox = document.getElementById('allow_customization');
    const section = document.getElementById('customization-section');
    
    if (checkbox.checked) {
        section.classList.remove('hidden');
    } else {
        section.classList.add('hidden');
    }
}

function addCustomizationType() {
    const container = document.getElementById('customization-types-container');
    const newRow = document.createElement('div');
    newRow.className = 'customization-type-row border border-gray-200 rounded-lg p-4 mb-4 bg-white shadow-sm';
    
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="customizations[${customizationIndex}][type]" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        onchange="updateCustomizationType(this)">
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="textarea">Textarea</option>
                    <option value="select">Select</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="file">File Upload</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price (USD)</label>
                <input type="number" 
                       name="customizations[${customizationIndex}][price]" 
                       step="0.01" 
                       min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="0.00"
                       onchange="updateTotalCustomizationPrice()">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Display Label</label>
                <input type="text" 
                       name="customizations[${customizationIndex}][label]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="e.g.: Name, Number, Logo..."
                       required>
            </div>
            
            <div class="flex justify-end">
                <button type="button" 
                        onclick="removeCustomizationType(this)" 
                        class="px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <div class="mt-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Placeholder</label>
                <input type="text" 
                       name="customizations[${customizationIndex}][placeholder]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Suggested text...">
            </div>
            
            <div id="select-options-${customizationIndex}" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Options (one per line)</label>
                <textarea name="customizations[${customizationIndex}][options]" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
            </div>
            
            <div class="flex items-center space-x-2">
                <input type="checkbox" 
                       name="customizations[${customizationIndex}][required]" 
                       value="1"
                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label class="text-sm font-medium text-gray-700">Required</label>
            </div>
        </div>
    `;
    
    container.appendChild(newRow);
    customizationIndex++;
}

function updateCustomizationType(select) {
    const row = select.closest('.customization-type-row');
    const optionsDiv = row.querySelector('[id^="select-options-"]');
    
    if (select.value === 'select') {
        optionsDiv.classList.remove('hidden');
    } else {
        optionsDiv.classList.add('hidden');
    }
}

function removeCustomizationType(button) {
    const row = button.closest('.customization-type-row');
    row.remove();
    updateTotalCustomizationPrice();
}

function updateTotalCustomizationPrice() {
    const priceInputs = document.querySelectorAll('input[name*="customizations"][name*="[price]"]');
    let total = 0;
    
    priceInputs.forEach(input => {
        const price = parseFloat(input.value) || 0;
        total += price;
    });
    
    document.getElementById('total-customization-price').textContent = `$${total.toFixed(2)}`;
}

// Bulk Actions Functions
let currentBulkType = '';

function openBulkModal(type) {
    currentBulkType = type;
    const modal = document.getElementById('bulk-modal');
    
    populateAttributeSelection();
    modal.classList.remove('hidden');
}

function closeBulkModal() {
    const modal = document.getElementById('bulk-modal');
    modal.classList.add('hidden');
    selectedMediaFiles = [];
    document.getElementById('bulk-media-input').value = '';
    document.getElementById('media-preview').classList.add('hidden');
}

function populateAttributeSelection() {
    const container = document.getElementById('attribute-selection');
    const attributeRows = document.querySelectorAll('.attribute-row');
    
    if (attributeRows.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No attributes defined yet. Please add attributes above.</p>';
        return;
    }
    
    let html = '';
    attributeRows.forEach((row, index) => {
        const nameInput = row.querySelector('input[name*="[name]"]');
        const valuesInput = row.querySelector('input[name*="[values]"]');
        
        if (nameInput.value.trim() && valuesInput.value.trim()) {
            const attributeName = nameInput.value.trim();
            const values = valuesInput.value.split(',').map(v => v.trim()).filter(v => v);
            
            html += `
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="font-semibold text-gray-800">${attributeName}</h5>
                        <div class="flex space-x-2">
                            <button type="button" 
                                    onclick="selectAllAttributeValues('${attributeName}')" 
                                    class="text-xs text-blue-600 hover:text-blue-800">
                                Select All
                            </button>
                            <button type="button" 
                                    onclick="deselectAllAttributeValues('${attributeName}')" 
                                    class="text-xs text-gray-600 hover:text-gray-800">
                                Deselect All
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
            `;
            
            values.forEach(value => {
                html += `
                    <label class="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                        <input type="checkbox" 
                               class="attribute-value-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                               data-attribute="${attributeName}"
                               data-value="${value}">
                        <span class="text-sm text-gray-700">${value}</span>
                    </label>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        }
    });
    
    container.innerHTML = html;
}

function selectAllAttributeValues(attributeName) {
    const checkboxes = document.querySelectorAll(`input[data-attribute="${attributeName}"]`);
    checkboxes.forEach(cb => cb.checked = true);
}

function deselectAllAttributeValues(attributeName) {
    const checkboxes = document.querySelectorAll(`input[data-attribute="${attributeName}"]`);
    checkboxes.forEach(cb => cb.checked = false);
}

function updateLogicDescription() {
    const select = document.getElementById('matching-logic');
    const description = document.getElementById('logic-description');
    
    if (select && description) {
        if (select.value === 'or') {
            description.textContent = 'OR: Apply to variants that have ANY of the selected attributes';
        } else {
            description.textContent = 'AND: Apply to variants that have values for ALL selected attributes (e.g., Size AND Color)';
        }
    }
}

function applyBulkValue() {
    const priceInput = document.getElementById('bulk-price-input');
    const listPriceInput = document.getElementById('bulk-list-price-input');
    const quantityInput = document.getElementById('bulk-quantity-input');
    
    const price = priceInput ? priceInput.value : '';
    const listPrice = listPriceInput ? listPriceInput.value : '';
    const quantity = quantityInput ? quantityInput.value : '';
    const mediaFiles = selectedMediaFiles;
    
    if (!price && !listPrice && !quantity && mediaFiles.length === 0) {
        alert('Please enter at least one value (price, list price, quantity, or media)');
        return;
    }
    
    const selectedValues = getSelectedAttributeValues();
    if (selectedValues.length === 0) {
        alert('Please select at least one attribute value');
        return;
    }
    
    const clearExistingCheckbox = document.getElementById('clear-existing-values');
    if (clearExistingCheckbox && clearExistingCheckbox.checked) {
        const clearMode = document.getElementById('clear-mode').value;
        if (clearMode === 'selected') {
            clearSelectedVariantsOnly(selectedValues);
        } else {
            clearPreviousBulkValues();
        }
    }
    
    let appliedCount = 0;
    const rows = document.querySelectorAll('tbody tr');
    
    if (rows.length === 0) {
        alert('No variants found! Please make sure you have added attributes and generated variants.');
        return;
    }
    
    rows.forEach((row, index) => {
        const variantNameElement = row.querySelector('.text-sm.font-semibold.text-gray-900');
        const variantKeyElement = row.querySelector('.text-sm.text-gray-500');
        
        if (variantNameElement && variantKeyElement) {
            const variantKey = variantKeyElement.textContent;
            const variantPairs = variantKey.split(',').map(pair => pair.trim());
            
            let shouldApply = false;
            const matchingLogic = document.getElementById('matching-logic').value;
            
            if (matchingLogic === 'or') {
                selectedValues.forEach(({attribute, value}) => {
                    const searchPattern = `${attribute}:${value}`;
                    if (variantPairs.includes(searchPattern)) {
                        shouldApply = true;
                    }
                });
            } else {
                const groupedByAttribute = {};
                selectedValues.forEach(({attribute, value}) => {
                    if (!groupedByAttribute[attribute]) {
                        groupedByAttribute[attribute] = [];
                    }
                    groupedByAttribute[attribute].push(value);
                });
                
                shouldApply = true;
                
                Object.keys(groupedByAttribute).forEach(attribute => {
                    const values = groupedByAttribute[attribute];
                    let hasAnyValue = false;
                    
                    values.forEach(value => {
                        const searchPattern = `${attribute}:${value}`;
                        if (variantPairs.includes(searchPattern)) {
                            hasAnyValue = true;
                        }
                    });
                    
                    if (!hasAnyValue) {
                        shouldApply = false;
                    }
                });
            }
            
            if (shouldApply) {
                let applied = false;
                
                if (price) {
                    const priceTargetInput = row.querySelector('input[name*="[price]"]');
                    if (priceTargetInput) {
                        priceTargetInput.value = price;
                        applied = true;
                    }
                }
                
                if (listPrice) {
                    const listPriceTargetInput = row.querySelector('input[name*="[list_price]"]');
                    if (listPriceTargetInput) {
                        listPriceTargetInput.value = listPrice;
                        applied = true;
                    }
                }
                
                if (quantity) {
                    const quantityTargetInput = row.querySelector('input[name*="[quantity]"]');
                    if (quantityTargetInput) {
                        quantityTargetInput.value = quantity;
                        applied = true;
                    }
                }
                
                if (mediaFiles.length > 0) {
                    const mediaTargetInput = row.querySelector('input[name*="[media]"]');
                    if (mediaTargetInput) {
                        const dt = new DataTransfer();
                        dt.items.add(mediaFiles[0]);
                        mediaTargetInput.files = dt.files;
                        previewVariantImage(mediaTargetInput);
                        applied = true;
                    }
                }
                
                if (applied) {
                    appliedCount++;
                }
            }
        }
    });
    
    let appliedFields = [];
    if (price) appliedFields.push('price');
    if (listPrice) appliedFields.push('list price');
    if (quantity) appliedFields.push('quantity');
    if (mediaFiles.length > 0) appliedFields.push(`${mediaFiles.length} media files`);
    
    alert(`Applied ${appliedFields.join(', ')} to ${appliedCount} variants`);
    closeBulkModal();
}

function getSelectedAttributeValues() {
    const selectedCheckboxes = document.querySelectorAll('.attribute-value-checkbox:checked');
    const selectedValues = [];
    
    selectedCheckboxes.forEach(checkbox => {
        selectedValues.push({
            attribute: checkbox.dataset.attribute,
            value: checkbox.dataset.value
        });
    });
    
    return selectedValues;
}

function clearPreviousBulkValues() {
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const priceInput = row.querySelector('input[name*="[price]"]');
        if (priceInput) priceInput.value = '';
        
        const listPriceInput = row.querySelector('input[name*="[list_price]"]');
        if (listPriceInput) listPriceInput.value = '';
        
        const quantityInput = row.querySelector('input[name*="[quantity]"]');
        if (quantityInput) quantityInput.value = '';
        
        const mediaInput = row.querySelector('input[name*="[media]"]');
        if (mediaInput) {
            mediaInput.value = '';
            const previewContainer = mediaInput.parentElement.querySelector('.variant-image-preview');
            if (previewContainer) {
                previewContainer.classList.add('hidden');
            }
        }
    });
}

function clearSelectedVariantsOnly(selectedValues) {
    const rows = document.querySelectorAll('tbody tr');
    const matchingLogic = document.getElementById('matching-logic').value;
    
    rows.forEach(row => {
        const variantKeyElement = row.querySelector('.text-sm.text-gray-500');
        
        if (variantKeyElement) {
            const variantKey = variantKeyElement.textContent;
            const variantPairs = variantKey.split(',').map(pair => pair.trim());
            
            let shouldClear = false;
            
            if (matchingLogic === 'or') {
                selectedValues.forEach(({attribute, value}) => {
                    const searchPattern = `${attribute}:${value}`;
                    if (variantPairs.includes(searchPattern)) {
                        shouldClear = true;
                    }
                });
            } else {
                const groupedByAttribute = {};
                selectedValues.forEach(({attribute, value}) => {
                    if (!groupedByAttribute[attribute]) {
                        groupedByAttribute[attribute] = [];
                    }
                    groupedByAttribute[attribute].push(value);
                });
                
                shouldClear = true;
                
                Object.keys(groupedByAttribute).forEach(attribute => {
                    const values = groupedByAttribute[attribute];
                    let hasAnyValue = false;
                    
                    values.forEach(value => {
                        const searchPattern = `${attribute}:${value}`;
                        if (variantPairs.includes(searchPattern)) {
                            hasAnyValue = true;
                        }
                    });
                    
                    if (!hasAnyValue) {
                        shouldClear = false;
                    }
                });
            }
            
            if (shouldClear) {
                const priceInput = row.querySelector('input[name*="[price]"]');
                if (priceInput) priceInput.value = '';
                
                const listPriceInput = row.querySelector('input[name*="[list_price]"]');
                if (listPriceInput) listPriceInput.value = '';
                
                const quantityInput = row.querySelector('input[name*="[quantity]"]');
                if (quantityInput) quantityInput.value = '';
                
                const mediaInput = row.querySelector('input[name*="[media]"]');
                if (mediaInput) {
                    mediaInput.value = '';
                    const previewContainer = mediaInput.parentElement.querySelector('.variant-image-preview');
                    if (previewContainer) {
                        previewContainer.classList.add('hidden');
                    }
                }
            }
        }
    });
}

// Media Upload Functions
let selectedMediaFiles = [];

function handleMediaDragOver(event) {
    event.preventDefault();
    event.currentTarget.classList.add('border-purple-400', 'bg-purple-50');
}

function handleMediaDragLeave(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('border-purple-400', 'bg-purple-50');
}

function handleMediaDrop(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('border-purple-400', 'bg-purple-50');
    
    const files = Array.from(event.dataTransfer.files);
    handleMediaFiles(files);
}

function handleMediaFiles(files) {
    selectedMediaFiles = Array.from(files);
    displayMediaPreview();
}

function displayMediaPreview() {
    const previewContainer = document.getElementById('media-preview');
    const previewList = document.getElementById('media-preview-list');
    
    if (selectedMediaFiles.length === 0) {
        previewContainer.classList.add('hidden');
        return;
    }
    
    previewContainer.classList.remove('hidden');
    previewList.innerHTML = '';
    
    selectedMediaFiles.forEach((file, index) => {
        const previewItem = document.createElement('div');
        previewItem.className = 'relative bg-white rounded-lg border border-gray-200 p-3 shadow-sm';
        
        const isVideo = file.type.startsWith('video/');
        const isImage = file.type.startsWith('image/');
        
        if (isImage) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewItem.innerHTML = `
                    <div class="aspect-square rounded-lg overflow-hidden mb-2">
                        <img src="${e.target.result}" alt="${file.name}" class="w-full h-full object-cover">
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-700 truncate">${file.name}</p>
                        <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(1)} MB</p>
                    </div>
                    <button type="button" onclick="removeMediaFile(${index})" 
                            class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors">
                        ×
                    </button>
                `;
            };
            reader.readAsDataURL(file);
        } else if (isVideo) {
            previewItem.innerHTML = `
                <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gray-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="text-center">
                    <p class="text-xs font-medium text-gray-700 truncate">${file.name}</p>
                    <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(1)} MB</p>
                </div>
                <button type="button" onclick="removeMediaFile(${index})" 
                        class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors">
                    ×
                </button>
            `;
        }
        
        previewList.appendChild(previewItem);
    });
}

function removeMediaFile(index) {
    selectedMediaFiles.splice(index, 1);
    displayMediaPreview();
}

function previewVariantImage(input) {
    const previewContainer = input.parentElement.querySelector('.variant-image-preview');
    const previewImg = previewContainer.querySelector('img');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const isVideo = file.type.startsWith('video/');
        
        if (isVideo) {
            if (previewContainer.querySelector('video')) {
                previewContainer.querySelector('video').remove();
            }
            
            const video = document.createElement('video');
            video.src = URL.createObjectURL(file);
            video.controls = true;
            video.className = 'w-20 h-20 object-cover rounded border border-gray-300';
            previewContainer.appendChild(video);
            previewImg.style.display = 'none';
        } else {
            if (previewContainer.querySelector('video')) {
                previewContainer.querySelector('video').remove();
            }
            previewImg.style.display = 'block';
            
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
        previewContainer.classList.remove('hidden');
    } else {
        previewContainer.classList.add('hidden');
    }
    
    highlightVariant(input);
}

function logFormData(event) {
    const form = event.target;
    const formData = new FormData(form);
    
    console.log('=== FORM SUBMISSION DEBUG ===');
    console.log('Form action:', form.action);
    console.log('Form method:', form.method);
    
    // Group and log attributes
    const attributesMap = {};
    console.log('--- ATTRIBUTES ---');
    for (let [key, value] of formData.entries()) {
        if (key.includes('attributes[')) {
            const match = key.match(/attributes\[(\d+)\]\[(\w+)\]/);
            if (match) {
                const index = match[1];
                const field = match[2];
                if (!attributesMap[index]) {
                    attributesMap[index] = {};
                }
                attributesMap[index][field] = value;
            }
        }
    }
    console.log('Attributes map:', attributesMap);
    console.log(`Total attributes being submitted: ${Object.keys(attributesMap).length}`);
    
    // Group and log variants
    const variantsMap = {};
    console.log('--- VARIANTS ---');
    for (let [key, value] of formData.entries()) {
        if (key.includes('variants[')) {
            const match = key.match(/variants\[(\d+)\]\[(\w+)\]/);
            if (match) {
                const index = match[1];
                const field = match[2];
                if (!variantsMap[index]) {
                    variantsMap[index] = {};
                }
                variantsMap[index][field] = value;
            }
        }
    }
    console.log('Variants map:', variantsMap);
    console.log(`Total variants being submitted: ${Object.keys(variantsMap).length}`);
    
    // Log first 3 variants in detail
    Object.keys(variantsMap).slice(0, 3).forEach(key => {
        console.log(`Variant ${key}:`, variantsMap[key]);
    });
    
    console.log('=== END DEBUG ===');
    
    // Don't prevent default - let form submit
    return true;
}

</script>
@endsection
