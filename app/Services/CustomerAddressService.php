<?php

namespace App\Services;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerAddressService
{
    /**
     * @return list<CustomerAddress>
     */
    public function listFor(User $user): array
    {
        return $user->addresses()
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('id')
            ->get()
            ->all();
    }

    public function defaultShipping(User $user): ?CustomerAddress
    {
        return $user->addresses()->where('is_default_shipping', true)->first()
            ?? $user->addresses()->latest('id')->first();
    }

    /**
     * @param  array{
     *   recipient_name: string,
     *   phone?: ?string,
     *   line1: string,
     *   line2?: ?string,
     *   city: string,
     *   state_province?: ?string,
     *   postal_code: string,
     *   country_code: string,
     *   label?: ?string,
     *   is_default_shipping?: bool,
     *   is_default_billing?: bool
     * }  $data
     */
    public function create(User $user, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($user, $data) {
            $address = $user->addresses()->create($this->attributes($data));
            $this->applyDefaults($user->id, $address, $data);

            return $address->fresh();
        });
    }

    public function update(User $user, CustomerAddress $address, array $data): CustomerAddress
    {
        $this->assertOwned($user, $address);

        return DB::transaction(function () use ($user, $address, $data) {
            $address->update($this->attributes($data));
            $this->applyDefaults($user->id, $address->fresh(), $data);

            return $address->fresh();
        });
    }

    public function delete(User $user, CustomerAddress $address): void
    {
        $this->assertOwned($user, $address);

        DB::transaction(function () use ($user, $address) {
            $wasShipping = $address->is_default_shipping;
            $wasBilling = $address->is_default_billing;
            $address->delete();

            $next = $user->addresses()->latest('id')->first();
            if ($next && ($wasShipping || $wasBilling)) {
                $next->update([
                    'is_default_shipping' => $wasShipping || $next->is_default_shipping,
                    'is_default_billing' => $wasBilling || $next->is_default_billing,
                ]);
            }
        });
    }

    public function makeDefault(User $user, CustomerAddress $address, bool $shipping = true, bool $billing = true): CustomerAddress
    {
        $this->assertOwned($user, $address);

        return DB::transaction(function () use ($user, $address, $shipping, $billing) {
            if ($shipping) {
                $user->addresses()->update(['is_default_shipping' => false]);
                $address->is_default_shipping = true;
            }
            if ($billing) {
                $user->addresses()->update(['is_default_billing' => false]);
                $address->is_default_billing = true;
            }
            $address->save();

            return $address->fresh();
        });
    }

    /**
     * Prefill map for checkout form fields.
     *
     * @return array{
     *   customer_name: ?string,
     *   customer_phone: ?string,
     *   shipping_address: ?string,
     *   city: ?string,
     *   state: ?string,
     *   postal_code: ?string,
     *   country: ?string
     * }|null
     */
    public function checkoutPrefill(User $user): ?array
    {
        $address = $this->defaultShipping($user);
        if (! $address) {
            return null;
        }

        $line = trim(implode("\n", array_filter([
            $address->line1,
            $address->line2,
        ])));

        return [
            'customer_name' => $address->recipient_name ?: $user->name,
            'customer_phone' => $address->phone ?: $user->phone,
            'shipping_address' => $line !== '' ? $line : null,
            'city' => $address->city,
            'state' => $address->state_province,
            'postal_code' => $address->postal_code,
            'country' => strtoupper((string) $address->country_code),
        ];
    }

    private function attributes(array $data): array
    {
        $out = [];
        foreach ([
            'recipient_name', 'phone', 'line1', 'line2', 'city',
            'state_province', 'postal_code', 'country_code', 'label',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                $out[$key] = $key === 'country_code'
                    ? strtoupper((string) $data[$key])
                    : $data[$key];
            }
        }

        return $out;
    }

    private function applyDefaults(int $userId, CustomerAddress $address, array $data): void
    {
        $makeShipping = (bool) ($data['is_default_shipping'] ?? $address->is_default_shipping);
        $makeBilling = (bool) ($data['is_default_billing'] ?? $address->is_default_billing);

        if (! CustomerAddress::where('user_id', $userId)->where('id', '!=', $address->id)->exists()) {
            $makeShipping = true;
            $makeBilling = true;
        }

        if ($makeShipping) {
            CustomerAddress::where('user_id', $userId)->update(['is_default_shipping' => false]);
            $address->is_default_shipping = true;
        }
        if ($makeBilling) {
            CustomerAddress::where('user_id', $userId)->update(['is_default_billing' => false]);
            $address->is_default_billing = true;
        }
        $address->save();
    }

    private function assertOwned(User $user, CustomerAddress $address): void
    {
        if ((int) $address->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'address' => 'Address not found.',
            ]);
        }
    }
}
