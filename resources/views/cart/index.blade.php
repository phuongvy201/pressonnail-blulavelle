@extends('layouts.app')

@section('title', 'Shopping Cart')

@section('content')
@php
    $currentCurrency = currency();
    $currencySymbol = currency_symbol();
    $currentCurrencyRate = currency_rate() ?? 1.0;
    $discount = $discount ?? 0;
    $appliedPromoCode = $appliedPromoCode ?? null;
    // Combo discount (based on total cart quantity) - keep display consistent with cart popup
    $bulkDiscountPercent = $bulkDiscountPercent ?? (\App\Models\Cart::getComboDiscountPercentForQty((int) $cartItems->sum('quantity')));
    $bulkDiscount = $bulkDiscount ?? ((float) $bulkDiscountPercent > 0 ? round((float) $subtotal * ((float) $bulkDiscountPercent / 100), 2) : 0.0);
    $subtotalAfterBulk = $subtotalAfterBulk ?? max(0, (float) $subtotal - (float) $bulkDiscount);
    $discountMode = $discountMode ?? session('discount_mode', $appliedPromoCode ? 'promo' : 'volume');

    // Get all active shipping rates (domain column removed)
    $shippingRates = \App\Models\ShippingRate::where('is_active', true)
        ->with('shippingZone')
        ->orderBy('is_default', 'desc')
        ->orderBy('sort_order')
        ->get();
    
    // Get default shipping rate (the one with is_default = true)
    $defaultShippingRate = $shippingRates->where('is_default', true)->first();
    
    // If no default rate, use the first active rate
    if (!$defaultShippingRate && $shippingRates->count() > 0) {
        $defaultShippingRate = $shippingRates->first();
    }
    
    // Get unique shipping zones from rates
    $shippingZones = $shippingRates->pluck('shippingZone')
        ->filter()
        ->unique('id')
        ->sortBy('sort_order')
        ->values();
    
    // Prepare shipping rates data for JavaScript (grouped by zone)
    $shippingRatesByZone = [];
    $shippingRatesData = [];
    
    // Mapping country codes to country names
    $countryNamesMap = [
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'UK' => 'United Kingdom',
        'CA' => 'Canada',
        'AU' => 'Australia',
        'MX' => 'Mexico',
        'DE' => 'Germany',
        'FR' => 'France',
        'IT' => 'Italy',
        'ES' => 'Spain',
        'NL' => 'Netherlands',
        'BE' => 'Belgium',
        'CH' => 'Switzerland',
        'AT' => 'Austria',
        'SE' => 'Sweden',
        'NO' => 'Norway',
        'DK' => 'Denmark',
        'FI' => 'Finland',
        'IE' => 'Ireland',
        'PT' => 'Portugal',
        'GR' => 'Greece',
        'PL' => 'Poland',
        'CZ' => 'Czech Republic',
        'HU' => 'Hungary',
        'RO' => 'Romania',
        'BG' => 'Bulgaria',
        'HR' => 'Croatia',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'EE' => 'Estonia',
        'LV' => 'Latvia',
        'LT' => 'Lithuania',
        'JP' => 'Japan',
        'CN' => 'China',
        'KR' => 'South Korea',
        'SG' => 'Singapore',
        'MY' => 'Malaysia',
        'TH' => 'Thailand',
        'ID' => 'Indonesia',
        'PH' => 'Philippines',
        'VN' => 'Vietnam',
        'IN' => 'India',
        'NZ' => 'New Zealand',
        'BR' => 'Brazil',
        'AR' => 'Argentina',
        'CL' => 'Chile',
        'CO' => 'Colombia',
        'PE' => 'Peru',
        'ZA' => 'South Africa',
        'EG' => 'Egypt',
        'AE' => 'United Arab Emirates',
        'SA' => 'Saudi Arabia',
        'IL' => 'Israel',
        'TR' => 'Turkey',
        'RU' => 'Russia',
        'UA' => 'Ukraine',
    ];
    
    // Helper function to convert country codes to names
    $convertCountryCodesToNames = function($countryCodes) use ($countryNamesMap) {
        if (empty($countryCodes) || !is_array($countryCodes)) {
            return [];
        }
        return array_map(function($code) use ($countryNamesMap) {
            $codeUpper = strtoupper($code);
            return $countryNamesMap[$codeUpper] ?? $codeUpper;
        }, $countryCodes);
    };
    
    foreach ($shippingRates as $rate) {
        // Determine zone name: use shippingZone name if exists, otherwise try to extract from rate name or use 'General'
        $zoneName = 'General';
        if ($rate->shippingZone) {
            $zoneName = $rate->shippingZone->name;
        } elseif ($rate->shipping_zone_id === null) {
            // For general domain rates, try to extract zone name from rate name
            $rateName = strtolower($rate->name ?? '');
            if (stripos($rateName, 'euro') !== false || stripos($rateName, 'europe') !== false) {
                $zoneName = 'Euro';
            } elseif (stripos($rateName, 'asia') !== false) {
                $zoneName = 'Asia';
            } elseif (stripos($rateName, 'america') !== false || stripos($rateName, 'us') !== false) {
                $zoneName = 'America';
            }
        }
        
        $rateData = [
            'id' => $rate->id,
            'zone_id' => $rate->shipping_zone_id,
            'zone_name' => $zoneName,
            'category_id' => $rate->category_id,
            'name' => $rate->name,
            'domain' => $rate->domain, // Add domain info to distinguish domain-specific vs general domain rates
            'first_item_cost' => (float) $rate->first_item_cost,
            'additional_item_cost' => (float) $rate->additional_item_cost,
            'is_default' => (bool) $rate->is_default,
            'min_items' => $rate->min_items,
            'max_items' => $rate->max_items,
            'min_order_value' => $rate->min_order_value ? (float) $rate->min_order_value : null,
            'max_order_value' => $rate->max_order_value ? (float) $rate->max_order_value : null,
        ];
        
        $shippingRatesData[] = $rateData;
        
        // Group by zone
        $zoneId = $rate->shipping_zone_id ?? 'none';
        if (!isset($shippingRatesByZone[$zoneId])) {
            $shippingRatesByZone[$zoneId] = [
                'zone_id' => $rate->shipping_zone_id,
                'zone_name' => $zoneName,
                'rates' => []
            ];
        }
        $shippingRatesByZone[$zoneId]['rates'][] = $rateData;
    }
    
    // Prepare zones data for dropdown
    // Create separate options for each country in each zone
    $zonesData = [];
    $zonesWithCountries = [];
    
    // Include zones with shipping_zone_id (from ShippingZone model)
    foreach ($shippingZones as $zone) {
        // Get country codes from zone
        $countries = $zone->countries ?? [];
        $countryCodes = is_array($countries) ? $countries : [];
        
        // Convert country codes to country names
        $countryNames = $convertCountryCodesToNames($countryCodes);
        
        // If zone has countries, create separate options for each country
        if (!empty($countryCodes)) {
            $zoneData = [
                'id' => $zone->id,
                'name' => $zone->name,
                'description' => $zone->description,
                'countries' => $countryCodes,
                'country_options' => []
            ];
            
            // Create an option for each country
            foreach ($countryCodes as $index => $countryCode) {
                $countryName = $countryNames[$index] ?? strtoupper($countryCode);
                $zoneData['country_options'][] = [
                    'value' => $zone->id . ':' . strtoupper($countryCode),
                    'label' => $countryName,
                    'zone_id' => $zone->id,
                    'country_code' => strtoupper($countryCode)
                ];
            }
            
            $zonesWithCountries[] = $zoneData;
        } else {
            // Zone without countries - keep as single option
            $zonesData[] = [
                'id' => $zone->id,
                'name' => $zone->name,
                'description' => $zone->description,
                'countries' => [],
                'display_name' => $zone->name,
            ];
        }
    }
    
    // Mapping for common general domain zones to country codes
    $generalZoneCountries = [
        'Euro' => ['AT', 'BE', 'DE', 'FR', 'IT', 'NL', 'ES', 'CH', 'UK'],
        'Europe' => ['AT', 'BE', 'DE', 'FR', 'IT', 'NL', 'ES', 'CH', 'UK'],
        'Asia' => ['CN', 'JP', 'KR', 'SG', 'MY', 'TH', 'ID', 'PH', 'VN'],
        'America' => ['US', 'CA', 'MX'],
        'US' => ['US'],
    ];
    
    // Also include zones with null shipping_zone_id (general domain zones)
    foreach ($shippingRatesByZone as $zoneId => $zoneData) {
        if ($zoneId === 'none' || $zoneData['zone_id'] === null) {
            // Check if this zone name already exists in zonesData
            $exists = collect($zonesData)->contains(function($zone) use ($zoneData) {
                return $zone['name'] === $zoneData['zone_name'];
            });
            
            if (!$exists && !empty($zoneData['zone_name'])) {
                // Get countries for this general domain zone from mapping
                $zoneName = $zoneData['zone_name'];
                $countries = $generalZoneCountries[$zoneName] ?? [];
                
                // Convert country codes to country names
                $countryNames = $convertCountryCodesToNames($countries);
                
                // If zone has countries, create separate options for each country
                if (!empty($countries)) {
                    $zoneIdValue = 'general_' . strtolower(str_replace(' ', '_', $zoneName));
                    $zoneDataItem = [
                        'id' => $zoneIdValue,
                        'name' => $zoneName,
                        'description' => null,
                        'countries' => $countries,
                        'country_options' => []
                    ];
                    
                    // Create an option for each country
                    foreach ($countries as $index => $countryCode) {
                        $countryName = $countryNames[$index] ?? strtoupper($countryCode);
                        $zoneDataItem['country_options'][] = [
                            'value' => $zoneIdValue . ':' . strtoupper($countryCode),
                            'label' => $countryName,
                            'zone_id' => $zoneIdValue,
                            'country_code' => strtoupper($countryCode)
                        ];
                    }
                    
                    $zonesWithCountries[] = $zoneDataItem;
                } else {
                    // Zone without countries - keep as single option
                    $zonesData[] = [
                        'id' => 'general_' . strtolower(str_replace(' ', '_', $zoneName)),
                        'name' => $zoneName,
                        'description' => null,
                        'countries' => [],
                        'display_name' => $zoneName,
                    ];
                }
            }
        }
    }
    
    // Prepare default shipping rate data for JavaScript
    $defaultShippingRateData = null;
    if ($defaultShippingRate) {
        $defaultShippingRateData = [
            'id' => $defaultShippingRate->id,
            'category_id' => $defaultShippingRate->category_id,
            'name' => $defaultShippingRate->name,
            'first_item_cost' => (float) $defaultShippingRate->first_item_cost,
            'additional_item_cost' => (float) $defaultShippingRate->additional_item_cost,
            'is_default' => true,
            'min_items' => $defaultShippingRate->min_items,
            'max_items' => $defaultShippingRate->max_items,
            'min_order_value' => $defaultShippingRate->min_order_value ? (float) $defaultShippingRate->min_order_value : null,
            'max_order_value' => $defaultShippingRate->max_order_value ? (float) $defaultShippingRate->max_order_value : null,
            'zone_id' => $defaultShippingRate->shipping_zone_id,
            'zone_name' => $defaultShippingRate->shippingZone ? $defaultShippingRate->shippingZone->name : null,
        ];
    }
    
    // Determine selected zone value for dropdown
    $selectedZoneValue = null;
    if ($defaultShippingRate) {
        $defaultZoneId = $defaultShippingRate->shipping_zone_id;
        
        // First, try to find in zones with countries
        foreach ($zonesWithCountries as $zone) {
            if ($zone['id'] == $defaultZoneId) {
                // If zone has countries, select first country option
                if (!empty($zone['country_options'])) {
                    $selectedZoneValue = $zone['country_options'][0]['value'];
                    break;
                }
            }
        }
        
        // If not found, try to find in regular zones
        if (!$selectedZoneValue) {
            foreach ($zonesData as $zone) {
                if ($zone['id'] == $defaultZoneId) {
                    $selectedZoneValue = $zone['id'];
                    break;
                }
            }
        }
        
        // If still not found and default zone is null, try to find general domain zone
        if (!$selectedZoneValue && $defaultZoneId === null) {
            $defaultZoneName = $defaultShippingRate->shippingZone 
                ? $defaultShippingRate->shippingZone->name 
                : ($defaultShippingRateData['zone_name'] ?? null);
            
            if ($defaultZoneName) {
                // Try to find in zones with countries
                foreach ($zonesWithCountries as $zone) {
                    if (strtolower($zone['name']) === strtolower($defaultZoneName)) {
                        if (!empty($zone['country_options'])) {
                            $selectedZoneValue = $zone['country_options'][0]['value'];
                            break;
                        }
                    }
                }
                
                // Try to find in regular zones
                if (!$selectedZoneValue) {
                    foreach ($zonesData as $zone) {
                        if (strtolower($zone['name']) === strtolower($defaultZoneName)) {
                            $selectedZoneValue = $zone['id'];
                            break;
                        }
                    }
                }
            }
        }
    }
    
    // If still no selected value, select first available option
    if (!$selectedZoneValue) {
        if (!empty($zonesWithCountries) && !empty($zonesWithCountries[0]['country_options'])) {
            $selectedZoneValue = $zonesWithCountries[0]['country_options'][0]['value'];
        } elseif (!empty($zonesData)) {
            $selectedZoneValue = $zonesData[0]['id'];
        }
    }
    
    // Calculate base subtotal in USD for free-shipping (based on subtotal BEFORE discount)
    $baseSubtotal = 0;
    foreach ($cartItems as $item) {
        $itemPrice = (float) $item->price;
        $basePrice = $currentCurrency !== 'USD' && $currentCurrencyRate > 0
            ? $itemPrice / $currentCurrencyRate
            : $itemPrice;
        $baseSubtotal += $basePrice * $item->quantity;
    }
    $freeShippingThreshold = 150;
    $freeShippingProgress = $baseSubtotal >= $freeShippingThreshold ? 100 : ($baseSubtotal / $freeShippingThreshold) * 100;
    $amountLeftForFreeShipping = max(0, $freeShippingThreshold - $baseSubtotal);

    $locationSvcCart = app(\App\Services\CustomerLocationService::class);
    $cartPolicyShipCountryCode = $locationSvcCart->detectCountryCode(request(), 'US');
    if (!empty($selectedZoneValue)) {
        $sv = (string) $selectedZoneValue;
        if (strlen($sv) === 2 && ctype_alpha($sv)) {
            $cartPolicyShipCountryCode = strtoupper($sv);
        } elseif (ctype_digit($sv)) {
            $zCart = \App\Models\ShippingZone::find((int) $sv);
            if ($zCart) {
                $zCountries = $zCart->countries ?? [];
                if (is_array($zCountries) && count($zCountries) > 0) {
                    $fc = $zCountries[0];
                    if (is_string($fc) && $fc !== '') {
                        $cartPolicyShipCountryCode = strtoupper($fc);
                    }
                }
            }
        }
    }
    $dsCart = now()->startOfDay()->addDays(11);
    $deCart = now()->startOfDay()->addDays(20);
    $cartPolicyDeliveryRangeText = $dsCart->format('M j') . '–' . ($dsCart->format('M') === $deCart->format('M') ? $deCart->format('j') : $deCart->format('M j'));
    if ($cartItems->isNotEmpty()) {
        try {
            $calcCart = app(\App\Services\ShippingCalculator::class);
            $linesUsd = $cartItems->map(function ($ci) use ($currentCurrency, $currentCurrencyRate) {
                $p = (float) $ci->price;
                $usd = $currentCurrency !== 'USD' && $currentCurrencyRate > 0 ? $p / $currentCurrencyRate : $p;

                return [
                    'product_id' => $ci->product_id,
                    'quantity' => max(1, (int) $ci->quantity),
                    'price' => $usd,
                ];
            });
            $cartPolicyDeliveryRangeText = $calcCart->getDeliveryEstimateRangeForCart($linesUsd, $cartPolicyShipCountryCode)['range_text'];
        } catch (\Throwable $e) {
            // giữ mặc định
        }
    }
@endphp
<div class="bg-background-light min-h-screen font-display text-slate-900 py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 lg:px-20">
        @if($cartItems->isEmpty())
            <div class="mb-10">
                <h2 class="text-3xl lg:text-4xl font-extrabold text-slate-900 mb-6">Your Shopping Bag</h2>
            </div>
            <!-- Empty Cart -->
            <div class="bg-white rounded-2xl border border-primary/10 shadow-sm p-12 text-center">
                <div class="max-w-md mx-auto">
                    <svg class="w-32 h-32 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 11-4 0v-6m4 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
                    </svg>
                    <h2 class="text-2xl font-bold text-slate-900 mb-2">Your cart is empty</h2>
                    <p class="text-slate-600 mb-8">Looks like you haven't added anything to your cart yet.</p>
                    <a href="{{ route('products.index') }}" class="inline-flex items-center space-x-2 bg-primary text-white px-8 py-3 rounded-xl hover:brightness-110 transition-colors font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Continue Shopping</span>
                    </a>
                </div>
            </div>
        @else
            <!-- Free Shipping Progress -->
            <div class="mb-10">
                <h2 class="text-3xl lg:text-4xl font-extrabold text-slate-900 mb-6">Your Shopping Bag</h2>
                @php
                    $freeShippingThreshold = 150;
                    $freeShippingProgress = $baseSubtotal >= $freeShippingThreshold ? 100 : ($baseSubtotal / $freeShippingThreshold) * 100;
                    $amountLeftForFreeShipping = max(0, $freeShippingThreshold - $baseSubtotal);
                @endphp
                <div class="bg-white p-4 rounded-xl border border-primary/10 shadow-sm max-w-2xl">
                    <div class="flex justify-between items-center mb-2">
                        <p class="text-sm font-semibold text-slate-700">Free Shipping Progress</p>
                        <p class="text-sm font-bold text-primary">{{ $currencySymbol }}{{ number_format($baseSubtotal >= $freeShippingThreshold ? $freeShippingThreshold : $baseSubtotal, 2) }} / {{ $currencySymbol }}{{ number_format($freeShippingThreshold, 2) }}</p>
                    </div>
                    <div class="h-2.5 w-full bg-primary/10 rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full transition-all duration-500" style="width: {{ min(100, $freeShippingProgress) }}%;"></div>
                    </div>
                    @if($amountLeftForFreeShipping > 0)
                        <p class="mt-2 text-xs font-medium text-slate-500">Add <span class="text-primary font-bold">{{ $currencySymbol }}{{ number_format($amountLeftForFreeShipping * $currentCurrencyRate, 2) }}</span> more to unlock free shipping!</p>
                    @else
                        <p class="mt-2 text-xs font-semibold text-primary">You've unlocked free shipping!</p>
                    @endif
                </div>
                <div class="mt-3 flex items-start gap-3 text-sm text-slate-700 max-w-2xl">
                    <span class="material-symbols-outlined text-slate-600 text-xl leading-none shrink-0">calendar_month</span>
                    <div class="leading-snug">
                        <span class="text-slate-600">Order today to get by</span>
                        <span class="font-bold underline underline-offset-2 decoration-[#0297FE] ml-1">{{ $cartPolicyDeliveryRangeText }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                <!-- Left: Cart Items -->
                <div class="lg:col-span-8 space-y-8">
                    <div class="divide-y divide-primary/10">
                        @foreach($cartItems as $item)
                            @php
                                $media = $item->product->getEffectiveMedia();
                                $imageUrl = '/images/placeholder.jpg';
                                $cartLineImgAlt = $item->product->name;
                                if ($media && count($media) > 0) {
                                    $cartLineImgAlt = $item->product->altForMediaItem($media[0], null, 0);
                                    if (is_string($media[0])) { $imageUrl = $media[0]; }
                                    elseif (is_array($media[0])) { $imageUrl = $media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? '/images/placeholder.jpg'; }
                                }
                                $variantLine = '';
                                if ($item->selected_variant && isset($item->selected_variant['attributes']) && is_array($item->selected_variant['attributes'])) {
                                    $variantLine = implode(' | ', array_map(fn($k, $v) => ucfirst($k) . ': ' . $v, array_keys($item->selected_variant['attributes']), $item->selected_variant['attributes']));
                                } elseif ($item->selected_variant && is_array($item->selected_variant)) {
                                    $parts = [];
                                    if (!empty($item->selected_variant['colour'])) $parts[] = 'Colour: ' . $item->selected_variant['colour'];
                                    if (!empty($item->selected_variant['size'])) $parts[] = 'Size: ' . $item->selected_variant['size'];
                                    $variantLine = implode(' | ', $parts);
                                }
                            @endphp
                            <div class="py-6 first:pt-0 flex flex-col sm:flex-row gap-6" data-cart-item-id="{{ $item->id }}">
                                <div class="w-32 h-40 bg-slate-100 rounded-xl overflow-hidden shrink-0 border border-primary/5">
                                    <img src="{{ $imageUrl }}" alt="{{ $cartLineImgAlt }}" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 flex flex-col justify-between py-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-900 leading-tight">
                                                <a href="{{ route('products.show', $item->product->slug) }}" class="hover:text-primary transition-colors">{{ $item->product->name }}</a>
                                            </h3>
                                            @if($variantLine)
                                                <p class="text-sm text-slate-500 mt-1">{{ $variantLine }}</p>
                                            @endif
                                            @if($item->customizations && count($item->customizations) > 0)
                                                <p class="text-sm text-slate-500 mt-0.5">
                                                    @foreach($item->customizations as $key => $c)
                                                        @php
                                                            $rawValue = $c['value'] ?? $c;
                                                            $displayValue = $rawValue;
                                                            // Nếu là JSON (file custom), chỉ hiển thị tên file ngắn gọn
                                                            if (is_string($rawValue) && str_starts_with(ltrim($rawValue), '{')) {
                                                                $decoded = json_decode($rawValue, true);
                                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                                    $fileName = $decoded['original_name'] ?? basename($decoded['file_url'] ?? '') ?? null;
                                                                    $displayValue = $fileName ?: $rawValue;
                                                                }
                                                            }
                                                            $displayValue = \Illuminate\Support\Str::limit((string) $displayValue, 40);
                                                        @endphp
                                                        {{ $key }}: {{ $displayValue }}{{ isset($c['price']) && $c['price'] > 0 ? ' (+' . format_price((float)$c['price']) . ')' : '' }}{{ $loop->last ? '' : ' · ' }}
                                                    @endforeach
                                                </p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button onclick="openEditCartModal({{ $item->id }})" class="p-2 text-slate-400 hover:text-primary transition-colors" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <p class="text-lg font-bold text-slate-900">{{ format_price((float) $item->getTotalPriceWithCustomizations()) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-4">
                                        <div class="flex items-center gap-4 bg-slate-50 px-3 py-1.5 rounded-lg border border-primary/5">
                                            <button onclick="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})" class="text-slate-400 hover:text-primary transition-colors disabled:opacity-50 disabled:cursor-not-allowed" {{ $item->quantity <= 1 ? 'disabled' : '' }}>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                            </button>
                                            <span class="text-sm font-bold w-4 text-center">{{ $item->quantity }}</span>
                                            <button onclick="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})" class="text-slate-400 hover:text-primary transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            </button>
                                        </div>
                                        <button onclick="removeFromCart({{ $item->id }})" class="flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-red-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Order Note -->
                    <div class="bg-white rounded-xl border border-primary/10 p-6 shadow-sm">
                        <label class="block text-sm font-bold text-slate-900 mb-3" for="order-note">Add an Order Note</label>
                        <textarea class="w-full rounded-xl border border-primary/20 bg-transparent focus:border-primary focus:ring-primary placeholder-slate-400 text-sm px-4 py-3" id="order-note" name="order_note" placeholder="Special instructions for your order..." rows="3"></textarea>
                    </div>
                </div>

                <!-- Right: Order Summary -->
                <div class="lg:col-span-4">
                    <div class="sticky top-28 space-y-6">
                        <div class="bg-white p-8 rounded-xl border border-primary/20 shadow-xl shadow-primary/5">
                            <h3 class="text-xl font-extrabold text-slate-900 mb-6">Order Summary</h3>
                            <div class="space-y-4 mb-6">
                                <div class="flex gap-2">
                                    <button type="button" id="cart-mode-volume" class="flex-1 px-3 py-2 rounded-lg border text-xs font-bold transition-colors {{ $discountMode === 'volume' ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 border-primary/20 hover:bg-primary/5' }}">
                                        Volume
                                    </button>
                                    <button type="button" id="cart-mode-promo" class="flex-1 px-3 py-2 rounded-lg border text-xs font-bold transition-colors {{ $discountMode === 'promo' ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 border-primary/20 hover:bg-primary/5' }}">
                                        Promo code
                                    </button>
                                </div>
                                <p class="text-[11px] leading-snug text-slate-500 px-0.5 -mt-1">
                                    <span class="font-semibold text-slate-600">Volume</span> is an automatic discount based on how many items are in your cart—no code needed. Choose <span class="font-semibold text-slate-600">Promo code</span> if you have a coupon instead.
                                </p>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Subtotal</span>
                                    <span class="font-bold text-slate-900" id="cart-subtotal">{{ format_price((float) $subtotalAfterBulk) }}</span>
                                </div>
                                @if((float) ($bulkDiscountPercent ?? 0) > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Discount</span>
                                    <span class="font-semibold text-emerald-600" id="cart-qty-discount-percent">-{{ number_format((float) $bulkDiscountPercent, 0) }}%</span>
                                </div>
                                @endif
                                @if((count($zonesData) > 0) || (count($zonesWithCountries) > 0))
                                    <div class="mb-2">
                                        <label for="shipping-zone-select" class="block text-xs font-bold text-slate-400 mb-1 uppercase">Shipping to</label>
                                        <select id="shipping-zone-select" onchange="updateShippingZone(this.value)" class="w-full rounded-lg border border-primary/20 bg-slate-50 text-sm focus:ring-primary focus:border-primary px-3 py-2">
                                            @if(count($zonesWithCountries) > 0)
                                                @foreach($zonesWithCountries as $zone)
                                                    <optgroup label="{{ $zone['name'] }}">
                                                        @foreach($zone['country_options'] as $country)
                                                            <option value="{{ $country['value'] }}" @if($selectedZoneValue == $country['value']) selected @endif>{{ $country['label'] }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            @endif
                                            @if(count($zonesData) > 0)
                                                @foreach($zonesData as $zone)
                                                    <option value="{{ $zone['id'] }}" @if($selectedZoneValue == $zone['id']) selected @endif>{{ $zone['display_name'] ?? $zone['name'] }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                @endif
                                @if($discount > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Discount</span>
                                    <span class="font-semibold text-emerald-600" id="cart-discount">-{{ format_price((float) $discount) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs text-slate-600 mt-1">
                                    <span>Code: <strong class="text-primary">{{ $appliedPromoCode }}</strong></span>
                                    <button type="button" id="promo-remove" class="text-primary hover:underline font-semibold">Remove</button>
                                </div>
                                @endif
                                <div class="flex justify-between text-sm" id="shipping-cost-row">
                                    <span class="text-slate-500" id="shipping-label">Shipping</span>
                                    <span class="font-semibold text-slate-500" id="shipping-cost">{{ format_price(0) }}</span>
                                </div>
                            </div>
                            <div class="pt-6 border-t border-primary/10 mb-6">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-extrabold text-slate-900 uppercase tracking-tighter">Total</span>
                                    <span class="text-2xl font-extrabold text-primary" id="cart-total">{{ format_price((float) $total) }}</span>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 uppercase" for="promo">Promo Code</label>
                                    <div class="flex gap-2">
                                        <input class="flex-1 rounded-lg border border-primary/20 bg-slate-50 text-sm focus:ring-primary focus:border-primary px-3 py-2 {{ $discountMode === 'volume' ? 'opacity-60 cursor-not-allowed' : '' }}" id="promo" placeholder="Enter code" type="text" value="{{ $appliedPromoCode ? '' : '' }}" autocomplete="off" {{ $discountMode === 'volume' ? 'disabled' : '' }}>
                                        <button type="button" id="promo-apply" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-bold hover:bg-primary transition-colors {{ $discountMode === 'volume' ? 'opacity-60 cursor-not-allowed' : '' }}" {{ $discountMode === 'volume' ? 'disabled' : '' }}>Apply</button>
                                    </div>
                                    <p id="promo-message" class="mt-1.5 text-xs hidden"></p>
                                </div>
                                <a href="{{ route('checkout.index') }}" onclick="trackInitiateCheckout(event)" class="block w-full bg-primary text-white py-4 rounded-xl font-extrabold uppercase tracking-widest text-sm shadow-lg shadow-primary/30 hover:brightness-110 active:scale-[0.98] transition-all text-center">
                                    Checkout Now
                                </a>
                            </div>
                            <div class="mt-8 flex flex-wrap justify-center gap-4 grayscale opacity-60">
                                <span class="text-xs font-bold text-slate-500">VISA</span>
                                <span class="text-xs font-bold text-slate-500">MC</span>
                                <span class="text-xs font-bold text-slate-500">AMEX</span>
                                <span class="text-xs font-bold text-slate-500">PayPal</span>
                            </div>
                        </div>
                        <a href="{{ route('products.index') }}" class="block w-full text-center text-primary hover:brightness-110 font-semibold py-3 border-2 border-primary/30 rounded-xl hover:bg-primary/10 transition-all">
                            Continue Shopping
                        </a>
                        <div class="bg-primary/5 rounded-xl p-6 border border-primary/10">
                            <div class="flex items-center gap-3 mb-4">
                                <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                <p class="text-sm font-bold text-slate-900">Guarantee</p>
                            </div>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-2 text-xs text-slate-600">
                                    <svg class="w-4 h-4 text-primary shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    <span>30-day money back guarantee if you're not satisfied.</span>
                                </li>
                                <li class="flex items-start gap-2 text-xs text-slate-600">
                                    <svg class="w-4 h-4 text-primary shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    <span>Don't love it? We'll fix it. For free.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Edit Cart Modal -->
        <div id="editCartModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center z-10">
                    <h2 class="text-2xl font-bold text-gray-900">Edit Cart Item</h2>
                    <button onclick="closeEditCartModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="editCartModalContent" class="p-6">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>


        <!-- Phần dưới: Recently Viewed (cùng component với home / products index; load AJAX nếu chưa có data) -->
        <section class="mt-12 pt-10">
            <x-recently-viewed :products="$recentlyViewedProducts ?? null" :limit="5" wrapperClass="" />
        </section>
    </div>
</div>

<!-- JavaScript for Cart Operations -->
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const cartItemsData = @json($cartItems);
const CURRENCY_SYMBOL = @json($currencySymbol);
const CURRENT_CURRENCY = @json($currentCurrency);
const CURRENT_CURRENCY_RATE = {{ $currentCurrencyRate }};
const CURRENT_DOMAIN = @json($currentDomain);
const SHIPPING_RATES = @json($shippingRatesData);
const SHIPPING_RATES_BY_ZONE = @json($shippingRatesByZone);
const SHIPPING_ZONES = @json($zonesData);
const SHIPPING_ZONES_WITH_COUNTRIES = @json($zonesWithCountries);
const DEFAULT_SHIPPING_RATE = @json($defaultShippingRateData);
const DEFAULT_SHIPPING_ZONE_ID = @json($defaultShippingRate ? $defaultShippingRate->shipping_zone_id : null);
const SELECTED_ZONE_VALUE = @json($selectedZoneValue);
const BASE_SUBTOTAL = {{ $baseSubtotal }};
const APPLY_PROMO_URL = '{{ route("api.cart.apply-promo") }}';
const REMOVE_PROMO_URL = '{{ route("api.cart.remove-promo") }}';
const DISCOUNT_MODE_URL = '{{ route("api.cart.discount-mode") }}';

function buildGaCartItems() {
    return (cartItemsData || []).map((item, index) => {
        const quantity = parseInt(item.quantity, 10) || 1;
        const unitPrice = parseFloat(item.price) || 0;
        const product = item.product || {};
        const categories = Array.isArray(product.categories) ? product.categories : [];
        const firstCategory = categories.length ? categories[0] : null;
        const categoryName = firstCategory && typeof firstCategory === 'object' ? (firstCategory.name || null) : null;

        const gaItem = {
            item_id: String(product.sku || product.id || item.product_id || item.id || (index + 1)),
            item_name: product.name || `Cart Item ${index + 1}`,
            price: Number(unitPrice.toFixed(2)),
            quantity
        };

        if (categoryName) {
            gaItem.item_category = categoryName;
        }

        const variantAttrs = item.selected_variant && item.selected_variant.attributes
            ? Object.values(item.selected_variant.attributes).filter(Boolean)
            : [];
        if (variantAttrs.length > 0) {
            gaItem.item_variant = variantAttrs.join(' / ');
        }

        return gaItem;
    });
}

function trackViewCart() {
    if (typeof dataLayer === 'undefined') return;
    const items = buildGaCartItems();
    if (!items.length) return;

    const value = Number(
        items.reduce((sum, item) => sum + ((parseFloat(item.price) || 0) * (parseInt(item.quantity, 10) || 1)), 0).toFixed(2)
    );

    dataLayer.push({ ecommerce: null });
    dataLayer.push({
        event: 'view_cart',
        ecommerce: {
            currency: CURRENT_CURRENCY || 'USD',
            value,
            items
        }
    });
}

function trackRemoveFromCart(cartItemId) {
    if (typeof dataLayer === 'undefined') return;
    const item = (cartItemsData || []).find(it => String(it.id) === String(cartItemId));
    if (!item) return;

    const product = item.product || {};
    const categories = Array.isArray(product.categories) ? product.categories : [];
    const firstCategory = categories.length ? categories[0] : null;
    const categoryName = firstCategory && typeof firstCategory === 'object' ? (firstCategory.name || null) : null;
    const unitPrice = parseFloat(item.price) || 0;
    const quantity = parseInt(item.quantity, 10) || 1;

    const gaItem = {
        item_id: String(product.sku || product.id || item.product_id || item.id),
        item_name: product.name || 'Cart Item',
        price: Number(unitPrice.toFixed(2)),
        quantity
    };

    if (categoryName) {
        gaItem.item_category = categoryName;
    }

    const variantAttrs = item.selected_variant && item.selected_variant.attributes
        ? Object.values(item.selected_variant.attributes).filter(Boolean)
        : [];
    if (variantAttrs.length > 0) {
        gaItem.item_variant = variantAttrs.join(' / ');
    }

    dataLayer.push({ ecommerce: null });
    dataLayer.push({
        event: 'remove_from_cart',
        ecommerce: {
            currency: CURRENT_CURRENCY || 'USD',
            value: Number((unitPrice * quantity).toFixed(2)),
            items: [gaItem]
        }
    });
}

function showPromoMessage(text, isError) {
    const el = document.getElementById('promo-message');
    if (!el) return;
    el.textContent = text;
    el.className = 'mt-1.5 text-xs ' + (isError ? 'text-red-600' : 'text-emerald-600');
    el.classList.remove('hidden');
}

document.getElementById('promo-apply') && document.getElementById('promo-apply').addEventListener('click', function() {
    const input = document.getElementById('promo');
    const code = (input && input.value) ? input.value.trim() : '';
    if (!code) {
        showPromoMessage('Please enter a promo code.', true);
        return;
    }
    this.disabled = true;
    const btn = this;
    fetch(APPLY_PROMO_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: JSON.stringify({ code: code })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            location.reload();
        } else {
            showPromoMessage(data.message || 'Invalid or expired promo code.', true);
        }
    })
    .catch(() => { btn.disabled = false; showPromoMessage('Something went wrong. Try again.', true); });
});

document.getElementById('promo-remove') && document.getElementById('promo-remove').addEventListener('click', function() {
    this.disabled = true;
    fetch(REMOVE_PROMO_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => { if (data.success) location.reload(); })
    .catch(() => location.reload());
});

document.getElementById('cart-mode-volume') && document.getElementById('cart-mode-volume').addEventListener('click', function() {
    fetch(DISCOUNT_MODE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: JSON.stringify({ mode: 'volume' })
    }).then(() => location.reload()).catch(() => location.reload());
});

document.getElementById('cart-mode-promo') && document.getElementById('cart-mode-promo').addEventListener('click', function() {
    fetch(DISCOUNT_MODE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: JSON.stringify({ mode: 'promo' })
    }).then(() => location.reload()).catch(() => location.reload());
});

function updateQuantity(cartItemId, newQuantity) {
    if (newQuantity < 1) return;
    
    fetch(`/api/cart/update/${cartItemId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ quantity: newQuantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update quantity');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

function removeFromCart(cartItemId) {
    if (!confirm('Are you sure you want to remove this item?')) return;

    trackRemoveFromCart(cartItemId);
    
    fetch(`/api/cart/remove/${cartItemId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove from localStorage
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            cart = cart.filter(item => item.id !== cartItemId);
            localStorage.setItem('cart', JSON.stringify(cart));
            
            location.reload();
        } else {
            alert('Failed to remove item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

// Edit Cart Modal Functions
function openEditCartModal(cartItemId) {
    // Get cart item data from the page
    const cartItem = cartItemsData.find(item => item.id === cartItemId);
    
    if (!cartItem) {
        alert('Cart item not found');
        return;
    }
    
    // Show modal
    const modal = document.getElementById('editCartModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Build modal content
    const content = document.getElementById('editCartModalContent');
    content.innerHTML = buildEditCartModalContent(cartItem);
    window.__editingCartContext = {
        id: cartItemId,
        item: cartItem,
        originalCustomizations: cartItem.customizations || {},
        variants: (cartItem.product && cartItem.product.variants) ? cartItem.product.variants : []
    };
}

function closeEditCartModal() {
    const modal = document.getElementById('editCartModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function buildEditCartModalContent(cartItem) {
    const product = cartItem.product;
    const variants = product.variants || [];
    const selectedVariant = cartItem.selected_variant || {};
    const customizations = cartItem.customizations || {};
    
    return `
        <div class="space-y-6">
            <!-- Product Info -->
            <div class="flex gap-4">
                <img src="${getProductImage(product)}" alt="${String(getProductImageAlt(product)).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;')}" class="w-24 h-24 object-cover rounded-lg">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">${product.name}</h3>
                    <p class="text-gray-600">${CURRENCY_SYMBOL}${parseFloat(cartItem.price).toFixed(2)} each</p>
                </div>
            </div>
            
            <!-- Variants -->
            ${variants.length > 0 ? `
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Variants</label>
                <div class="space-y-2">
                    ${buildVariantOptions(variants, selectedVariant)}
                </div>
            </div>
            ` : ''}

            ${Object.keys(customizations).length > 0 ? `
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Customizations</label>
                <div class="space-y-3">
                    ${Object.entries(customizations).map(([key, value]) => `
                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-center">
                            <div class="sm:col-span-2">
                                <span class="text-sm text-gray-600">${key}</span>
                            </div>
                            <div class="sm:col-span-3">
                                <input type="text" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 customization-input" 
                                       data-label="${key}" placeholder="Value" value="${(value && value.value) ? String(value.value).replace(/"/g, '&quot;') : ''}" 
                                       oninput="updateCartModalTotal()" />
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            ` : ''}
            
            <!-- Quantity -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                <div class="flex items-center gap-3">
                    <button onclick="updateModalQuantity(${cartItem.id}, ${cartItem.quantity - 1})" 
                            class="w-10 h-10 rounded-lg border border-gray-300 flex items-center justify-center hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            ${cartItem.quantity <= 1 ? 'disabled' : ''}>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </button>
                    <span class="text-xl font-semibold" id="modalQuantity${cartItem.id}">${cartItem.quantity}</span>
                    <button onclick="updateModalQuantity(${cartItem.id}, ${cartItem.quantity + 1})" 
                            class="w-10 h-10 rounded-lg border border-gray-300 flex items-center justify-center hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Total Price -->
            <div class="border-t pt-4">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold text-gray-900">Total</span>
                    <span class="text-2xl font-bold text-[#005366]" id="modalTotal${cartItem.id}">${CURRENCY_SYMBOL}${(parseFloat(cartItem.price) * cartItem.quantity).toFixed(2)}</span>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex gap-3 pt-4">
                <button onclick="saveCartChanges(${cartItem.id})" 
                        class="flex-1 bg-[#F0427C] hover:bg-[#d6386a] text-white font-bold py-3 rounded-xl transition-colors">
                    Save Changes
                </button>
                <button onclick="closeEditCartModal()" 
                        class="px-6 py-3 border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-medium rounded-xl transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    `;
}

function buildVariantOptions(variants, selectedVariant) {
    // Group variants by attribute type
    const attributeGroups = {};
    variants.forEach(variant => {
        if (variant.attributes) {
            Object.keys(variant.attributes).forEach(key => {
                if (!attributeGroups[key]) {
                    attributeGroups[key] = new Set();
                }
                attributeGroups[key].add(variant.attributes[key]);
            });
        }
    });
    
    return Object.keys(attributeGroups).map(key => {
        const values = Array.from(attributeGroups[key]);
        const selectedValue = selectedVariant && selectedVariant.attributes ? selectedVariant.attributes[key] : '';
        
        return `
            <div>
                <label class="block text-sm text-gray-600 mb-1">${key.charAt(0).toUpperCase() + key.slice(1)}</label>
                <select class="w-full border-2 border-gray-200 rounded-lg px-4 py-2 focus:border-[#005366] focus:outline-none" 
                        id="variant-${key}" 
                        onchange="updateCartModalTotal(${JSON.stringify(variants).replace(/"/g, '&quot;')})">
                    ${values.map(value => `
                        <option value="${value}" ${value === selectedValue ? 'selected' : ''}>${value}</option>
                    `).join('')}
                </select>
            </div>
        `;
    }).join('');
}

function getProductImage(product) {
    if (product.media && product.media.length > 0) {
        const media = product.media[0];
        if (typeof media === 'string') {
            return media;
        } else if (media.url) {
            return media.url;
        } else if (media.path) {
            return media.path;
        }
    }
    return '/images/placeholder.jpg';
}

function getProductImageAlt(product) {
    const name = (product && product.name) ? String(product.name) : 'Product';
    if (!product) return name;
    if (product.primary_image_alt && String(product.primary_image_alt).trim()) {
        return String(product.primary_image_alt).trim().slice(0, 500);
    }
    if (!product.media || !product.media.length) return name;
    const m = product.media[0];
    if (m && typeof m === 'object' && m.keywords && String(m.keywords).trim()) {
        return String(m.keywords).trim().slice(0, 500);
    }
    return name;
}

function updateModalQuantity(cartItemId, newQuantity) {
    if (newQuantity < 1) return;
    
    // Update display
    const quantityDisplay = document.getElementById('modalQuantity' + cartItemId);
    if (quantityDisplay) {
        quantityDisplay.textContent = newQuantity;
    }
    
    // Update total
    updateCartModalTotal();
}

function updateCartModalTotal(variants) {
    const ctx = window.__editingCartContext;
    if (!ctx) return;
    if (Array.isArray(variants) && variants.length) {
        ctx.variants = variants;
    }
    const cartItemId = ctx.id;
    const cartItem = ctx.item;
    const quantity = parseInt(document.getElementById('modalQuantity' + cartItemId)?.textContent || '1');

    const selectedVariant = (function getSelectedVariant() {
        const vars = ctx.variants || [];
        if (!vars.length) return null;
        const attributes = {};
        vars.forEach(v => {
            if (v.attributes) {
                Object.keys(v.attributes).forEach(key => {
                    const sel = document.getElementById('variant-' + key);
                    if (sel) attributes[key] = sel.value;
                });
            }
        });
        const match = vars.find(v => v.attributes && Object.keys(attributes).every(k => String(v.attributes[k]) === String(attributes[k])));
        if (match) return { id: match.id, attributes: match.attributes, price: match.price };
        return Object.keys(attributes).length ? { attributes } : null;
    })();

    let unitPrice = (function getBaseUnitPrice() {
        if (selectedVariant && selectedVariant.price != null && selectedVariant.price !== '') {
            const v = parseFloat(selectedVariant.price);
            if (!isNaN(v)) return v;
        }
        const p = cartItem.product || {};
        const candidates = [p.price, p.base_price, (p.template || {}).base_price, cartItem.price];
        for (const c of candidates) {
            const v = parseFloat(c);
            if (!isNaN(v)) return v;
        }
        return 0;
    })();

    const customizations = (function getSelectedCustomizationsPreservePrice() {
        const map = {};
        document.querySelectorAll('.customization-input').forEach(input => {
            const label = input.dataset.label;
            const value = input.value || '';
            const original = ctx.originalCustomizations && ctx.originalCustomizations[label];
            const price = original && original.price ? parseFloat(original.price) || 0 : 0;
            if (value.trim() !== '') {
                map[label] = { value: value.trim(), price };
            }
        });
        return map;
    })();

    let customizationUnitTotal = 0;
    Object.values(customizations).forEach(c => { customizationUnitTotal += parseFloat(c.price) || 0; });
    const unitTotal = unitPrice + customizationUnitTotal;
    const total = unitTotal * quantity;
    const totalDisplay = document.getElementById('modalTotal' + cartItemId);
    if (totalDisplay) totalDisplay.textContent = `${CURRENCY_SYMBOL}${total.toFixed(2)}`;
}

function saveCartChanges(cartItemId) {
    const ctx = window.__editingCartContext;
    if (!ctx || ctx.id !== cartItemId) return;
    const cartItem = ctx.item;
    const newQuantity = parseInt(document.getElementById('modalQuantity' + cartItemId)?.textContent || '1');

    // Recompute payload like in updateCartModalTotal
    const vars = ctx.variants || [];
    const attributes = {};
    vars.forEach(v => {
        if (v.attributes) {
            Object.keys(v.attributes).forEach(key => {
                const sel = document.getElementById('variant-' + key);
                if (sel) attributes[key] = sel.value;
            });
        }
    });
    const match = vars.find(v => v.attributes && Object.keys(attributes).every(k => String(v.attributes[k]) === String(attributes[k])));
    const selectedVariant = match ? { id: match.id, attributes: match.attributes, price: match.price } : (Object.keys(attributes).length ? { attributes } : null);

    const customizations = {};
    document.querySelectorAll('.customization-input').forEach(input => {
        const label = input.dataset.label;
        const value = input.value || '';
        const original = ctx.originalCustomizations && ctx.originalCustomizations[label];
        const price = original && original.price ? parseFloat(original.price) || 0 : 0;
        if (value.trim() !== '') {
            customizations[label] = { value: value.trim(), price };
        }
    });

    let unitPrice = (function () {
        if (selectedVariant && selectedVariant.price != null && selectedVariant.price !== '') {
            const v = parseFloat(selectedVariant.price);
            if (!isNaN(v)) return v;
        }
        const p = cartItem.product || {};
        const candidates = [p.price, p.base_price, (p.template || {}).base_price, cartItem.price];
        for (const c of candidates) {
            const v = parseFloat(c);
            if (!isNaN(v)) return v;
        }
        return 0;
    })();
    Object.values(customizations).forEach(c => { unitPrice += parseFloat(c.price) || 0; });

    fetch(`/api/cart/update/${cartItemId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            quantity: newQuantity,
            selected_variant: selectedVariant,
            customizations: customizations,
            price: unitPrice
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update cart item');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('An error occurred');
    });
}

// Track InitiateCheckout when clicking Proceed to Checkout
function trackInitiateCheckout(event) {
    if (typeof fbq !== 'undefined') {
        // Get cart data
        const cart = JSON.parse(localStorage.getItem('cart') || '[]');
        
        if (cart.length > 0) {
            // Calculate cart total and collect product IDs
            let cartTotal = 0;
            const productIds = [];
            
            cart.forEach(item => {
                const price = parseFloat(item.price) || 0;
                const quantity = parseInt(item.quantity) || 1;
                cartTotal += price * quantity;
                productIds.push(item.id);
            });
            
            // Track InitiateCheckout event
            fbq('track', 'InitiateCheckout', {
                content_ids: productIds,
                content_type: 'product',
                value: cartTotal.toFixed(2),
                currency: 'USD',
                num_items: cart.length
            });
            
            console.log('✅ Facebook Pixel: InitiateCheckout tracked from cart', {
                items: cart.length,
                total: cartTotal.toFixed(2),
                ids: productIds
            });
        }
    }

    // Event tracking được xử lý bởi GTM thông qua dataLayer
    if (typeof dataLayer !== 'undefined') {
        const cart = JSON.parse(localStorage.getItem('cart') || '[]');

        if (cart.length > 0) {
            let cartTotal = 0;

            const gaItems = cart.map((item, index) => {
                const quantity = parseInt(item.quantity, 10) || 1;
                const unitPrice = parseFloat(item.price) || 0;
                cartTotal += unitPrice * quantity;

                const gaItem = {
                    item_id: (item.selectedVariant && item.selectedVariant.id) ? String(item.selectedVariant.id) : String(item.id),
                    item_name: item.name || `Cart Item ${index + 1}`,
                    price: Number(unitPrice.toFixed(2)),
                    quantity
                };

                if (item.selectedVariant && item.selectedVariant.attributes) {
                    const variantAttributes = Object.values(item.selectedVariant.attributes || {}).filter(Boolean);
                    if (variantAttributes.length > 0) {
                        gaItem.item_variant = variantAttributes.join(' / ');
                    }
                }

                return gaItem;
            });

            dataLayer.push({
                'event': 'begin_checkout',
                'currency': 'USD',
                'value': Number(cartTotal.toFixed(2)),
                'items': gaItems
            });

            console.log('✅ GTM: begin_checkout tracked from cart', {
                items: gaItems.length,
                value: cartTotal.toFixed(2)
            });
        }
    }
    // Let the link navigate normally
    return true;
}

/**
 * Calculate shipping cost for cart items based on categories and zone
 * @param {Array} cartItems - Array of cart items
 * @param {number} baseSubtotal - Base subtotal in USD
 * @param {number|null} zoneId - Selected shipping zone ID (optional)
 * @returns {Object} - Object containing shipping cost details
 */
function calculateShippingCost(cartItems, baseSubtotal, zoneId = null) {
    if (!cartItems || cartItems.length === 0) {
        return {
            cost: 0,
            costConverted: 0,
            rate: null,
            name: null,
            zoneId: null,
            zoneName: null
        };
    }

    // ---- Match backend ShippingCalculator logic ----
    // 1) Filter rates by zone (keep existing filtering approach)
    let availableRates = SHIPPING_RATES;
    let currentZoneName = null;

    if (zoneId !== null) {
        let actualZoneId = zoneId;
        if (typeof zoneId === 'string' && zoneId.includes(':')) {
            actualZoneId = zoneId.split(':')[0];
        }

        if (typeof actualZoneId === 'string' && actualZoneId.startsWith('general_')) {
            currentZoneName = actualZoneId.replace('general_', '').split('_').map(word =>
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join(' ');
        } else {
            const parsedZoneId = typeof actualZoneId === 'string' && !isNaN(actualZoneId)
                ? parseInt(actualZoneId)
                : actualZoneId;
            currentZoneName = SHIPPING_ZONES.find(z => z.id === parsedZoneId)?.name || null;
        }

        if (typeof actualZoneId === 'string' && actualZoneId.startsWith('general_')) {
            const zoneName = actualZoneId.replace('general_', '').split('_').map(word =>
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join(' ');
            const normalizeZoneName = (name) => name ? name.toLowerCase().trim().replace(/\s+/g, ' ') : '';
            const normalizedZoneName = normalizeZoneName(zoneName);

            availableRates = SHIPPING_RATES.filter(r => {
                if (r.zone_id !== null) return false;
                if (!r.zone_name) return false;
                const normalizedRateZoneName = normalizeZoneName(r.zone_name);
                return normalizedRateZoneName === normalizedZoneName ||
                    normalizedRateZoneName.includes(normalizedZoneName) ||
                    normalizedZoneName.includes(normalizedRateZoneName);
            });

            if (availableRates.length === 0) {
                const allGeneralRates = SHIPPING_RATES.filter(r => r.zone_id === null);
                if (allGeneralRates.length > 0) {
                    availableRates = allGeneralRates;
                }
            }
        } else {
            const parsedZoneId = typeof actualZoneId === 'string' && !isNaN(actualZoneId)
                ? parseInt(actualZoneId)
                : actualZoneId;
            availableRates = SHIPPING_RATES.filter(r => r.zone_id === parsedZoneId);
            if (availableRates.length === 0) {
                const generalRates = SHIPPING_RATES.filter(r => r.zone_id === null);
                if (generalRates.length > 0) {
                    availableRates = generalRates;
                }
            }
        }
    }

    // 2) Prepare items, totalItems, and apply-first-item-to-most-expensive
    const totalItems = cartItems.reduce((sum, it) => sum + (parseInt(it.quantity, 10) || 0), 0);
    const totalValue = parseFloat(baseSubtotal) || 0; // USD

    const itemsWithMeta = cartItems.map(it => {
        const qty = parseInt(it.quantity, 10) || 0;
        const unitPrice = parseFloat(it.price) || 0;
        const unitUsd = (CURRENT_CURRENCY !== 'USD' && CURRENT_CURRENCY_RATE > 0) ? (unitPrice / CURRENT_CURRENCY_RATE) : unitPrice;
        const categories = (it.product && it.product.categories) ? it.product.categories : [];
        let categoryId = null;
        if (Array.isArray(categories) && categories.length > 0) {
            const firstCategory = categories[0];
            categoryId = firstCategory.id || (typeof firstCategory === 'object' ? firstCategory.category_id : null);
        }
        return { item: it, qty, unitUsd, categoryId };
    }).sort((a, b) => (b.unitUsd || 0) - (a.unitUsd || 0));

    const isApplicable = (r) => {
        const minItemsOk = (!r.min_items || totalItems >= r.min_items);
        const maxItemsOk = (!r.max_items || totalItems <= r.max_items);
        const minValueOk = (!r.min_order_value || totalValue >= r.min_order_value);
        const maxValueOk = (!r.max_order_value || totalValue <= r.max_order_value);
        return minItemsOk && maxItemsOk && minValueOk && maxValueOk;
    };

    const pickRate = (categoryId) => {
        // 1) Default rate for category
        if (categoryId != null) {
            const r1 = availableRates.find(r => r.category_id === categoryId && r.is_default && isApplicable(r));
            if (r1) return r1;
        }
        // 2) Default general rate
        const r2 = availableRates.find(r => r.category_id === null && r.is_default && isApplicable(r));
        if (r2) return r2;
        // 3) Category-specific (any)
        if (categoryId != null) {
            const r3 = availableRates.find(r => r.category_id === categoryId && isApplicable(r));
            if (r3) return r3;
        }
        // 4) General rate
        const r4 = availableRates.find(r => r.category_id === null && isApplicable(r));
        if (r4) return r4;
        // Fallbacks (ignore applicability)
        if (categoryId != null) {
            const r5 = availableRates.find(r => r.category_id === categoryId);
            if (r5) return r5;
        }
        return availableRates.find(r => r.category_id === null) || availableRates[0] || null;
    };

    // 3) Compute shipping: only ONE first item uses first_item_cost (plus additional for remaining qty of same line),
    // all remaining lines use additional_item_cost * qty. (matches backend)
    let totalShippingCost = 0;
    let firstItemProcessed = false;
    let shippingRateUsed = null;
    let shippingName = null;
    let zoneName = null;

    for (const meta of itemsWithMeta) {
        if (!meta.qty || meta.qty < 1) continue;
        const rate = pickRate(meta.categoryId);
        if (!rate) continue;

        let lineShip = 0;
        if (!firstItemProcessed) {
            lineShip = (parseFloat(rate.first_item_cost) || 0);
            if (meta.qty > 1) {
                lineShip += (meta.qty - 1) * (parseFloat(rate.additional_item_cost) || 0);
            }
            firstItemProcessed = true;
        } else {
            lineShip = meta.qty * (parseFloat(rate.additional_item_cost) || 0);
        }

        totalShippingCost += lineShip;

        // store first used rate for display
        if (!shippingRateUsed) {
            shippingRateUsed = rate;
            shippingName = rate.name;
            zoneName = rate.zone_name || currentZoneName;
        }
    }

    // absolute fallback if no rate chosen
    if (!shippingRateUsed && DEFAULT_SHIPPING_RATE) {
        const r = DEFAULT_SHIPPING_RATE;
        const qty = totalItems || 0;
        totalShippingCost = qty > 0
            ? (parseFloat(r.first_item_cost) || 0) + Math.max(0, qty - 1) * (parseFloat(r.additional_item_cost) || 0)
            : 0;
        shippingRateUsed = r;
        shippingName = r.name;
        zoneName = r.zone_name || currentZoneName;
    }

    const costConverted = CURRENT_CURRENCY !== 'USD' && CURRENT_CURRENCY_RATE > 0
        ? totalShippingCost * CURRENT_CURRENCY_RATE
        : totalShippingCost;

    return {
        cost: totalShippingCost,
        costConverted,
        rate: shippingRateUsed,
        name: shippingName || 'Standard Shipping',
        zoneId,
        zoneName,
        available: true
    };
}

/**
 * Update shipping zone and recalculate shipping cost
 * @param {string|number} zoneId - Selected shipping zone ID
 */
function updateShippingZone(zoneId) {
    if (!zoneId) return;
    
    // Extract zone_id from value if format is "zone_id:country_code"
    let actualZoneId = zoneId;
    if (typeof zoneId === 'string' && zoneId.includes(':')) {
        actualZoneId = zoneId.split(':')[0];
    }
    
    // Check if it's a general domain zone (starts with 'general_') or a numeric ID
    // For general domain zones, keep as string; for regular zones, parse as integer
    if (!actualZoneId.toString().startsWith('general_')) {
        const parsed = parseInt(actualZoneId);
        if (!isNaN(parsed)) {
            actualZoneId = parsed;
        }
    }
    
    // Save selected zone to localStorage (save the full value including country code)
    localStorage.setItem('selectedShippingZoneId', zoneId);
    
    // Calculate base subtotal from cart items
    const baseSubtotal = calculateBaseSubtotal(cartItemsData);
    const qualifiesFreeShip = (parseFloat(baseSubtotal) || 0) >= 150;
    
    // Calculate shipping cost with new zone (unless freeship)
    const shippingInfo = qualifiesFreeShip ? { available: true, zoneName: null, name: 'Free Shipping' } : calculateShippingCost(cartItemsData, baseSubtotal, zoneId);
    const shippingCost = qualifiesFreeShip ? 0 : shippingInfo.costConverted;
    
    // Get current subtotal (already AFTER combo discount in UI)
    const subtotalText = document.getElementById('cart-subtotal')?.textContent || '';
    const subtotal = parseFloat(subtotalText.replace(/[^0-9.-]/g, '')) || 0;

    // Promo discount (if any) should be included in total like popup
    const promoDiscountText = document.getElementById('cart-discount')?.textContent || '';
    const promoDiscount = parseFloat(promoDiscountText.replace(/[^0-9.-]/g, '')) || 0; // already negative in UI
    
    // Calculate total
    const total = subtotal + shippingCost + promoDiscount;
    
    // Update shipping cost display
    const shippingCostEl = document.getElementById('shipping-cost');
    const shippingLabelEl = document.getElementById('shipping-label');
    const totalEl = document.getElementById('cart-total');
    
    // Check if shipping is available
    if (shippingInfo.available === false && !qualifiesFreeShip) {
        if (shippingCostEl) {
            shippingCostEl.textContent = 'N/A';
            shippingCostEl.classList.add('text-red-600');
        }
        
        if (shippingLabelEl) {
            shippingLabelEl.textContent = 'Shipping not available for this area';
            shippingLabelEl.classList.add('text-red-600');
        }
    } else {
        if (shippingCostEl) {
            shippingCostEl.textContent = formatPrice(shippingCost);
            shippingCostEl.classList.remove('text-red-600');
        }
        
        if (shippingLabelEl) {
            if (qualifiesFreeShip) {
                shippingLabelEl.textContent = 'Free Shipping';
            } else {
                shippingLabelEl.textContent = `Shipping${shippingInfo.zoneName ? ` (${shippingInfo.zoneName})` : shippingInfo.name ? ` (${shippingInfo.name})` : ''}`;
            }
            shippingLabelEl.classList.remove('text-red-600');
        }
    }
    
    if (totalEl) {
        totalEl.textContent = formatPrice(total);
    }
}

/**
 * Format price with currency symbol
 */
function formatPrice(amount) {
    return `${CURRENCY_SYMBOL}${parseFloat(amount).toFixed(2)}`;
}

/**
 * Calculate base subtotal from cart items
 */
function calculateBaseSubtotal(cartItems) {
    let baseSubtotal = 0;
    cartItems.forEach(item => {
        const itemPrice = parseFloat(item.price) || 0;
        let basePrice = CURRENT_CURRENCY !== 'USD' && CURRENT_CURRENCY_RATE > 0
            ? itemPrice / CURRENT_CURRENCY_RATE
            : itemPrice;
        baseSubtotal += basePrice * item.quantity;
    });
    return baseSubtotal;
}

// Initialize shipping cost on page load
function initializeShippingCost() {
    // Get selected zone from localStorage or use default from server
    let selectedZoneId = localStorage.getItem('selectedShippingZoneId');
    
    // Verify selected zone exists in dropdown options
    if (selectedZoneId) {
        const zoneSelect = document.getElementById('shipping-zone-select');
        if (zoneSelect) {
            // Check if the value exists in options
            const optionExists = Array.from(zoneSelect.options).some(option => option.value === selectedZoneId);
            if (!optionExists) {
                selectedZoneId = null; // Reset if option doesn't exist
            }
        }
    }
    
    // Use default selected value from server if no valid localStorage value
    if (!selectedZoneId && SELECTED_ZONE_VALUE) {
        selectedZoneId = SELECTED_ZONE_VALUE;
    }
    
    // Update dropdown if exists
    const zoneSelect = document.getElementById('shipping-zone-select');
    if (zoneSelect && selectedZoneId) {
        zoneSelect.value = selectedZoneId;
    }
    
    // Calculate base subtotal from cart items
    const baseSubtotal = calculateBaseSubtotal(cartItemsData);
    const qualifiesFreeShip = (parseFloat(baseSubtotal) || 0) >= 150;
    
    // Calculate and display shipping cost
    const shippingInfo = qualifiesFreeShip ? { available: true, zoneName: null, name: 'Free Shipping' } : calculateShippingCost(cartItemsData, baseSubtotal, selectedZoneId);
    const shippingCost = qualifiesFreeShip ? 0 : shippingInfo.costConverted;
    
    // Get current subtotal (already AFTER combo discount in UI)
    const subtotalText = document.getElementById('cart-subtotal')?.textContent || '';
    const subtotal = parseFloat(subtotalText.replace(/[^0-9.-]/g, '')) || 0;

    // Promo discount (if any) should be included in total like popup
    const promoDiscountText = document.getElementById('cart-discount')?.textContent || '';
    const promoDiscount = parseFloat(promoDiscountText.replace(/[^0-9.-]/g, '')) || 0; // already negative in UI
    
    // Calculate total
    const total = subtotal + shippingCost + promoDiscount;
    
    // Update displays
    const shippingCostEl = document.getElementById('shipping-cost');
    const shippingLabelEl = document.getElementById('shipping-label');
    const totalEl = document.getElementById('cart-total');
    
    if (shippingCostEl) {
        shippingCostEl.textContent = formatPrice(shippingCost);
    }
    
    if (shippingLabelEl) {
        if (qualifiesFreeShip) {
            shippingLabelEl.textContent = 'Free Shipping';
        } else {
            shippingLabelEl.textContent = `Shipping${shippingInfo.zoneName ? ` (${shippingInfo.zoneName})` : shippingInfo.name ? ` (${shippingInfo.name})` : ''}`;
        }
    }
    
    if (totalEl) {
        totalEl.textContent = formatPrice(total);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    trackViewCart();
    initializeShippingCost();
});
</script>

<style>
/* Hide default select arrows - Force override */
select {
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
    background-image: none !important;
}

select::-ms-expand {
    display: none !important;
}

select::-webkit-appearance {
    -webkit-appearance: none !important;
}

</style>

@endsection
