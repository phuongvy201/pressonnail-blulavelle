<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Services\CustomerAddressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(private readonly CustomerAddressService $addresses)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $this->addresses->create($request->user(), $validated);

        return redirect()
            ->to(route('customer.profile.edit').'#address')
            ->with('success', __('Address saved. You can set it as default for checkout.'));
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        abort_unless((int) $address->user_id === (int) $request->user()->id, 404);

        $validated = $this->validated($request, false);
        $this->addresses->update($request->user(), $address, $validated);

        return redirect()
            ->to(route('customer.profile.edit').'#address')
            ->with('success', __('Address updated.'));
    }

    public function destroy(Request $request, CustomerAddress $address): RedirectResponse
    {
        abort_unless((int) $address->user_id === (int) $request->user()->id, 404);

        $this->addresses->delete($request->user(), $address);

        return redirect()
            ->to(route('customer.profile.edit').'#address')
            ->with('success', __('Address deleted.'));
    }

    public function makeDefault(Request $request, CustomerAddress $address): RedirectResponse
    {
        abort_unless((int) $address->user_id === (int) $request->user()->id, 404);

        $this->addresses->makeDefault($request->user(), $address, true, true);

        return redirect()
            ->to(route('customer.profile.edit').'#address')
            ->with('success', __('Default address updated. It will be used at checkout.'));
    }

    private function validated(Request $request, bool $creating = true): array
    {
        $req = $creating ? 'required' : 'sometimes';

        $validated = $request->validate([
            'recipient_name' => [$req, 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'line1' => [$req, 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => [$req, 'string', 'max:100'],
            'state_province' => ['nullable', 'string', 'max:100'],
            'postal_code' => [$req, 'string', 'max:20'],
            'country_code' => [$req, 'string', 'size:2'],
            'label' => ['nullable', 'string', 'max:40'],
            'is_default_shipping' => ['sometimes', 'boolean'],
            'is_default_billing' => ['sometimes', 'boolean'],
        ]);

        $validated['is_default_shipping'] = $request->boolean('is_default_shipping');
        $validated['is_default_billing'] = $request->boolean('is_default_billing') || $validated['is_default_shipping'];
        $validated['country_code'] = strtoupper((string) ($validated['country_code'] ?? ''));

        return $validated;
    }
}
