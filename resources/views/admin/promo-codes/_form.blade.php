@php
    $promoCode = $promoCode ?? null;
    $isEdit = $promoCode !== null;
@endphp

<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
    <div class="border-b border-gray-200 pb-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Code details</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">Code <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <input type="text" name="code" id="code" value="{{ old('code', $promoCode ? $promoCode->code : ($suggestedCode ?? '')) }}"
                           class="flex-1 min-w-0 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 uppercase @error('code') border-red-500 @enderror"
                           placeholder="e.g. SAVE10 or PON-ABC12XYZ" required maxlength="64">
                    @unless($isEdit)
                    <button type="button" id="promo-generate-code-btn"
                            class="shrink-0 px-4 py-2 border border-violet-300 rounded-lg text-sm font-medium text-violet-800 bg-violet-50 hover:bg-violet-100 transition-colors whitespace-nowrap"
                            title="Generate another random code">
                        Random
                    </button>
                    @endunless
                </div>
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Saved as uppercase. Random format: <code class="font-mono">PON-XXXXXXXX</code></p>
            </div>

            <div>
                <label for="type" class="block text-sm font-semibold text-gray-700 mb-2">Discount type <span class="text-red-500">*</span></label>
                <select name="type" id="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('type') border-red-500 @enderror" required>
                    <option value="percentage" {{ old('type', $promoCode ? $promoCode->type : '') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                    <option value="fixed" {{ old('type', $promoCode ? $promoCode->type : '') === 'fixed' ? 'selected' : '' }}>Fixed amount (USD)</option>
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label for="value" class="block text-sm font-semibold text-gray-700 mb-2">Value <span class="text-red-500">*</span></label>
                <div class="flex items-center gap-2">
                    <input type="number" name="value" id="value" step="0.01" min="0" max="100"
                           value="{{ old('value', $promoCode ? $promoCode->value : 0) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('value') border-red-500 @enderror"
                           placeholder="{{ (old('type', $promoCode ? $promoCode->type : 'percentage')) === 'percentage' ? '10' : '5' }}">
                    <span class="text-gray-500 whitespace-nowrap" id="value-suffix">%</span>
                </div>
                @error('value')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Percentage: 0–100. Fixed: USD discount (e.g. 5 = $5).</p>
            </div>

            <div>
                <label for="min_order_value" class="block text-sm font-semibold text-gray-700 mb-2">Minimum order (USD)</label>
                <input type="number" name="min_order_value" id="min_order_value" step="0.01" min="0"
                       value="{{ old('min_order_value', $promoCode ? $promoCode->min_order_value : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="Leave empty for no minimum">
                @error('min_order_value')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label for="max_uses" class="block text-sm font-semibold text-gray-700 mb-2">Maximum redemptions</label>
                <input type="number" name="max_uses" id="max_uses" min="0" value="{{ old('max_uses', $promoCode ? $promoCode->max_uses : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="Leave empty for unlimited">
                @error('max_uses')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center pt-8">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $promoCode ? $promoCode->is_active : true) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm font-medium text-gray-700">Active (can be applied)</span>
                </label>
            </div>
        </div>
    </div>

    <div class="border-b border-gray-200 pb-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Validity</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="starts_at" class="block text-sm font-semibold text-gray-700 mb-2">Starts at</label>
                <input type="datetime-local" name="starts_at" id="starts_at"
                       value="{{ old('starts_at', $promoCode && $promoCode->starts_at ? $promoCode->starts_at->format('Y-m-d\TH:i') : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @error('starts_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="expires_at" class="block text-sm font-semibold text-gray-700 mb-2">Expires at</label>
                <input type="datetime-local" name="expires_at" id="expires_at"
                       value="{{ old('expires_at', $promoCode && $promoCode->expires_at ? $promoCode->expires_at->format('Y-m-d\TH:i') : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @error('expires_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Leave empty for no expiry.</p>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Email automation</h3>
        <div>
            <label for="send_on_trigger" class="block text-sm font-semibold text-gray-700 mb-2">Send this code when</label>
            <select name="send_on_trigger" id="send_on_trigger" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">— Do not send automatically —</option>
                <option value="thank_you" {{ old('send_on_trigger', $promoCode ? $promoCode->send_on_trigger : '') === 'thank_you' ? 'selected' : '' }}>Thank you (after successful order)</option>
                <option value="wishlist" {{ old('send_on_trigger', $promoCode ? $promoCode->send_on_trigger : '') === 'wishlist' ? 'selected' : '' }}>Product added to wishlist</option>
                <option value="add_to_cart" {{ old('send_on_trigger', $promoCode ? $promoCode->send_on_trigger : '') === 'add_to_cart' ? 'selected' : '' }}>Add to cart</option>
                <option value="checkout_fail" {{ old('send_on_trigger', $promoCode ? $promoCode->send_on_trigger : '') === 'checkout_fail' ? 'selected' : '' }}>Checkout failed</option>
            </select>
            @error('send_on_trigger')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Use one code per trigger. When selected, this code is emailed to the customer when the event occurs.</p>
        </div>
    </div>

    @php
        $affiliatesList = $affiliates ?? collect();
    @endphp
    <div class="border-t border-gray-200 pt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Affiliate (optional)</h3>
        <label for="affiliate_id" class="block text-sm font-semibold text-gray-700 mb-2">Assign to affiliate</label>
        <select name="affiliate_id" id="affiliate_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            <option value="">— None —</option>
            @foreach($affiliatesList as $af)
                <option value="{{ $af->id }}" {{ (string) old('affiliate_id', $selectedAffiliateId ?? $promoCode?->affiliate_id) === (string) $af->id ? 'selected' : '' }}>
                    {{ $af->code }}@if($af->display_name) — {{ $af->display_name }}@endif
                </option>
            @endforeach
        </select>
        @error('affiliate_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-gray-500">When a buyer enters this promo code at checkout, attribution takes priority over the <span class="font-mono">ref</span> cookie.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeEl = document.getElementById('type');
    var valueEl = document.getElementById('value');
    var suffixEl = document.getElementById('value-suffix');
    function updateSuffix() {
        if (typeEl.value === 'percentage') {
            suffixEl.textContent = '%';
            if (valueEl) valueEl.setAttribute('max', '100');
        } else {
            suffixEl.textContent = ' USD';
            if (valueEl) valueEl.removeAttribute('max');
        }
    }
    typeEl.addEventListener('change', updateSuffix);
    updateSuffix();

    var genBtn = document.getElementById('promo-generate-code-btn');
    var codeInput = document.getElementById('code');
    if (genBtn && codeInput) {
        genBtn.addEventListener('click', function () {
            genBtn.disabled = true;
            genBtn.textContent = '…';
            fetch(@json(route('admin.promo-codes.suggest-code')), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                .then(function (res) {
                    if (res.ok && res.data.code) {
                        codeInput.value = res.data.code;
                        codeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    } else {
                        alert(res.data.message || 'Could not generate code.');
                    }
                })
                .catch(function () { alert('Could not generate code.'); })
                .finally(function () {
                    genBtn.disabled = false;
                    genBtn.textContent = 'Random';
                });
        });
    }
});
</script>
