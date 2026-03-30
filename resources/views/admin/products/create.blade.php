@extends('layouts.admin')

@section('title', 'Create Product')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Create Product</h1>
            <p class="mt-1 text-sm text-gray-600">Create a new product from a template</p>
        </div>
        <a href="{{ route('admin.products.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Products
        </a>
    </div>

    <!-- Error Messages -->
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-start">
                <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="font-medium mb-2">Please fix the following errors:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Success Message -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Form -->
    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-6" onsubmit="return validateForm()">
        @csrf
        
        <!-- Template Selection -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <h3 class="text-lg font-medium text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Select Template *
                </h3>
                <p class="text-sm text-gray-600 mt-1">Choose a template to base this product on</p>
            </div>
            <div class="p-6">
                <select id="template_id" 
                        name="template_id" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('template_id') border-red-500 @enderror"
                        required
                        onchange="loadTemplateData(this.value)">
                    <option value="">-- Select a Template --</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}" 
                                data-name="{{ $template->name }}"
                                data-price="{{ $template->base_price }}"
                                data-list-price="{{ $template->list_price ?? '' }}"
                                data-description="{{ $template->description }}"
                                data-variants='@json($template->variants)'
                                {{ old('template_id') == $template->id ? 'selected' : '' }}>
                            #{{ $template->id }} - {{ $template->name }} ({{ $template->category->name }}) - ${{ number_format($template->base_price, 2) }}
                        </option>
                    @endforeach
                </select>
                @error('template_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                
                <!-- Template Preview -->
                <div id="template-preview" class="hidden mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Template Preview:</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm mb-3">
                        <div>
                            <span class="text-gray-600">Base Price:</span>
                            <span class="font-semibold text-gray-900" id="preview-price">$0.00</span>
                        </div>
                        <div>
                            <span class="text-gray-600">List Price:</span>
                            <span class="font-semibold text-gray-900" id="preview-list-price">—</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Variants:</span>
                            <span class="font-semibold text-gray-900" id="preview-variants-count">0</span>
                        </div>
                    </div>
                    <div class="border-t border-blue-200 pt-3">
                        <span class="text-gray-600 text-sm">Template Description:</span>
                        <div id="preview-description" class="mt-1 text-sm text-gray-700 bg-white p-2 rounded border max-h-20 overflow-y-auto"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Basic Information -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Product Information</h3>
            </div>
            <div class="p-6 space-y-6">
                <!-- Product Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                           placeholder="Enter product name"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Price & Quantity -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Price -->
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                            Product Price
                        </label>
                        
                        <!-- Price Type Selection -->
                        <div class="mb-3">
                            <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" 
                                           name="price_type" 
                                           value="template" 
                                           checked
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                           onchange="updatePriceType()">
                                    <span class="text-sm font-medium text-gray-700">Use Template Price</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" 
                                           name="price_type" 
                                           value="override" 
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                           onchange="updatePriceType()">
                                    <span class="text-sm font-medium text-gray-700">Override Price</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" 
                                           name="price_type" 
                                           value="add" 
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                           onchange="updatePriceType()">
                                    <span class="text-sm font-medium text-gray-700">Add to Template Price</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Price Input -->
                        <div id="price-input-container" class="hidden">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 font-medium" id="price-prefix">$</span>
                                </div>
                                <input type="number" 
                                       id="price" 
                                       name="price" 
                                       value="{{ old('price') }}"
                                       step="0.01"
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('price') border-red-500 @enderror"
                                       placeholder="0.00">
                            </div>
                            <p class="mt-1 text-xs text-gray-500" id="price-hint">Enter the amount to override template price</p>
                        </div>
                        <div class="mt-4">
                            <label for="list_price" class="block text-sm font-medium text-gray-700 mb-1">List Price (giá niêm yết, hiển thị gạch ngang khi giảm giá)</label>
                            <input type="number" id="list_price" name="list_price" value="{{ old('list_price') }}" step="0.01" min="0" placeholder="Để trống = dùng từ template"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <!-- Template Price Preview -->
                        <div id="template-price-preview" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg hidden">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Template Price:</span>
                                <span class="text-lg font-bold text-blue-600" id="template-price-display">$0.00</span>
                            </div>
                            <div id="final-price-display" class="mt-2 pt-2 border-t border-blue-200 hidden">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-700">Final Price:</span>
                                    <span class="text-xl font-bold text-green-600" id="final-price-value">$0.00</span>
                                </div>
                            </div>
                        </div>
                        
                        @error('price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Total Quantity *</label>
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               value="{{ old('quantity', 0) }}"
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('quantity') border-red-500 @enderror"
                               placeholder="0"
                               required>
                        @error('quantity')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Status & Shop Assignment Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select id="status" 
                                name="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror"
                                required>
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Shop Assignment (Admin Only) -->
                    @if(auth()->user()->hasRole('admin') && $shops)
                    <div>
                        <label for="shop_id" class="block text-sm font-medium text-gray-700 mb-2">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                <span>Assign to Shop</span>
                            </div>
                        </label>
                        <select id="shop_id" 
                                name="shop_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('shop_id') border-red-500 @enderror">
                            <option value="">-- No Shop (Unassigned) --</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" 
                                        {{ old('shop_id') == $shop->id ? 'selected' : '' }}
                                        data-owner="{{ $shop->user ? $shop->user->name : 'Unknown' }}"
                                        data-status="{{ $shop->shop_status }}">
                                    {{ $shop->shop_name }} 
                                    @if($shop->shop_status === 'active')
                                        ✓
                                    @elseif($shop->shop_status === 'suspended')
                                        ⚠️
                                    @endif
                                    (Owner: {{ $shop->user ? $shop->user->name : 'Unknown' }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            <span class="inline-flex items-center">
                                <svg class="w-3 h-3 mr-1 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                Admin can assign product to any shop
                            </span>
                        </p>
                        @error('shop_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Product Description (Optional)
                    </label>
                    <p class="text-xs text-gray-500 mb-3">
                        <span class="font-medium text-blue-600">💡 Tip:</span> 
                        Leave empty to automatically use the template description, or enter your own custom description.
                    </p>
                    <input type="hidden" id="description" name="description" value="{{ old('description') }}">
                    <div id="description-editor" 
                         contenteditable="true"
                         class="w-full min-h-[100px] px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror bg-white"
                         style="white-space: pre-wrap;"
                         placeholder="Enter your custom product description here... (Leave empty to use template description)"
                         oninput="updateDescriptionValue()">{{ old('description') }}</div>
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
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Product Media (Optional)
                </h3>
                <p class="text-sm text-gray-600">Upload custom media or leave empty to use template media. Mỗi lần chọn &quot;Choose Files&quot; sẽ thêm ảnh/video vào danh sách đã chọn (không thay thế).</p>
            </div>
            <div class="p-6">
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-purple-400 transition-colors bg-gray-50" 
                     id="product-media-drop-zone"
                     ondrop="handleMediaDrop(event)" 
                     ondragover="handleMediaDragOver(event)"
                     ondragleave="handleMediaDragLeave(event)">
                    <input type="file" 
                           id="media" 
                           name="media[]" 
                           multiple
                           accept="image/*,video/*"
                           class="hidden"
                           onchange="handleMediaFiles(this.files); this.value='';">
                    
                    <div class="space-y-4">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-lg font-semibold text-gray-700">Upload Product Media</p>
                        <p class="text-sm text-gray-500">Drag and drop files here, or click to browse</p>
                        <button type="button" 
                                onclick="document.getElementById('media').click()"
                                class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all shadow-md">
                            Choose Files
                        </button>
                    </div>
                </div>
                
                <div id="media-preview" class="mt-6 hidden">
                    <h5 class="text-sm font-semibold text-gray-700 mb-4">Selected Files (Drag to reorder):</h5>
                    <div id="media-preview-list" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4"></div>
                    <input type="hidden" id="media-order" name="media_order" value="">
                </div>
            </div>
        </div>

        <!-- Variants from Template -->
        <div id="variants-section" class="bg-white shadow rounded-lg hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-teal-50">
                <h3 class="text-lg font-medium text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    Product Variants
                </h3>
                <p class="text-sm text-gray-600 mt-1">Set price and quantity for each variant</p>
            </div>
            <div class="p-6">
                <div id="variants-container">
                    <!-- Variants will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('admin.products.index') }}" 
               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                Create Product
            </button>
        </div>
    </form>
</div>

<script>
let templateVariants = [];
let selectedMediaFiles = [];

// Load template data when template is selected
function loadTemplateData(templateId) {
    if (!templateId) {
        document.getElementById('template-preview').classList.add('hidden');
        document.getElementById('variants-section').classList.add('hidden');
        return;
    }
    
    const select = document.getElementById('template_id');
    const selectedOption = select.options[select.selectedIndex];
    
    // Get template data from option attributes
    const templateName = selectedOption.dataset.name;
    const templatePrice = selectedOption.dataset.price;
    const templateListPrice = selectedOption.dataset.listPrice || '';
    const templateDescription = selectedOption.dataset.description || '';
    const variants = JSON.parse(selectedOption.dataset.variants || '[]');
    
    // Update preview
    document.getElementById('preview-price').textContent = '$' + parseFloat(templatePrice).toFixed(2);
    document.getElementById('preview-list-price').textContent = templateListPrice ? '$' + parseFloat(templateListPrice).toFixed(2) : '—';
    document.getElementById('preview-variants-count').textContent = variants.length;
    document.getElementById('preview-description').textContent = templateDescription || 'No description available';
    document.getElementById('template-preview').classList.remove('hidden');
    
    // Auto-fill product name if empty
    const nameInput = document.getElementById('name');
    if (!nameInput.value) {
        nameInput.value = templateName;
    }
    
    // Load variants
    if (variants.length > 0) {
        loadVariants(variants);
        document.getElementById('variants-section').classList.remove('hidden');
    } else {
        document.getElementById('variants-section').classList.add('hidden');
    }
}

function loadVariants(variants) {
    templateVariants = variants;
    const container = document.getElementById('variants-container');
    
    let html = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Variant</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Template Price</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">List Price</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Override Price</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Quantity</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    variants.forEach((variant, index) => {
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-r from-green-500 to-teal-500 flex items-center justify-center">
                                <span class="text-white font-semibold text-sm">${index + 1}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-semibold text-gray-900">${variant.variant_name}</div>
                        </div>
                    </div>
                    <input type="hidden" name="variants[${index}][variant_name]" value="${variant.variant_name}">
                    <input type="hidden" name="variants[${index}][attributes]" value='${JSON.stringify(variant.attributes || {})}'>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm font-medium text-gray-900">$${parseFloat(variant.price || 0).toFixed(2)}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="number" 
                           name="variants[${index}][list_price]" 
                           value="${variant.list_price != null ? variant.list_price : ''}"
                           step="0.01" 
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="${variant.list_price != null ? parseFloat(variant.list_price).toFixed(2) : '—'}">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="number" 
                           name="variants[${index}][price]" 
                           value="${variant.price || ''}"
                           step="0.01" 
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                           placeholder="${parseFloat(variant.price || 0).toFixed(2)}">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="number" 
                           name="variants[${index}][quantity]" 
                           value="${variant.quantity || 0}"
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                           placeholder="0"
                           required>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

// Media Upload Functions
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

/** Gán lại input#media từ mảng selectedMediaFiles (DataTransfer hỗ trợ gửi nhiều file đã gom). */
function syncMediaFileInput() {
    const input = document.getElementById('media');
    const dt = new DataTransfer();
    selectedMediaFiles.forEach(function (file) {
        dt.items.add(file);
    });
    input.files = dt.files;
}

function handleMediaFiles(files) {
    const incoming = Array.from(files || []);
    if (incoming.length === 0) {
        return;
    }
    selectedMediaFiles = selectedMediaFiles.concat(incoming);
    syncMediaFileInput();
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
        previewItem.className = 'relative bg-white rounded-lg border-2 border-gray-200 p-2 shadow-sm cursor-move draggable-media';
        previewItem.draggable = true;
        previewItem.dataset.index = index;
        
        // Drag event handlers
        previewItem.addEventListener('dragstart', handleMediaDragStart);
        previewItem.addEventListener('dragover', handleMediaDragOver);
        previewItem.addEventListener('drop', handleMediaDrop);
        previewItem.addEventListener('dragend', handleMediaDragEnd);
        
        const isVideo = file.type.startsWith('video/');
        const isImage = file.type.startsWith('image/');
        
        if (isImage) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewItem.innerHTML = `
                    <div class="aspect-square rounded-lg overflow-hidden mb-2">
                        <img src="${e.target.result}" alt="${file.name}" class="w-full h-full object-cover">
                    </div>
                    <p class="text-xs font-medium text-gray-700 truncate">${file.name}</p>
                    <div class="absolute top-1 left-1 bg-blue-500 text-white text-xs px-2 py-1 rounded">${index + 1}</div>
                    <button type="button" onclick="removeMediaFile(${index})" 
                            class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 z-10">×</button>
                `;
            };
            reader.readAsDataURL(file);
        } else if (isVideo) {
            previewItem.innerHTML = `
                <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-purple-100 flex items-center justify-center">
                    <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                    </svg>
                </div>
                <p class="text-xs font-medium text-gray-700 truncate">${file.name}</p>
                <div class="absolute top-1 left-1 bg-blue-500 text-white text-xs px-2 py-1 rounded">${index + 1}</div>
                <button type="button" onclick="removeMediaFile(${index})" 
                        class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 z-10">×</button>
            `;
        }
        
        previewList.appendChild(previewItem);
    });
    
    updateMediaOrder();
}

let draggedMediaIndex = null;

function handleMediaDragStart(e) {
    draggedMediaIndex = parseInt(e.currentTarget.dataset.index);
    e.currentTarget.classList.add('opacity-50', 'border-blue-500');
    e.dataTransfer.effectAllowed = 'move';
}

function handleMediaDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const target = e.currentTarget;
    if (target.classList.contains('draggable-media') && target.dataset.index != draggedMediaIndex) {
        target.classList.add('border-green-500', 'bg-green-50');
    }
}

function handleMediaDrop(e) {
    e.preventDefault();
    const targetIndex = parseInt(e.currentTarget.dataset.index);
    
    if (draggedMediaIndex !== null && draggedMediaIndex !== targetIndex) {
        // Reorder files array
        const draggedFile = selectedMediaFiles[draggedMediaIndex];
        selectedMediaFiles.splice(draggedMediaIndex, 1);
        selectedMediaFiles.splice(targetIndex, 0, draggedFile);
        
        syncMediaFileInput();
        displayMediaPreview();
    }
}

function handleMediaDragEnd(e) {
    e.currentTarget.classList.remove('opacity-50', 'border-blue-500');
    document.querySelectorAll('.draggable-media').forEach(item => {
        item.classList.remove('border-green-500', 'bg-green-50');
    });
    draggedMediaIndex = null;
}

function updateMediaOrder() {
    const order = selectedMediaFiles.map((file, index) => index).join(',');
    document.getElementById('media-order').value = order;
}

function removeMediaFile(index) {
    selectedMediaFiles.splice(index, 1);
    syncMediaFileInput();
    displayMediaPreview();
    updateMediaOrder();
}

// Rich Text Editor Functions
function updateDescriptionValue() {
    const editor = document.getElementById('description-editor');
    const hiddenInput = document.getElementById('description');
    hiddenInput.value = editor.innerHTML;
}

// Price Type Management
let currentTemplatePrice = 0;

function updatePriceType() {
    const priceType = document.querySelector('input[name="price_type"]:checked').value;
    const priceInputContainer = document.getElementById('price-input-container');
    const priceInput = document.getElementById('price');
    const pricePrefix = document.getElementById('price-prefix');
    const priceHint = document.getElementById('price-hint');
    const templatePricePreview = document.getElementById('template-price-preview');
    const finalPriceDisplay = document.getElementById('final-price-display');
    
    if (priceType === 'template') {
        // Use template price - hide input
        priceInputContainer.classList.add('hidden');
        priceInput.value = '';
        templatePricePreview.classList.add('hidden');
    } else if (priceType === 'override') {
        // Override - show input with $
        priceInputContainer.classList.remove('hidden');
        pricePrefix.textContent = '$';
        priceHint.textContent = 'Enter the new price to override template price';
        templatePricePreview.classList.remove('hidden');
        finalPriceDisplay.classList.add('hidden');
        priceInput.placeholder = '0.00';
        priceInput.oninput = function() {
            if (this.value) {
                document.getElementById('final-price-display').classList.remove('hidden');
                document.getElementById('final-price-value').textContent = '$' + parseFloat(this.value).toFixed(2);
            } else {
                document.getElementById('final-price-display').classList.add('hidden');
            }
        };
    } else if (priceType === 'add') {
        // Add to template - show input with +
        priceInputContainer.classList.remove('hidden');
        pricePrefix.textContent = '+$';
        priceHint.textContent = 'Enter the amount to ADD to template price';
        templatePricePreview.classList.remove('hidden');
        finalPriceDisplay.classList.remove('hidden');
        priceInput.placeholder = '0.00';
        priceInput.oninput = function() {
            const addAmount = parseFloat(this.value) || 0;
            const finalPrice = currentTemplatePrice + addAmount;
            document.getElementById('final-price-value').textContent = '$' + finalPrice.toFixed(2);
        };
        
        // Calculate initial final price
        const addAmount = parseFloat(priceInput.value) || 0;
        const finalPrice = currentTemplatePrice + addAmount;
        document.getElementById('final-price-value').textContent = '$' + finalPrice.toFixed(2);
    }
}

// Update loadTemplateData to store template price
function loadTemplateData(templateId) {
    if (!templateId) {
        document.getElementById('template-preview').classList.add('hidden');
        document.getElementById('variants-section').classList.add('hidden');
        return;
    }
    
    const select = document.getElementById('template_id');
    const selectedOption = select.options[select.selectedIndex];
    
    // Get template data from option attributes
    const templateName = selectedOption.dataset.name;
    const templatePrice = selectedOption.dataset.price;
    const templateDescription = selectedOption.dataset.description || '';
    const variants = JSON.parse(selectedOption.dataset.variants || '[]');
    
    // Store template price for calculations
    currentTemplatePrice = parseFloat(templatePrice);
    
    // Update preview
    document.getElementById('preview-price').textContent = '$' + parseFloat(templatePrice).toFixed(2);
    document.getElementById('preview-variants-count').textContent = variants.length;
    document.getElementById('preview-description').textContent = templateDescription || 'No description available';
    document.getElementById('template-preview').classList.remove('hidden');
    
    // Update template price display in price section
    document.getElementById('template-price-display').textContent = '$' + parseFloat(templatePrice).toFixed(2);
    
    // Update price type display
    updatePriceType();
    
    // Auto-fill product name if empty
    const nameInput = document.getElementById('name');
    if (!nameInput.value) {
        nameInput.value = templateName;
    }
    
    // Load variants
    if (variants.length > 0) {
        loadVariants(variants);
        document.getElementById('variants-section').classList.remove('hidden');
    } else {
        document.getElementById('variants-section').classList.add('hidden');
    }
}

// Form validation
function validateForm() {
    console.log('Form validation started...');
    
    const templateId = document.getElementById('template_id').value;
    const name = document.getElementById('name').value;
    const quantity = document.getElementById('quantity').value;
    const status = document.getElementById('status').value;
    
    console.log('Template ID:', templateId);
    console.log('Name:', name);
    console.log('Quantity:', quantity);
    console.log('Status:', status);
    
    if (!templateId) {
        alert('Please select a template');
        return false;
    }
    
    if (!name.trim()) {
        alert('Please enter a product name');
        return false;
    }
    
    if (!quantity || quantity === '' || isNaN(quantity) || parseInt(quantity) < 0) {
        alert('Please enter a valid quantity');
        return false;
    }
    
    console.log('Form validation passed');
    return true;
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Load template if there's old value
    const templateId = document.getElementById('template_id').value;
    if (templateId) {
        loadTemplateData(templateId);
    }
    
    // Add form submit listener
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        console.log('Form submitted');
        console.log('Form data:', new FormData(form));
    });
});
</script>
@endsection
