<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BulkDiscountSettingsController extends Controller
{
    /**
     * Configure quantity-based discounts (tier rules).
     *
     * Stored at: settings key `pricing.bulk_discounts` as JSON:
     * [
     *   {"min_qty":2,"percent":15},
     *   {"min_qty":3,"percent":17},
     *   {"min_qty":5,"percent":18}
     * ]
     * The percent applies once to every eligible line, from the summed quantity.
     * Excluded product ids: `pricing.volume_discount_excluded_product_ids`.
     */
    public function edit(): View
    {
        $defaults = Cart::defaultVolumeDiscountTiers();

        $raw = Settings::get('pricing.bulk_discounts');
        $rules = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $rules = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $rules = $raw;
        }

        $rules = is_array($rules) && count($rules) > 0 ? $rules : $defaults;
        $excludedProductIds = implode(', ', Cart::volumeDiscountExcludedProductIds());

        return view('admin.settings.bulk-discounts', compact('rules', 'defaults', 'excludedProductIds'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rules' => ['nullable', 'array'],
            'rules.*.min_qty' => ['required_with:rules', 'integer', 'min:1', 'max:999'],
            'rules.*.percent' => ['required_with:rules', 'numeric', 'min:0', 'max:95'],
            'excluded_product_ids' => ['nullable', 'string', 'max:2000'],
        ]);

        $rules = $validated['rules'] ?? [];

        // Normalize, remove empty rows, sort ascending by min_qty, and de-duplicate by min_qty (keep max percent).
        $normalized = [];
        foreach ($rules as $row) {
            $minQty = (int) ($row['min_qty'] ?? 0);
            $percent = (float) ($row['percent'] ?? 0);
            if ($minQty < 1 || $percent <= 0) {
                continue;
            }
            if (!isset($normalized[$minQty])) {
                $normalized[$minQty] = $percent;
            } else {
                $normalized[$minQty] = max($normalized[$minQty], $percent);
            }
        }

        ksort($normalized);
        $out = [];
        foreach ($normalized as $minQty => $percent) {
            $out[] = ['min_qty' => (int) $minQty, 'percent' => (float) $percent];
        }

        Settings::set('pricing.bulk_discounts', !empty($out) ? json_encode($out) : null);

        $rawIds = preg_split('/[\s,]+/', (string) ($validated['excluded_product_ids'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $excludedIds = [];
        foreach ($rawIds as $id) {
            if (ctype_digit((string) $id)) {
                $excludedIds[(int) $id] = (int) $id;
            }
        }
        $excludedIds = array_values(array_filter($excludedIds));
        Settings::set(
            'pricing.volume_discount_excluded_product_ids',
            $excludedIds === [] ? null : json_encode($excludedIds)
        );

        return redirect()
            ->route('admin.settings.bulk-discounts.edit')
            ->with('success', 'Bulk discount rules updated successfully.');
    }
}

