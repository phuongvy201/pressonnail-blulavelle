@php
    $countryOptions = [
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'CA' => 'Canada',
        'AU' => 'Australia',
        'DE' => 'Germany',
        'FR' => 'France',
        'IT' => 'Italy',
        'ES' => 'Spain',
        'NL' => 'Netherlands',
        'BE' => 'Belgium',
        'IE' => 'Ireland',
        'NZ' => 'New Zealand',
        'SG' => 'Singapore',
        'JP' => 'Japan',
        'KR' => 'South Korea',
        'VN' => 'Vietnam',
        'PH' => 'Philippines',
        'MY' => 'Malaysia',
        'TH' => 'Thailand',
        'IN' => 'India',
        'MX' => 'Mexico',
        'BR' => 'Brazil',
    ];
    $editingId = (int) old('editing_address_id', 0);
    $openNewForm = $errors->hasAny(['recipient_name', 'line1', 'city', 'postal_code', 'country_code']) && $editingId === 0;
@endphp

<section id="address" class="bg-white border border-primary/10 rounded-xl p-8 shadow-sm scroll-mt-24">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">location_on</span>
                {{ __('Address Book') }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Save shipping addresses and set a default. The default address is filled in automatically at checkout.') }}
            </p>
        </div>
        <button type="button" id="toggle-new-address"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-primary text-white text-sm font-bold hover:bg-primary/90 transition-colors">
            <span class="material-symbols-outlined text-base">add</span>
            {{ __('Add address') }}
        </button>
    </div>

    <div class="space-y-4 mb-6">
        @forelse ($addresses as $address)
            <div class="rounded-xl border {{ $address->is_default_shipping ? 'border-primary bg-primary/5' : 'border-primary/10' }} p-5">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <p class="font-bold text-slate-900">{{ $address->recipient_name }}</p>
                            @if ($address->label)
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">{{ $address->label }}</span>
                            @endif
                            @if ($address->is_default_shipping)
                                <span class="px-2 py-0.5 rounded-full bg-primary text-white text-xs font-bold uppercase tracking-wide">{{ __('Default') }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-slate-700">{{ $address->line1 }}</p>
                        @if ($address->line2)
                            <p class="text-sm text-slate-700">{{ $address->line2 }}</p>
                        @endif
                        <p class="text-sm text-slate-700">
                            {{ collect([$address->city, $address->state_province, $address->postal_code])->filter()->implode(', ') }}
                        </p>
                        <p class="text-sm text-slate-700">{{ $countryOptions[$address->country_code] ?? $address->country_code }}</p>
                        @if ($address->phone)
                            <p class="text-sm text-slate-500 mt-1">{{ $address->phone }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        @unless ($address->is_default_shipping)
                            <form method="POST" action="{{ route('customer.addresses.default', $address) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg border border-primary/20 text-xs font-semibold text-primary hover:bg-primary/10">
                                    {{ __('Set default') }}
                                </button>
                            </form>
                        @endunless
                        <button type="button" class="js-edit-address px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-target="address-edit-{{ $address->id }}">
                            {{ __('Edit') }}
                        </button>
                        <form method="POST" action="{{ route('customer.addresses.destroy', $address) }}" onsubmit="return confirm(@js(__('Delete this address?')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-lg border border-red-200 text-xs font-semibold text-red-600 hover:bg-red-50">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>

                <div id="address-edit-{{ $address->id }}" class="mt-5 pt-5 border-t border-primary/10 {{ $editingId === (int) $address->id ? '' : 'hidden' }}">
                    @include('customer.profile.partials.address-form', [
                        'formAction' => route('customer.addresses.update', $address),
                        'formMethod' => 'PUT',
                        'address' => $address,
                        'countryOptions' => $countryOptions,
                        'addresses' => $addresses,
                        'submitLabel' => __('Update address'),
                    ])
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-primary/20 bg-primary/5 px-5 py-8 text-center">
                <p class="text-slate-600">{{ __('No saved addresses yet. Add one to speed up checkout.') }}</p>
            </div>
        @endforelse
    </div>

    <div id="new-address-form" class="rounded-xl border border-primary/10 p-5 bg-slate-50/80 {{ $openNewForm || count($addresses) === 0 ? '' : 'hidden' }}">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-bold text-slate-900">{{ __('New address') }}</h4>
            @if (count($addresses) > 0)
                <button type="button" id="cancel-new-address" class="text-sm text-slate-500 hover:text-slate-800">{{ __('Cancel') }}</button>
            @endif
        </div>
        @include('customer.profile.partials.address-form', [
            'formAction' => route('customer.addresses.store'),
            'formMethod' => 'POST',
            'address' => null,
            'countryOptions' => $countryOptions,
            'addresses' => $addresses,
            'submitLabel' => __('Save address'),
        ])
    </div>
</section>

<script>
(function () {
    var newForm = document.getElementById('new-address-form');
    var toggleBtn = document.getElementById('toggle-new-address');
    var cancelBtn = document.getElementById('cancel-new-address');
    if (toggleBtn && newForm) {
        toggleBtn.addEventListener('click', function () {
            newForm.classList.remove('hidden');
            newForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }
    if (cancelBtn && newForm) {
        cancelBtn.addEventListener('click', function () {
            newForm.classList.add('hidden');
        });
    }
    document.querySelectorAll('.js-edit-address').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-target');
            var panel = id ? document.getElementById(id) : null;
            if (!panel) return;
            document.querySelectorAll('[id^="address-edit-"]').forEach(function (el) {
                if (el !== panel) el.classList.add('hidden');
            });
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden')) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });
})();
</script>
