<?php

namespace App\Services;

use App\Models\ShippingZone;
use App\Models\ShippingRate;
use App\Models\Product;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    /**
     * Calculate shipping cost for a cart
     * 
     * @param Collection $cartItems Array of items with 'product_id', 'quantity', 'price'
     * @param string $countryCode ISO country code (e.g., 'US', 'VN')
     * @return array Shipping details with cost breakdown per item
     */
    public function calculateShipping(Collection $cartItems, string $countryCode): array
    {
        // Find shipping zone for the country
        $zone = ShippingZone::findByCountry($countryCode);

        if (!$zone) {
            return [
                'success' => false,
                'message' => 'Shipping not available for this country',
                'total_shipping' => 0,
                'items' => []
            ];
        }

        // Group items by category and calculate total value
        $itemsWithDetails = $this->prepareItems($cartItems);
        $totalValue = $itemsWithDetails->sum('total_price');
        $totalItems = $itemsWithDetails->sum('quantity');

        // Sort items by price (descending) to apply first-item rate to most expensive
        $sortedItems = $itemsWithDetails->sortByDesc('unit_price');

        // Calculate shipping for each item
        $shippingDetails = [];
        $totalShipping = 0;
        $isFirstItemProcessed = false;

        foreach ($sortedItems as $index => $item) {
            $product = Product::with('template.category')->find($item['product_id']);
            $categoryId = $product->template->category_id ?? null;

            $shippingRate = $this->findApplicableRate($zone->id, $categoryId, $totalItems, $totalValue);

            if (!$shippingRate) {
                // Get zone name for better error message
                $zoneName = $zone->name ?? 'this zone';
                $categoryName = $product->template->category->name ?? 'this category';

                return [
                    'success' => false,
                    'message' => "No shipping rate found for {$categoryName} in {$zoneName}. Please contact support.",
                    'total_shipping' => 0,
                    'items' => []
                ];
            }

            // Calculate cost for this item
            $itemShipping = 0;
            $isFirstItem = false;
            $itemQuantity = $item['quantity'];

            if (!$isFirstItemProcessed) {
                // CHỈ CÓ 1 ITEM ĐẦU TIÊN được tính first_item_cost
                // Các items còn lại (kể cả cùng product) tính additional_item_cost
                $itemShipping = $shippingRate->first_item_cost; // Chỉ 1 item

                // Nếu quantity > 1, các items còn lại tính additional
                if ($itemQuantity > 1) {
                    $itemShipping += ($itemQuantity - 1) * $shippingRate->additional_item_cost;
                }

                $isFirstItem = true;
                $isFirstItemProcessed = true;
            } else {
                // Tất cả items tiếp theo đều tính additional_item_cost
                $itemShipping = $shippingRate->additional_item_cost * $itemQuantity;
            }

            $totalShipping += $itemShipping;

            $shippingDetails[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'shipping_cost' => $itemShipping,
                'total_item_shipping' => $itemShipping,
                'is_first_item' => $isFirstItem,
                'shipping_rate_id' => $shippingRate->id,
                'shipping_rate_name' => $shippingRate->name,
                'is_default' => $shippingRate->is_default ?? false,  // Include default flag
            ];
        }

        // Check if any rate used is default (for display purposes)
        $hasDefaultRate = !empty($shippingDetails) && ($shippingDetails[0]['is_default'] ?? false);

        return [
            'success' => true,
            'zone_id' => $zone->id,
            'zone_name' => $zone->name,
            'country' => $countryCode,
            'total_shipping' => round($totalShipping, 2),
            'items' => $shippingDetails,
            'is_default' => $hasDefaultRate,  // Indicate if default rate was used
            'breakdown' => [
                'total_items' => $totalItems,
                'total_value' => $totalValue,
                'currency' => 'USD'
            ]
        ];
    }

    /**
     * Prepare cart items with product details
     * 
     * @param Collection $cartItems
     * @return Collection
     */
    protected function prepareItems(Collection $cartItems): Collection
    {
        return $cartItems->map(function ($item) {
            $product = Product::find($item['product_id']);

            return [
                'product_id' => $item['product_id'],
                'product_name' => $product->name ?? 'Unknown Product',
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['price'] ?? $product->price ?? 0,
                'total_price' => ($item['price'] ?? $product->price ?? 0) * ($item['quantity'] ?? 1),
            ];
        });
    }

    /**
     * Find the most applicable shipping rate
     *
     * @param int $zoneId
     * @param int|null $categoryId
     * @param int $itemCount
     * @param float $orderValue
     * @return ShippingRate|null
     */
    protected function findApplicableRate(int $zoneId, ?int $categoryId, int $itemCount, float $orderValue): ?ShippingRate
    {
        $applicable = fn($r) => $r->isApplicable($itemCount, $orderValue);

        // 1. Default rate for category
        if ($categoryId) {
            $defaultRate = ShippingRate::active()
                ->forZone($zoneId)
                ->where('category_id', $categoryId)
                ->where('is_default', true)
                ->ordered()
                ->get()
                ->first($applicable);
            if ($defaultRate) {
                return $defaultRate;
            }
        }

        // 2. Default general rate (no category)
        $defaultGeneral = ShippingRate::active()
            ->forZone($zoneId)
            ->whereNull('category_id')
            ->where('is_default', true)
            ->ordered()
            ->get()
            ->first($applicable);
        if ($defaultGeneral) {
            return $defaultGeneral;
        }

        // 3. Category-specific rate (any)
        if ($categoryId) {
            $rate = ShippingRate::active()
                ->forZone($zoneId)
                ->where('category_id', $categoryId)
                ->ordered()
                ->get()
                ->first($applicable);
            if ($rate) {
                return $rate;
            }
        }

        // 4. General rate for zone
        return ShippingRate::active()
            ->forZone($zoneId)
            ->whereNull('category_id')
            ->ordered()
            ->get()
            ->first($applicable);

        // If still no rate found, try to get any active rate for this zone (ignore applicability)
        // This is a last resort fallback
        $anyRate = ShippingRate::active()
            ->forZone($zoneId)
            ->ordered()
            ->first();

        return $anyRate;
    }

    /**
     * Rate áp dụng cho một sản phẩm + quốc gia (ví dụ ước lượng ngày giao trên trang chi tiết).
     */
    public function findRateForProduct(Product $product, string $countryCode, int $quantity = 1, ?float $unitPriceUsd = null): ?ShippingRate
    {
        $zone = ShippingZone::findByCountry($countryCode);
        if (!$zone) {
            return null;
        }

        $categoryId = $product->template?->category_id;
        $unit = $unitPriceUsd ?? (float) ($product->price ?? $product->base_price ?? 0);
        $quantity = max(1, $quantity);
        $orderValue = $unit * $quantity;

        return $this->findApplicableRate((int) $zone->id, $categoryId, $quantity, $orderValue);
    }

    /**
     * Ước lượng khoảng ngày giao cho giỏ: mỗi dòng dùng cùng totalItems/totalValue như calculateShipping,
     * lấy max(min_days) và max(max_days) giữa các dòng (theo line chậm nhất).
     *
     * @return array{min_days: int, max_days: int, range_text: string}
     */
    public function getDeliveryEstimateRangeForCart(Collection $cartItems, string $countryCode): array
    {
        $defaultMin = 11;
        $defaultMax = 20;
        $formatRange = static function (int $minD, int $maxD): string {
            $start = now()->startOfDay()->addDays($minD);
            $end = now()->startOfDay()->addDays($maxD);

            return $start->format('M j') . '–' . ($start->format('M') === $end->format('M') ? $end->format('j') : $end->format('M j'));
        };

        $zone = ShippingZone::findByCountry($countryCode);
        if (!$zone || $cartItems->isEmpty()) {
            return [
                'min_days' => $defaultMin,
                'max_days' => $defaultMax,
                'range_text' => $formatRange($defaultMin, $defaultMax),
            ];
        }

        $itemsWithDetails = $this->prepareItems($cartItems);
        if ($itemsWithDetails->isEmpty()) {
            return [
                'min_days' => $defaultMin,
                'max_days' => $defaultMax,
                'range_text' => $formatRange($defaultMin, $defaultMax),
            ];
        }

        $totalValue = $itemsWithDetails->sum('total_price');
        $totalItems = (int) $itemsWithDetails->sum('quantity');

        $worstMin = null;
        $worstMax = null;

        foreach ($itemsWithDetails as $item) {
            $product = Product::with('template')->find($item['product_id']);
            if (!$product) {
                continue;
            }

            $categoryId = $product->template?->category_id;
            $rate = $this->findApplicableRate((int) $zone->id, $categoryId, $totalItems, (float) $totalValue);
            if (!$rate) {
                continue;
            }

            $min = $rate->delivery_min_days;
            $max = $rate->delivery_max_days;
            if ($min === null) {
                $min = $max;
            }
            if ($max === null) {
                $max = $min;
            }
            if ($min === null && $max === null) {
                continue;
            }

            $min = max(0, (int) $min);
            $max = max($min, (int) $max);
            $worstMin = $worstMin === null ? $min : max($worstMin, $min);
            $worstMax = $worstMax === null ? $max : max($worstMax, $max);
        }

        if ($worstMin === null || $worstMax === null) {
            return [
                'min_days' => $defaultMin,
                'max_days' => $defaultMax,
                'range_text' => $formatRange($defaultMin, $defaultMax),
            ];
        }

        return [
            'min_days' => $worstMin,
            'max_days' => $worstMax,
            'range_text' => $formatRange($worstMin, $worstMax),
        ];
    }

    /**
     * Get available shipping zones
     * 
     * @return Collection
     */
    public function getAvailableZones(): Collection
    {
        return ShippingZone::active()->ordered()->get();
    }

    /**
     * Get shipping rates for a specific zone
     * 
     * @param int $zoneId
     * @return Collection
     */
    public function getRatesForZone(int $zoneId): Collection
    {
        return ShippingRate::active()
            ->forZone($zoneId)
            ->ordered()
            ->get();
    }

    /**
     * Estimate shipping for quick display (simplified version)
     * 
     * @param string $countryCode
     * @param int $itemCount
     * @param float $estimatedValue
     * @return array
     */
    public function estimateShipping(string $countryCode, int $itemCount = 1, float $estimatedValue = 0): array
    {
        $zone = ShippingZone::findByCountry($countryCode);

        if (!$zone) {
            return [
                'available' => false,
                'message' => 'Shipping not available',
                'estimated_cost' => 0
            ];
        }

        // Get a general rate
        $rate = ShippingRate::active()
            ->forZone($zone->id)
            ->whereNull('category_id')
            ->ordered()
            ->first();

        if (!$rate || !$rate->isApplicable($itemCount, $estimatedValue)) {
            return [
                'available' => true,
                'message' => 'Rate depends on product type',
                'estimated_cost' => 0
            ];
        }

        $estimatedCost = $rate->calculateCost($itemCount);

        return [
            'available' => true,
            'zone_name' => $zone->name,
            'estimated_cost' => round($estimatedCost, 2),
            'currency' => 'USD'
        ];
    }
}
