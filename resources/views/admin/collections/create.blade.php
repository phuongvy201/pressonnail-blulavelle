@extends('layouts.admin')

@section('title', 'Create Collection')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <h1 class="text-3xl font-bold">📚 Create New Collection</h1>
        <p class="text-purple-100 mt-2">Organize your products into themed collections for better customer experience</p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.collections.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-md p-8 space-y-6">
        @csrf

        <!-- Basic Information -->
        <div class="border-b border-gray-200 pb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Basic Information
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Collection Name -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Collection Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           placeholder="e.g: Summer Collection 2024">
                    @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                              placeholder="Describe what this collection is about...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Collection Type <span class="text-red-500">*</span></label>
                    <select name="type" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <option value="manual" {{ old('type') == 'manual' ? 'selected' : '' }}>📝 Manual - Add products manually</option>
                        <option value="automatic" {{ old('type') == 'automatic' ? 'selected' : '' }}>🤖 Automatic - Auto-generate based on rules</option>
                    </select>
                    @error('type')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                    <select name="status" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>✅ Active - Visible to customers</option>
                        <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>📝 Draft - Not visible to customers</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>❌ Inactive - Hidden from customers</option>
                    </select>
                    @error('status')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Collection Image -->
        <div class="border-b border-gray-200 pb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Collection Cover Image
            </h3>
            
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-purple-400 transition">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <input type="file" name="image" accept="image/*"
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                <p class="text-xs text-gray-500 mt-2">PNG, JPG, GIF (Max 5MB, 1920x400px recommended)</p>
            </div>
            @error('image')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Products Selection -->
        <div class="border-b border-gray-200 pb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                Select Products
            </h3>
            
            <div class="space-y-4">
                <!-- Selected Products Display -->
                <div id="selected-products-display" class="min-h-[60px] border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <p class="text-sm text-gray-500" id="selected-count">No products selected</p>
                    <div id="selected-products-list" class="flex flex-wrap gap-2 mt-2"></div>
                </div>

                <!-- Open Modal Button -->
                <button type="button" onclick="openProductModal()" 
                        class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 transition flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Select Products
                </button>
            </div>

            <!-- Hidden inputs for selected products -->
            <div id="selected-products-inputs"></div>
        </div>

        <!-- Product Selection Modal -->
        <div id="product-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-t-xl p-6 text-white">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold">Select Products</h2>
                            <p class="text-purple-100 mt-1">Choose products to add to this collection</p>
                        </div>
                        <button onclick="closeProductModal()" class="text-white hover:text-gray-200 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                            <input type="text" id="product-search" placeholder="Search by product name..." 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                   onkeyup="filterProducts()">
                        </div>
                        <!-- Category Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                            <select id="category-filter" onchange="filterProducts()"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">All Categories</option>
                                @php
                                    $categories = $products->pluck('template.category')->filter()->unique('id')->values();
                                @endphp
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Template Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Template</label>
                            <select id="template-filter" onchange="filterProducts()"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">All Templates</option>
                                @php
                                    $templates = $products->pluck('template')->filter()->unique('id')->values();
                                @endphp
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Shop Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Shop</label>
                            <select id="shop-filter" onchange="filterProducts()"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">All Shops</option>
                                @php
                                    $shops = $products->pluck('shop')->filter()->unique('id')->values();
                                @endphp
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->shop_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <!-- Price Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Price Range</label>
                            <select id="price-filter" onchange="filterProducts()"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">All Prices</option>
                                <option value="0-50">$0 - $50</option>
                                <option value="50-100">$50 - $100</option>
                                <option value="100-200">$100 - $200</option>
                                <option value="200+">Above $200</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-auto p-6">
                    @if($products->count() > 0)
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" id="select-all-products" onchange="toggleSelectAll()"
                                       class="mr-2 w-4 h-4 text-purple-600 focus:ring-purple-500 rounded">
                                <label for="select-all-products" class="text-sm font-semibold text-gray-700 cursor-pointer">
                                    Select All
                                </label>
                            </div>
                            <p class="text-sm text-gray-600">
                                <span id="filtered-count">{{ $products->count() }}</span> products
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                            <input type="checkbox" id="select-all-header" onchange="toggleSelectAll()"
                                                   class="w-4 h-4 text-purple-600 focus:ring-purple-500 rounded">
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    </tr>
                                </thead>
                                <tbody id="products-table-body" class="bg-white divide-y divide-gray-200">
                                    @foreach($products as $product)
                                    <tr class="product-row hover:bg-purple-50 transition" 
                                        data-name="{{ strtolower($product->name) }}" 
                                        data-price="{{ $product->getEffectivePrice() }}"
                                        data-category-id="{{ $product->template->category_id ?? '' }}"
                                        data-template-id="{{ $product->template_id ?? '' }}"
                                        data-shop-id="{{ $product->shop_id ?? '' }}">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <input type="checkbox" class="product-checkbox w-4 h-4 text-purple-600 focus:ring-purple-500 rounded" 
                                                   value="{{ $product->id }}" 
                                                   data-name="{{ $product->name }}"
                                                   data-image="{{ $product->primary_image ?? '' }}"
                                                   data-price="{{ number_format($product->getEffectivePrice(), 2) }}"
                                                   {{ in_array($product->id, old('products', [])) ? 'checked' : '' }}
                                                   onchange="updateSelectedProducts()">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center text-lg mr-3 overflow-hidden">
                                                    @if(!empty($product->primary_image))
                                                        <img src="{{ $product->primary_image }}" alt="{{ $product->name }}" class="w-full h-full object-cover" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <span class="w-full h-full items-center justify-center text-lg hidden">📦</span>
                                                    @else
                                                        <span class="w-full h-full flex items-center justify-center text-lg">📦</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                                    <div class="text-xs text-gray-500">ID: {{ $product->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">${{ number_format($product->getEffectivePrice(), 2) }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-500 truncate max-w-xs">
                                                {{ $product->description ?? 'No description' }}
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p class="text-lg">No products available. Please create products first.</p>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="border-t border-gray-200 p-6 bg-gray-50 rounded-b-xl">
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-600">
                            Selected: <span id="modal-selected-count" class="font-semibold text-purple-600">0</span> products
                        </p>
                        <div class="flex space-x-3">
                            <button type="button" onclick="closeProductModal()" 
                                    class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
                                Cancel
                            </button>
                            <button type="button" onclick="saveSelectedProducts()" 
                                    class="px-6 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 transition">
                                Save Selection
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            let selectedProducts = new Set(@json(old('products', [])));

            function openProductModal() {
                document.getElementById('product-modal').classList.remove('hidden');
                document.getElementById('product-modal').classList.add('flex');
                updateModalSelectedCount();
            }

            function closeProductModal() {
                document.getElementById('product-modal').classList.add('hidden');
                document.getElementById('product-modal').classList.remove('flex');
            }

            function filterProducts() {
                const searchTerm = document.getElementById('product-search').value.toLowerCase();
                const priceFilter = document.getElementById('price-filter').value;
                const categoryFilter = document.getElementById('category-filter').value;
                const templateFilter = document.getElementById('template-filter').value;
                const shopFilter = document.getElementById('shop-filter').value;
                const rows = document.querySelectorAll('.product-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const name = row.getAttribute('data-name');
                    const price = parseFloat(row.getAttribute('data-price'));
                    const categoryId = row.getAttribute('data-category-id');
                    const templateId = row.getAttribute('data-template-id');
                    const shopId = row.getAttribute('data-shop-id');
                    let visible = true;

                    // Filter by name
                    if (searchTerm && !name.includes(searchTerm)) {
                        visible = false;
                    }

                    // Filter by price
                    if (priceFilter) {
                        const [min, max] = priceFilter === '200+' ? [200, Infinity] : priceFilter.split('-').map(Number);
                        if (price < min || (max !== Infinity && price > max)) {
                            visible = false;
                        }
                    }

                    // Filter by category
                    if (categoryFilter && categoryId !== categoryFilter) {
                        visible = false;
                    }

                    // Filter by template
                    if (templateFilter && templateId !== templateFilter) {
                        visible = false;
                    }

                    // Filter by shop
                    if (shopFilter && shopId !== shopFilter) {
                        visible = false;
                    }

                    row.style.display = visible ? '' : 'none';
                    if (visible) visibleCount++;
                });

                document.getElementById('filtered-count').textContent = visibleCount;
            }

            function toggleSelectAll() {
                const selectAll = document.getElementById('select-all-products').checked;
                const headerSelectAll = document.getElementById('select-all-header');
                headerSelectAll.checked = selectAll;
                
                const checkboxes = document.querySelectorAll('.product-checkbox');
                const visibleRows = Array.from(document.querySelectorAll('.product-row')).filter(row => row.style.display !== 'none');
                
                visibleRows.forEach(row => {
                    const checkbox = row.querySelector('.product-checkbox');
                    if (checkbox) {
                        checkbox.checked = selectAll;
                        if (selectAll) {
                            selectedProducts.add(parseInt(checkbox.value));
                        } else {
                            selectedProducts.delete(parseInt(checkbox.value));
                        }
                    }
                });

                updateModalSelectedCount();
            }

            function updateSelectedProducts() {
                const checkboxes = document.querySelectorAll('.product-checkbox');
                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        selectedProducts.add(parseInt(checkbox.value));
                    } else {
                        selectedProducts.delete(parseInt(checkbox.value));
                    }
                });

                // Update select all checkbox
                const visibleCheckboxes = Array.from(document.querySelectorAll('.product-row'))
                    .filter(row => row.style.display !== 'none')
                    .map(row => row.querySelector('.product-checkbox'))
                    .filter(cb => cb);
                
                const allChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(cb => cb.checked);
                document.getElementById('select-all-products').checked = allChecked;
                document.getElementById('select-all-header').checked = allChecked;

                updateModalSelectedCount();
            }

            function updateModalSelectedCount() {
                document.getElementById('modal-selected-count').textContent = selectedProducts.size;
            }

            function saveSelectedProducts() {
                // Update hidden inputs
                const container = document.getElementById('selected-products-inputs');
                container.innerHTML = '';
                selectedProducts.forEach(productId => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'products[]';
                    input.value = productId;
                    container.appendChild(input);
                });

                // Update checkboxes in modal
                document.querySelectorAll('.product-checkbox').forEach(checkbox => {
                    checkbox.checked = selectedProducts.has(parseInt(checkbox.value));
                });

                // Update display
                updateSelectedProductsDisplay();
                closeProductModal();
            }

            function updateSelectedProductsDisplay() {
                const display = document.getElementById('selected-products-display');
                const list = document.getElementById('selected-products-list');
                const count = document.getElementById('selected-count');

                if (selectedProducts.size === 0) {
                    count.textContent = 'No products selected';
                    list.innerHTML = '';
                    return;
                }

                count.textContent = `${selectedProducts.size} product${selectedProducts.size > 1 ? 's' : ''} selected`;
                list.innerHTML = '';

                selectedProducts.forEach(productId => {
                    const checkbox = document.querySelector(`.product-checkbox[value="${productId}"]`);
                    if (checkbox) {
                        const badge = document.createElement('div');
                        badge.className = 'inline-flex items-center px-2.5 py-1 bg-purple-100 text-purple-800 rounded-full text-sm';
                        const imageUrl = checkbox.getAttribute('data-image');
                        badge.innerHTML = `
                            ${imageUrl ? `<img src="${imageUrl}" alt="${checkbox.getAttribute('data-name')}" class="w-6 h-6 rounded-full object-cover mr-2 border border-purple-200" onerror="this.remove();">` : `<span class="w-6 h-6 mr-2 rounded-full bg-purple-200 inline-flex items-center justify-center text-xs">📦</span>`}
                            <span>${checkbox.getAttribute('data-name')} - $${checkbox.getAttribute('data-price')}</span>
                            <button type="button" onclick="removeProduct(${productId})" class="ml-2 text-purple-600 hover:text-purple-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        `;
                        list.appendChild(badge);
                    }
                });
            }

            function removeProduct(productId) {
                selectedProducts.delete(productId);
                document.querySelector(`.product-checkbox[value="${productId}"]`).checked = false;
                updateSelectedProductsDisplay();
                updateModalSelectedCount();
                
                // Remove from hidden inputs
                const input = document.querySelector(`input[name="products[]"][value="${productId}"]`);
                if (input) input.remove();
            }

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function() {
                updateSelectedProductsDisplay();
                // Sync checkboxes with selectedProducts
                document.querySelectorAll('.product-checkbox').forEach(checkbox => {
                    checkbox.checked = selectedProducts.has(parseInt(checkbox.value));
                });
            });

            // Close modal when clicking outside
            document.getElementById('product-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeProductModal();
                }
            });
        </script>

        <!-- Advanced Settings -->
        <div class="border-b border-gray-200 pb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Advanced Settings
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Featured -->
                <div class="flex items-center">
                    <input type="checkbox" name="featured" value="1" {{ old('featured') ? 'checked' : '' }}
                           class="mr-3 text-purple-600 focus:ring-purple-500 rounded">
                    <label class="text-sm font-medium text-gray-700">⭐ Featured Collection</label>
                    <p class="text-xs text-gray-500 ml-2">Highlight this collection on homepage</p>
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Lower numbers appear first</p>
                </div>
            </div>
        </div>

        <!-- SEO Settings -->
        <div class="pb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                SEO Settings (Optional)
            </h3>
            
            <div class="space-y-4">
                <!-- Meta Title -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           placeholder="SEO title for search engines">
                    @error('meta_title')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Meta Description -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Description</label>
                    <textarea name="meta_description" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                              placeholder="SEO description for search engines">{{ old('meta_description') }}</textarea>
                    @error('meta_description')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-4 pt-6">
            <a href="{{ route('admin.collections.index') }}" 
               class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
                Cancel
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 shadow-lg transition transform hover:scale-105">
                🚀 Create Collection
            </button>
        </div>
    </form>
</div>
@endsection
