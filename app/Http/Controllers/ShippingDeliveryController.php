<?php

namespace App\Http\Controllers;

use App\Models\ShippingRate;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class ShippingDeliveryController extends Controller
{
    /**
     * Display the shipping & delivery page
     */
    public function index()
    {
        $domain = CurrencyService::getCurrentDomain();
        $currency = CurrencyService::getCurrencyForDomain($domain);
        $currencyRate = CurrencyService::getCurrencyRateForDomain($domain) ?? 1.0;

        // Get region from domain
        $region = $this->getRegionFromDomain($domain);

        // Project hiện không có bảng/model DomainShippingCost → luôn fallback sang ShippingRate
        $shippingCosts = collect();

        // Fallback sang ShippingRate (bảng shipping_rates không còn cột domain/domains)
        if ($shippingCosts->isEmpty()) {
            $shippingRates = ShippingRate::where('is_active', true)
                ->whereNotNull('first_item_cost')
                ->whereNotNull('additional_item_cost')
                ->with(['category', 'shippingZone'])
                ->orderBy('sort_order')
                ->get();

            // Chuyển ShippingRate thành cấu trúc DomainShippingCost tương ứng
            $shippingCosts = $shippingRates->map(function ($rate) {
                // Chỉ hiển thị tên danh mục; nếu không có danh mục => "general"
                $productType = $rate->category
                    ? strtolower(str_replace(' ', '_', $rate->category->name))
                    : 'general';

                // Xác định region từ shipping zone (nếu có)
                $detectedRegion = null;
                if ($rate->shippingZone) {
                    $zone = $rate->shippingZone;
                    $countries = $zone->countries ?? [];
                    $zoneName = strtolower($zone->name ?? '');

                    $detectedRegion = $this->getRegionFromCountries($countries, $detectedRegion);
                    $detectedRegion = $this->getRegionFromZoneName($zoneName, $detectedRegion);
                }

                return (object) [
                    'region' => $detectedRegion,
                    'product_type' => $productType,
                    'first_item_cost' => (float) $rate->first_item_cost,
                    'additional_item_cost' => (float) $rate->additional_item_cost,
                    'delivery_min_days' => $rate->delivery_min_days,
                    'delivery_max_days' => $rate->delivery_max_days,
                    'delivery_note' => $rate->delivery_note,
                    'is_active' => $rate->is_active,
                ];
            })->filter(function ($cost) {
                // Bỏ các rate không xác định được region
                return !empty($cost->region);
            });
        }

        // Ưu tiên: nếu trong cùng region có sản phẩm cụ thể, ẩn general; mỗi product_type chỉ giữ rate ưu tiên (thấp nhất first_item_cost, rồi additional)
        $shippingCosts = $shippingCosts
            ->filter(fn($c) => !empty($c->region)) // đảm bảo có region
            ->groupBy('region')
            ->flatMap(function ($itemsByRegion) {
                $hasSpecific = $itemsByRegion->contains(fn($c) => $c->product_type !== 'general');
                if ($hasSpecific) {
                    // Có category cụ thể → bỏ toàn bộ general
                    $itemsByRegion = $itemsByRegion->filter(fn($c) => $c->product_type !== 'general');
                } else {
                    // Chỉ có general → giữ nguyên (nhưng chỉ lấy 1 bản ghi ưu tiên)
                    $itemsByRegion = $itemsByRegion->take(1);
                }

                // Mỗi product_type chọn 1 rate ưu tiên (cost thấp nhất)
                return $itemsByRegion->groupBy('product_type')->map(function ($group) {
                    return $group->sortBy([
                        ['first_item_cost', 'asc'],
                        ['additional_item_cost', 'asc'],
                    ])->first();
                })->values();
            });

        // Helper to format costs with currency conversion
        $formatCosts = function ($costCollection) use ($currency, $currencyRate, $domain) {
            return $costCollection->map(function ($cost) use ($currency, $currencyRate, $domain) {
                $firstItemConverted = CurrencyService::convertFromUSDWithRate(
                    $cost->first_item_cost,
                    $currency,
                    $currencyRate
                );
                $additionalItemConverted = CurrencyService::convertFromUSDWithRate(
                    $cost->additional_item_cost,
                    $currency,
                    $currencyRate
                );

                $minDays = $cost->delivery_min_days ?? null;
                $maxDays = $cost->delivery_max_days ?? null;
                $deliveryNote = $cost->delivery_note ?? null;

                $deliveryText = null;
                if (!is_null($minDays) && !is_null($maxDays)) {
                    $deliveryText = $minDays == $maxDays
                        ? "{$minDays} days"
                        : "{$minDays} - {$maxDays} days";
                } elseif (!is_null($minDays)) {
                    $deliveryText = "{$minDays}+ days";
                } elseif (!is_null($maxDays)) {
                    $deliveryText = "Up to {$maxDays} days";
                }
                if (!$deliveryText && $deliveryNote) {
                    $deliveryText = $deliveryNote;
                }

                return [
                    'product_type' => $cost->product_type,
                    'first_item' => CurrencyService::formatPrice($firstItemConverted, $currency, $domain),
                    'additional_item' => CurrencyService::formatPrice($additionalItemConverted, $currency, $domain),
                    'first_item_raw' => $firstItemConverted,
                    'additional_item_raw' => $additionalItemConverted,
                    'delivery_text' => $deliveryText,
                ];
            });
        };

        // Format shipping costs cho khu vực chính của domain
        $formattedCosts = $formatCosts(
            $shippingCosts->where('region', $region)
        );

        // Gom nhóm tất cả khu vực (bao gồm domain chỉ định + general)
        $allShippingCosts = $shippingCosts->groupBy('region');

        $allFormattedCosts = $allShippingCosts->map(function ($costs) use ($formatCosts) {
            return $formatCosts($costs);
        })->filter(function ($costs) {
            return $costs->isNotEmpty();
        });

        // Region display names
        $regionNames = [
            'US' => 'United States',
            'UK' => 'United Kingdom',
            'CA' => 'Canada',
            'MX' => 'Mexico',
            'EU' => 'Europe',
        ];

        $regionName = $regionNames[$region] ?? $region;

        return view('shipping-delivery.index', compact(
            'domain',
            'currency',
            'region',
            'regionName',
            'formattedCosts',
            'shippingCosts',
            'allFormattedCosts',
            'regionNames'
        ));
    }

    /**
     * Get region from countries array
     */
    private function getRegionFromCountries(array $countries, ?string $defaultRegion = null): ?string
    {
        $countryToRegion = [
            'US' => 'US',
            'USA' => 'US',
            'GB' => 'UK',
            'GBR' => 'UK',
            'UK' => 'UK',
            'CA' => 'CA',
            'CAN' => 'CA',
            'MX' => 'MX',
            'MEX' => 'MX',
        ];

        // Map EU countries to Europe (EU)
        $euCountries = [
            'AL',
            'AD',
            'AM',
            'AT',
            'AZ',
            'BY',
            'BE',
            'BA',
            'BG',
            'CH',
            'CY',
            'CZ',
            'DE',
            'DK',
            'EE',
            'ES',
            'FI',
            'FO',
            'FR',
            'GB',
            'GE',
            'GI',
            'GR',
            'HR',
            'HU',
            'IE',
            'IS',
            'IT',
            'LI',
            'LT',
            'LU',
            'LV',
            'MC',
            'MD',
            'ME',
            'MK',
            'MT',
            'NL',
            'NO',
            'PL',
            'PT',
            'RO',
            'RS',
            'RU',
            'SE',
            'SI',
            'SJ',
            'SK',
            'SM',
            'TR',
            'UA',
            'VA'
        ];

        foreach ($countries as $country) {
            $countryUpper = strtoupper($country);
            if (isset($countryToRegion[$countryUpper])) {
                return $countryToRegion[$countryUpper];
            }
            if (in_array($countryUpper, $euCountries, true)) {
                return 'EU';
            }
        }

        return $defaultRegion;
    }

    /**
     * Get region from zone name
     */
    private function getRegionFromZoneName(string $zoneName, ?string $defaultRegion = null): ?string
    {
        $zoneNameLower = strtolower($zoneName);

        // Check for Europe
        if (
            strpos($zoneNameLower, 'europe') !== false ||
            strpos($zoneNameLower, 'eu') !== false
        ) {
            return 'EU';
        }

        // Check for UK
        if (
            strpos($zoneNameLower, 'united kingdom') !== false ||
            strpos($zoneNameLower, 'uk') !== false ||
            strpos($zoneNameLower, 'britain') !== false
        ) {
            return 'UK';
        }

        // Check for Canada
        if (
            strpos($zoneNameLower, 'canada') !== false ||
            strpos($zoneNameLower, 'ca') !== false
        ) {
            return 'CA';
        }

        // Check for Mexico
        if (
            strpos($zoneNameLower, 'mexico') !== false ||
            strpos($zoneNameLower, 'mx') !== false
        ) {
            return 'MX';
        }

        // Check for US (should be last to avoid false positives)
        if (
            strpos($zoneNameLower, 'united states') !== false ||
            strpos($zoneNameLower, 'usa') !== false ||
            strpos($zoneNameLower, 'us') !== false
        ) {
            return 'US';
        }

        return $defaultRegion;
    }

    /**
     * Detect region from current domain/host.
     *
     * @return 'US'|'UK'|'CA'|'MX'|'EU'
     */
    private function getRegionFromDomain(?string $domain): string
    {
        $d = strtolower(trim((string) $domain));
        if ($d === '') {
            return 'US';
        }

        // Basic TLD / host heuristics
        if (str_ends_with($d, '.co.uk') || str_ends_with($d, '.uk') || str_contains($d, 'uk')) {
            return 'UK';
        }
        if (str_ends_with($d, '.ca') || str_contains($d, 'canada') || str_contains($d, 'ca')) {
            return 'CA';
        }
        if (str_ends_with($d, '.mx') || str_contains($d, 'mexico') || str_contains($d, 'mx')) {
            return 'MX';
        }

        return 'US';
    }
}
