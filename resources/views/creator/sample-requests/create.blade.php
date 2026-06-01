@extends('layouts.creator')

@section('title', 'Request a sample')

@section('content')
<div class="mx-auto max-w-3xl px-5 py-10 md:px-16 md:py-12">
    <a href="{{ route('creator.sample-requests.index') }}" class="text-sm font-semibold text-primary hover:underline">← Sample requests</a>
    <h1 class="creator-font-headline mt-2 text-3xl font-bold text-[#0b1c30]">Request a sample</h1>
    <p class="mt-2 text-sm text-[#707884]">{{ $quota['remaining'] }} of {{ $quota['max_requests'] }} requests remaining this period.</p>

    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $hasSampleProducts)
        <p class="mt-8 text-sm text-[#707884]">
            No sample-eligible products are available for your tier (<span class="font-semibold capitalize">{{ $affiliateTier }}</span>) right now.
            Products must be enabled for samples by admin and in stock.
        </p>
    @else
        <form method="post" action="{{ route('creator.sample-requests.store') }}" class="mt-8 space-y-6" id="sample-request-form">
            @csrf

            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm space-y-4">
                <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Product</h2>

                <div>
                    <label for="product_search" class="sr-only">Search product</label>
                    <div class="relative">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-[#707884]">search</span>
                        <input type="search" id="product_search" autocomplete="off"
                               placeholder="Search by name, SKU, or category…"
                               class="w-full rounded-lg border border-[#bfc7d5] py-2.5 pl-10 pr-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                    </div>
                    @error('product_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                @if ($filterCollections->isNotEmpty())
                    <div class="flex flex-wrap gap-2" id="collection-filters">
                        <button type="button" data-collection-id=""
                                class="sample-collection-chip rounded-full border border-primary bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                            All
                        </button>
                        @foreach ($filterCollections as $collection)
                            <button type="button" data-collection-id="{{ $collection->id }}"
                                    class="sample-collection-chip rounded-full border border-[#bfc7d5] bg-white px-3 py-1 text-xs font-semibold text-[#404753] hover:border-primary/40 hover:text-primary">
                                {{ $collection->name }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id') }}" required>

                <div id="product_list_panel" class="overflow-hidden rounded-lg border border-[#bfc7d5]">
                    <div id="product_list_caption" class="border-b border-[#eef1f6] px-3 py-2 text-xs font-semibold text-[#707884]">
                        Suggested products
                    </div>
                    <div id="product_search_loading" class="hidden px-4 py-8 text-center text-xs text-[#707884]">Loading products…</div>
                    <div id="product_search_results" class="max-h-[320px] overflow-y-auto"></div>
                    <div id="product_list_scroll_hint" class="hidden border-t border-[#eef1f6] py-2 text-center text-[#707884]">
                        <span class="material-symbols-outlined text-[22px]">keyboard_arrow_down</span>
                    </div>
                </div>

                <div id="product_selected" class="hidden rounded-lg border border-primary/30 bg-primary/5 p-3">
                    <p class="text-xs font-semibold uppercase text-primary">Selected product</p>
                    <div class="mt-2 flex items-center gap-3">
                        <div id="product_selected_thumb_wrap" class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-[#bfc7d5] bg-white">
                            <img id="product_selected_thumb" src="" alt="" class="hidden h-full w-full object-cover">
                            <span id="product_selected_thumb_placeholder" class="material-symbols-outlined text-[20px] text-[#707884]">inventory_2</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div id="product_selected_name" class="truncate text-sm font-semibold text-[#0b1c30]"></div>
                            <div id="product_selected_meta" class="text-xs text-[#707884]"></div>
                        </div>
                        <button type="button" id="product_clear_btn" class="shrink-0 text-xs font-semibold text-primary hover:underline">Change</button>
                    </div>
                </div>

                <div id="variant-wrap" class="hidden">
                    <label for="product_variant_id" class="block text-xs font-semibold uppercase text-[#707884]">Variant *</label>
                    <select name="product_variant_id" id="product_variant_id" class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                        <option value="">Select variant</option>
                    </select>
                    @error('product_variant_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div id="size-wrap" class="hidden">
                    <label for="size_preset" class="block text-xs font-semibold uppercase text-[#707884]">Nail size preset</label>
                    <select name="size_preset" id="size_preset" class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                        <option value="">Select size (optional)</option>
                        @foreach ($sizePresets as $size)
                            <option value="{{ $size }}" @selected(old('size_preset') === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                    @error('size_preset')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div id="quantity-wrap" class="hidden">
                    <label for="quantity" class="block text-xs font-semibold uppercase text-[#707884]">Quantity</label>
                    <input type="number" name="quantity" id="quantity" min="1" max="1" value="{{ old('quantity', 1) }}"
                           class="mt-1 w-24 rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                    <p id="quantity-hint" class="mt-1 text-xs text-[#707884]"></p>
                    @error('quantity')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm space-y-4">
                <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Shipping address</h2>
                <div>
                    <label for="shipping_name" class="block text-xs font-semibold uppercase text-[#707884]">Full name *</label>
                    <input type="text" name="shipping_name" id="shipping_name" value="{{ old('shipping_name', $affiliate->display_name) }}" required
                           class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="shipping_phone" class="block text-xs font-semibold uppercase text-[#707884]">Phone</label>
                    <input type="text" name="shipping_phone" id="shipping_phone" value="{{ old('shipping_phone', $affiliate->phone) }}"
                           class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="shipping_address" class="block text-xs font-semibold uppercase text-[#707884]">Address line 1 *</label>
                    <input type="text" name="shipping_address" id="shipping_address" value="{{ old('shipping_address') }}" required
                           class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="shipping_address_line2" class="block text-xs font-semibold uppercase text-[#707884]">Address line 2</label>
                    <input type="text" name="shipping_address_line2" id="shipping_address_line2" value="{{ old('shipping_address_line2') }}"
                           class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="shipping_city" class="block text-xs font-semibold uppercase text-[#707884]">City *</label>
                        <input type="text" name="shipping_city" id="shipping_city" value="{{ old('shipping_city') }}" required
                               class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="shipping_state" class="block text-xs font-semibold uppercase text-[#707884]">State</label>
                        <input type="text" name="shipping_state" id="shipping_state" value="{{ old('shipping_state') }}"
                               class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="shipping_postal_code" class="block text-xs font-semibold uppercase text-[#707884]">ZIP *</label>
                        <input type="text" name="shipping_postal_code" id="shipping_postal_code" value="{{ old('shipping_postal_code') }}" required
                               class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="shipping_country" class="block text-xs font-semibold uppercase text-[#707884]">Country *</label>
                        <input type="text" name="shipping_country" id="shipping_country" value="{{ old('shipping_country', 'US') }}" maxlength="2" required
                               class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm uppercase">
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
                <label for="creator_notes" class="creator-font-label block text-sm font-semibold uppercase tracking-wide text-[#404753]">Notes for the team</label>
                <textarea name="creator_notes" id="creator_notes" rows="3" maxlength="2000"
                          class="mt-2 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm"
                          placeholder="Content plans, preferred shades, etc.">{{ old('creator_notes') }}</textarea>
            </div>

            <button type="submit" class="creator-btn-primary creator-font-label w-full rounded-lg px-5 py-3 text-sm font-semibold tracking-wide sm:w-auto">
                Submit request
            </button>
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const searchUrl = @json(route('creator.sample-products.search'));
    const productShowUrlTemplate = @json(route('creator.sample-products.show', ['product' => 0]));
    const initialProduct = @json($initialProduct);
    const oldVariant = @json(old('product_variant_id'));

    const searchInput = document.getElementById('product_search');
    const searchLoading = document.getElementById('product_search_loading');
    const resultsBox = document.getElementById('product_search_results');
    const listCaption = document.getElementById('product_list_caption');
    const scrollHint = document.getElementById('product_list_scroll_hint');
    const hiddenProductId = document.getElementById('product_id');
    const selectedWrap = document.getElementById('product_selected');
    const selectedName = document.getElementById('product_selected_name');
    const selectedMeta = document.getElementById('product_selected_meta');
    const selectedThumb = document.getElementById('product_selected_thumb');
    const selectedThumbPlaceholder = document.getElementById('product_selected_thumb_placeholder');
    const clearBtn = document.getElementById('product_clear_btn');
    const variantWrap = document.getElementById('variant-wrap');
    const variantSelect = document.getElementById('product_variant_id');
    const sizeWrap = document.getElementById('size-wrap');
    const quantityInput = document.getElementById('quantity');
    const quantityHint = document.getElementById('quantity-hint');
    const quantityWrap = document.getElementById('quantity-wrap');
    const collectionFilters = document.getElementById('collection-filters');

    if (!searchInput || !hiddenProductId) return;

    let activeCollectionId = '';
    let searchTimer = null;
    let currentController = null;
    let selectedProductId = hiddenProductId.value ? String(hiddenProductId.value) : '';

    const categoryBadgeClasses = [
        'bg-emerald-50 text-emerald-700',
        'bg-amber-50 text-amber-700',
        'bg-sky-50 text-sky-700',
        'bg-violet-50 text-violet-700',
        'bg-rose-50 text-rose-700',
    ];

    function debounce(fn, delay) {
        return function (...args) {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function productShowEndpoint(id) {
        return productShowUrlTemplate.replace(/\/0(\?.*)?$/, '/' + String(id) + '$1');
    }

    function categoryBadgeClass(categoryId) {
        const index = Math.abs(parseInt(categoryId || 0, 10)) % categoryBadgeClasses.length;
        return categoryBadgeClasses[index];
    }

    function setCollectionChipActive(button) {
        if (!collectionFilters) return;
        collectionFilters.querySelectorAll('.sample-collection-chip').forEach(function (chip) {
            chip.classList.remove('border-primary', 'bg-primary/10', 'text-primary');
            chip.classList.add('border-[#bfc7d5]', 'bg-white', 'text-[#404753]');
        });
        button.classList.remove('border-[#bfc7d5]', 'bg-white', 'text-[#404753]');
        button.classList.add('border-primary', 'bg-primary/10', 'text-primary');
    }

    function updateScrollHint() {
        if (!scrollHint || !resultsBox) return;
        const hasOverflow = resultsBox.scrollHeight > resultsBox.clientHeight + 4;
        scrollHint.classList.toggle('hidden', !hasOverflow || resultsBox.children.length === 0);
    }

    function highlightSelectedRow() {
        resultsBox.querySelectorAll('.sample-product-result').forEach(function (btn) {
            const isSelected = String(btn.dataset.id) === selectedProductId;
            btn.classList.toggle('bg-primary/5', isSelected);
            btn.classList.toggle('ring-1', isSelected);
            btn.classList.toggle('ring-inset', isSelected);
            btn.classList.toggle('ring-primary/20', isSelected);
        });
    }

    function renderResults(items, emptyMessage) {
        if (!items.length) {
            resultsBox.innerHTML = '<div class="px-4 py-8 text-center text-xs text-[#707884]">' + escapeHtml(emptyMessage) + '</div>';
            updateScrollHint();
            return;
        }

        resultsBox.innerHTML = items.map(function (item) {
            const thumb = item.thumbnail
                ? '<img src="' + escapeHtml(item.thumbnail) + '" alt="" class="h-12 w-12 shrink-0 rounded-lg border border-[#eef1f6] object-cover">'
                : '<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-[#eef1f6] bg-[#f9fafb]"><span class="material-symbols-outlined text-[22px] text-[#bfc7d5]">image</span></div>';
            const sku = item.sku
                ? '<div class="mt-0.5 text-xs text-[#707884]">SKU: ' + escapeHtml(item.sku) + '</div>'
                : '';
            const badge = item.category
                ? '<span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ' + categoryBadgeClass(item.category_id) + '">' + escapeHtml(item.category) + '</span>'
                : '';

            return '<button type="button" class="sample-product-result flex w-full items-center gap-3 border-b border-[#eef1f6] px-3 py-3 text-left transition hover:bg-[#f8faff] last:border-b-0"'
                + ' data-id="' + escapeHtml(item.id) + '"'
                + ' data-name="' + escapeHtml(item.name) + '"'
                + ' data-sku="' + escapeHtml(item.sku || '') + '"'
                + ' data-thumb="' + escapeHtml(item.thumbnail || '') + '">'
                + thumb
                + '<div class="min-w-0 flex-1">'
                + '<div class="text-sm font-semibold leading-snug text-[#0b1c30]">' + escapeHtml(item.name) + '</div>'
                + sku
                + '</div>'
                + badge
                + '</button>';
        }).join('');

        resultsBox.querySelectorAll('.sample-product-result').forEach(function (btn) {
            btn.addEventListener('click', function () {
                selectProduct(parseInt(btn.dataset.id, 10));
            });
        });

        highlightSelectedRow();
        updateScrollHint();
    }

    function applyProductDetails(product) {
        selectedProductId = String(product.id);
        hiddenProductId.value = selectedProductId;
        selectedName.textContent = product.name;

        const metaParts = [];
        if (product.sku) metaParts.push('SKU: ' + product.sku);
        if (product.category) metaParts.push(product.category);
        if (product.min_tier) metaParts.push(product.min_tier + '+ tier');
        selectedMeta.textContent = metaParts.join(' · ');

        if (product.thumbnail) {
            selectedThumb.src = product.thumbnail;
            selectedThumb.classList.remove('hidden');
            selectedThumbPlaceholder.classList.add('hidden');
        } else {
            selectedThumb.removeAttribute('src');
            selectedThumb.classList.add('hidden');
            selectedThumbPlaceholder.classList.remove('hidden');
        }

        selectedWrap.classList.remove('hidden');
        highlightSelectedRow();

        const maxQty = parseInt(product.max_qty || 1, 10) || 1;
        if (quantityInput) {
            quantityInput.max = maxQty;
            quantityInput.min = 1;
            if (parseInt(quantityInput.value, 10) > maxQty) quantityInput.value = maxQty;
            if (maxQty <= 1) {
                quantityInput.value = 1;
                quantityWrap.classList.add('hidden');
            } else {
                quantityWrap.classList.remove('hidden');
                quantityHint.textContent = 'Max ' + maxQty + ' per request for this product.';
            }
        }

        variantSelect.innerHTML = '<option value="">Select variant</option>';
        const variants = Array.isArray(product.variants) ? product.variants : [];

        if (variants.length) {
            variantWrap.classList.remove('hidden');
            sizeWrap.classList.add('hidden');
            variantSelect.required = true;
            variants.forEach(function (v) {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = v.label + ' (stock: ' + v.qty + ')';
                if (String(oldVariant) === String(v.id)) opt.selected = true;
                variantSelect.appendChild(opt);
            });
        } else {
            variantWrap.classList.add('hidden');
            sizeWrap.classList.remove('hidden');
            variantSelect.required = false;
            variantSelect.value = '';
        }
    }

    function selectProduct(id) {
        fetch(productShowEndpoint(id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (response) {
                if (!response.ok) throw new Error('not found');
                return response.json();
            })
            .then(function (product) {
                applyProductDetails(product);
            })
            .catch(function () {
                selectedProductId = '';
                hiddenProductId.value = '';
                selectedWrap.classList.add('hidden');
                renderResults([], 'This product is no longer available for samples.');
            });
    }

    function runSearch() {
        const q = searchInput.value.trim();

        if (listCaption) {
            listCaption.textContent = q
                ? 'Search results'
                : (activeCollectionId !== '' ? 'Suggested products in selected collection' : 'Suggested products');
        }

        if (q.length === 1) {
            resultsBox.innerHTML = '<div class="px-4 py-8 text-center text-xs text-[#707884]">Type at least 2 characters to search.</div>';
            if (scrollHint) scrollHint.classList.add('hidden');
            return;
        }

        if (currentController) currentController.abort();
        currentController = new AbortController();

        searchLoading.classList.remove('hidden');
        resultsBox.innerHTML = '';

        const params = new URLSearchParams();
        if (q) params.set('q', q);
        if (activeCollectionId !== '') params.set('collection_id', activeCollectionId);

        fetch(searchUrl + '?' + params.toString(), {
            signal: currentController.signal,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                const emptyMessage = q
                    ? 'No products found.'
                    : (activeCollectionId !== '' ? 'No products in this collection.' : 'No sample products available.');
                renderResults(payload.data || [], emptyMessage);
            })
            .catch(function (error) {
                if (error.name === 'AbortError') return;
                renderResults([], 'Could not load products. Please try again.');
            })
            .finally(function () {
                searchLoading.classList.add('hidden');
            });
    }

    const debouncedSearch = debounce(runSearch, 350);

    searchInput.addEventListener('input', debouncedSearch);

    if (collectionFilters) {
        collectionFilters.addEventListener('click', function (event) {
            const chip = event.target.closest('.sample-collection-chip');
            if (!chip) return;
            activeCollectionId = chip.dataset.collectionId || '';
            setCollectionChipActive(chip);
            runSearch();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            selectedProductId = '';
            hiddenProductId.value = '';
            searchInput.value = '';
            selectedWrap.classList.add('hidden');
            variantWrap.classList.add('hidden');
            sizeWrap.classList.add('hidden');
            quantityWrap.classList.add('hidden');
            variantSelect.required = false;
            variantSelect.innerHTML = '<option value="">Select variant</option>';
            highlightSelectedRow();
            runSearch();
            searchInput.focus();
        });
    }

    if (resultsBox) {
        resultsBox.addEventListener('scroll', updateScrollHint);
        window.addEventListener('resize', updateScrollHint);
    }

    runSearch();

    if (initialProduct) {
        applyProductDetails(initialProduct);
    }
})();
</script>
@endpush
