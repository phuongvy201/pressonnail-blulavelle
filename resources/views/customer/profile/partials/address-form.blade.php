@php
    /** @var \App\Models\CustomerAddress|null $address */
    $isEdit = $address !== null;
@endphp
<form method="POST" action="{{ $formAction }}" class="space-y-4">
    @csrf
    @if (($formMethod ?? 'POST') !== 'POST')
        @method($formMethod)
    @endif
    @if ($isEdit)
        <input type="hidden" name="editing_address_id" value="{{ $address->id }}">
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-slate-700">{{ __('Recipient name') }} *</label>
            <input type="text" name="recipient_name" required
                value="{{ old('recipient_name', $address->recipient_name ?? auth()->user()->name) }}"
                class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none">
        </div>
        <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-slate-700">{{ __('Phone') }}</label>
            <input type="tel" name="phone"
                value="{{ old('phone', $address->phone ?? auth()->user()->phone) }}"
                class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none">
        </div>
        <div class="md:col-span-2 flex flex-col gap-2">
            <label class="text-sm font-semibold text-slate-700">{{ __('Address line 1') }} *</label>
            <input type="text" name="line1" required
                value="{{ old('line1', $address->line1 ?? '') }}"
                class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none">
        </div>
        <div class="md:col-span-2 flex flex-col gap-2">
            <label class="text-sm font-semibold text-slate-700">{{ __('Address line 2') }}</label>
            <input type="text" name="line2"
                value="{{ old('line2', $address->line2 ?? '') }}"
                class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none">
        </div>
        <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-slate-700">{{ __('City') }} *</label>
            <input type="text" name="city" required
                value="{{ old('city', $address->city ?? '') }}"
                class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none">
        </div>
        <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-slate-700">{{ __('State / Province') }}</label>
            <input type="text" name="state_province"
                value="{{ old('state_province', $address->state_province ?? '') }}"
                class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none">
        </div>
        <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-slate-700">{{ __('Postal code') }} *</label>
            <input type="text" name="postal_code" required
                value="{{ old('postal_code', $address->postal_code ?? '') }}"
                class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none">
        </div>
        <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-slate-700">{{ __('Country') }} *</label>
            <select name="country_code" required
                class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                <option value="">{{ __('Select country') }}</option>
                @foreach ($countryOptions as $code => $label)
                    <option value="{{ $code }}" @selected(old('country_code', $address->country_code ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-slate-700">{{ __('Label') }}</label>
            <input type="text" name="label" placeholder="{{ __('Home, Office…') }}"
                value="{{ old('label', $address->label ?? '') }}"
                class="w-full px-4 py-3 rounded-lg border border-primary/10 bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none">
        </div>
    </div>

    <label class="flex items-center gap-3 text-sm text-slate-700">
        <input type="checkbox" name="is_default_shipping" value="1" class="rounded border-primary/30 text-primary focus:ring-primary"
            @checked(old('is_default_shipping', $address->is_default_shipping ?? (isset($addresses) && count($addresses) === 0)))>
        <span>{{ __('Set as default shipping address for checkout') }}</span>
    </label>

    <div class="flex justify-end gap-3">
        <button type="submit" class="px-6 py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90 transition-colors">
            {{ $submitLabel }}
        </button>
    </div>
</form>
