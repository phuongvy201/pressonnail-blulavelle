@php
    use App\Support\AffiliateTier;

    $productModel = $product ?? null;
    $canEnableSample = $productModel ? $productModel->canEnableSampleRequest() : true;
    $sampleEnabled = old('sample_request_enabled', $productModel?->sample_request_enabled ?? false);
    $tierOptions = AffiliateTier::options();
@endphp

<div class="md:col-span-2 {{ $canEnableSample || $sampleEnabled ? '' : 'opacity-60' }}" id="sample_request_wrap">
    <div class="rounded-lg border border-violet-200 bg-violet-50/80 p-4 space-y-4">
        <label class="flex items-start gap-3 {{ $canEnableSample || $sampleEnabled ? 'cursor-pointer' : 'cursor-not-allowed' }}">
            <input type="checkbox"
                   name="sample_request_enabled"
                   value="1"
                   id="sample_request_enabled"
                   {{ $sampleEnabled ? 'checked' : '' }}
                   @disabled(! $canEnableSample && ! $sampleEnabled)
                   class="mt-1 h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
            <span>
                <span class="block text-sm font-semibold text-violet-900">Sample request enabled</span>
                <span class="block text-xs text-violet-800">
                    Creator có thể request mẫu sản phẩm này (tách biệt với affiliate link).
                    @if($productModel && ! $canEnableSample)
                        SP chưa đủ điều kiện hiển thị shop — không thể bật mới.
                    @endif
                </span>
            </span>
        </label>

        <div id="sample_request_fields" class="grid grid-cols-1 gap-4 sm:grid-cols-2 {{ $sampleEnabled ? '' : 'hidden' }}">
            <div>
                <label for="sample_min_tier" class="block text-xs font-semibold uppercase text-violet-900">Tier tối thiểu</label>
                <select name="sample_min_tier" id="sample_min_tier"
                        class="mt-1 w-full rounded-lg border border-violet-200 bg-white px-3 py-2 text-sm">
                    <option value="">Any tier</option>
                    @foreach ($tierOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('sample_min_tier', $productModel?->sample_min_tier) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-violet-700">VD: Silver = chỉ creator tier Silver trở lên.</p>
            </div>

            <div>
                <label for="sample_max_quantity_per_request" class="block text-xs font-semibold uppercase text-violet-900">Max qty / request</label>
                <input type="number"
                       name="sample_max_quantity_per_request"
                       id="sample_max_quantity_per_request"
                       min="1"
                       max="10"
                       value="{{ old('sample_max_quantity_per_request', $productModel?->sample_max_quantity_per_request) }}"
                       placeholder="Global default"
                       class="mt-1 w-full rounded-lg border border-violet-200 bg-white px-3 py-2 text-sm">
            </div>

            <div>
                <label for="sample_quota_per_affiliate" class="block text-xs font-semibold uppercase text-violet-900">Quota / creator / product</label>
                <input type="number"
                       name="sample_quota_per_affiliate"
                       id="sample_quota_per_affiliate"
                       min="1"
                       max="99"
                       value="{{ old('sample_quota_per_affiliate', $productModel?->sample_quota_per_affiliate) }}"
                       placeholder="Unlimited"
                       class="mt-1 w-full rounded-lg border border-violet-200 bg-white px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-violet-700">Trong cùng chu kỳ quota tier (90 ngày). Để trống = không giới hạn riêng.</p>
            </div>

            <div class="flex items-end">
                <label class="flex items-start gap-3 rounded-lg border border-violet-200 bg-white p-3 w-full cursor-pointer">
                    <input type="hidden" name="sample_requires_approval" value="0">
                    <input type="checkbox"
                           name="sample_requires_approval"
                           value="1"
                           id="sample_requires_approval"
                           {{ old('sample_requires_approval', $productModel?->sample_requires_approval ?? true) ? 'checked' : '' }}
                           class="mt-1 h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                    <span>
                        <span class="block text-sm font-semibold text-violet-900">Require admin approval</span>
                        <span class="block text-xs text-violet-700">Tắt = tự duyệt &amp; tạo đơn sample khi creator submit.</span>
                    </span>
                </label>
            </div>
        </div>
    </div>
</div>

@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('sample_request_enabled');
        const fields = document.getElementById('sample_request_fields');
        const giftCard = document.getElementById('is_gift_card');
        if (!toggle || !fields) return;

        function syncSampleFields() {
            const giftOff = !giftCard || !giftCard.checked;
            const show = toggle.checked && giftOff;
            fields.classList.toggle('hidden', !show);
            if (giftCard && giftCard.checked) {
                toggle.checked = false;
                toggle.disabled = true;
            } else if (!toggle.hasAttribute('data-force-disabled')) {
                toggle.disabled = false;
            }
        }

        toggle.addEventListener('change', syncSampleFields);
        if (giftCard) giftCard.addEventListener('change', syncSampleFields);
        syncSampleFields();
    });
</script>
@endonce
