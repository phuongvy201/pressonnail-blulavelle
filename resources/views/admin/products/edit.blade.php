@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Edit Product</h1>
                <span class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                    </svg>
                    ID: {{ $product->id }}
                </span>
            </div>
            <p class="mt-2 text-sm text-gray-600">Update product information</p>
        </div>
        <a href="{{ route('admin.products.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Products
        </a>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('admin.products.update', $product->id) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        
        <!-- Template Info (Read-only) -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-center space-x-3">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-gray-600">Based on Template:</p>
                    <p class="text-lg font-bold text-gray-900">{{ $product->template->name }} <span class="text-sm text-gray-600">(ID: #{{ $product->template->id }})</span></p>
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
                           value="{{ old('name', $product->name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                           placeholder="Enter product name"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Price & Quantity -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                            Price (Template: ${{ number_format($product->template->base_price, 2) }})
                        </label>
                        <input type="number" 
                               id="price" 
                               name="price" 
                               value="{{ old('price', $product->price) }}"
                               step="0.01" 
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('price') border-red-500 @enderror"
                               placeholder="{{ $product->template->base_price }}">
                        @error('price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="list_price" class="block text-sm font-medium text-gray-700 mb-2">List Price (giá niêm yết)</label>
                        <input type="number" 
                               id="list_price" 
                               name="list_price" 
                               value="{{ old('list_price', $product->list_price) }}"
                               step="0.01" 
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Để trống = không hiển thị gạch ngang">
                    </div>

                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Total Quantity *</label>
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               value="{{ old('quantity', $product->quantity) }}"
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('quantity') border-red-500 @enderror"
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
                            <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="draft" {{ old('status', $product->status) == 'draft' ? 'selected' : '' }}>Draft</option>
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
                                        {{ old('shop_id', $product->shop_id) == $shop->id ? 'selected' : '' }}
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
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <input type="hidden" id="description" name="description" value="{{ old('description', $product->description) }}">
                    <div id="description-editor" 
                         contenteditable="true"
                         class="w-full min-h-[100px] px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
                         style="white-space: pre-wrap;"
                         oninput="updateDescriptionValue()">{{ old('description', $product->description) }}</div>
                </div>
            </div>
        </div>

        <!-- Product Variants -->
        @if($product->variants && $product->variants->count() > 0)
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-teal-50">
                <h3 class="text-lg font-medium text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    Product Variants
                </h3>
                <p class="text-sm text-gray-600 mt-1">Edit price and quantity for each variant</p>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Variant</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Current Price</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">List Price</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">New Price</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Quantity</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($product->variants as $index => $variant)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-r from-green-500 to-teal-500 flex items-center justify-center">
                                                <span class="text-white font-semibold text-sm">{{ $index + 1 }}</span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900">{{ $variant->variant_name }}</div>
                                            @if($variant->attributes)
                                                <div class="text-xs text-gray-500">
                                                    @foreach($variant->attributes as $key => $value)
                                                        {{ $key }}: {{ $value }}@if(!$loop->last), @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
                                    <input type="hidden" name="variants[{{ $index }}][variant_name]" value="{{ $variant->variant_name }}">
                                    <input type="hidden" name="variants[{{ $index }}][attributes]" value='{{ json_encode($variant->attributes ?? []) }}'>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">${{ number_format($variant->price ?? 0, 2) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="number" 
                                           name="variants[{{ $index }}][list_price]" 
                                           value="{{ old("variants.{$index}.list_price", $variant->list_price) }}"
                                           step="0.01" 
                                           min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                           placeholder="—">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="number" 
                                           name="variants[{{ $index }}][price]" 
                                           value="{{ old("variants.{$index}.price", $variant->price) }}"
                                           step="0.01" 
                                           min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                           placeholder="{{ number_format($variant->price ?? 0, 2) }}">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="number" 
                                           name="variants[{{ $index }}][quantity]" 
                                           value="{{ old("variants.{$index}.quantity", $variant->quantity) }}"
                                           min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                           placeholder="0"
                                           required>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Media Upload -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Product Media</h3>
                <p class="text-sm text-gray-600">Upload new media or keep existing. Mỗi lần chọn &quot;Choose Files&quot; sẽ thêm file vào danh sách mới (không thay thế). Kéo thả file vào ô bên dưới cũng được cộng thêm.</p>
            </div>
            <div class="p-6">
                @if($product->media && count($product->media) > 0)
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-gray-700">Current Media</h4>
                        <span class="text-xs text-gray-500 italic">Kéo và thả để thay đổi thứ tự</span>
                    </div>
                    <div id="current-media-list" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($product->media as $mediaItem)
                            @php
                                // orderUrl = URL gốc (hidden + rehydrate); previewUrl = webp cho ảnh khi có
                                $orderUrl = null;
                                $previewUrl = null;
                                if (is_string($mediaItem)) {
                                    $orderUrl = $previewUrl = $mediaItem;
                                } elseif (is_array($mediaItem) && ! empty($mediaItem)) {
                                    $orderUrl = $mediaItem['url'] ?? $mediaItem['path'] ?? reset($mediaItem) ?? null;
                                    $isVideo = ($mediaItem['type'] ?? '') === 'video';
                                    if ($isVideo) {
                                        $previewUrl = $orderUrl;
                                    } else {
                                        $previewUrl = ! empty($mediaItem['webp']) ? $mediaItem['webp'] : $orderUrl;
                                    }
                                }
                            @endphp
                            
                            @if($orderUrl)
                            <div class="current-media-item relative bg-white rounded-lg border-2 border-gray-200 p-2 cursor-move group"
                                 draggable="true"
                                 data-media-key="{{ $loop->index }}">
                                @if(str_contains($orderUrl, '.mp4') || str_contains($orderUrl, '.mov') || str_contains($orderUrl, '.avi'))
                                    <div class="aspect-square rounded-lg bg-purple-100 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                        </svg>
                                    </div>
                                @else
                                    <img src="{{ $previewUrl }}" class="w-full aspect-square object-cover rounded-lg" alt="{{ $product->altForMediaItem(is_array($mediaItem) || is_string($mediaItem) ? $mediaItem : [], null, $loop->index) }}">
                                @endif
                                <input type="hidden" name="current_media_order[]" value="{{ $orderUrl }}">
                                <button type="button" 
                                        onclick="removeCurrentMedia(this)" 
                                        class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 z-10 opacity-0 group-hover:opacity-100 transition-opacity shadow"
                                        title="Xóa ảnh này">
                                    <span class="sr-only">Xóa</span>×
                                </button>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-purple-400 transition-colors bg-gray-50"
                     id="product-media-drop-zone"
                     ondrop="handleNewMediaDrop(event)"
                     ondragover="handleNewMediaDragOver(event)"
                     ondragleave="handleNewMediaDragLeave(event)">
                    <!--
                      IMPORTANT:
                      - `media-picker` is only for selecting files, and is safe to clear after each pick.
                      - `media` is the actual input that will be submitted to backend (`name="media[]"`).
                    -->
                    <input type="file" id="media-picker" multiple accept="image/*,video/*" class="hidden"
                           onchange="handleMediaFiles(this.files); this.value='';">
                    <input type="file" id="media" name="media[]" multiple accept="image/*,video/*" class="hidden">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <p class="text-lg font-semibold text-gray-700 mb-2">Upload New Media</p>
                    <p class="text-sm text-gray-500 mb-4">Kéo thả file vào đây hoặc chọn nhiều lần &quot;Choose Files&quot; để thêm dần.</p>
                    <button type="button" onclick="document.getElementById('media-picker').click()" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                        Choose Files
                    </button>
                </div>
                
                <div id="media-preview" class="mt-6 hidden">
                    <div class="flex items-center justify-between mb-2">
                        <h5 class="text-sm font-semibold text-gray-700">New Files Selected</h5>
                        <span class="text-xs text-gray-500 italic">Kéo để sắp xếp</span>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">Ảnh chọn sau nằm cuối danh sách. Kéo thả ô để đổi thứ tự.</p>
                    <div id="media-preview-list" class="grid grid-cols-2 md:grid-cols-4 gap-4"></div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('admin.products.index') }}" 
               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Update Product
            </button>
        </div>
    </form>
</div>

<script>
let selectedMediaFiles = [];
let draggedElement = null;

function handleNewMediaDragOver(event) {
    event.preventDefault();
    event.currentTarget.classList.add('border-purple-400', 'bg-purple-50');
}

function handleNewMediaDragLeave(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('border-purple-400', 'bg-purple-50');
}

function handleNewMediaDrop(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('border-purple-400', 'bg-purple-50');
    const files = Array.from(event.dataTransfer.files || []);
    handleMediaFiles(files);
}

function handleMediaFiles(files) {
    const incoming = Array.from(files || []);
    if (incoming.length === 0) {
        return;
    }
    let uid = Date.now();
    incoming.forEach((file) => {
        const dup = selectedMediaFiles.some(
            (item) =>
                item.file.name === file.name &&
                item.file.size === file.size &&
                item.file.lastModified === file.lastModified
        );
        if (!dup) {
            uid += 1;
            selectedMediaFiles.push({
                file,
                id: `nf-${uid}-${file.name.replace(/\s+/g, '_')}`
            });
        }
    });
    refreshMediaInput();
    displayMediaPreview();
}

function refreshMediaInput() {
    const input = document.getElementById('media');
    if (!input) return;
    const dt = new DataTransfer();
    selectedMediaFiles.forEach(item => dt.items.add(item.file));
    input.files = dt.files;
}

function displayMediaPreview() {
    const previewContainer = document.getElementById('media-preview');
    const previewList = document.getElementById('media-preview-list');
    
    if (!previewContainer || !previewList) return;
    
    if (selectedMediaFiles.length === 0) {
        previewContainer.classList.add('hidden');
        previewList.innerHTML = '';
        return;
    }
    
    previewContainer.classList.remove('hidden');
    previewList.innerHTML = '';
    
    selectedMediaFiles.forEach((item) => {
        const { file, id } = item;
        const previewItem = document.createElement('div');
        previewItem.className =
            'media-preview-item relative bg-white rounded-lg border-2 border-gray-200 p-2 cursor-grab active:cursor-grabbing select-none';
        previewItem.dataset.fileId = id;
        previewItem.setAttribute('draggable', 'true');
        previewItem.title = 'Kéo để đổi thứ tự';

        if (file.type.startsWith('image/')) {
            previewItem.innerHTML = `
                <div class="aspect-square rounded-lg bg-gray-100 flex items-center justify-center min-h-[5rem] mb-2">
                    <span class="text-xs text-gray-400">Đang tải…</span>
                </div>
            `;
            previewList.appendChild(previewItem);
            const reader = new FileReader();
            reader.onload = function (e) {
                previewItem.innerHTML = `
                    <img src="${e.target.result}" class="w-full aspect-square object-cover rounded-lg mb-2" alt="">
                    <p class="text-xs text-gray-700 truncate">${file.name}</p>
                    <button type="button" draggable="false" onclick="removeMediaFile('${id}')"
                            class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full hover:bg-red-600 z-10">×</button>
                `;
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            previewItem.innerHTML = `
                <div class="aspect-square rounded-lg bg-purple-100 flex items-center justify-center mb-2">
                    <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                    </svg>
                </div>
                <p class="text-xs text-gray-700 truncate">${file.name}</p>
                <button type="button" draggable="false" onclick="removeMediaFile('${id}')"
                        class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full hover:bg-red-600 z-10">×</button>
            `;
            previewList.appendChild(previewItem);
        } else {
            previewItem.innerHTML = `
                <div class="aspect-square rounded-lg bg-gray-100 flex items-center justify-center mb-2">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4-4 4 4m2-4.5a2.5 2.5 0 115 0 2.5 2.5 0 01-5 0z"></path>
                    </svg>
                </div>
                <p class="text-xs text-gray-700 truncate">${file.name}</p>
                <button type="button" draggable="false" onclick="removeMediaFile('${id}')"
                        class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full hover:bg-red-600 z-10">×</button>
            `;
            previewList.appendChild(previewItem);
        }
    });
    
    initDragAndDrop('#media-preview-list', '.media-preview-item', syncSelectedFilesWithPreview);
}

function removeMediaFile(id) {
    selectedMediaFiles = selectedMediaFiles.filter(item => item.id !== id);
    refreshMediaInput();
    displayMediaPreview();
}

function syncSelectedFilesWithPreview(container = null) {
    const scope = container || document.getElementById('media-preview-list');
    if (!scope) return;
    const items = scope.querySelectorAll('.media-preview-item');
    if (!items.length) return;
    
    const orderedIds = Array.from(items).map(item => item.dataset.fileId);
    selectedMediaFiles.sort((a, b) => orderedIds.indexOf(a.id) - orderedIds.indexOf(b.id));
    refreshMediaInput();
}

function initDragAndDrop(containerSelector, itemSelector, onDropCallback = null) {
    const container = document.querySelector(containerSelector);
    if (!container) return;
    
    const items = container.querySelectorAll(itemSelector);
    items.forEach(item => {
        if (item.dataset.dragBound === '1') return;
        item.dataset.dragBound = '1';
        
        item.addEventListener('dragstart', (event) => {
            if (event.target.closest && event.target.closest('button')) {
                event.preventDefault();
                return;
            }
            draggedElement = item;
            item.classList.add('opacity-60');
            event.dataTransfer.effectAllowed = 'move';
        });
        
        item.addEventListener('dragend', () => {
            item.classList.remove('opacity-60', 'ring-2', 'ring-purple-400');
            draggedElement = null;
        });
        
        item.addEventListener('dragover', (event) => {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            item.classList.add('ring-2', 'ring-purple-400');
        });
        
        item.addEventListener('dragleave', () => {
            item.classList.remove('ring-2', 'ring-purple-400');
        });
        
        item.addEventListener('drop', (event) => {
            item.classList.remove('ring-2', 'ring-purple-400');
            handleDrop(event, item, container, itemSelector, onDropCallback);
        });
    });
}

function handleDrop(event, target, container, itemSelector, onDropCallback) {
    event.preventDefault();
    if (!draggedElement || draggedElement === target) return;
    
    const items = Array.from(container.querySelectorAll(itemSelector));
    const draggedIndex = items.indexOf(draggedElement);
    const targetIndex = items.indexOf(target);
    
    if (draggedIndex < targetIndex) {
        container.insertBefore(draggedElement, target.nextSibling);
    } else {
        container.insertBefore(draggedElement, target);
    }
    
    if (typeof onDropCallback === 'function') {
        onDropCallback(container);
    }
}

function updateDescriptionValue() {
    const editor = document.getElementById('description-editor');
    const hiddenInput = document.getElementById('description');
    if (!editor || !hiddenInput) return;
    hiddenInput.value = editor.innerHTML;
}

function removeCurrentMedia(btn) {
    const item = btn.closest('.current-media-item');
    if (item && confirm('Bạn có chắc muốn xóa ảnh này khỏi sản phẩm?')) {
        item.remove();
    }
}

function initCurrentMediaDrag() {
    initDragAndDrop('#current-media-list', '.current-media-item');
}

document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('description-editor');
    const hiddenInput = document.getElementById('description');
    
    if (hiddenInput && hiddenInput.value) {
        editor.innerHTML = hiddenInput.value;
    }
    
    initCurrentMediaDrag();
});
</script>
@endsection













