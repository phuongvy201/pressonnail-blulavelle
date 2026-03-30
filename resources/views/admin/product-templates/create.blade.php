@extends('layouts.admin')

@section('title', 'Create Product Template')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Create Product Template</h1>
            <p class="mt-1 text-sm text-gray-600">Add a new product template with base configuration</p>
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
    <form method="POST" action="{{ route('admin.product-templates.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        
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
                           value="{{ old('name') }}"
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
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->parent_id ? '├─ ' : '' }}{{ $category->name }}{{ $category->parent ? ' (Child of ' . $category->parent->name . ')' : '' }}
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
                               value="{{ old('base_price') }}"
                               step="0.01" 
                               min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('base_price') border-red-500 @enderror"
                               placeholder="0.00"
                               onchange="applyBasePriceToAllVariants()"
                               oninput="applyBasePriceToAllVariants()"
                               required>
                    @error('base_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Giá này sẽ tự động được áp dụng cho tất cả variants</p>
                </div>

                <!-- List Price -->
                <div>
                    <label for="list_price" class="block text-sm font-medium text-gray-700 mb-2">List Price (giá niêm yết)</label>
                    <input type="number" 
                           id="list_price" 
                           name="list_price" 
                           value="{{ old('list_price') }}"
                           step="0.01" 
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('list_price') border-red-500 @enderror"
                           placeholder="0.00 (để trống = không hiển thị gạch ngang)"
                           onchange="applyListPriceToAllVariants()"
                           oninput="applyListPriceToAllVariants()">
                    @error('list_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Giá niêm yết ban đầu; hiển thị gạch ngang khi giảm giá. Có thể áp dụng cho tất cả variants hoặc nhập từng variant.</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <p class="text-xs text-gray-500 mb-2">Rich text editor - paste formatted content and it will be preserved</p>
                    
                    <!-- Hidden input to store the actual value -->
                    <input type="hidden" id="description" name="description" value="{{ old('description') }}">
                    
                    <!-- Rich Text Editor Container -->
                    <div class="border border-gray-300 rounded-lg overflow-hidden @error('description') border-red-500 @enderror">
                        <!-- Toolbar -->
                        <div class="bg-gray-50 border-b border-gray-200 p-2 flex flex-wrap items-center gap-1">
                            <button type="button" onclick="formatText('bold')" class="p-2 hover:bg-gray-200 rounded text-sm font-bold" title="Bold">
                                <strong>B</strong>
                            </button>
                            <button type="button" onclick="formatText('italic')" class="p-2 hover:bg-gray-200 rounded text-sm italic" title="Italic">
                                <em>I</em>
                            </button>
                            <button type="button" onclick="formatText('underline')" class="p-2 hover:bg-gray-200 rounded text-sm underline" title="Underline">
                                <u>U</u>
                            </button>
                            <div class="w-px h-6 bg-gray-300 mx-1"></div>
                            <button type="button" onclick="formatText('insertUnorderedList')" class="p-2 hover:bg-gray-200 rounded" title="Bullet List">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="formatText('insertOrderedList')" class="p-2 hover:bg-gray-200 rounded" title="Numbered List">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </button>
                            <div class="w-px h-6 bg-gray-300 mx-1"></div>
                            <button type="button" onclick="insertLink()" class="p-2 hover:bg-gray-200 rounded" title="Insert Link">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                </svg>
                            </button>
                            <div class="w-px h-6 bg-gray-300 mx-1"></div>
                            <button type="button" onclick="clearFormatting()" class="px-3 py-1 hover:bg-gray-200 rounded text-xs text-gray-600" title="Clear Formatting">
                                Clear Format
                            </button>
                            <button type="button" onclick="togglePreview()" class="px-3 py-1 hover:bg-gray-200 rounded text-xs text-blue-600 ml-auto" title="Toggle Preview">
                                <span id="preview-toggle-text">Preview</span>
                            </button>
                        </div>
                        
                        <!-- Editor Area -->
                        <div id="description-editor" 
                             contenteditable="true"
                             class="w-full min-h-[150px] px-4 py-3 focus:outline-none bg-white"
                             style="white-space: pre-wrap; line-height: 1.6;"
                             data-placeholder="Enter template description... You can paste formatted text here and it will be preserved."
                             oninput="updateDescriptionValue()"
                             onpaste="handlePaste(event)"
                             onkeydown="handleKeyDown(event)">{{ old('description') }}</div>
                        
                        <!-- Preview Area (Hidden by default) -->
                        <div id="description-preview" 
                             class="w-full min-h-[150px] px-4 py-3 bg-gray-50 border-t border-gray-200 hidden"
                             style="white-space: pre-wrap; line-height: 1.6;"></div>
                    </div>
                    
                    <!-- Character Count -->
                    <div class="mt-2 flex justify-between items-center text-xs text-gray-500">
                        <span>Rich text editor - supports formatting, lists, links</span>
                        <span id="char-count">0 characters</span>
                    </div>
                    
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
                            <p class="text-xl font-semibold text-gray-700">Upload Template Media</p>
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
                        Selected Files (<span id="template-file-count">0</span>):
                    </h5>
                    <p class="text-xs text-gray-500 mb-3">Ảnh chọn sau nằm cuối danh sách. Kéo thả từng ô để đổi thứ tự.</p>
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
                               {{ old('allow_customization') ? 'checked' : '' }}
                               class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                               onchange="toggleCustomizationSection()">
                        <div>
                            <span class="text-lg font-semibold text-gray-900">Allow customers to customize products</span>
                            <p class="text-sm text-gray-600">When this feature is enabled, customers can enter custom content for the product (e.g.: name, wishes, etc.)</p>
                        </div>
                    </label>
                </div>

                <!-- Customization Types -->
                <div id="customization-section" class="hidden">
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
                                <span class="text-lg font-bold">Total Customization Price: <span id="total-customization-price">$0.00</span></span>
                            </div>
                        </div>
                        
                        <!-- Customization Types Container -->
                        <div id="customization-types-container">
                            <!-- Customization types will be added here -->
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
                Create Template
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
                <button onclick="closeBulkModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                Cancel
                            </button>
                <button onclick="applyBulkValue()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                    Apply
            </button>
        </div>
                    </div>
                </div>
</div>

<script>
let attributeIndex = 1;
let variantIndex = 0;
let customizationIndex = 0;

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
    
    // Restore form data if there are old values
    restoreFormData();
});

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

function restoreFormData() {
    // Check if customization was enabled
    const allowCustomization = document.getElementById('allow_customization');
    if (allowCustomization && allowCustomization.checked) {
        toggleCustomizationSection();
    }
    
    // Restore attributes if they exist
    restoreAttributes();
    
    // Restore customizations if they exist
    restoreCustomizations();
    
    // Generate variants if attributes exist
    generateVariants();
    
    // Restore variants data if they exist
    restoreVariants();
    
    // Restore template media if they exist
    restoreTemplateMedia();
}

function restoreAttributes() {
    // Check for additional attributes in old data
    const oldAttributes = @json(old('attributes', []));
    if (oldAttributes && oldAttributes.length > 1) {
        console.log('Restoring additional attributes:', oldAttributes);
        
        for (let i = 1; i < oldAttributes.length; i++) {
            if (oldAttributes[i] && oldAttributes[i].name && oldAttributes[i].values) {
                addAttribute();
                
                // Set the values
                const nameInput = document.querySelector(`input[name="attributes[${i}][name]"]`);
                const valuesInput = document.querySelector(`input[name="attributes[${i}][values]"]`);
                
                if (nameInput) nameInput.value = oldAttributes[i].name;
                if (valuesInput) valuesInput.value = oldAttributes[i].values;
            }
        }
    }
}

function restoreCustomizations() {
    const oldCustomizations = @json(old('customizations', []));
    if (oldCustomizations && oldCustomizations.length > 0) {
        console.log('Restoring customizations:', oldCustomizations);
        
        oldCustomizations.forEach((customization, index) => {
            if (customization && customization.type && customization.label) {
                addCustomizationType();
                
                // Set the values
                const typeSelect = document.querySelector(`select[name="customizations[${index}][type]"]`);
                const priceInput = document.querySelector(`input[name="customizations[${index}][price]"]`);
                const labelInput = document.querySelector(`input[name="customizations[${index}][label]"]`);
                const placeholderInput = document.querySelector(`input[name="customizations[${index}][placeholder]"]`);
                const optionsTextarea = document.querySelector(`textarea[name="customizations[${index}][options]"]`);
                const requiredCheckbox = document.querySelector(`input[name="customizations[${index}][required]"]`);
                
                if (typeSelect) typeSelect.value = customization.type;
                if (priceInput) priceInput.value = customization.price || '';
                if (labelInput) labelInput.value = customization.label;
                if (placeholderInput) placeholderInput.value = customization.placeholder || '';
                if (optionsTextarea) optionsTextarea.value = customization.options || '';
                if (requiredCheckbox) requiredCheckbox.checked = customization.required || false;
                
                // Update customization type to show/hide options
                if (typeSelect) {
                    updateCustomizationType(typeSelect);
                }
            }
        });
    }
}

function restoreVariants() {
    const oldVariants = @json(old('variants', []));
    if (oldVariants && oldVariants.length > 0) {
        console.log('Restoring variants data:', oldVariants);
        
        // Wait a bit for variants to be generated
        setTimeout(() => {
            oldVariants.forEach((variant, index) => {
                if (variant) {
                    // Find the variant row by variant_name
                    const rows = document.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        const variantNameElement = row.querySelector('.text-sm.font-semibold.text-gray-900');
                        if (variantNameElement && variantNameElement.textContent === variant.variant_name) {
                            // Restore price
                            if (variant.price) {
                                const priceInput = row.querySelector('input[name*="[price]"]');
                                if (priceInput) priceInput.value = variant.price;
                            }
                            
                            // Restore list_price
                            if (variant.list_price != null && variant.list_price !== '') {
                                const listPriceInput = row.querySelector('input[name*="[list_price]"]');
                                if (listPriceInput) listPriceInput.value = variant.list_price;
                            }
                            
                            // Restore quantity
                            if (variant.quantity) {
                                const quantityInput = row.querySelector('input[name*="[quantity]"]');
                                if (quantityInput) quantityInput.value = variant.quantity;
                            }
                            
                            // Note: Media files cannot be restored from old() as they are file uploads
                            console.log(`Restored variant ${index + 1}: ${variant.variant_name}`);
                        }
                    });
                }
            });
        }, 500); // Wait 500ms for variants to be generated
    }
}

function restoreTemplateMedia() {
    const oldMedia = @json(old('media', []));
    if (oldMedia && oldMedia.length > 0) {
        console.log('Restoring template media:', oldMedia);
        
        // Display media previews
        displayTemplateMediaPreview(oldMedia);
    }
}

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
    
    attributeRows.forEach(row => {
        const nameInput = row.querySelector('input[name*="[name]"]');
        const valuesInput = row.querySelector('input[name*="[values]"]');
        
        if (nameInput.value.trim() && valuesInput.value.trim()) {
            const values = valuesInput.value.split(',').map(v => v.trim()).filter(v => v);
            if (values.length > 0) {
                attributes.push({
                    name: nameInput.value.trim(),
                    values: values
                });
            }
        }
    });
    
    if (attributes.length === 0) {
        showEmptyVariants();
        return;
    }
    
    // Generate all combinations
    const combinations = generateCombinations(attributes);
    displayVariants(combinations);
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

function displayVariants(combinations) {
    const container = document.getElementById('variants-container');
    
    if (combinations.length === 0) {
        showEmptyVariants();
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
    
    // Get base price and list price values
    const basePriceInput = document.getElementById('base_price');
    const basePrice = basePriceInput ? basePriceInput.value : '';
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
                               value="${basePrice}"
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
                           value="100"
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
        html += `<input type="hidden" name="variants[${index}][variant_key]" value="${variantKey}">`;
        
        // Create attributes object from combination
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

function showEmptyVariants() {
    const container = document.getElementById('variants-container');
    container.innerHTML = `
        <div class="text-center text-gray-500 py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="mt-2">Add attributes above to generate variants</p>
        </div>
    `;
}

function applyBasePriceToAllVariants() {
    const basePriceInput = document.getElementById('base_price');
    if (!basePriceInput) return;
    
    const basePrice = basePriceInput.value;
    
    // Find all variant price inputs
    const variantPriceInputs = document.querySelectorAll('input[name*="variants"][name*="[price]"]');
    
    if (variantPriceInputs.length === 0) return;
    
    // Update all variant prices
    variantPriceInputs.forEach(input => {
        input.value = basePrice;
        // Highlight the variant row to show it was updated
        highlightVariant(input);
    });
    
    console.log(`Đã áp dụng giá base_price (${basePrice}) cho ${variantPriceInputs.length} variants`);
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
    
    console.log(`Đã áp dụng list_price (${listPrice}) cho ${variantListPriceInputs.length} variants`);
}

function removeVariant(button) {
    const row = button.closest('tr');
    
    // Get variant info for logging
    const variantName = row.querySelector('.text-sm.font-semibold.text-gray-900')?.textContent || 'Unknown';
    
    // Show confirmation dialog
    if (!confirm(`Are you sure you want to permanently delete variant "${variantName}"? This action cannot be undone.`)) {
        return;
    }
    
    // Get variant index from data attribute or first input name for logging
    let variantIndex = row.dataset.variantIndex;
    if (!variantIndex) {
        const firstInput = row.querySelector('input[name*="variants["]');
        if (firstInput) {
            const match = firstInput.name.match(/variants\[(\d+)\]/);
            variantIndex = match ? match[1] : undefined;
        }
    }
    
    // Create hidden inputs to mark variant as removed for backend
    const form = document.querySelector('form');
    
    // Main removed input - THIS IS CRITICAL
    const removedInput = document.createElement('input');
    removedInput.type = 'hidden';
    removedInput.name = `variants[${variantIndex || 'deleted'}][removed]`;
    removedInput.value = '1';
    form.appendChild(removedInput);
    
    // Get attributes for backend to identify which variant to delete
    const attributesInput = row.querySelector('input[name*="[attributes]"]');
    if (attributesInput) {
        const attrInput = document.createElement('input');
        attrInput.type = 'hidden';
        attrInput.name = `variants[${variantIndex || 'deleted'}][attributes]`;
        attrInput.value = attributesInput.value;
        form.appendChild(attrInput);
    }
    
    // Get variant name for backend
    const variantNameInput = row.querySelector('input[name*="[variant_name]"]');
    if (variantNameInput) {
        const nameInput = document.createElement('input');
        nameInput.type = 'hidden';
        nameInput.name = `variants[${variantIndex || 'deleted'}][variant_name]`;
        nameInput.value = variantNameInput.value;
        form.appendChild(nameInput);
    }
    
    console.log(`✅ Added removed input: variants[${variantIndex}][removed] = 1`);
    
    // Add visual effect before removal
    row.style.transition = 'all 0.3s ease';
    row.style.opacity = '0';
    row.style.maxHeight = '0';
    row.style.padding = '0';
    row.style.margin = '0';
    
    // Remove row completely after animation
    setTimeout(() => {
        row.remove();
        console.log(`🗑️ PERMANENTLY DELETED variant ${variantIndex}: ${variantName}`);
    }, 300);
}


// Highlight variant when user interacts with it
function highlightVariant(input) {
    const row = input.closest('tr');
    row.classList.add('bg-yellow-50', 'border-yellow-200');
    row.style.borderLeft = '4px solid #f59e0b';
    
    // Remove highlight after 2 seconds
    setTimeout(() => {
        row.classList.remove('bg-yellow-50', 'border-yellow-200');
        row.style.borderLeft = '';
    }, 2000);
}

// Bulk Actions Functions

// Modal Functions
let currentBulkType = '';

function openBulkModal(type) {
    currentBulkType = type;
    const modal = document.getElementById('bulk-modal');
    
    // Populate attribute selection
    populateAttributeSelection();
    
    // Show modal
    modal.classList.remove('hidden');
}

function closeBulkModal() {
    const modal = document.getElementById('bulk-modal');
    modal.classList.add('hidden');
    // Reset media files
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

function applyBulkValue() {
    // Get input values
    const priceInput = document.getElementById('bulk-price-input');
    const listPriceInput = document.getElementById('bulk-list-price-input');
    const quantityInput = document.getElementById('bulk-quantity-input');
    
    const price = priceInput ? priceInput.value : '';
    const listPrice = listPriceInput ? listPriceInput.value : '';
    const quantity = quantityInput ? quantityInput.value : '';
    const mediaFiles = selectedMediaFiles;
    
    // Check if at least one value is provided
    if (!price && !listPrice && !quantity && mediaFiles.length === 0) {
        alert('Please enter at least one value (price, list price, quantity, or media)');
        return;
    }
    
    // Get selected attribute values
    const selectedValues = getSelectedAttributeValues();
    if (selectedValues.length === 0) {
        alert('Please select at least one attribute value');
        return;
    }
    
    // Clear previous bulk applied values if checkbox is checked
    const clearExistingCheckbox = document.getElementById('clear-existing-values');
    if (clearExistingCheckbox && clearExistingCheckbox.checked) {
        const clearMode = document.getElementById('clear-mode').value;
        if (clearMode === 'selected') {
            // Clear only the variants that match current selection
            clearSelectedVariantsOnly(selectedValues);
        } else {
            // Clear all variants
            clearPreviousBulkValues();
        }
    }
    
    // Debug logging
    console.log('Selected values:', selectedValues);
    
    // Find matching variants and apply values
    let appliedCount = 0;
    const rows = document.querySelectorAll('tbody tr');
    
    console.log('Total variants found:', rows.length);
    
    // Show debug info in alert
    if (rows.length === 0) {
        alert('No variants found! Please make sure you have added attributes and generated variants.');
        return;
    }
    
    rows.forEach((row, index) => {
        // Get variant info from displayed text instead of hidden inputs
        const variantNameElement = row.querySelector('.text-sm.font-semibold.text-gray-900');
        const variantKeyElement = row.querySelector('.text-sm.text-gray-500');
        
        if (variantNameElement && variantKeyElement) {
            const variantName = variantNameElement.textContent;
            const variantKey = variantKeyElement.textContent;
            
            console.log(`Variant ${index + 1} name:`, variantName);
            console.log(`Variant ${index + 1} key:`, variantKey);
            console.log(`Selected values to match:`, selectedValues);
            
            // Check if this variant matches selected attribute values based on logic
            let shouldApply = false;
            
            // Parse variant key to get all attribute-value pairs
            const variantPairs = variantKey.split(',').map(pair => pair.trim());
            console.log(`Variant pairs:`, variantPairs);
            
            // Get selected logic
            const matchingLogic = document.getElementById('matching-logic').value;
            console.log(`Using logic: ${matchingLogic}`);
            
            if (matchingLogic === 'or') {
                // OR Logic: Match if variant has ANY of the selected attributes
                selectedValues.forEach(({attribute, value}) => {
                    const searchPattern = `${attribute}:${value}`;
                    console.log(`Checking pattern: ${searchPattern} in variant pairs:`, variantPairs);
                    
                    if (variantPairs.includes(searchPattern)) {
                        shouldApply = true;
                        console.log(`✅ OR Match found: ${searchPattern} in ${variantKey}`);
                    } else {
                        console.log(`❌ No OR match: ${searchPattern} not found in ${variantKey}`);
                    }
                });
            } else {
                // AND Logic: Match if variant has ALL of the selected attributes
                // Group selected values by attribute
                const groupedByAttribute = {};
                selectedValues.forEach(({attribute, value}) => {
                    if (!groupedByAttribute[attribute]) {
                        groupedByAttribute[attribute] = [];
                    }
                    groupedByAttribute[attribute].push(value);
                });
                
                console.log('Grouped by attribute:', groupedByAttribute);
                
                shouldApply = true; // Start with true
                
                // For each attribute, check if variant has ANY of its values
                Object.keys(groupedByAttribute).forEach(attribute => {
                    const values = groupedByAttribute[attribute];
                    let hasAnyValue = false;
                    
                    values.forEach(value => {
                        const searchPattern = `${attribute}:${value}`;
                        if (variantPairs.includes(searchPattern)) {
                            hasAnyValue = true;
                            console.log(`✅ AND Match found: ${searchPattern} in ${variantKey}`);
                        }
                    });
                    
                    if (!hasAnyValue) {
                        shouldApply = false;
                        console.log(`❌ AND Match failed: No ${attribute} values found in ${variantKey}`);
                    }
                });
            }
            
            console.log(`Should apply to variant ${index + 1}:`, shouldApply);
            
            if (shouldApply) {
                console.log(`Applying to variant ${index + 1}`);
                let applied = false;
                
                // Apply price if provided
                if (price) {
                    const priceTargetInput = row.querySelector('input[name*="[price]"]');
                    if (priceTargetInput) {
                        priceTargetInput.value = price;
                        applied = true;
                    }
                }
                
                // Apply list_price if provided
                if (listPrice) {
                    const listPriceTargetInput = row.querySelector('input[name*="[list_price]"]');
                    if (listPriceTargetInput) {
                        listPriceTargetInput.value = listPrice;
                        applied = true;
                    }
                }
                
                // Apply quantity if provided
                if (quantity) {
                    const quantityTargetInput = row.querySelector('input[name*="[quantity]"]');
                    if (quantityTargetInput) {
                        quantityTargetInput.value = quantity;
                        applied = true;
                    }
                }
                
                // Apply media if provided (only first file for variants)
                if (mediaFiles.length > 0) {
                    const mediaTargetInput = row.querySelector('input[name*="[media]"]');
                    if (mediaTargetInput) {
        const dt = new DataTransfer();
                        dt.items.add(mediaFiles[0]); // Only first file for variant
                        mediaTargetInput.files = dt.files;
                        
                        // Trigger preview
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
    
    // Show success message
    let appliedFields = [];
    if (price) appliedFields.push('price');
    if (listPrice) appliedFields.push('list price');
    if (quantity) appliedFields.push('quantity');
    if (mediaFiles.length > 0) appliedFields.push(`${mediaFiles.length} media files`);
    
    console.log(`Applied ${appliedFields.join(', ')} to ${appliedCount} variants`);
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
    // Clear all price, quantity, and media inputs in variants table
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        // Clear price
            const priceInput = row.querySelector('input[name*="[price]"]');
            if (priceInput) {
            priceInput.value = '';
        }
        
        // Clear list_price
        const listPriceInput = row.querySelector('input[name*="[list_price]"]');
        if (listPriceInput) {
            listPriceInput.value = '';
        }
        
        // Clear quantity
        const quantityInput = row.querySelector('input[name*="[quantity]"]');
        if (quantityInput) {
            quantityInput.value = '';
        }
        
        // Clear media
        const mediaInput = row.querySelector('input[name*="[media]"]');
        if (mediaInput) {
            mediaInput.value = '';
            // Also clear preview
            const previewContainer = mediaInput.parentElement.querySelector('.variant-image-preview');
            if (previewContainer) {
                previewContainer.classList.add('hidden');
            }
        }
    });
    
    console.log('Cleared all previous bulk values');
}

function clearSelectedVariantsOnly(selectedValues) {
    // Clear only the variants that match the selected attribute values
    const rows = document.querySelectorAll('tbody tr');
    const matchingLogic = document.getElementById('matching-logic').value;
    
    rows.forEach(row => {
        const variantNameElement = row.querySelector('.text-sm.font-semibold.text-gray-900');
        const variantKeyElement = row.querySelector('.text-sm.text-gray-500');
        
        if (variantNameElement && variantKeyElement) {
            const variantKey = variantKeyElement.textContent;
            const variantPairs = variantKey.split(',').map(pair => pair.trim());
            
            // Check if this variant matches selected attribute values based on logic
            let shouldClear = false;
            
            if (matchingLogic === 'or') {
                // OR Logic: Clear if variant has ANY of the selected attributes
                selectedValues.forEach(({attribute, value}) => {
                    const searchPattern = `${attribute}:${value}`;
                    if (variantPairs.includes(searchPattern)) {
                        shouldClear = true;
                    }
                });
            } else {
                // AND Logic: Clear if variant has ALL of the selected attributes
                // Group selected values by attribute
                const groupedByAttribute = {};
                selectedValues.forEach(({attribute, value}) => {
                    if (!groupedByAttribute[attribute]) {
                        groupedByAttribute[attribute] = [];
                    }
                    groupedByAttribute[attribute].push(value);
                });
                
                shouldClear = true; // Start with true
                
                // For each attribute, check if variant has ANY of its values
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
                // Clear price
                const priceInput = row.querySelector('input[name*="[price]"]');
                if (priceInput) {
                    priceInput.value = '';
                }
                
                // Clear list_price
                const listPriceInput = row.querySelector('input[name*="[list_price]"]');
                if (listPriceInput) {
                    listPriceInput.value = '';
                }
                
                // Clear quantity
            const quantityInput = row.querySelector('input[name*="[quantity]"]');
            if (quantityInput) {
                    quantityInput.value = '';
                }
                
                // Clear media
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
    
    console.log('Cleared values for selected variants only');
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
        } else {
            previewItem.innerHTML = `
                <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gray-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
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

// Variant Image Preview
function previewVariantImage(input) {
    const previewContainer = input.parentElement.querySelector('.variant-image-preview');
    const previewImg = previewContainer.querySelector('img');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const isVideo = file.type.startsWith('video/');
        
        if (isVideo) {
            // For video, show video element instead of img
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
            // For image, show img element
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
    const incoming = Array.from(files || []);
    if (incoming.length === 0) {
        return;
    }

    // Gộp với file đã chọn trước đó (Choose Files lần 2+ hoặc kéo thêm) — không ghi đè
    const merged = templateMediaFiles.slice();
    incoming.forEach((file) => {
        const dup = merged.some(
            (f) =>
                f.name === file.name &&
                f.size === file.size &&
                f.lastModified === file.lastModified
        );
        if (!dup) {
            merged.push(file);
        }
    });
    templateMediaFiles = merged;

    syncTemplateMediaFilesToInput();
    displayTemplateMediaPreview();
}

function syncTemplateMediaFilesToInput() {
    const input = document.getElementById('media');
    if (!input) {
        return;
    }
    const dt = new DataTransfer();
    templateMediaFiles.forEach((file) => dt.items.add(file));
    input.files = dt.files;
}

let templateMediaDragFromIndex = null;

function finalizeTemplateMediaPreviewItem(el, index, enableDrag) {
    el.dataset.templateMediaIndex = String(index);
    if (!enableDrag) {
        el.draggable = false;
        return;
    }
    el.draggable = true;
    el.classList.add('cursor-grab', 'active:cursor-grabbing', 'select-none');
    el.title = 'Kéo để đổi thứ tự';
    el.addEventListener('dragstart', templateMediaPreviewDragStart);
    el.addEventListener('dragover', templateMediaPreviewDragOver);
    el.addEventListener('dragleave', templateMediaPreviewDragLeave);
    el.addEventListener('drop', templateMediaPreviewDrop);
    el.addEventListener('dragend', templateMediaPreviewDragEnd);
}

function templateMediaPreviewDragStart(e) {
    if (e.target.closest('button')) {
        e.preventDefault();
        return;
    }
    templateMediaDragFromIndex = parseInt(e.currentTarget.dataset.templateMediaIndex, 10);
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', String(templateMediaDragFromIndex));
    e.currentTarget.classList.add('opacity-60', 'ring-2', 'ring-blue-400');
}

function templateMediaPreviewDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    e.currentTarget.classList.add('ring-2', 'ring-blue-500', 'border-blue-400');
}

function templateMediaPreviewDragLeave(e) {
    e.currentTarget.classList.remove('ring-2', 'ring-blue-500', 'border-blue-400');
}

function templateMediaPreviewDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    const from = templateMediaDragFromIndex;
    const to = parseInt(e.currentTarget.dataset.templateMediaIndex, 10);
    e.currentTarget.classList.remove('ring-2', 'ring-blue-500', 'border-blue-400');
    if (from === null || Number.isNaN(from) || Number.isNaN(to) || from === to) {
        return;
    }
    reorderTemplateMediaFiles(from, to);
}

function templateMediaPreviewDragEnd(e) {
    e.currentTarget.classList.remove('opacity-60', 'ring-2', 'ring-blue-400');
    document.querySelectorAll('#template-media-preview-list .template-media-preview-item').forEach((el) => {
        el.classList.remove('ring-2', 'ring-blue-500', 'border-blue-400');
    });
    templateMediaDragFromIndex = null;
}

function reorderTemplateMediaFiles(fromIndex, toIndex) {
    const arr = templateMediaFiles;
    if (fromIndex < 0 || fromIndex >= arr.length || toIndex < 0 || toIndex >= arr.length) {
        return;
    }
    const [moved] = arr.splice(fromIndex, 1);
    arr.splice(toIndex, 0, moved);
    syncTemplateMediaFilesToInput();
    displayTemplateMediaPreview();
}

function displayTemplateMediaPreview(mediaData = null) {
    const previewContainer = document.getElementById('template-media-preview');
    const previewList = document.getElementById('template-media-preview-list');
    const fileCount = document.getElementById('template-file-count');
    
    // Use provided mediaData or templateMediaFiles
    const filesToDisplay = mediaData || templateMediaFiles;
    
    if (filesToDisplay.length === 0) {
        previewContainer.classList.add('hidden');
        return;
    }
    
    previewContainer.classList.remove('hidden');
    fileCount.textContent = filesToDisplay.length;
    previewList.innerHTML = '';

    const itemBaseClass =
        'relative bg-white rounded-lg border-2 border-gray-200 p-2 shadow-sm hover:shadow-md transition-shadow template-media-preview-item';

    filesToDisplay.forEach((file, index) => {
        const previewItem = document.createElement('div');
        const enableDrag = !mediaData && typeof file !== 'string';

        const isUrl = typeof file === 'string';
        const isVideo = isUrl
            ? file.includes('.mp4') || file.includes('.mov') || file.includes('.avi')
            : file.type.startsWith('video/');
        const isImage = isUrl
            ? file.includes('.jpg') ||
              file.includes('.jpeg') ||
              file.includes('.png') ||
              file.includes('.gif')
            : file.type.startsWith('image/');

        if (isImage) {
            if (isUrl) {
                previewItem.className = itemBaseClass;
                previewItem.innerHTML = `
                    <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gray-100">
                        <img src="${file}" alt="Media" class="w-full h-full object-cover">
                    </div>
                    <div class="text-center px-1">
                        <p class="text-xs font-medium text-gray-700 truncate">Image</p>
                        <p class="text-xs text-gray-500">Restored</p>
                    </div>
                    <button type="button" draggable="false" onclick="removeTemplateMediaFile(${index})"
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
                finalizeTemplateMediaPreviewItem(previewItem, index, false);
            } else {
                previewItem.className = itemBaseClass;
                previewItem.innerHTML = `
                    <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gray-100 flex items-center justify-center min-h-[5rem]">
                        <span class="text-xs text-gray-400">Đang tải…</span>
                    </div>
                `;
                previewList.appendChild(previewItem);
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewItem.innerHTML = `
                        <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gray-100">
                            <img src="${e.target.result}" alt="${file.name}" class="w-full h-full object-cover">
                        </div>
                        <div class="text-center px-1">
                            <p class="text-xs font-medium text-gray-700 truncate" title="${file.name}">${file.name}</p>
                            <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                        </div>
                        <button type="button" draggable="false" onclick="removeTemplateMediaFile(${index})"
                                class="absolute -top-2 -right-2 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <div class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full font-semibold">
                            Image
                        </div>
                    `;
                    finalizeTemplateMediaPreviewItem(previewItem, index, enableDrag);
                };
                reader.readAsDataURL(file);
            }
        } else if (isVideo) {
            if (isUrl) {
                previewItem.className = itemBaseClass;
                previewItem.innerHTML = `
                    <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                        <video class="w-full h-full object-cover" controls>
                            <source src="${file}" type="video/mp4">
                        </video>
                    </div>
                    <div class="text-center px-1">
                        <p class="text-xs font-medium text-gray-700 truncate">Video</p>
                        <p class="text-xs text-gray-500">Restored</p>
                    </div>
                    <button type="button" draggable="false" onclick="removeTemplateMediaFile(${index})"
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
                finalizeTemplateMediaPreviewItem(previewItem, index, false);
            } else {
                previewItem.className = itemBaseClass;
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
                    <button type="button" draggable="false" onclick="removeTemplateMediaFile(${index})"
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
                finalizeTemplateMediaPreviewItem(previewItem, index, enableDrag);
            }
        } else {
            if (isUrl) {
                previewItem.className = itemBaseClass;
                previewItem.innerHTML = `
                    <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gray-100 flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="text-center px-1">
                        <p class="text-xs font-medium text-gray-700 truncate">File</p>
                        <p class="text-xs text-gray-500">Restored</p>
                    </div>
                    <button type="button" draggable="false" onclick="removeTemplateMediaFile(${index})"
                            class="absolute -top-2 -right-2 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div class="absolute top-2 left-2 bg-gray-500 text-white text-xs px-2 py-1 rounded-full font-semibold">
                        File
                    </div>
                `;
                previewList.appendChild(previewItem);
                finalizeTemplateMediaPreviewItem(previewItem, index, false);
            } else {
                previewItem.className = itemBaseClass;
                previewItem.innerHTML = `
                    <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gray-100 flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="text-center px-1">
                        <p class="text-xs font-medium text-gray-700 truncate" title="${file.name}">${file.name}</p>
                        <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                    </div>
                    <button type="button" draggable="false" onclick="removeTemplateMediaFile(${index})"
                            class="absolute -top-2 -right-2 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div class="absolute top-2 left-2 bg-gray-500 text-white text-xs px-2 py-1 rounded-full font-semibold">
                        File
                    </div>
                `;
                previewList.appendChild(previewItem);
                finalizeTemplateMediaPreviewItem(previewItem, index, enableDrag);
            }
        }
    });
}

function removeTemplateMediaFile(index) {
    templateMediaFiles.splice(index, 1);
    syncTemplateMediaFilesToInput();
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
            <!-- Type Dropdown -->
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
            
            <!-- Price Input -->
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
            
            <!-- Display Label -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Display Label</label>
                <input type="text" 
                       name="customizations[${customizationIndex}][label]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="e.g.: Name, Number, Logo..."
                       required>
            </div>
            
            <!-- Action Button -->
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
        
        <!-- Additional Fields -->
        <div class="mt-4 space-y-4">
            <!-- Placeholder -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Placeholder</label>
                <input type="text" 
                       name="customizations[${customizationIndex}][placeholder]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Suggested text...">
            </div>
            
            <!-- Options for Select type -->
            <div id="select-options-${customizationIndex}" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Options (one per line)</label>
                <textarea name="customizations[${customizationIndex}][options]" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
            </div>
            
            <!-- Required Checkbox -->
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

// Rich Text Editor Functions
function updateDescriptionValue() {
    const editor = document.getElementById('description-editor');
    const hiddenInput = document.getElementById('description');
    const preview = document.getElementById('description-preview');
    const charCount = document.getElementById('char-count');
    
    // Update hidden input with HTML content
    hiddenInput.value = editor.innerHTML;
    
    // Update preview
    if (preview) {
        preview.innerHTML = editor.innerHTML;
    }
    
    // Update character count
    if (charCount) {
        const text = editor.innerText || editor.textContent || '';
        charCount.textContent = `${text.length} characters`;
    }
}

function formatText(command) {
    document.execCommand(command, false, null);
    updateDescriptionValue();
}

function clearFormatting() {
    const editor = document.getElementById('description-editor');
    const text = editor.innerText || editor.textContent || '';
    editor.innerHTML = text;
    updateDescriptionValue();
}

function insertLink() {
    const url = prompt('Enter URL:');
    if (url) {
        const text = prompt('Enter link text:', url);
        if (text) {
            document.execCommand('createLink', false, url);
            updateDescriptionValue();
        }
    }
}

function togglePreview() {
    const editor = document.getElementById('description-editor');
    const preview = document.getElementById('description-preview');
    const toggleText = document.getElementById('preview-toggle-text');
    
    if (preview.classList.contains('hidden')) {
        // Show preview
        preview.classList.remove('hidden');
        editor.classList.add('hidden');
        preview.innerHTML = editor.innerHTML;
        toggleText.textContent = 'Edit';
    } else {
        // Show editor
        preview.classList.add('hidden');
        editor.classList.remove('hidden');
        toggleText.textContent = 'Preview';
    }
}

function handlePaste(event) {
    // Allow default paste behavior to preserve formatting
    // The browser will handle rich text paste automatically
    setTimeout(() => {
        updateDescriptionValue();
    }, 100);
}

function handleKeyDown(event) {
    // Handle keyboard shortcuts
    if (event.ctrlKey || event.metaKey) {
        switch(event.key) {
            case 'b':
                event.preventDefault();
                formatText('bold');
                break;
            case 'i':
                event.preventDefault();
                formatText('italic');
                break;
            case 'u':
                event.preventDefault();
                formatText('underline');
                break;
            case 'k':
                event.preventDefault();
                insertLink();
                break;
        }
    }
}

// Initialize editor on page load
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('description-editor');
    const hiddenInput = document.getElementById('description');
    
    // If there's old value, set it
    if (hiddenInput.value) {
        editor.innerHTML = hiddenInput.value;
    }
    
    // Initialize character count
    updateDescriptionValue();
    
    // Add focus behavior
    editor.addEventListener('focus', function() {
        this.style.outline = 'none';
    });
    
    // Add toolbar button active states
    const toolbarButtons = document.querySelectorAll('[onclick*="formatText"]');
    toolbarButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            toolbarButtons.forEach(btn => btn.classList.remove('toolbar-button-active'));
            // Add active class to clicked button
            this.classList.add('toolbar-button-active');
            
            // Remove active class after a short delay
            setTimeout(() => {
                this.classList.remove('toolbar-button-active');
            }, 200);
        });
    });
    
    // Add paste event listener for better formatting preservation
    editor.addEventListener('paste', function(e) {
        // Allow default paste behavior first
        setTimeout(() => {
            // Clean up any unwanted formatting
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                const fragment = range.extractContents();
                
                // Remove unwanted attributes but keep basic formatting
                const walker = document.createTreeWalker(
                    fragment,
                    NodeFilter.SHOW_ELEMENT,
                    null,
                    false
                );
                
                const elementsToProcess = [];
                let node;
                while (node = walker.nextNode()) {
                    elementsToProcess.push(node);
                }
                
                elementsToProcess.forEach(el => {
                    // Keep only basic formatting tags
                    const allowedTags = ['strong', 'em', 'u', 'b', 'i', 'p', 'br', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
                    if (!allowedTags.includes(el.tagName.toLowerCase())) {
                        // Replace with span or unwrap
                        const parent = el.parentNode;
                        while (el.firstChild) {
                            parent.insertBefore(el.firstChild, el);
                        }
                        parent.removeChild(el);
                    } else {
                        // Remove unwanted attributes
                        Array.from(el.attributes).forEach(attr => {
                            if (!['href', 'target'].includes(attr.name)) {
                                el.removeAttribute(attr.name);
                            }
                        });
                    }
                });
                
                range.insertNode(fragment);
                updateDescriptionValue();
            }
        }, 10);
    });
});

</script>
<style>
/* Rich Text Editor Styles */
#description-editor:empty:before {
    content: attr(data-placeholder);
    color: #9CA3AF;
    pointer-events: none;
    position: absolute;
}

#description-editor {
    position: relative;
}

#description-editor:focus {
    outline: none;
}

/* Formatting styles */
#description-editor strong, #description-preview strong {
    font-weight: bold;
}

#description-editor em, #description-preview em {
    font-style: italic;
}

#description-editor u, #description-preview u {
    text-decoration: underline;
}

#description-editor a, #description-preview a {
    color: #2563eb;
    text-decoration: underline;
}

#description-editor a:hover, #description-preview a:hover {
    color: #1d4ed8;
}

/* List styles */
#description-editor ul, #description-preview ul {
    list-style-type: disc;
    margin-left: 1.5rem;
    margin-bottom: 0.5rem;
}

#description-editor ol, #description-preview ol {
    list-style-type: decimal;
    margin-left: 1.5rem;
    margin-bottom: 0.5rem;
}

#description-editor li, #description-preview li {
    margin-bottom: 0.25rem;
}

/* Paragraph styles */
#description-editor p, #description-preview p {
    margin-bottom: 0.5rem;
}

/* Headings */
#description-editor h1, #description-preview h1 {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.75rem;
}

#description-editor h2, #description-preview h2 {
    font-size: 1.25rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

#description-editor h3, #description-preview h3 {
    font-size: 1.125rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

/* Blockquote */
#description-editor blockquote, #description-preview blockquote {
    border-left: 4px solid #d1d5db;
    padding-left: 1rem;
    margin: 1rem 0;
    font-style: italic;
    color: #6b7280;
}

/* Code */
#description-editor code, #description-preview code {
    background-color: #f3f4f6;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 0.875rem;
}

/* Toolbar button active state */
.toolbar-button-active {
    background-color: #dbeafe !important;
    color: #1d4ed8 !important;
}

/* Character count styling */
#char-count {
    font-weight: 500;
    color: #6b7280;
}

/* Preview area styling */
#description-preview {
    background-color: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

/* Selection styling */
#description-editor::selection {
    background-color: #dbeafe;
    color: #1d4ed8;
}

/* Removed variant styling */
.removed-variant {
    opacity: 0.6;
    background-color: #fef2f2;
    border-left: 4px solid #ef4444;
}

.removed-variant td {
    text-decoration: line-through;
    color: #9ca3af;
}

.removed-variant input:not([type="hidden"]),
.removed-variant button {
    pointer-events: none;
    opacity: 0.5;
}
</style>
@endsection
