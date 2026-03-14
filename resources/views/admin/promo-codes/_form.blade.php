@php
    $promoCode = $promoCode ?? null;
    $isEdit = $promoCode !== null;
@endphp

<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
    <div class="border-b border-gray-200 pb-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin mã</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">Mã code <span class="text-red-500">*</span></label>
                <input type="text" name="code" id="code" value="{{ old('code', $promoCode ? $promoCode->code : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 uppercase @error('code') border-red-500 @enderror"
                       placeholder="VD: SAVE10" required maxlength="64">
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Lưu sẽ tự chuyển sang chữ IN HOA</p>
            </div>

            <div>
                <label for="type" class="block text-sm font-semibold text-gray-700 mb-2">Loại giảm giá <span class="text-red-500">*</span></label>
                <select name="type" id="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('type') border-red-500 @enderror" required>
                    <option value="percentage" {{ old('type', $promoCode ? $promoCode->type : '') === 'percentage' ? 'selected' : '' }}>Theo % (percentage)</option>
                    <option value="fixed" {{ old('type', $promoCode ? $promoCode->type : '') === 'fixed' ? 'selected' : '' }}>Số tiền cố định (USD)</option>
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label for="value" class="block text-sm font-semibold text-gray-700 mb-2">Giá trị <span class="text-red-500">*</span></label>
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
                <p class="mt-1 text-xs text-gray-500">%: 0–100. Fixed: số USD giảm (VD: 5 = $5)</p>
            </div>

            <div>
                <label for="min_order_value" class="block text-sm font-semibold text-gray-700 mb-2">Đơn tối thiểu (USD)</label>
                <input type="number" name="min_order_value" id="min_order_value" step="0.01" min="0"
                       value="{{ old('min_order_value', $promoCode ? $promoCode->min_order_value : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="Để trống = không yêu cầu">
                @error('min_order_value')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label for="max_uses" class="block text-sm font-semibold text-gray-700 mb-2">Số lần dùng tối đa</label>
                <input type="number" name="max_uses" id="max_uses" min="0" value="{{ old('max_uses', $promoCode ? $promoCode->max_uses : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="Để trống = không giới hạn">
                @error('max_uses')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center pt-8">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $promoCode ? $promoCode->is_active : true) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm font-medium text-gray-700">Đang bật (áp dụng được)</span>
                </label>
            </div>
        </div>
    </div>

    <div class="border-b border-gray-200 pb-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Hạn sử dụng</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="starts_at" class="block text-sm font-semibold text-gray-700 mb-2">Có hiệu lực từ</label>
                <input type="datetime-local" name="starts_at" id="starts_at"
                       value="{{ old('starts_at', $promoCode && $promoCode->starts_at ? $promoCode->starts_at->format('Y-m-d\TH:i') : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @error('starts_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="expires_at" class="block text-sm font-semibold text-gray-700 mb-2">Hết hạn lúc</label>
                <input type="datetime-local" name="expires_at" id="expires_at"
                       value="{{ old('expires_at', $promoCode && $promoCode->expires_at ? $promoCode->expires_at->format('Y-m-d\TH:i') : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @error('expires_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Để trống = không hết hạn</p>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Gửi mã qua email khi</h3>
        <div>
            <label for="send_on_trigger" class="block text-sm font-semibold text-gray-700 mb-2">Trigger gửi email</label>
            <select name="send_on_trigger" id="send_on_trigger" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">— Không gửi tự động —</option>
                <option value="thank_you" {{ old('send_on_trigger', $promoCode ? $promoCode->send_on_trigger : '') === 'thank_you' ? 'selected' : '' }}>Thank you (sau khi đặt hàng thành công)</option>
                <option value="wishlist" {{ old('send_on_trigger', $promoCode ? $promoCode->send_on_trigger : '') === 'wishlist' ? 'selected' : '' }}>Thêm sản phẩm vào Wishlist</option>
                <option value="add_to_cart" {{ old('send_on_trigger', $promoCode ? $promoCode->send_on_trigger : '') === 'add_to_cart' ? 'selected' : '' }}>Add to cart</option>
            </select>
            @error('send_on_trigger')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Mỗi trigger chỉ nên gán 1 mã. Nếu chọn trigger, mã này sẽ được gửi tới email khách khi sự kiện xảy ra.</p>
        </div>
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
});
</script>
