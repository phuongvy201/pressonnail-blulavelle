@php
    $affiliate = $affiliate ?? null;
    $tierRates = $tierRates ?? \App\Support\AffiliateSettings::tierRates();
@endphp

<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Code (URL <span class="font-mono">?ref=</span>) <span class="text-red-500">*</span></label>
            <input type="text" name="code" value="{{ old('code', $affiliate?->code ?? request('code')) }}" required maxlength="64"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono lowercase @error('code') border-red-500 @enderror"
                   placeholder="anna">
            @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-gray-500">Stored lowercase; last-click cookie follows config.</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Display name</label>
            <input type="text" name="display_name" value="{{ old('display_name', $affiliate?->display_name ?? request('display_name')) }}" maxlength="255"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">User ID (optional)</label>
            <input type="number" name="user_id" value="{{ old('user_id', $affiliate?->user_id ?? request('user_id')) }}" min="1"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('user_id') border-red-500 @enderror"
                   placeholder="Linked account ID">
            @error('user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Tier <span class="text-red-500">*</span></label>
            <select name="tier" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                @foreach(\App\Support\AffiliateTier::options() as $val => $label)
                    <option value="{{ $val }}" {{ old('tier', \App\Support\AffiliateTier::normalize($affiliate?->tier ?? 'basic')) === $val ? 'selected' : '' }}>
                        {{ $label }} ({{ $tierRates[$val] ?? '—' }}%)
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Commission % override (admin)</label>
            <input type="number" name="commission_rate_override" step="0.01" min="0" max="100"
                   value="{{ old('commission_rate_override', $affiliate?->commission_rate_override) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                   placeholder="Leave empty to use tier rate">
        </div>
        <div class="flex items-center gap-6 pt-6">
            <label class="inline-flex items-center cursor-pointer">
                <input type="hidden" name="tier_locked" value="0">
                <input type="checkbox" name="tier_locked" value="1" {{ old('tier_locked', $affiliate?->tier_locked) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600">
                <span class="ml-2 text-sm text-gray-700">Lock tier (disable auto tier)</span>
            </label>
            <label class="inline-flex items-center cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $affiliate?->is_active ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600">
                <span class="ml-2 text-sm text-gray-700">Active</span>
            </label>
        </div>
    </div>
</div>
