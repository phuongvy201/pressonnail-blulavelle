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

    @if ($products->isEmpty())
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
                    <label for="product_id" class="block text-xs font-semibold uppercase text-[#707884]">Product *</label>
                    <select name="product_id" id="product_id" required
                            class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm"
                            onchange="window.sampleRequestOnProductChange && window.sampleRequestOnProductChange()">
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            @php $meta = $productMeta[$product->id] ?? []; @endphp
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)
                                data-has-variants="{{ ($meta['has_variants'] ?? false) ? '1' : '0' }}"
                                data-max-qty="{{ $meta['max_qty'] ?? 1 }}">
                                {{ $product->name }}
                                @if(!empty($meta['min_tier']))
                                    ({{ ucfirst($meta['min_tier']) }}+ tier)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="variant-wrap" class="hidden">
                    <label for="product_variant_id" class="block text-xs font-semibold uppercase text-[#707884]">Variant *</label>
                    <select name="product_variant_id" id="product_variant_id" class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                        <option value="">Select variant</option>
                    </select>
                </div>

                <div id="size-wrap" class="hidden">
                    <label for="size_preset" class="block text-xs font-semibold uppercase text-[#707884]">Nail size preset</label>
                    <select name="size_preset" id="size_preset" class="mt-1 w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                        <option value="">Select size (optional)</option>
                        @foreach ($sizePresets as $size)
                            <option value="{{ $size }}" @selected(old('size_preset') === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="quantity-wrap">
                    <label for="quantity" class="block text-xs font-semibold uppercase text-[#707884]">Quantity</label>
                    <input type="number" name="quantity" id="quantity" min="1" max="1" value="{{ old('quantity', 1) }}"
                           class="mt-1 w-24 rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm">
                    <p id="quantity-hint" class="mt-1 text-xs text-[#707884]"></p>
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
@php
    $productVariantsJson = [];
    foreach ($products as $p) {
        $productVariantsJson[$p->id] = $p->variants->map(function ($v) {
            $attr = '';
            if (is_array($v->attributes) && $v->attributes !== []) {
                $attr = ' ('.collect($v->attributes)->map(fn ($val, $key) => $key.': '.$val)->implode(', ').')';
            }
            return ['id' => $v->id, 'label' => $v->variant_name.$attr, 'qty' => $v->quantity];
        })->values()->all();
    }
@endphp
<script>
(function () {
    const productVariants = @json($productVariantsJson);

    const productSelect = document.getElementById('product_id');
    const variantWrap = document.getElementById('variant-wrap');
    const variantSelect = document.getElementById('product_variant_id');
    const sizeWrap = document.getElementById('size-wrap');
    const quantityInput = document.getElementById('quantity');
    const quantityHint = document.getElementById('quantity-hint');
    const quantityWrap = document.getElementById('quantity-wrap');
    const oldVariant = @json(old('product_variant_id'));

    function onProductChange() {
        const id = productSelect.value;
        const selectedOpt = productSelect.options[productSelect.selectedIndex];
        const maxQty = parseInt(selectedOpt?.dataset?.maxQty || '1', 10) || 1;
        if (quantityInput) {
            quantityInput.max = maxQty;
            quantityInput.min = 1;
            if (parseInt(quantityInput.value, 10) > maxQty) quantityInput.value = maxQty;
            if (maxQty <= 1) {
                quantityInput.value = 1;
                quantityWrap?.classList.add('hidden');
            } else {
                quantityWrap?.classList.remove('hidden');
                if (quantityHint) quantityHint.textContent = 'Max ' + maxQty + ' per request for this product.';
            }
        }
        const variants = productVariants[id] || [];
        variantSelect.innerHTML = '<option value="">Select variant</option>';
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

    window.sampleRequestOnProductChange = onProductChange;
    if (productSelect) {
        productSelect.addEventListener('change', onProductChange);
        onProductChange();
    }
})();
</script>
@endpush
