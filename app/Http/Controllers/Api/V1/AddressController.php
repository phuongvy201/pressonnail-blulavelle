<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->addresses()->orderByDesc('is_default_shipping')->orderByDesc('id')->get()
            ->map(fn (CustomerAddress $address) => self::toPayload($address))
            ->values()
            ->all();

        return ApiResponse::success($items, ['count' => count($items)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->rules($request);
        $address = DB::transaction(function () use ($request, $validated) {
            $address = $request->user()->addresses()->create($this->attributes($validated));
            $this->applyDefaults($request->user()->id, $address, $validated);

            return $address->fresh();
        });

        return ApiResponse::success(self::toPayload($address), null, 201);
    }

    public function show(Request $request, int $addressId): JsonResponse
    {
        $address = $this->owned($request, $addressId);

        return ApiResponse::success(self::toPayload($address));
    }

    public function update(Request $request, int $addressId): JsonResponse
    {
        $address = $this->owned($request, $addressId);
        $validated = $this->rules($request, false);

        DB::transaction(function () use ($request, $address, $validated) {
            $address->update($this->attributes($validated));
            $this->applyDefaults($request->user()->id, $address->fresh(), $validated);
        });

        return ApiResponse::success(self::toPayload($address->fresh()));
    }

    public function destroy(Request $request, int $addressId): JsonResponse
    {
        $address = $this->owned($request, $addressId);

        DB::transaction(function () use ($request, $address) {
            $wasShipping = $address->is_default_shipping;
            $wasBilling = $address->is_default_billing;
            $address->delete();

            $next = $request->user()->addresses()->latest('id')->first();
            if ($next && ($wasShipping || $wasBilling)) {
                $next->update([
                    'is_default_shipping' => $wasShipping || $next->is_default_shipping,
                    'is_default_billing' => $wasBilling || $next->is_default_billing,
                ]);
            }
        });

        return ApiResponse::success(['deleted' => true]);
    }

    public function makeDefault(Request $request, int $addressId): JsonResponse
    {
        $address = $this->owned($request, $addressId);

        $validated = $request->validate([
            'shipping' => ['sometimes', 'boolean'],
            'billing' => ['sometimes', 'boolean'],
        ]);

        $shipping = array_key_exists('shipping', $validated) ? (bool) $validated['shipping'] : true;
        $billing = array_key_exists('billing', $validated) ? (bool) $validated['billing'] : true;

        DB::transaction(function () use ($request, $address, $shipping, $billing) {
            if ($shipping) {
                $request->user()->addresses()->update(['is_default_shipping' => false]);
                $address->is_default_shipping = true;
            }
            if ($billing) {
                $request->user()->addresses()->update(['is_default_billing' => false]);
                $address->is_default_billing = true;
            }
            $address->save();
        });

        return ApiResponse::success(self::toPayload($address->fresh()));
    }

    public static function toPayload(CustomerAddress $address): array
    {
        return [
            'addressId' => $address->id,
            'recipientName' => $address->recipient_name,
            'phone' => $address->phone,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'city' => $address->city,
            'stateProvince' => $address->state_province,
            'postalCode' => $address->postal_code,
            'countryCode' => $address->country_code,
            'label' => $address->label,
            'isDefaultShipping' => $address->is_default_shipping,
            'isDefaultBilling' => $address->is_default_billing,
            'createdAt' => ApiResponse::iso($address->created_at),
            'updatedAt' => ApiResponse::iso($address->updated_at),
        ];
    }

    private function owned(Request $request, int $addressId): CustomerAddress
    {
        $address = $request->user()->addresses()->where('id', $addressId)->first();

        if (! $address) {
            abort(404, 'Address not found.');
        }

        return $address;
    }

    private function rules(Request $request, bool $creating = true): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'recipientName' => [$req, 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'line1' => [$req, 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => [$req, 'string', 'max:100'],
            'stateProvince' => ['nullable', 'string', 'max:100'],
            'postalCode' => [$req, 'string', 'max:20'],
            'countryCode' => [$req, 'string', 'size:2'],
            'label' => ['nullable', 'string', 'max:40'],
            'isDefaultShipping' => ['sometimes', 'boolean'],
            'isDefaultBilling' => ['sometimes', 'boolean'],
        ]);
    }

    private function attributes(array $validated): array
    {
        $map = [
            'recipientName' => 'recipient_name',
            'phone' => 'phone',
            'line1' => 'line1',
            'line2' => 'line2',
            'city' => 'city',
            'stateProvince' => 'state_province',
            'postalCode' => 'postal_code',
            'countryCode' => 'country_code',
            'label' => 'label',
        ];

        $out = [];
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $validated)) {
                $out[$to] = $from === 'countryCode' ? strtoupper((string) $validated[$from]) : $validated[$from];
            }
        }

        return $out;
    }

    private function applyDefaults(int $userId, CustomerAddress $address, array $validated): void
    {
        $makeShipping = $validated['isDefaultShipping'] ?? $address->is_default_shipping;
        $makeBilling = $validated['isDefaultBilling'] ?? $address->is_default_billing;

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
}
