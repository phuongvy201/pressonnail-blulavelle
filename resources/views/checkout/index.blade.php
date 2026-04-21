@extends('layouts.app')

@section('title', 'Checkout - Bluprinter')

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- PayPal SDK - Currency from domain config -->
<script src="https://www.paypal.com/sdk/js?client-id={{ config('services.paypal.client_id') }}&currency={{ $currency ?? 'USD' }}&intent=capture&components=buttons"></script>

<!-- Stripe JS SDK -->
<script src="https://js.stripe.com/v3/"></script>

@section('content')
@php
    use Illuminate\Support\Collection;
    
    // Get currency and rate using helper functions
    $currentCurrency = currency();
    $currentCurrencyRate = currency_rate() ?? 1.0;
    $shippingRates = \App\Models\ShippingRate::where('is_active', true)
        ->with('shippingZone')
        ->orderBy('is_default', 'desc')
        ->orderBy('sort_order')
        ->get();
    
    // Get default shipping rate
    $defaultShippingRate = $shippingRates->where('is_default', true)->first();
    if (!$defaultShippingRate && $shippingRates->count() > 0) {
        $defaultShippingRate = $shippingRates->first();
    }
    
    // Get all shipping zones
    $shippingZones = \App\Models\ShippingZone::where('is_active', true)
        ->orderBy('sort_order')
        ->get();
    
    // Create country to zone mapping
    $countryToZoneMap = [];
    foreach ($shippingZones as $zone) {
        $countries = $zone->countries ?? [];
        foreach ($countries as $countryCode) {
            $countryToZoneMap[strtoupper($countryCode)] = $zone->id;
        }
    }
    
    // Country names mapping (must be defined before use in zonesWithCountries)
    $countryNames = [
        'US' => '🇺🇸 United States',
        'GB' => '🇬🇧 United Kingdom',
        'CA' => '🇨🇦 Canada',
        'AU' => '🇦🇺 Australia',
        'DE' => '🇩🇪 Germany',
        'FR' => '🇫🇷 France',
        'IT' => '🇮🇹 Italy',
        'ES' => '🇪🇸 Spain',
        'NL' => '🇳🇱 Netherlands',
        'BE' => '🇧🇪 Belgium',
        'CH' => '🇨🇭 Switzerland',
        'AT' => '🇦🇹 Austria',
        'SE' => '🇸🇪 Sweden',
        'NO' => '🇳🇴 Norway',
        'DK' => '🇩🇰 Denmark',
        'FI' => '🇫🇮 Finland',
        'IE' => '🇮🇪 Ireland',
        'PT' => '🇵🇹 Portugal',
        'GR' => '🇬🇷 Greece',
        'PL' => '🇵🇱 Poland',
        'CZ' => '🇨🇿 Czech Republic',
        'HU' => '🇭🇺 Hungary',
        'RO' => '🇷🇴 Romania',
        'BG' => '🇧🇬 Bulgaria',
        'HR' => '🇭🇷 Croatia',
        'SK' => '🇸🇰 Slovakia',
        'SI' => '🇸🇮 Slovenia',
        'EE' => '🇪🇪 Estonia',
        'LV' => '🇱🇻 Latvia',
        'LT' => '🇱🇹 Lithuania',
        'JP' => '🇯🇵 Japan',
        'CN' => '🇨🇳 China',
        'KR' => '🇰🇷 South Korea',
        'SG' => '🇸🇬 Singapore',
        'MY' => '🇲🇾 Malaysia',
        'TH' => '🇹🇭 Thailand',
        'ID' => '🇮🇩 Indonesia',
        'PH' => '🇵🇭 Philippines',
        'VN' => '🇻🇳 Vietnam',
        'IN' => '🇮🇳 India',
        'NZ' => '🇳🇿 New Zealand',
        'BR' => '🇧🇷 Brazil',
        'MX' => '🇲🇽 Mexico',
        'AR' => '🇦🇷 Argentina',
        'CL' => '🇨🇱 Chile',
        'CO' => '🇨🇴 Colombia',
        'PE' => '🇵🇪 Peru',
        'ZA' => '🇿🇦 South Africa',
        'EG' => '🇪🇬 Egypt',
        'AE' => '🇦🇪 United Arab Emirates',
        'SA' => '🇸🇦 Saudi Arabia',
        'IL' => '🇮🇱 Israel',
        'TR' => '🇹🇷 Turkey',
        'RU' => '🇷🇺 Russia',
        'UA' => '🇺🇦 Ukraine',
    ];
    
    // Prepare shipping rates data for JavaScript
    $shippingRatesData = [];
    $shippingRatesByZone = [];
    
    foreach ($shippingRates as $rate) {
        $rateData = [
            'id' => $rate->id,
            'zone_id' => $rate->shipping_zone_id,
            'zone_name' => $rate->shippingZone ? $rate->shippingZone->name : null,
            'category_id' => $rate->category_id,
            'name' => $rate->name,
            'first_item_cost' => (float) $rate->first_item_cost,
            'additional_item_cost' => (float) $rate->additional_item_cost,
            'is_default' => (bool) $rate->is_default,
            'min_items' => $rate->min_items,
            'max_items' => $rate->max_items,
            'min_order_value' => $rate->min_order_value ? (float) $rate->min_order_value : null,
            'max_order_value' => $rate->max_order_value ? (float) $rate->max_order_value : null,
        ];
        
        $shippingRatesData[] = $rateData;
        
        $zoneId = $rate->shipping_zone_id ?? 'none';
        if (!isset($shippingRatesByZone[$zoneId])) {
            $shippingRatesByZone[$zoneId] = [
                'zone_id' => $rate->shipping_zone_id,
                'zone_name' => $rate->shippingZone ? $rate->shippingZone->name : 'General',
                'rates' => []
            ];
        }
        $shippingRatesByZone[$zoneId]['rates'][] = $rateData;
    }
    
    // Prepare zones data with countries grouped by zone
    $zonesData = [];
    $zonesWithCountries = [];
    $zonesWithRates = $shippingRates->pluck('shipping_zone_id')->unique();
    
    foreach ($shippingZones as $zone) {
        $countries = $zone->countries ?? [];
        $countryCodes = is_array($countries) ? $countries : [];
        
        // Only include zones that have shipping rates
        if ($zonesWithRates->contains($zone->id)) {
            if (!empty($countryCodes)) {
                // Zone with countries - create separate options for each country
                $zoneData = [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'description' => $zone->description,
                    'countries' => $countryCodes,
                    'country_options' => []
                ];
                
                // Create an option for each country
                foreach ($countryCodes as $countryCode) {
                    $countryCodeUpper = strtoupper($countryCode);
                    $countryName = $countryNames[$countryCodeUpper] ?? $countryCodeUpper;
                    $zoneData['country_options'][] = [
                        'value' => $countryCodeUpper,
                        'label' => $countryName,
                        'zone_id' => $zone->id,
                        'country_code' => $countryCodeUpper
                    ];
                }
                
                $zonesWithCountries[] = $zoneData;
            } else {
                // Zone without countries
                $zonesData[] = [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'description' => $zone->description,
                    'countries' => [],
                ];
            }
        }
    }
    
    // Prepare default shipping rate data
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
    
    // Calculate base subtotal in USD for shipping calculation
    $baseSubtotal = 0;
    foreach ($products as $item) {
        $product = $item['product'];
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $lineTotal = (float) ($item['total'] ?? ($product->price ?? $product->base_price ?? 0) * $quantity);
        
        // Convert to USD if needed
        $baseLineTotal = $currentCurrency !== 'USD' && $currentCurrencyRate > 0 
            ? $lineTotal / $currentCurrencyRate 
            : $lineTotal;
        
        $baseSubtotal += $baseLineTotal;
    }

    $gtagItems = [];
    $tiktokContents = [];
    foreach ($products as $index => $item) {
        $product = $item['product'];
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $lineTotal = (float) ($item['total'] ?? ($product->price ?? $product->base_price ?? 0) * $quantity);
        $unitPrice = $quantity > 0 ? $lineTotal / $quantity : 0;

        $categories = $product->categories ?? collect();
        if (!($categories instanceof Collection)) {
            $categories = collect($categories);
        }
        $primaryCategory = optional($categories->first())->name ?? null;

        $variantAttributes = null;
        $selectedVariant = $item['cart_item']->selected_variant ?? null;
        if (is_array($selectedVariant) && isset($selectedVariant['attributes']) && is_array($selectedVariant['attributes'])) {
            $variantAttributes = implode(' / ', $selectedVariant['attributes']);
        }

        $gtagItem = [
            'item_id' => $product->sku ?? $product->id,
            'item_name' => $product->name,
            'item_category' => $primaryCategory,
            'price' => round($unitPrice, 2),
            'quantity' => $quantity,
            'index' => $index + 1,
        ];

        if (!empty($variantAttributes)) {
            $gtagItem['item_variant'] = $variantAttributes;
        }

        $gtagItems[] = $gtagItem;

        $tiktokContents[] = [
            'content_id' => (string) ($product->id ?? $product->sku ?? ''),
            'content_type' => 'product',
            'content_name' => $product->name,
            'quantity' => $quantity,
            'price' => round($unitPrice, 2),
        ];
    }

    $tiktokContents = array_values(array_filter($tiktokContents, function ($item) {
        return !empty($item['content_id']) && !empty($item['content_name']);
    }));

    $checkoutTotal = round((float) ($total ?? 0), 2);
@endphp

<script>
// Global constants - must be declared before any code that uses them
const TIKTOK_CHECKOUT_CONTENTS = {!! json_encode($tiktokContents, JSON_UNESCAPED_UNICODE) !!};
const TIKTOK_CHECKOUT_VALUE = {{ $checkoutTotal }};
const CHECKOUT_CURRENCY = '{{ $currency ?? "USD" }}';
const CHECKOUT_CURRENCY_RATE = {{ $currencyRate ?? 1.0 }};
const CHECKOUT_BASE_TOTAL = {{ $total }};
// Subtotal should be BEFORE combo discount (display + client-side calculations)
const CHECKOUT_CONVERTED_SUBTOTAL = {{ $convertedSubtotal ?? $subtotal }};
const CHECKOUT_CONVERTED_TOTAL = {{ $convertedTotal ?? $total }};
const CHECKOUT_DISCOUNT = {{ $discount ?? 0 }};
const CHECKOUT_BULK_DISCOUNT = {{ $bulkDiscount ?? 0 }};
const CHECKOUT_SUBTOTAL_AFTER_BULK = {{ $subtotalAfterBulk ?? ($convertedSubtotal ?? $subtotal) }};
const CHECKOUT_BULK_DISCOUNT_PERCENT = {{ $bulkDiscountPercent ?? 0 }};
const CHECKOUT_DISCOUNT_MODE = @json($discountMode ?? 'volume');
const CHECKOUT_DISCOUNT_MODE_URL = @json(route('api.cart.discount-mode'));
const CHECKOUT_APPLY_PROMO_URL = @json(route('api.cart.apply-promo'));
const CHECKOUT_REMOVE_PROMO_URL = @json(route('api.cart.remove-promo'));
const CHECKOUT_PROMO_DRAFT_KEY = 'checkout_promo_code_draft';
const CHECKOUT_CURRENCY_SYMBOL = @json(\App\Services\CurrencyService::getCurrencySymbol($currency ?? 'USD'));
const SHIPPING_RATES = @json($shippingRatesData);
const SHIPPING_RATES_BY_ZONE = @json($shippingRatesByZone);
const SHIPPING_ZONES = @json($zonesData);
const SHIPPING_ZONES_WITH_COUNTRIES = @json($zonesWithCountries);
const COUNTRY_TO_ZONE_MAP = @json($countryToZoneMap);
const DEFAULT_SHIPPING_RATE = @json($defaultShippingRateData);
const DEFAULT_SHIPPING_ZONE_ID = @json($defaultShippingRate ? $defaultShippingRate->shipping_zone_id : null);
const CHECKOUT_BASE_SUBTOTAL = {{ $baseSubtotal }};
const CHECKOUT_FREE_SHIP_THRESHOLD_USD = 150;

// GA4 ecommerce items shared across multiple checkout events.
window.PRESSONNail_CHECKOUT_GA4_ITEMS = @json($gtagItems);

document.addEventListener('DOMContentLoaded', function() {
    // Event tracking được xử lý bởi GTM thông qua dataLayer
    if (typeof dataLayer !== 'undefined') {
        const checkoutItems = window.PRESSONNail_CHECKOUT_GA4_ITEMS || [];
        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            event: 'begin_checkout',
            ecommerce: {
                currency: '{{ $currency ?? "USD" }}',
                value: Number({{ $convertedTotal ?? $checkoutTotal }}) || 0,
                items: checkoutItems
            }
        });
        console.log('✅ GTM: begin_checkout tracked', {
            items: checkoutItems.length,
            value: {{ $checkoutTotal }}
        });
    }

    if (typeof window !== 'undefined') {
        window.tiktokCheckoutPayload = {
            contents: Array.isArray(TIKTOK_CHECKOUT_CONTENTS) ? TIKTOK_CHECKOUT_CONTENTS : [],
            value: Number(TIKTOK_CHECKOUT_VALUE) || 0,
            currency: CHECKOUT_CURRENCY
        };

        if (window.ttq) {
            window.ttq.track('InitiateCheckout', window.tiktokCheckoutPayload);
        }
    }
});
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    
    .checkout-page {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    /* Theme match cart: primary = pink #f0427c, background-light = #f8f6f6 */

function buildCheckoutCustomizationInputs(customizations) {
    var html = '';
    if (!customizations) return html;
    Object.keys(customizations).forEach(function(k){
        var v = customizations[k] || {};
        var value = v && v.value ? String(v.value).replace(/"/g, '&quot;') : '';
        html += '<div class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-center">'
             + '<div class="sm:col-span-2"><span class="text-sm text-slate-600">' + k + '</span></div>'
             + '<div class="sm:col-span-3">'
             + '<input type="text" class="w-full border-2 border-primary/20 rounded-lg px-3 py-2 checkout-customization-input" data-label="' + k + '" value="' + value + '" oninput="updateCheckoutModalTotal()" title="' + value + '" />'
             + '</div>'
             + '</div>';
    });
    return html;
}

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes shimmer {
        0% {
            background-position: -200px 0;
        }
        100% {
            background-position: calc(200px + 100%) 0;
        }
    }

    .animate-fadeInUp {
        animation: fadeInUp 0.8s ease-out forwards;
    }

    .animate-slideInLeft {
        animation: slideInLeft 0.8s ease-out forwards;
    }

    .animate-slideInRight {
        animation: slideInRight 0.8s ease-out forwards;
    }

    .gradient-bg {
        background: linear-gradient(135deg, #f0427c 0%, #e91e6e 100%);
    }

    .gradient-text {
        background: linear-gradient(135deg, #f0427c 0%, #c71e54 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }


    /* Payment option styling - match cart (primary pink) */
    .payment-option {
        transition: all 0.3s ease;
    }

    .payment-option:hover {
        @apply border-primary shadow-lg;
    }

    input[type="radio"]:checked + * {
        @apply border-primary bg-primary/10;
    }

    label.payment-option:has(input[type="radio"]:checked) {
        @apply border-primary bg-primary/10;
    }

    .payment-option input[type="radio"]:checked {
        @apply text-primary border-primary;
    }

    .payment-option.selected {
        @apply border-primary bg-primary/10;
    }


    .product-item {
        @apply transition-all duration-300 rounded-xl hover:shadow-lg hover:transform hover:translate-x-1;
    }

    .step-indicator {
        @apply flex items-center justify-center w-10 h-10 rounded-full bg-primary text-white font-semibold text-sm shadow-md;
    }

    .step-indicator.active {
        @apply bg-primary shadow-lg;
    }

    .step-indicator.completed {
        @apply bg-primary shadow-lg;
    }


    .floating-label {
        @apply relative;
    }

    .floating-label input:focus + label,
    .floating-label input:not(:placeholder-shown) + label {
        @apply -translate-y-5 scale-90 text-primary;
    }

    .floating-label label {
        @apply absolute left-3 top-3 transition-all duration-200 pointer-events-none text-slate-500;
    }

    /* Main containers */
    .checkout-container {
        @apply rounded-2xl;
    }

    .order-summary-container {
        @apply rounded-2xl;
    }

    /* PayPal button styles */
    #paypal-button-container {
        @apply min-h-[200px] rounded-xl relative;
    }

    #paypal-button {
        @apply min-h-[120px] flex items-center justify-center;
    }

    #paypal-button button {
        @apply rounded-lg min-h-[48px];
    }
    
    /* Stripe card element styles */
    #stripe-card-container {
        @apply min-h-[200px] rounded-xl relative;
    }
    
    #stripe-card-element {
        @apply min-h-[50px] p-3;
    }
    
    .StripeElement {
        @apply bg-white p-4 rounded-lg border border-primary/20 shadow-sm;
    }
    
    .StripeElement--focus {
        @apply border-primary shadow-lg ring-2 ring-primary/20;
    }
    
    .StripeElement--invalid {
        @apply border-red-500 shadow-lg ring-2 ring-red-200;
    }

    /* Ensure submit button is always visible */
    button[type="submit"] {
        position: relative !important;
        z-index: 10 !important;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    /* Prevent text overflow in customizations */
    .customization-value {
        word-break: break-all;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
        display: inline-block;
    }

    /* Tip selection styling */
    .tip-option {
        transition: all 0.2s ease;
        position: relative;
    }

    .tip-option:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .tip-option.selected {
        border: 3px solid #f0427c !important;
        background-color: #fce7ef !important;
        box-shadow: 0 0 0 4px rgba(240, 66, 124, 0.3) !important;
        transform: translateY(-2px) !important;
    }

    .tip-option.selected span {
        color: #c71e54 !important;
        font-weight: 700 !important;
    }

    button.tip-option.selected {
        border: 3px solid #f0427c !important;
        background-color: #fce7ef !important;
        box-shadow: 0 0 0 4px rgba(240, 66, 124, 0.3) !important;
    }

    .tip-option.selected::before {
        content: '✓';
        position: absolute;
        top: -3px;
        right: -3px;
        background: #f0427c;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        z-index: 20;
        border: 2px solid white;
    }
</style>

<style>
    /* Responsive tweaks for small screens */
    @media (max-width: 640px) {
        /* Reduce inner padding blocks */
        .checkout-container .p-8 { padding: 1rem /* 16px */; }
        .order-summary-container { padding: 1rem /* 16px */; }

        /* Payment option card spacing */
        label.payment-option { padding: 1rem /* 16px */ !important; }
        label.payment-option .mr-5 { margin-right: 0.75rem /* 12px */; }
        label.payment-option .p-4 { padding: 0.5rem /* 8px */; }
        label.payment-option .text-xl { font-size: 1rem; }
        label.payment-option .w-10.h-10 { width: 1.75rem; height: 1.75rem; }

        /* PayPal/Stripe containers */
        #paypal-button-container, #stripe-card-container { padding: 1rem /* 16px */; }

        /* Submit button a bit thinner */
        button[type="submit"].w-full { padding-top: 0.9rem; padding-bottom: 0.9rem; }

        /* Reduce gaps */
        .gap-8 { gap: 1rem; }
        .space-y-8 > * + * { margin-top: 1rem; }
    }

    /* Tablet adjustments */
    @media (min-width: 641px) and (max-width: 1024px) {
        .checkout-container .p-8 { padding: 1.5rem /* 24px */; }
        label.payment-option { padding: 1.25rem /* 20px */ !important; }
    }
</style>

<div class="min-h-screen bg-background-light font-display text-slate-900 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <div class="mb-6 sm:mb-8">
            <nav class="text-xs sm:text-sm" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 overflow-x-auto whitespace-nowrap">
                    <li>
                        <a href="{{ route('cart.index') }}" class="text-primary hover:underline font-medium">Cart</a>
                    </li>
                    <li class="text-slate-300">/</li>
                    <li>
                        <span class="text-slate-900 font-semibold">Order Information</span>
                    </li>
                    <li class="text-slate-300 hidden xs:inline sm:inline">/</li>
                    <li class="hidden xs:inline sm:inline">
                        <span class="text-slate-500">Complete</span>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="flex flex-col lg:grid lg:grid-cols-3 gap-8">
            <!-- Checkout Form -->
            <div class="order-2 lg:order-1 lg:col-span-2 animate-slideInLeft">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden checkout-container">
                    <!-- Header - match cart primary (pink) -->
                    <div class="bg-primary p-6">
                        <div class="flex items-center text-white">
                            <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold">Shipping Information</h2>
                                <p class="text-white/90 text-sm">Please provide your delivery details</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-8">
                    
                    <form id="checkout-form" method="POST" action="{{ route('checkout.process') }}" class="space-y-8">
                        @csrf
                        <input type="hidden" id="tip_amount" name="tip_amount" value="0">
                        <input type="hidden" id="currency" name="currency" value="{{ $currency ?? 'USD' }}">
                        <input type="hidden" id="shipping_cost" name="shipping_cost" value="0">
                        <input type="hidden" id="shipping_zone_id" name="shipping_zone_id" value="">
                        <input type="hidden" id="retention_free_shipping" name="retention_free_shipping" value="0">
                        <input type="hidden" id="retention_popup_source" name="retention_popup_source" value="">
                        
                        <!-- Contact Information -->
                        <div class="space-y-5">
                            <div class="flex items-center space-x-3 pb-3 border-b-2 border-primary/10">
                                <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-900">Contact Details</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="relative">
                                    <label for="customer_name" class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            Full Name *
                                        </span>
                                    </label>
                                    <input type="text" id="customer_name" name="customer_name" 
                                           class="w-full px-4 py-3 bg-white border-2 border-primary/20 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 shadow-sm hover:shadow-md focus:shadow-lg focus:-translate-y-0.5" required
                                           value="{{ auth()->user() ? auth()->user()->name : '' }}"
                                           placeholder="John Doe">
                                    @error('customer_name')
                                        <p class="text-red-500 text-xs mt-1.5 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                
                                <div class="relative">
                                    <label for="customer_email" class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            Email Address *
                                        </span>
                                    </label>
                                    <input type="email" id="customer_email" name="customer_email" 
                                           class="w-full px-4 py-3 bg-white border-2 border-primary/20 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 shadow-sm hover:shadow-md focus:shadow-lg focus:-translate-y-0.5" required
                                           value="{{ auth()->user() ? auth()->user()->email : '' }}"
                                           placeholder="john@example.com">
                                    @error('customer_email')
                                        <p class="text-red-500 text-xs mt-1.5 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            <div class="relative">
                                <label for="customer_phone" class="block text-sm font-semibold text-slate-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                        Phone Number
                                    </span>
                                </label>
                                <input type="tel" id="customer_phone" name="customer_phone" 
                                       class="w-full px-4 py-3 bg-white border-2 border-primary/20 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 shadow-sm hover:shadow-md focus:shadow-lg focus:-translate-y-0.5"
                                       placeholder="+1 (555) 123-4567">
                                @error('customer_phone')
                                    <p class="text-red-500 text-xs mt-1.5 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="space-y-5">
                            <div class="flex items-center space-x-3 pb-3 border-b-2 border-primary/10">
                                <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-900">Delivery Address</h3>
                            </div>
                            
                            <div class="relative">
                                <label for="shipping_address" class="block text-sm font-semibold text-slate-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                        </svg>
                                        Street Address *
                                    </span>
                                </label>
                                <textarea id="shipping_address" name="shipping_address" 
                                          class="w-full px-4 py-3 bg-white border-2 border-primary/20 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 shadow-sm hover:shadow-md focus:shadow-lg focus:-translate-y-0.5 resize-vertical min-h-[100px]" rows="3" required
                                          placeholder="Street address, apartment, suite, unit, etc."></textarea>
                                @error('shipping_address')
                                    <p class="text-red-500 text-xs mt-1.5 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div class="relative">
                                    <label for="city" class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            City *
                                        </span>
                                    </label>
                                    <input type="text" id="city" name="city" 
                                           class="w-full px-4 py-3 bg-white border-2 border-primary/20 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 shadow-sm hover:shadow-md focus:shadow-lg focus:-translate-y-0.5" required
                                           placeholder="New York">
                                    @error('city')
                                        <p class="text-red-500 text-xs mt-1.5 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                
                                <div class="relative">
                                    <label for="state" class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                            </svg>
                                            State/Province
                                        </span>
                                    </label>
                                    <input type="text" id="state" name="state" 
                                           class="w-full px-4 py-3 bg-white border-2 border-primary/20 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 shadow-sm hover:shadow-md focus:shadow-lg focus:-translate-y-0.5"
                                           placeholder="NY">
                                    @error('state')
                                        <p class="text-red-500 text-xs mt-1.5 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                
                                <div class="relative">
                                    <label for="postal_code" class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            Postal Code *
                                        </span>
                                    </label>
                                    <input type="text" id="postal_code" name="postal_code" 
                                           class="w-full px-4 py-3 bg-white border-2 border-primary/20 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 shadow-sm hover:shadow-md focus:shadow-lg focus:-translate-y-0.5" required
                                           placeholder="10001">
                                    @error('postal_code')
                                        <p class="text-red-500 text-xs mt-1.5 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            <div class="relative">
                                <label for="country" class="block text-sm font-semibold text-slate-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Country *
                                    </span>
                                </label>
                                <select id="country" name="country" class="w-full px-4 py-3 bg-white border-2 border-primary/20 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 shadow-sm hover:shadow-md focus:shadow-lg focus:-translate-y-0.5 cursor-pointer" required>
                                    <option value="">Select Country</option>
                                    @if(count($zonesWithCountries) > 0)
                                        @foreach($zonesWithCountries as $zone)
                                            <optgroup label="{{ $zone['name'] }}">
                                                @foreach($zone['country_options'] as $country)
                                                    <option value="{{ $country['value'] }}" data-zone-id="{{ $country['zone_id'] }}">
                                                        {{ $country['label'] }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    @else
                                        {{-- Fallback: show all countries if no shipping rates configured --}}
                                        <option value="US">🇺🇸 United States</option>
                                        <option value="GB">🇬🇧 United Kingdom</option>
                                        <option value="CA">🇨🇦 Canada</option>
                                        <option value="AU">🇦🇺 Australia</option>
                                        <option value="DE">🇩🇪 Germany</option>
                                        <option value="FR">🇫🇷 France</option>
                                        <option value="IT">🇮🇹 Italy</option>
                                        <option value="ES">🇪🇸 Spain</option>
                                        <option value="NL">🇳🇱 Netherlands</option>
                                        <option value="BE">🇧🇪 Belgium</option>
                                        <option value="CH">🇨🇭 Switzerland</option>
                                        <option value="AT">🇦🇹 Austria</option>
                                        <option value="SE">🇸🇪 Sweden</option>
                                        <option value="NO">🇳🇴 Norway</option>
                                        <option value="DK">🇩🇰 Denmark</option>
                                        <option value="FI">🇫🇮 Finland</option>
                                        <option value="IE">🇮🇪 Ireland</option>
                                        <option value="PT">🇵🇹 Portugal</option>
                                        <option value="GR">🇬🇷 Greece</option>
                                        <option value="PL">🇵🇱 Poland</option>
                                        <option value="CZ">🇨🇿 Czech Republic</option>
                                        <option value="HU">🇭🇺 Hungary</option>
                                        <option value="RO">🇷🇴 Romania</option>
                                        <option value="BG">🇧🇬 Bulgaria</option>
                                        <option value="HR">🇭🇷 Croatia</option>
                                        <option value="SK">🇸🇰 Slovakia</option>
                                        <option value="SI">🇸🇮 Slovenia</option>
                                        <option value="EE">🇪🇪 Estonia</option>
                                        <option value="LV">🇱🇻 Latvia</option>
                                        <option value="LT">🇱🇹 Lithuania</option>
                                        <option value="JP">🇯🇵 Japan</option>
                                        <option value="CN">🇨🇳 China</option>
                                        <option value="KR">🇰🇷 South Korea</option>
                                        <option value="SG">🇸🇬 Singapore</option>
                                        <option value="MY">🇲🇾 Malaysia</option>
                                        <option value="TH">🇹🇭 Thailand</option>
                                        <option value="ID">🇮🇩 Indonesia</option>
                                        <option value="PH">🇵🇭 Philippines</option>
                                        <option value="VN">🇻🇳 Vietnam</option>
                                        <option value="IN">🇮🇳 India</option>
                                        <option value="NZ">🇳🇿 New Zealand</option>
                                        <option value="BR">🇧🇷 Brazil</option>
                                        <option value="MX">🇲🇽 Mexico</option>
                                        <option value="AR">🇦🇷 Argentina</option>
                                        <option value="CL">🇨🇱 Chile</option>
                                        <option value="CO">🇨🇴 Colombia</option>
                                        <option value="PE">🇵🇪 Peru</option>
                                        <option value="ZA">🇿🇦 South Africa</option>
                                        <option value="EG">🇪🇬 Egypt</option>
                                        <option value="AE">🇦🇪 United Arab Emirates</option>
                                        <option value="SA">🇸🇦 Saudi Arabia</option>
                                        <option value="IL">🇮🇱 Israel</option>
                                        <option value="TR">🇹🇷 Turkey</option>
                                        <option value="RU">🇷🇺 Russia</option>
                                        <option value="UA">🇺🇦 Ukraine</option>
                                    @endif
                                </select>
                                @error('country')
                                    <p class="text-red-500 text-xs mt-1.5 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="space-y-5">
                            <div class="flex items-center space-x-3 pb-3 border-b-2 border-primary/10">
                                <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-900">Additional Notes</h3>
                            </div>
                            <div class="relative">
                                <label for="notes" class="block text-sm font-semibold text-slate-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                        </svg>
                                        Order Notes (Optional)
                                    </span>
                                </label>
                                <textarea id="notes" name="notes" 
                                          class="w-full px-4 py-3 bg-white border-2 border-primary/20 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 shadow-sm hover:shadow-md focus:shadow-lg focus:-translate-y-0.5 resize-vertical min-h-[100px]" rows="3"
                                          placeholder="Any special instructions for your order..."></textarea>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="space-y-5">
                            <div class="flex items-center space-x-3 pb-3 border-b-2 border-primary/10">
                                <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-900">Payment Method</h3>
                            </div>
                            <div class="space-y-4">
                                <!-- Stripe -->
                                <div class="relative">
                                    <label for="payment_stripe" class="flex items-center p-6 border-2 border-primary/20 rounded-2xl cursor-pointer hover:border-primary hover:shadow-xl transition-all duration-300 payment-option bg-white">
                                        <input type="radio" id="payment_stripe" name="payment_method" value="stripe" class="w-6 h-6 text-primary border-primary/30 focus:ring-primary mr-5" checked>
                                        <div class="flex items-center flex-1">
                                            <div class="bg-primary rounded-2xl p-4 mr-5 shadow-lg">
                                                <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.274 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.386-2.061 1.386-1.705 0-3.888-.921-5.811-1.758L4.443 24c2.254.893 5.18 1.758 7.83 1.758 2.532 0 4.633-.624 6.123-1.844 1.543-1.271 2.346-3.116 2.346-5.342 0-3.896-2.467-5.76-6.476-7.219z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-3">
                                                    <span class="font-bold text-slate-900 text-xl">Credit Card (Stripe)</span>
                                                    <span class="px-3 py-1 bg-primary text-white text-xs font-bold rounded-full shadow-md">SECURE</span>
                                                </div>
                                                <p class="text-sm text-slate-600 mt-2">Direct credit card processing</p>
                                                <div class="flex items-center mt-3 text-sm text-primary bg-primary/10 px-3 py-2 rounded-lg">
                                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span class="font-medium">PCI-DSS compliant & 3D Secure</span>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                    
                                    <!-- Stripe Card Element -->
                                    <div id="stripe-card-container" class="mt-4 p-6 border-2 border-primary/20 rounded-xl bg-primary/5">
                                        <div class="flex items-center mb-4">
                                            <div class="w-8 h-8 bg-primary rounded-xl flex items-center justify-center mr-3">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                </svg>
                                            </div>
                                            <h4 class="font-bold text-slate-900 text-lg">💳 Credit Card Details</h4>
                                        </div>
                                        
                                        <!-- Card Type Logos -->
                                        <div class="flex gap-3 mb-4 justify-center">
                                            <div class="w-12 h-8 bg-slate-600 rounded text-white text-xs font-bold flex items-center justify-center">VISA</div>
                                            <div class="w-12 h-8 bg-slate-600 rounded text-white text-xs font-bold flex items-center justify-center">MC</div>
                                            <div class="w-12 h-8 bg-slate-600 rounded text-white text-xs font-bold flex items-center justify-center">AMEX</div>
                                            <div class="w-12 h-8 bg-slate-600 rounded text-white text-xs font-bold flex items-center justify-center">DISC</div>
                                        </div>

                                        <!-- Stripe Card Element Container -->
                                        <div id="stripe-card-element" class="p-4 border-2 border-primary/20 rounded-xl bg-white">
                                            <!-- Stripe Elements will be inserted here -->
                                        </div>
                                        <div id="stripe-card-errors" class="text-red-500 text-sm mt-2" role="alert"></div>

                                        <!-- Security Notice -->
                                        <div class="mt-4 p-3 bg-white/60 rounded-lg border border-primary/20">
                                            <div class="flex items-start">
                                                <svg class="w-5 h-5 text-primary mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                <div class="text-sm text-slate-800">
                                                    <p class="font-semibold mb-1">🔒 100% Secure Payment</p>
                                                    <p>Your payment information is encrypted and processed securely by Stripe. We never store your card details.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PayPal -->
                                <div class="relative">
                                    <label for="payment_paypal" class="flex items-center p-6 border-2 border-primary/20 rounded-2xl cursor-pointer hover:border-primary hover:shadow-xl transition-all duration-300 payment-option bg-white">
                                        <input type="radio" id="payment_paypal" name="payment_method" value="paypal" class="w-6 h-6 text-primary border-primary/30 focus:ring-primary mr-5">
                                        <div class="flex items-center flex-1">
                                            <div class="bg-blue-600 rounded-2xl p-4 mr-5 shadow-lg">
                                                <img src="https://www.paypalobjects.com/webstatic/icon/pp258.png" 
                                                     alt="PayPal" class="h-10 w-10">
                                            </div>
                                            <div class="flex-1">
                                                <span class="font-bold text-slate-900 text-xl">PayPal</span>
                                                <p class="text-sm text-slate-600 mt-2">Safe & secure payment platform</p>
                                                <div class="flex items-center mt-3 text-sm text-primary bg-primary/10 px-3 py-2 rounded-lg">
                                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span class="font-medium">Fast and reliable checkout</span>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- PayPal Button Container -->
                                <div id="paypal-button-container" class="hidden mt-4 p-6 border-2 border-primary/20 rounded-xl bg-primary/5">
                                    <div class="flex items-center mb-4">
                                        <div class="w-8 h-8 bg-primary rounded-xl flex items-center justify-center text-white mr-3">
                                            <img src="https://www.paypalobjects.com/webstatic/icon/pp258.png" 
                                                 alt="PayPal" class="h-6 w-6">
                                        </div>
                                        <h4 class="font-bold text-slate-900 text-lg">💳 PayPal Checkout</h4>
                                    </div>
                                    <!-- PayPal button will be rendered here -->
                                    <div id="paypal-button" class="min-h-[120px] flex items-center justify-center">
                                        <div class="text-center">
                                            <div class="animate-spin w-8 h-8 border-4 border-primary/20 border-t-primary rounded-full mx-auto mb-3"></div>
                                            <p class="text-primary text-sm">Loading PayPal...</p>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                            @error('payment_method')
                                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button - match cart primary -->
                        <div class="mt-8 pt-6 border-t-2 border-primary/10 relative z-10">
                            <button type="submit" 
                                    class="w-full py-5 px-6 bg-primary hover:brightness-110 text-white rounded-xl font-bold text-lg shadow-lg shadow-primary/30 hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer">
                                <span class="flex items-center justify-center relative z-20">
                                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                    Secure Checkout
                                    <svg class="w-6 h-6 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                </span>
                                <!-- Shimmer effect -->
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-20 -translate-x-full group-hover:translate-x-full transition-transform duration-700 z-10"></div>
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>

            <!-- Order Summary - match cart -->
            <div class="order-1 lg:order-2 animate-slideInRight">
                <div class="bg-white rounded-2xl border border-primary/10 shadow-sm p-6 lg:sticky lg:top-8 order-summary-container">
                    <div class="flex items-center mb-6">
                        <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center text-white mr-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900">Order Summary</h2>
                    </div>
                    
                    <!-- Products -->
                    <div class="space-y-3 mb-6">
                        @foreach($products as $item)
                            <div class="product-item p-3 bg-white border border-primary/10 rounded-xl hover:shadow-sm transition flex gap-3" data-checkout-cart-item-id="{{ $item['cart_item']->id }}">
                                @php
                                    $media = $item['product']->getEffectiveMedia();
                                    $imageUrl = null;
                                    $checkoutLineImgAlt = $item['product']->name;
                                    if ($media && count($media) > 0) {
                                        $checkoutLineImgAlt = $item['product']->altForMediaItem($media[0], null, 0);
                                        if (is_string($media[0])) {
                                            $imageUrl = $media[0];
                                        } elseif (is_array($media[0])) {
                                            $imageUrl = $media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? null;
                                        }
                                    }
                                @endphp
                                <div class="shrink-0">
                                    @if($imageUrl)
                                        <img src="{{ $imageUrl }}" 
                                             alt="{{ $checkoutLineImgAlt }}"
                                             class="w-14 h-14 object-cover rounded-lg">
                                    @else
                                        <div class="w-14 h-14 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <h3 class="font-semibold text-gray-900 text-sm truncate">{{ Str::limit($item['product']->name, 42) }}</h3>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button onclick="openCheckoutEditCartModal({{ $item['cart_item']->id }})" class="p-1.5 text-slate-400 hover:text-primary transition-colors" title="Edit item">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <p class="font-semibold text-slate-900">
                                                {{ \App\Services\CurrencyService::formatPrice($item['total'], $currency ?? 'USD') }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Qty: {{ $item['quantity'] }}</p>

                                    @php $sv = $item['cart_item']->selected_variant; @endphp
                                    @if($sv && is_array($sv) && isset($sv['attributes']) && is_array($sv['attributes']))
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach($sv['attributes'] as $k => $v)
                                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] rounded">{{ $k }}: {{ $v }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($item['cart_item']->customizations && count($item['cart_item']->customizations) > 0)
                                        @php $cid = $item['cart_item']->id; @endphp
                                        <div class="mt-2">
                                            <ul id="cust-list-{{ $cid }}" class="space-y-0.5">
                                                @foreach($item['cart_item']->customizations as $k => $c)
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
                                                        $displayValue = Str::limit((string) $displayValue, 50);
                                                    @endphp
                                                    <li class="text-[11px] text-slate-700 {{ $loop->iteration > 3 ? 'hidden more-'.$cid : '' }}">
                                                        <span class="text-slate-500">{{ $k }}:</span>
                                                        <span class="font-medium customization-value" title="{{ $displayValue }}">
                                                            {{ $displayValue }}
                                                        </span>
                                                        @if(isset($c['price']) && $c['price']>0)
                                                            <span class="text-green-600">(+${{ number_format($c['price'],2) }})</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                            @if(count($item['cart_item']->customizations) > 3)
                                                <button type="button" class="mt-1 text-[11px] text-primary hover:underline" onclick="toggleCheckoutCustList({{ $cid }})" id="cust-toggle-{{ $cid }}">View more</button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Tip Selection - primary pink -->
                    <div class="border-t border-primary/10 pt-4 mb-4">
                        <div class="flex items-center mb-3">
                            <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            <h3 class="text-sm font-semibold text-slate-700">Love your items? Please support our designers. Thank you! ❤️</h3>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <button type="button" onclick="selectTip(0)" class="tip-option p-3 border-2 border-primary/20 rounded-lg text-center hover:border-primary transition-all duration-200" data-tip="0">
                                <span class="text-sm font-medium text-slate-700">No tips</span>
                            </button>
                            <button type="button" onclick="selectTip(5)" class="tip-option p-3 border-2 border-primary/20 rounded-lg text-center hover:border-primary transition-all duration-200" data-tip="5">
                                <span class="text-sm font-medium text-slate-700 tip-amount-5">
                                    {{ \App\Services\CurrencyService::formatPrice(($currency ?? 'USD') !== 'USD' && isset($currencyRate) ? 5 * $currencyRate : 5, $currency ?? 'USD') }}
                                </span>
                            </button>
                            <button type="button" onclick="selectTip(3)" class="tip-option p-3 border-2 border-primary/20 rounded-lg text-center hover:border-primary transition-all duration-200" data-tip="3">
                                <span class="text-sm font-medium text-slate-700 tip-amount-3">
                                    {{ \App\Services\CurrencyService::formatPrice(($currency ?? 'USD') !== 'USD' && isset($currencyRate) ? 3 * $currencyRate : 3, $currency ?? 'USD') }}
                                </span>
                            </button>
                            <button type="button" onclick="selectTip(3.15)" class="tip-option p-3 border-2 border-primary/20 rounded-lg text-center hover:border-primary transition-all duration-200" data-tip="3.15">
                                <span class="text-sm font-medium text-slate-700 tip-amount-3.15">
                                    {{ \App\Services\CurrencyService::formatPrice(($currency ?? 'USD') !== 'USD' && isset($currencyRate) ? 3.15 * $currencyRate : 3.15, $currency ?? 'USD') }}
                                </span>
                            </button>
                        </div>
                        
                        <div class="flex gap-2">
                            <button type="button" onclick="selectTip('custom')" class="tip-option flex-1 p-3 border-2 border-primary/20 rounded-lg text-center hover:border-primary transition-all duration-200" data-tip="custom">
                                <span class="text-sm font-medium text-slate-700">Other</span>
                            </button>
                            <input type="number" id="custom-tip-amount" placeholder="Custom amount" min="0" step="0.01" class="hidden w-32 px-3 py-2 border-2 border-primary/20 rounded-lg text-sm focus:border-primary focus:outline-none" onchange="updateCustomTip(this.value)">
                        </div>
                    </div>

                    <!-- Order Totals -->
                    <div class="border-t border-primary/10 pt-4 space-y-3">
                        <div class="flex gap-2">
                            <button type="button" id="checkout-mode-volume" class="flex-1 px-3 py-2 rounded-lg border text-xs font-bold transition-colors {{ ($discountMode ?? 'volume') === 'volume' ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 border-primary/20 hover:bg-primary/5' }}">
                                Volume
                            </button>
                            <button type="button" id="checkout-mode-promo" class="flex-1 px-3 py-2 rounded-lg border text-xs font-bold transition-colors {{ ($discountMode ?? 'volume') === 'promo' ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 border-primary/20 hover:bg-primary/5' }}">
                                Promo code
                            </button>
                        </div>
                        <p class="text-[11px] leading-snug text-slate-500 px-0.5">
                            <span class="font-semibold text-slate-600">Volume</span> is an automatic discount based on how many items are in this order—no code needed. Choose <span class="font-semibold text-slate-600">Promo code</span> if you have a coupon instead.
                        </p>
                        <div class="space-y-1">
                            <div class="flex w-full min-w-0 flex-col gap-2 sm:flex-row sm:items-stretch sm:gap-2">
                                <input
                                    type="text"
                                    id="checkout-promo-input"
                                    placeholder="Enter promo code"
                                    value="{{ $appliedPromoCode ?? '' }}"
                                    class="w-full min-w-0 rounded-lg border border-primary/20 bg-slate-50 text-sm px-3 py-2 focus:ring-primary focus:border-primary sm:flex-1 {{ ($discountMode ?? 'volume') !== 'promo' ? 'opacity-60 cursor-not-allowed' : '' }}"
                                    autocomplete="off"
                                    {{ ($discountMode ?? 'volume') !== 'promo' ? 'disabled' : '' }}
                                >
                                <div class="flex w-full min-w-0 gap-2 sm:w-auto sm:shrink-0 sm:justify-end">
                                    <button
                                        type="button"
                                        id="checkout-promo-apply"
                                        class="min-w-0 flex-1 px-3 py-2 sm:flex-none sm:px-4 bg-primary text-white rounded-lg text-xs font-bold hover:bg-primary/90 transition-colors {{ ($discountMode ?? 'volume') !== 'promo' ? 'opacity-60 cursor-not-allowed' : '' }}"
                                        {{ ($discountMode ?? 'volume') !== 'promo' ? 'disabled' : '' }}
                                    >
                                        Apply
                                    </button>
                                    <button
                                        type="button"
                                        id="checkout-promo-remove"
                                        class="min-w-0 flex-1 px-3 py-2 sm:flex-none sm:px-4 border border-primary/20 text-slate-700 rounded-lg text-xs font-bold hover:bg-primary/5 transition-colors {{ !empty($appliedPromoCode) ? '' : 'hidden' }}"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <p id="checkout-promo-message" class="text-xs hidden"></p>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal</span>
                            <span class="subtotal-display" id="checkout-subtotal">
                                {{ \App\Services\CurrencyService::formatPrice($subtotal, $currency ?? 'USD') }}
                            </span>
                        </div>
                        @if(($bulkDiscount ?? 0) > 0)
                        <div class="flex justify-between text-emerald-600" id="checkout-bulk-row">
                            <span>Discount{{ ($bulkDiscountPercent ?? 0) > 0 ? ' (-' . number_format((float)$bulkDiscountPercent, 0) . '%)' : '' }}</span>
                            <span class="bulk-discount-display" id="checkout-bulk-discount">-{{ \App\Services\CurrencyService::formatPrice($bulkDiscount ?? 0, $currency ?? 'USD') }}</span>
                        </div>
                        @endif
                        @if(!empty($appliedPromoCode) && ($discount ?? 0) > 0)
                        <div class="flex justify-between text-emerald-600" id="checkout-promo-row">
                            <span>Promo ({{ $appliedPromoCode }})</span>
                            <span class="promo-discount-display" id="checkout-promo-discount">-{{ \App\Services\CurrencyService::formatPrice($discount ?? 0, $currency ?? 'USD') }}</span>
                        </div>
                        @endif
                        <!-- Shipping Zone is auto-detected from country selection -->
                        <div class="flex justify-between text-slate-600" id="checkout-shipping-cost-row">
                            <span id="checkout-shipping-label">Shipping</span>
                            <span class="font-semibold" id="checkout-shipping-cost">{{ \App\Services\CurrencyService::formatPrice(0, $currency ?? 'USD') }}</span>
                        </div>
                        
                        <!-- Exchange Rate Display (only show if currency is not USD) -->
                        <div id="exchange-rate-display" class="text-xs text-slate-500 bg-slate-50 p-2 rounded-lg border border-primary/10" style="display: {{ ($currency ?? 'USD') !== 'USD' && isset($currencyRate) ? 'block' : 'none' }};">
                            <div class="flex justify-between items-center">
                                <span>Exchange Rate:</span>
                                <span class="font-medium" id="exchange-rate-value">
                                    @if(($currency ?? 'USD') !== 'USD' && isset($currencyRate))
                                        1 USD = {{ number_format($currencyRate, 4) }} {{ $currency }}
                                    @else
                                        1 USD = 1.0000 USD
                                    @endif
                                </span>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1">
                                Prices converted from USD
                            </div>
                        </div>
                        
                        <div class="flex justify-between text-slate-600" id="tip-line" style="display: none;">
                            <span>Tips</span>
                            <span class="tip-amount-display">{{ \App\Services\CurrencyService::getCurrencySymbol($currency ?? 'USD') }}0.00</span>
                        </div>
                        
                        <div class="flex justify-between text-lg font-bold text-slate-900 border-t border-primary/10 pt-3 mt-3">
                            <span>Total</span>
                            <span class="text-primary total-display" id="checkout-total">
                                {{ \App\Services\CurrencyService::formatPrice($convertedTotal ?? ($subtotal - ($discount ?? 0) + ($convertedShipping ?? $shippingCost ?? 0)), $currency ?? 'USD') }}
                            </span>
                        </div>
                    </div>

                    <!-- Security Badge - sidebar Order Summary -->
                    <div class="mt-6 p-6 bg-primary/5 border border-primary/10 rounded-2xl">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-xl mb-2 text-slate-900">🔒 100% Secure Checkout</h3>
                            <div class="space-y-1 text-slate-600 text-sm">
                                <div class="flex items-center justify-center">
                                    <span class="mr-2">🔒</span>
                                    <span>SSL Encrypted</span>
                                </div>
                                <div class="flex items-center justify-center">
                                    <span class="mr-2">🛡️</span>
                                    <span>PCI Compliant</span>
                                </div>
                                <div class="flex items-center justify-center">
                                    <span class="mr-2">💳</span>
                                    <span>Safe Payments</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Edit Cart Modal -->
<div id="checkoutEditCartModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-primary/10 shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-primary/10 px-6 py-4 flex justify-between items-center z-10">
            <h2 class="text-2xl font-bold text-slate-900">Edit Item</h2>
            <button onclick="closeCheckoutEditCartModal()" class="text-slate-400 hover:text-primary transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="checkoutEditCartModalContent" class="p-6"></div>
    </div>
    </div>

<script>
// Clear any cached data
console.log('🔄 Loading checkout script...', new Date().toISOString());

// Track Facebook Pixel InitiateCheckout event
document.addEventListener('DOMContentLoaded', function() {
    // Get cart data from localStorage
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    
    if (cart.length > 0 && typeof fbq !== 'undefined') {
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
        
        console.log('✅ Facebook Pixel: InitiateCheckout tracked', {
            items: cart.length,
            total: cartTotal.toFixed(2),
            ids: productIds
        });
    }
});

// Global currency variables - must be declared before functions that use them
// Use window object to ensure they are truly global and accessible from all script tags
window.currentCurrency = CHECKOUT_CURRENCY || 'USD';
window.currentCurrencyRate = CHECKOUT_CURRENCY_RATE || 1.0;
let currentCurrency = window.currentCurrency;
let currentCurrencyRate = window.currentCurrencyRate;


// Currency mapping
const countryToCurrency = {
    'US': 'USD',
    'GB': 'GBP',
    'CA': 'CAD',
    'AU': 'AUD',
    'NZ': 'NZD',
    'JP': 'JPY',
    'CN': 'CNY',
    'HK': 'HKD',
    'SG': 'SGD',
};

// Default currency rates (can be updated from server)
let currencyRates = {
    'USD': 1.0,
    'GBP': 0.79,
    'EUR': 0.92,
    'CAD': 1.35,
    'AUD': 1.52,
};

// Get currency from country
function getCurrencyFromCountry(country) {
    return countryToCurrency[country] || 'USD';
}

// Format price with currency symbol
function formatPrice(amount, currency) {
    const symbols = {
        'USD': '$',
        'GBP': '£',
        'EUR': '€',
        'CAD': 'C$',
        'AUD': 'A$',
        'JPY': '¥',
        'CNY': '¥',
    };
    const symbol = symbols[currency] || currency;
    return symbol + parseFloat(amount).toFixed(2);
}

// Convert amount from USD to target currency
function convertFromUSD(usdAmount, targetCurrency) {
    if (targetCurrency === 'USD') return usdAmount;
    // Use current currency rate from window object (updated from API) or fallback to constants
    const rate = window.currentCurrencyRate || CHECKOUT_CURRENCY_RATE || currencyRates[targetCurrency] || 1.0;
    return usdAmount * rate;
}


document.addEventListener('DOMContentLoaded', function() {
    console.log('📅 DOM Content Loaded at:', new Date().toISOString());
    console.log('📱 User Agent:', navigator.userAgent);
    console.log('📱 Is Mobile:', /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent));
    console.log('🔒 Current protocol:', window.location.protocol);
    console.log('🔒 Current origin:', window.location.origin);
    console.log('🔒 Full URL:', window.location.href);
    
    // Toast notification function - define early
    const showToast = (type, title, message) => {
        Swal.fire({
            icon: type,
            title: title,
            text: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true
        });
    };
    
    let tikTokAddPaymentTracked = false;
    const trackTikTokAddPayment = (paymentMethod) => {
        if (tikTokAddPaymentTracked || typeof window === 'undefined' || !window.ttq) {
            return;
        }

        tikTokAddPaymentTracked = true;

        const basePayload = window.tiktokCheckoutPayload || {};
        const payload = {
            contents: Array.isArray(basePayload.contents) ? basePayload.contents : [],
            value: Number(basePayload.value) || 0,
            currency: basePayload.currency || 'USD'
        };

        if (paymentMethod) {
            payload.payment_method = paymentMethod;
        }

        try {
            window.ttq.track('AddPaymentInfo', payload);
        } catch (error) {
            console.error('TikTok AddPaymentInfo error:', error);
        }
    };
    
    const form = document.getElementById('checkout-form');
    
    // Check if form exists
    if (!form) {
        console.error('❌ Checkout form not found!');
        return;
    }
    
    console.log('✅ Checkout form found and ready');
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const paymentOptions = document.querySelectorAll('.payment-option');
    
    // PayPal integration
    let paypalButtonsInitialized = false;
    let paypalSDKLoadAttempts = 0;
    const MAX_PAYPAL_SDK_ATTEMPTS = 50; // 5 seconds max
    
    
    // Loading state function
    const showLoading = (loading) => {
        if (loading) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            submitBtn.innerHTML = `
                <span class="flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                </span>
            `;
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            submitBtn.innerHTML = `
                <span class="flex items-center justify-center relative z-10">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    Secure Checkout
                    <svg class="w-6 h-6 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </span>
            `;
        }
    };
    
    // Unified order processing function
    const processUnifiedOrder = async (orderData) => {
        try {
            console.log('📦 Creating unified order with data:', orderData);

            // Validate required fields
            const requiredFields = ['customer_name', 'customer_email', 'shipping_address', 'city', 'postal_code', 'country'];
            const missingFields = requiredFields.filter(field => !orderData[field]);

            if (missingFields.length > 0) {
                throw new Error(`Missing required fields: ${missingFields.join(', ')}`);
            }

            // Create order using unified endpoint
            const response = await fetch('{{ route("checkout.process") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(orderData),
                credentials: 'same-origin',
                mode: 'same-origin'
            });

            if (!response.ok) {
                let errorMessage = `Order processing failed: ${response.status} ${response.statusText}`;
                
                try {
                    const errorData = await response.json();
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    } else if (errorData.errors) {
                        errorMessage = Object.values(errorData.errors).flat().join(', ');
                    }
                    
                    // Handle specific error types
                    if (errorData.error === 'cart_empty') {
                        errorMessage = 'Your cart is empty. Please add items to your cart and try again.';
                    }
                } catch (e) {
                    // If response is not JSON, use default message
                    console.error('Failed to parse error response:', e);
                }
                
                throw new Error(errorMessage);
            }

            const responseData = await response.json();
            console.log('📋 Unified order response:', responseData);

            if (responseData.success) {
                // Handle different payment method responses
                if (orderData.payment_method === 'paypal') {
                    await handlePayPalResponse(responseData);
                } else if (orderData.payment_method === 'stripe') {
                    await handleStripeResponse(responseData);
                } else {
                    // Generic success handling
                    await handleGenericSuccess(responseData);
                }
            } else {
                throw new Error(responseData.message || 'Order processing failed');
            }

        } catch (error) {
            console.error('❌ Unified order processing error:', error);
            showToast('error', 'Order Error', error.message);
            throw error;
        }
    };

    // Helper functions for payment responses
    const handlePayPalResponse = async (responseData) => {
        console.log('🔄 Handling PayPal response:', responseData);
        
        if (responseData.payment_completed === true || responseData.payment_status === 'paid') {
            console.log('✅ PayPal Payment Completed');
            showToast('success', 'Payment Successful!', 'Your payment has been processed successfully');
            
            // Clear cart from localStorage
            localStorage.removeItem('cart');
            
            // Redirect to success page
            setTimeout(() => {
                if (responseData.order_number) {
                    window.location.href = '{{ route("checkout.success", ":order_number") }}'.replace(':order_number', responseData.order_number);
                } else {
                    window.location.href = '{{ route("home") }}';
                }
            }, 2000);
        } else if (responseData.payment_url) {
            // Redirect to payment URL
            window.location.href = responseData.payment_url;
        } else {
            // Payment pending
            console.log('⏳ PayPal Payment Pending');
            showToast('info', 'Payment is processing...', 'Your payment is being processed');
            setTimeout(() => {
                if (responseData.order_number) {
                    window.location.href = '{{ route("checkout.success", ":order_number") }}'.replace(':order_number', responseData.order_number);
                } else {
                    window.location.href = '{{ route("checkout.index") }}';
                }
            }, 2000);
        }
    };

    const handleStripeResponse = async (responseData) => {
        console.log('🔄 Handling Stripe response:', responseData);
        
        if (responseData.payment_completed === true || responseData.payment_status === 'paid') {
            console.log('✅ Stripe Payment Completed');
            showToast('success', 'Payment Successful!', 'Your payment has been processed successfully');
            
            // Clear cart from localStorage
            localStorage.removeItem('cart');
            
            // Redirect to success page
            setTimeout(() => {
                if (responseData.order_number) {
                    window.location.href = '{{ route("checkout.success", ":order_number") }}'.replace(':order_number', responseData.order_number);
                } else {
                    window.location.href = '{{ route("home") }}';
                }
            }, 2000);
        } else if (responseData.payment_url) {
            // Redirect to payment URL
            window.location.href = responseData.payment_url;
        } else {
            // Payment pending
            console.log('⏳ Stripe Payment Pending');
            showToast('info', 'Payment is processing...', 'Your payment is being processed');
            setTimeout(() => {
                if (responseData.order_number) {
                    window.location.href = '{{ route("checkout.success", ":order_number") }}'.replace(':order_number', responseData.order_number);
                } else {
                    window.location.href = '{{ route("checkout.index") }}';
                }
            }, 2000);
        }
    };

    const handleGenericSuccess = async (responseData) => {
        console.log('🔄 Handling generic success:', responseData);
        
        if (responseData.payment_completed === true || responseData.payment_status === 'paid') {
            console.log('✅ Generic Payment Completed');
            showToast('success', 'Payment Successful!', 'Your payment has been processed successfully');
            
            // Clear cart from localStorage
            localStorage.removeItem('cart');
            
            // Redirect to success page
            setTimeout(() => {
                if (responseData.order_number) {
                    window.location.href = '{{ route("checkout.success", ":order_number") }}'.replace(':order_number', responseData.order_number);
                } else {
                    window.location.href = '{{ route("home") }}';
                }
            }, 2000);
        } else if (responseData.payment_url) {
            // Redirect to payment URL
            window.location.href = responseData.payment_url;
        } else {
            // Payment pending
            console.log('⏳ Generic Payment Pending');
            showToast('info', 'Payment is processing...', 'Your payment is being processed');
            setTimeout(() => {
                if (responseData.order_number) {
                    window.location.href = '{{ route("checkout.success", ":order_number") }}'.replace(':order_number', responseData.order_number);
                } else {
                    window.location.href = '{{ route("checkout.index") }}';
                }
            }, 2000);
        }
    };

    // Initialize PayPal buttons
    const initializePayPalButtons = async () => {
        try {
            console.log('🚀 Initializing PayPal buttons...');
            
            if (paypalButtonsInitialized) {
                console.log('⚠️ PayPal buttons already initialized');
                return;
            }
            
            if (!window.paypal) {
                console.error('❌ PayPal SDK not loaded. Cannot initialize buttons.');
                showToast('error', 'PayPal Error', 'PayPal SDK is not loaded. Please refresh the page and try again.');
                return;
            }
            
            const paypalButtonContainer = document.getElementById('paypal-button');
            if (!paypalButtonContainer) {
                console.error('❌ PayPal button container not found');
                return;
            }
            
            // Clear any existing content
            paypalButtonContainer.innerHTML = '';
            
            // Calculate total amount
            const subtotal = parseFloat('{{ $subtotal }}');
            const discount = parseFloat(CHECKOUT_DISCOUNT || 0);
            const tax = parseFloat('{{ $taxAmount }}');
            const tip = parseFloat(document.getElementById('tip_amount')?.value || 0);
            const total = subtotal - discount + tax + tip;
            
            // Get order data from form
            const checkoutForm = document.getElementById('checkout-form');
            const orderData = {
                customer_name: checkoutForm.querySelector('[name="customer_name"]')?.value?.trim() || '',
                customer_email: checkoutForm.querySelector('[name="customer_email"]')?.value?.trim() || '',
                customer_phone: checkoutForm.querySelector('[name="customer_phone"]')?.value?.trim() || '',
                shipping_address: checkoutForm.querySelector('[name="shipping_address"]')?.value?.trim() || '',
                city: checkoutForm.querySelector('[name="city"]')?.value?.trim() || '',
                state: checkoutForm.querySelector('[name="state"]')?.value?.trim() || '',
                postal_code: checkoutForm.querySelector('[name="postal_code"]')?.value?.trim() || '',
                country: checkoutForm.querySelector('[name="country"]')?.value?.trim() || '',
                notes: checkoutForm.querySelector('[name="notes"]')?.value?.trim() || '',
            };
            
            // Render PayPal buttons
            window.paypal.Buttons({
                style: {
                    color: 'blue',
                    shape: 'rect',
                    label: 'paypal',
                    height: 48
                },
                
                // Set up the transaction
                createOrder: async function(data, actions) {
                    console.log('📝 Creating PayPal order for SDK...');
                    
                    try {
                        // Validate form data before creating PayPal order
                        const checkoutForm = document.getElementById('checkout-form');
                        const requiredFields = {
                            customer_name: checkoutForm.querySelector('[name="customer_name"]')?.value?.trim() || '',
                            customer_email: checkoutForm.querySelector('[name="customer_email"]')?.value?.trim() || '',
                            shipping_address: checkoutForm.querySelector('[name="shipping_address"]')?.value?.trim() || '',
                            city: checkoutForm.querySelector('[name="city"]')?.value?.trim() || '',
                            postal_code: checkoutForm.querySelector('[name="postal_code"]')?.value?.trim() || '',
                            country: checkoutForm.querySelector('[name="country"]')?.value?.trim() || ''
                        };
                        
                        // Check for missing required fields
                        const missingFields = [];
                        Object.entries(requiredFields).forEach(([field, value]) => {
                            if (!value) {
                                missingFields.push(field.replace('_', ' '));
                            }
                        });
                        
                        if (missingFields.length > 0) {
                            showToast('error', 'Missing information', 'Please fill in all information: ' + missingFields.join(', '));
                            throw new Error(`Missing required fields: ${missingFields.join(', ')}`);
                        }
                        
                        // Validate email format
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(requiredFields.customer_email)) {
                            showToast('error', 'Invalid Email', 'Please enter a valid email address');
                            throw new Error('Invalid email format');
                        }
                        
                        console.log('✅ Form validation passed, creating PayPal order...');
                        
                        // Calculate total amount for PayPal
                        // Subtotal is already in current currency
                        const subtotal = parseFloat(CHECKOUT_CONVERTED_SUBTOTAL || CHECKOUT_BASE_SUBTOTAL || '{{ $subtotal }}');
                        const comboDiscount = parseFloat(CHECKOUT_BULK_DISCOUNT || 0);
                        const discount = parseFloat(CHECKOUT_DISCOUNT || 0);
                        const tax = parseFloat('{{ $taxAmount }}');
                        const tip = parseFloat(document.getElementById('tip_amount')?.value || 0);
                        
                        // Get shipping cost from display element
                        const shippingCostEl = document.getElementById('checkout-shipping-cost');
                        const shippingCost = shippingCostEl ? parseFloat(shippingCostEl.textContent.replace(/[^0-9.-]/g, '')) || 0 : 0;
                        
                        // Convert tip from USD to current currency
                        const convertedTip = convertFromUSD(tip, currentCurrency);
                        const total = subtotal - comboDiscount - discount + tax + convertedTip + shippingCost;
                        
                        // Build description from product SKUs
                        const skuList = [];
                        if (typeof checkoutItemsData !== 'undefined' && Array.isArray(checkoutItemsData)) {
                            checkoutItemsData.forEach(function(item) {
                                if (item.product && item.product.sku) {
                                    // Add SKU with quantity if > 1
                                    const qty = item.quantity || 1;
                                    if (qty > 1) {
                                        skuList.push(item.product.sku + ' x' + qty);
                                    } else {
                                        skuList.push(item.product.sku);
                                    }
                                }
                            });
                        }
                        const description = skuList.length > 0 
                            ? skuList.join(', ') 
                            : 'Order from Bluprinter';
                        
                        // Create order on PayPal side using actions.order.create()
                        return actions.order.create({
                            purchase_units: [{
                                amount: {
                                    value: total.toFixed(2),
                                    currency_code: currentCurrency
                                },
                                description: description,
                                custom_id: 'order-' + Date.now()
                            }]
                        });
                        
                    } catch (error) {
                        console.error('❌ Error creating PayPal order:', error);
                        if (!error.message.includes('Missing required fields') && !error.message.includes('Invalid email')) {
                            showToast('error', 'PayPal Error', 'Failed to create PayPal order: ' + error.message);
                        }
                        throw error;
                    }
                },
                
                // Finalize the transaction
                onApprove: async function(data, actions) {
                    console.log('✅ PayPal payment approved:', data);
                    
                    try {
                        showLoading(true);
                        
                        // Capture the payment details
                        const details = await actions.order.capture();
                        console.log('💰 Payment captured:', details);
                        
                        // Get fresh order data from form at the time of approval
                        const checkoutForm = document.getElementById('checkout-form');
                        const currentOrderData = {
                            customer_name: checkoutForm.querySelector('[name="customer_name"]')?.value?.trim() || '',
                            customer_email: checkoutForm.querySelector('[name="customer_email"]')?.value?.trim() || '',
                            customer_phone: checkoutForm.querySelector('[name="customer_phone"]')?.value?.trim() || '',
                            shipping_address: checkoutForm.querySelector('[name="shipping_address"]')?.value?.trim() || '',
                            city: checkoutForm.querySelector('[name="city"]')?.value?.trim() || '',
                            state: checkoutForm.querySelector('[name="state"]')?.value?.trim() || '',
                            postal_code: checkoutForm.querySelector('[name="postal_code"]')?.value?.trim() || '',
                            country: checkoutForm.querySelector('[name="country"]')?.value?.trim() || '',
                            notes: checkoutForm.querySelector('[name="notes"]')?.value?.trim() || '',
                            tip_amount: parseFloat(document.getElementById('tip_amount')?.value || 0),
                            shipping_cost: parseFloat(document.getElementById('shipping_cost')?.value || 0),
                            shipping_zone_id: document.getElementById('shipping_zone_id')?.value || '',
                            retention_free_shipping: parseInt(document.getElementById('retention_free_shipping')?.value || '0', 10) ? 1 : 0,
                            retention_popup_source: document.getElementById('retention_popup_source')?.value || '',
                        };
                        
                        console.log('📋 Sending order data:', currentOrderData);
                        
                        // Validate required fields before sending
                        const requiredFields = ['customer_name', 'customer_email', 'shipping_address', 'city', 'postal_code', 'country'];
                        const missingFields = requiredFields.filter(field => !currentOrderData[field]);
                        
                        if (missingFields.length > 0) {
                            throw new Error(`Missing required fields: ${missingFields.join(', ')}`);
                        }
                        
                        // Now create order on our server and process with PayPal order ID
                        const response = await fetch('{{ route("checkout.process") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                ...currentOrderData,
                                payment_method: 'paypal',
                                paypal_order_id: data.orderID,
                                paypal_payer_id: data.payerID
                            }),
                            credentials: 'same-origin',
                            mode: 'same-origin'
                        });
                        
                        // Check content type first
                        const contentType = response.headers.get('content-type') || '';
                        const isJson = contentType.includes('application/json');
                        
                        if (!response.ok) {
                            // Try to parse error response
                            let errorMessage = `Order processing failed: ${response.status} ${response.statusText}`;
                            
                            try {
                                if (isJson) {
                                    const errorData = await response.json();
                                    console.error('❌ Server Error:', errorData);
                                    
                                    if (response.status === 422) {
                                        // Validation error
                                        errorMessage = errorData.message || Object.values(errorData.errors || {}).flat().join(', ');
                                    } else {
                                        errorMessage = errorData.message || errorData.error || errorMessage;
                                    }
                                } else {
                                    // HTML response - try to extract error message
                                    const text = await response.text();
                                    console.error('❌ HTML Error Response:', text.substring(0, 500));
                                    errorMessage = 'Server returned an error. Please try again or contact support.';
                                }
                            } catch (parseError) {
                                console.error('❌ Error parsing response:', parseError);
                                errorMessage = `Order processing failed: ${response.status} ${response.statusText}`;
                            }
                            
                            throw new Error(errorMessage);
                        }
                        
                        // Parse successful response
                        if (!isJson) {
                            const text = await response.text();
                            console.error('❌ Non-JSON Response:', text.substring(0, 500));
                            throw new Error('Server returned an unexpected response format. Please try again.');
                        }
                        
                        const responseData = await response.json();
                        console.log('📋 Order processed:', responseData);
                        
                        if (responseData.success) {
                            showToast('success', 'Payment successful!', 'Your payment has been processed successfully');
                            
                            // Redirect to success page
                            setTimeout(() => {
                                // Since the current flow redirects, we'll wait for redirect or handle success
                                if (responseData.order_number) {
                                    window.location.href = '{{ route("checkout.success", ":order_number") }}'.replace(':order_number', responseData.order_number);
                                } else {
                                    window.location.href = '{{ route("checkout.index") }}';
                                }
                            }, 2000);
                        } else {
                            throw new Error(responseData.message || 'Order processing failed');
                        }
                        
                    } catch (error) {
                        console.error('❌ Error processing payment:', error);
                        showToast('error', 'Payment Error', 'Payment processing failed: ' + error.message);
                    } finally {
                        showLoading(false);
                    }
                },
                
                onError: function(err) {
                    console.error('❌ PayPal error:', err);
                    showToast('error', 'PayPal Error', 'An error occurred during payment: ' + (err.message || 'Unknown error'));
                    showLoading(false);
                },
                
                onCancel: function(data) {
                    console.log('⚠️ PayPal payment cancelled:', data);
                    showToast('info', 'Payment Cancelled', 'Payment was cancelled by user');
                    showLoading(false);
                }
                
            }).render('#paypal-button');
            
            paypalButtonsInitialized = true;
            console.log('✅ PayPal buttons initialized successfully');
            
            // Update button state after rendering
            setTimeout(() => updatePayPalButtonState(), 100);
            
        } catch (error) {
            console.error('❌ PayPal buttons initialization error:', error);
            showToast('error', 'PayPal Error', 'Failed to initialize PayPal: ' + error.message);
            
            const paypalButtonContainer = document.getElementById('paypal-button');
            if (paypalButtonContainer) {
                paypalButtonContainer.innerHTML = `
                    <div class="flex items-center justify-center h-full text-red-500">
                        <div class="text-center">
                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm">Failed to load PayPal</p>
                        </div>
                    </div>
                `;
            }
        }
    };
    
    // Function to validate form fields
    const validateCheckoutForm = function() {
        const checkoutForm = document.getElementById('checkout-form');
        if (!checkoutForm) return false;
        
        const requiredFields = {
            customer_name: checkoutForm.querySelector('[name="customer_name"]')?.value?.trim() || '',
            customer_email: checkoutForm.querySelector('[name="customer_email"]')?.value?.trim() || '',
            shipping_address: checkoutForm.querySelector('[name="shipping_address"]')?.value?.trim() || '',
            city: checkoutForm.querySelector('[name="city"]')?.value?.trim() || '',
            postal_code: checkoutForm.querySelector('[name="postal_code"]')?.value?.trim() || '',
            country: checkoutForm.querySelector('[name="country"]')?.value?.trim() || ''
        };
        
        // Check for missing required fields
        const missingFields = [];
        Object.entries(requiredFields).forEach(([field, value]) => {
            if (!value) {
                missingFields.push(field.replace('_', ' '));
            }
        });
        
        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isEmailValid = emailRegex.test(requiredFields.customer_email);
        
        return {
            isValid: missingFields.length === 0 && isEmailValid && requiredFields.customer_email,
            missingFields: missingFields,
            isEmailValid: isEmailValid
        };
    };
    
    // Function to update PayPal button state
    const updatePayPalButtonState = function() {
        const paypalContainer = document.getElementById('paypal-button-container');
        const validation = validateCheckoutForm();
        
        if (paypalContainer) {
            const paypalButtons = paypalContainer.querySelector('#paypal-button');
            
            if (!validation.isValid) {
                // Add disabled styling
                paypalContainer.classList.add('opacity-50', 'pointer-events-none');
                
                // Add warning message if not already present
                let warningMsg = paypalContainer.querySelector('.validation-warning');
                if (!warningMsg) {
                    warningMsg = document.createElement('div');
                    warningMsg.className = 'validation-warning mt-3 p-3 bg-yellow-100 border border-yellow-300 rounded-lg';
                    
                    if (validation.missingFields.length > 0) {
                        warningMsg.innerHTML = `
                            <div class="flex items-center text-yellow-800">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm font-medium">Please fill in all required fields: ${validation.missingFields.join(', ')}</span>
                            </div>
                        `;
                    } else if (!validation.isEmailValid) {
                        warningMsg.innerHTML = `
                            <div class="flex items-center text-yellow-800">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm font-medium">Please enter a valid email address</span>
                            </div>
                        `;
                    }
                    
                    paypalContainer.appendChild(warningMsg);
                }
            } else {
                // Remove disabled styling
                paypalContainer.classList.remove('opacity-50', 'pointer-events-none');
                
                // Remove warning message
                const warningMsg = paypalContainer.querySelector('.validation-warning');
                if (warningMsg) {
                    warningMsg.remove();
                }
            }
        }
    };
    
    // Stripe integration
    let stripeInstance = null;
    let stripeCardElement = null;
    let stripePaymentIntent = null;
    
    // Payment method change handler
    const handlePaymentMethodChange = function() {
        const selectedRadio = document.querySelector('input[name="payment_method"]:checked');
        const paypalContainer = document.getElementById('paypal-button-container');
        const stripeContainer = document.getElementById('stripe-card-container');
        
        // Remove selected class from all payment options
        paymentOptions.forEach(option => {
            option.classList.remove('selected');
        });
        
        // Add selected class to the parent label of checked radio
        if (selectedRadio) {
            const label = selectedRadio.closest('.payment-option');
            if (label) {
                label.classList.add('selected');
            }
        }
        
        console.log('💳 Payment method changed:', selectedRadio ? selectedRadio.value : 'none');

        if (selectedRadio) {
            const paymentType = selectedRadio.value === 'paypal'
                ? 'PayPal'
                : (selectedRadio.value === 'stripe' ? 'Stripe' : selectedRadio.value);
            if (typeof window !== 'undefined' && typeof window.__PRESSONNailTrackAddPaymentInfo === 'function') {
                window.__PRESSONNailTrackAddPaymentInfo(paymentType);
            }
        }
        
        if (selectedRadio && selectedRadio.value === 'paypal') {
            console.log('🔧 PayPal selected - initializing buttons');
            if (stripeContainer) {
                stripeContainer.classList.add('hidden');
            }
            if (paypalContainer) {
                paypalContainer.classList.remove('hidden');
                if (!paypalButtonsInitialized && window.paypal) {
                    initializePayPalButtons();
                }
                setTimeout(() => updatePayPalButtonState(), 100);
            }
        } else if (selectedRadio && selectedRadio.value === 'stripe') {
            console.log('🔧 Stripe selected - initializing card element');
            if (paypalContainer) {
                paypalContainer.classList.add('hidden');
            }
            if (stripeContainer) {
                stripeContainer.classList.remove('hidden');
                if (!stripeCardElement) {
                    initializeStripeElements();
                }
            }
        } else {
            console.log('🔧 Hiding all payment methods...');
            if (paypalContainer) {
                paypalContainer.classList.add('hidden');
            }
            if (stripeContainer) {
                stripeContainer.classList.add('hidden');
            }
        }
    };

    // Listen for radio button changes
    document.addEventListener('change', function(e) {
        if (e.target.name === 'payment_method' && e.target.type === 'radio') {
            console.log('💳 Radio button changed:', e.target.value, 'checked:', e.target.checked);
            handlePaymentMethodChange();
        }
    });
    
    // Set default selection to Stripe
    const defaultPaymentRadio = document.querySelector('input[value="stripe"]');
    if (defaultPaymentRadio) {
        console.log('🎯 Setting default payment method to Stripe');
        // Ensure it's checked
        defaultPaymentRadio.checked = true;
        
        // Handle payment method change (will prepare the Stripe UI)
        handlePaymentMethodChange();
        
        console.log('✅ Stripe is now the default payment method');
    }
    
    // Initialize PayPal SDK when ready
    const initializePayPalSDK = () => {
        paypalSDKLoadAttempts++;
        
        if (window.paypal) {
            console.log('✅ PayPal SDK loaded successfully');
            return true;
        } else if (paypalSDKLoadAttempts >= MAX_PAYPAL_SDK_ATTEMPTS) {
            console.error('❌ PayPal SDK failed to load after', paypalSDKLoadAttempts, 'attempts');
            console.log('🔍 Checking PayPal configuration...');
            
            // Check if client ID is configured
            const paypalScript = document.querySelector('script[src*="paypal.com/sdk/js"]');
            if (paypalScript && paypalScript.src.includes('client-id=')) {
                const clientIdMatch = paypalScript.src.match(/client-id=([^&]+)/);
                if (clientIdMatch && clientIdMatch[1] && !clientIdMatch[1].includes('null') && clientIdMatch[1] !== '') {
                    console.log('⚠️ PayPal SDK script loaded but window.paypal not available. This might be a network issue.');
                } else {
                    console.error('❌ PayPal Client ID not configured properly');
                }
            } else {
                console.error('❌ PayPal script not found or malformed');
            }
            return false;
        } else {
            console.log('⏳ Waiting for PayPal SDK to load...', `(${paypalSDKLoadAttempts}/${MAX_PAYPAL_SDK_ATTEMPTS})`);
            setTimeout(initializePayPalSDK, 100);
            return false;
        }
    };
    
    // Initialize Stripe Elements
    async function initializeStripeElements() {
        try {
            console.log('🚀 Initializing Stripe Elements...');
            
            if (!window.Stripe) {
                throw new Error('Stripe.js not loaded');
            }
            
            // Initialize Stripe with publishable key
            const stripeKey = '{{ config("services.stripe.key") }}';
            if (!stripeKey) {
                throw new Error('Stripe publishable key not configured');
            }
            
            stripeInstance = window.Stripe(stripeKey);
            
            // Create Elements instance
            const elements = stripeInstance.elements();
            
            // Create card element with styling
            stripeCardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#32325d',
                        fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                        '::placeholder': {
                            color: '#aab7c4',
                        },
                        iconColor: '#666EE8',
                    },
                    invalid: {
                        color: '#fa755a',
                        iconColor: '#fa755a',
                    },
                },
                hidePostalCode: true,
            });
            
            // Mount the card element
            stripeCardElement.mount('#stripe-card-element');
            
            // Handle real-time validation errors
            stripeCardElement.on('change', function(event) {
                const displayError = document.getElementById('stripe-card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });
            
            console.log('✅ Stripe Elements initialized successfully');
            
        } catch (error) {
            console.error('❌ Stripe Elements initialization error:', error);
            showToast('error', 'Payment Error', 'Failed to initialize Stripe: ' + error.message);
        }
    }
    
    // Handle Stripe Payment
    const handleStripePayment = async () => {
        try {
            console.log('🚀 Processing Stripe payment...');
            showLoading(true);
            trackTikTokAddPayment('stripe');
            
            if (!stripeInstance || !stripeCardElement) {
                throw new Error('Stripe not initialized. Please refresh and try again.');
            }
            
            // Calculate total amount
            // Subtotal is already in current currency
            const subtotal = parseFloat(CHECKOUT_CONVERTED_SUBTOTAL || CHECKOUT_BASE_SUBTOTAL || '{{ $subtotal }}');
            const comboDiscount = parseFloat(CHECKOUT_BULK_DISCOUNT || 0);
            const discount = parseFloat(CHECKOUT_DISCOUNT || 0);
            const tax = parseFloat('{{ $taxAmount }}');
            const tip = parseFloat(document.getElementById('tip_amount')?.value || 0);
            
            // Get shipping cost from display element
            const shippingCostEl = document.getElementById('checkout-shipping-cost');
            const shippingCost = shippingCostEl ? parseFloat(shippingCostEl.textContent.replace(/[^0-9.-]/g, '')) || 0 : 0;
            
            // Convert tip from USD to current currency
            const convertedTip = convertFromUSD(tip, currentCurrency);
            const total = subtotal - comboDiscount - discount + tax + convertedTip + shippingCost;
            
            // Create Payment Intent on server
            console.log('📝 Creating Payment Intent...');
            const intentResponse = await fetch('{{ route("payment.stripe.create-intent") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    amount: total,
                    currency: currentCurrency.toLowerCase(),
                }),
            });
            
            const intentData = await intentResponse.json();
            
            if (!intentData.success) {
                throw new Error(intentData.message || 'Failed to create payment intent');
            }
            
            const clientSecret = intentData.clientSecret;
            console.log('✅ Payment Intent created');
            
            // Get billing details from form
            const checkoutForm = document.getElementById('checkout-form');
            const billingDetails = {
                name: checkoutForm.querySelector('[name="customer_name"]')?.value?.trim() || '',
                email: checkoutForm.querySelector('[name="customer_email"]')?.value?.trim() || '',
                phone: checkoutForm.querySelector('[name="customer_phone"]')?.value?.trim() || '',
                address: {
                    line1: checkoutForm.querySelector('[name="shipping_address"]')?.value?.trim() || '',
                    city: checkoutForm.querySelector('[name="city"]')?.value?.trim() || '',
                    state: checkoutForm.querySelector('[name="state"]')?.value?.trim() || '',
                    postal_code: checkoutForm.querySelector('[name="postal_code"]')?.value?.trim() || '',
                    country: checkoutForm.querySelector('[name="country"]')?.value?.trim() || '',
                },
            };
            
            // Confirm the payment with Stripe
            console.log('💳 Confirming payment with Stripe...');
            const { error, paymentIntent } = await stripeInstance.confirmCardPayment(clientSecret, {
                payment_method: {
                    card: stripeCardElement,
                    billing_details: billingDetails,
                },
            });
            
            if (error) {
                throw new Error(error.message);
            }
            
            if (paymentIntent.status === 'succeeded') {
                console.log('✅ Payment succeeded:', paymentIntent.id);
                
                // Process order on server
                await processStripeOrder(paymentIntent.id);
            } else {
                throw new Error('Payment was not successful. Status: ' + paymentIntent.status);
            }
            
        } catch (error) {
            console.error('❌ Stripe payment error:', error);
            showToast('error', 'Payment Error', error.message);
            showLoading(false);
        }
    };
    
    // Process Stripe Order
    const processStripeOrder = async (paymentIntentId) => {
        try {
            console.log('📦 Creating order with payment intent:', paymentIntentId);
            
            const checkoutForm = document.getElementById('checkout-form');
            const orderData = {
                payment_intent_id: paymentIntentId,
                customer_name: checkoutForm.querySelector('[name="customer_name"]')?.value?.trim() || '',
                customer_email: checkoutForm.querySelector('[name="customer_email"]')?.value?.trim() || '',
                customer_phone: checkoutForm.querySelector('[name="customer_phone"]')?.value?.trim() || '',
                shipping_address: checkoutForm.querySelector('[name="shipping_address"]')?.value?.trim() || '',
                city: checkoutForm.querySelector('[name="city"]')?.value?.trim() || '',
                state: checkoutForm.querySelector('[name="state"]')?.value?.trim() || '',
                postal_code: checkoutForm.querySelector('[name="postal_code"]')?.value?.trim() || '',
                country: checkoutForm.querySelector('[name="country"]')?.value?.trim() || '',
                notes: checkoutForm.querySelector('[name="notes"]')?.value?.trim() || '',
                tip_amount: parseFloat(document.getElementById('tip_amount')?.value || 0),
                shipping_cost: parseFloat(document.getElementById('shipping_cost')?.value || 0),
                shipping_zone_id: document.getElementById('shipping_zone_id')?.value || '',
                retention_free_shipping: parseInt(document.getElementById('retention_free_shipping')?.value || '0', 10) ? 1 : 0,
                retention_popup_source: document.getElementById('retention_popup_source')?.value || '',
                payment_method: 'stripe'
            };
            
            // Use unified order processing
            await processUnifiedOrder(orderData);
            
        } catch (error) {
            console.error('❌ Order processing error:', error);
            showToast('error', 'Order Error', error.message);
            showLoading(false);
        }
    };
    
    // Start checking for PayPal SDK
    initializePayPalSDK();
    
    // Form submission handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Check if payment method is selected
        const selectedPaymentRadio = form.querySelector('input[name="payment_method"]:checked');
        if (!selectedPaymentRadio) {
            showToast('error', 'Payment Error', 'Please select a payment method');
            return;
        }
        
        const selectedPaymentMethod = selectedPaymentRadio.value;
        console.log('💳 Selected payment method:', selectedPaymentMethod);
        
        // Validate form data before proceeding
        const requiredFields = ['customer_name', 'customer_email', 'shipping_address', 'city', 'postal_code', 'country'];
        const missingFields = [];
        
        requiredFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (!field || !field.value.trim()) {
                missingFields.push(fieldName);
            }
        });
        
        if (missingFields.length > 0) {
            showToast('error', 'Form Error', 'Please fill in all required fields: ' + missingFields.join(', '));
            return;
        }
        
        // Validate minimum order amount for Stripe
        if (selectedPaymentMethod === 'stripe') {
            const subtotal = parseFloat(CHECKOUT_CONVERTED_SUBTOTAL || '{{ $subtotal }}');
            const comboDiscount = parseFloat(CHECKOUT_BULK_DISCOUNT || 0);
            const discount = parseFloat(CHECKOUT_DISCOUNT || 0);
            const shippingCostEl = document.getElementById('checkout-shipping-cost');
            const shippingCost = shippingCostEl ? parseFloat(shippingCostEl.textContent.replace(/[^0-9.-]/g, '')) || 0 : 0;
            const tip = parseFloat(document.getElementById('tip_amount')?.value || 0);
            const convertedTip = typeof convertFromUSD === 'function' ? convertFromUSD(tip, CHECKOUT_CURRENCY) : tip;
            const total = subtotal - comboDiscount - discount + shippingCost + convertedTip;
            
            if (total < 0.5) {
                showToast('error', 'Minimum Order Amount', 
                    'Stripe requires a minimum order of $0.50. Your order total is $' + total.toFixed(2) + 
                    '. Please use PayPal for smaller orders.');
                return;
            }
        }
        
        if (selectedPaymentMethod === 'paypal') {
            // PayPal is handled by the SDK, just show info
            showToast('info', 'PayPal Checkout', 'Please use the PayPal button below to complete your payment');
            return;
        } else if (selectedPaymentMethod === 'stripe') {
            handleStripePayment();
        } else {
            handleRegularPayment();
        }
    });
    
    // Handle regular payment (PayPal) - improved for mobile
    const handleRegularPayment = async () => {
        try {
            console.log('🔄 Processing PayPal payment...');
            showLoading(true);
            trackTikTokAddPayment('paypal');
            
            // Double-check form validation before submitting
            const requiredFields = ['customer_name', 'customer_email', 'shipping_address', 'city', 'postal_code', 'country'];
            const missingFields = [];
            
            requiredFields.forEach(fieldName => {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (!field || !field.value.trim()) {
                    missingFields.push(fieldName);
                }
            });
            
            if (missingFields.length > 0) {
                showLoading(false);
                showToast('error', 'Form Error', 'Please fill in all required fields: ' + missingFields.join(', '));
                return;
            }
            
            // Submit form after validation
            console.log('✅ Form validated, submitting to PayPal...');
            setTimeout(() => {
                form.submit();
            }, 300);
            
        } catch (error) {
            console.error('❌ PayPal payment error:', error);
            showLoading(false);
            showToast('error', 'Payment Error', 'Failed to process payment: ' + error.message);
        }
    };
    
    // Add event listeners for form field changes to update PayPal button state
    const formFieldsToWatch = [
        'customer_name', 'customer_email', 'customer_phone',
        'shipping_address', 'city', 'state', 'postal_code', 'country'
    ];
    
    formFieldsToWatch.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.addEventListener('input', updatePayPalButtonState);
            field.addEventListener('change', updatePayPalButtonState);
            field.addEventListener('blur', updatePayPalButtonState);
        }
    });
    
    // Initial validation when page loads
    setTimeout(() => {
        updatePayPalButtonState();
        initializeTipSelection();
        updateTipButtons();
        updateExchangeRateDisplay();
        
    }, 500);
    
    // Initialize tip selection on page load
    function initializeTipSelection() {
        // Check if there's a default tip amount and select it
        const tipAmount = parseFloat('{{ $tipAmount ?? 0 }}');
        if (tipAmount > 0) {
            const tipButton = document.querySelector(`[data-tip="${tipAmount}"]`);
            if (tipButton) {
                tipButton.classList.add('selected');
                selectedTipAmount = tipAmount;
                updateTotal();
            }
        }
        
        // Debug: Log all tip options (remove in production)
        // console.log('Available tip options:', document.querySelectorAll('.tip-option'));
    }
    
    // Tip selection functionality
    let selectedTipAmount = 0;
    
    window.selectTip = function(amount) {
        console.log('Selecting tip:', amount);
        
        // Remove selected class from all tip options
        document.querySelectorAll('.tip-option').forEach(btn => {
            btn.classList.remove('selected');
            
            // Clear inline styles
            btn.style.border = '';
            btn.style.backgroundColor = '';
            btn.style.boxShadow = '';
            btn.style.transform = '';
            
            console.log('Removed selected from:', btn);
        });
        
        // Add selected class to clicked button
        const clickedButton = event.target.closest('.tip-option');
        if (clickedButton) {
            clickedButton.classList.add('selected');
            
            // Force inline styles as backup (primary pink)
            clickedButton.style.border = '3px solid #f0427c';
            clickedButton.style.backgroundColor = '#fce7ef';
            clickedButton.style.boxShadow = '0 0 0 4px rgba(240, 66, 124, 0.3)';
            clickedButton.style.transform = 'translateY(-2px)';
            
            console.log('Added selected to:', clickedButton);
        }
        
        if (amount === 'custom') {
            // Show custom tip input
            const customInput = document.getElementById('custom-tip-amount');
            customInput.classList.remove('hidden');
            customInput.focus();
            selectedTipAmount = 0;
        } else {
            // Hide custom tip input
            const customInput = document.getElementById('custom-tip-amount');
            customInput.classList.add('hidden');
            selectedTipAmount = parseFloat(amount) || 0;
            updateTotal();
        }
    };
    
    window.updateCustomTip = function(value) {
        // User enters tip in current currency, convert back to USD for storage
        const tipInCurrentCurrency = parseFloat(value) || 0;
        
        // Convert from current currency to USD for storage
        // If currency is USD, no conversion needed
        // Otherwise, divide by currency rate
        if (tipInCurrentCurrency > 0) {
            if (currentCurrency === 'USD') {
                selectedTipAmount = tipInCurrentCurrency;
            } else {
                // Convert from current currency to USD
                selectedTipAmount = currentCurrencyRate > 0 
                    ? tipInCurrentCurrency / currentCurrencyRate 
                    : tipInCurrentCurrency;
            }
        } else {
            selectedTipAmount = 0;
        }
        
        // Add selected class to custom tip button when user types
        if (tipInCurrentCurrency > 0) {
            document.querySelectorAll('.tip-option').forEach(btn => {
                btn.classList.remove('selected');
                
                // Clear inline styles
                btn.style.border = '';
                btn.style.backgroundColor = '';
                btn.style.boxShadow = '';
                btn.style.transform = '';
            });
            
            const customButton = document.querySelector('[data-tip="custom"]');
            customButton.classList.add('selected');
            
            // Force inline styles for custom button (primary pink)
            customButton.style.border = '3px solid #f0427c';
            customButton.style.backgroundColor = '#fce7ef';
            customButton.style.boxShadow = '0 0 0 4px rgba(240, 66, 124, 0.3)';
            customButton.style.transform = 'translateY(-2px)';
        }
        
        updateTotal();
    };
    
    // Update tip buttons with current currency
    function updateTipButtons() {
        const tipAmounts = [5, 3, 3.15];
        tipAmounts.forEach(amount => {
            const button = document.querySelector(`[data-tip="${amount}"]`);
            if (button) {
                // Find span with class tip-amount-{amount} (escape dots for CSS selector)
                const className = `tip-amount-${amount}`.replace(/\./g, '\\.');
                let span = button.querySelector(`.${className}`);
                if (!span) {
                    // Fallback: find any span with text-sm class inside button
                    span = button.querySelector('span.text-sm');
                }
                if (span && amount !== 0) {
                    const convertedAmount = convertFromUSD(amount, currentCurrency);
                    span.textContent = formatPrice(convertedAmount, currentCurrency);
                }
            }
        });
    }
    
    // Update exchange rate display
    function updateExchangeRateDisplay() {
        const exchangeRateDisplay = document.getElementById('exchange-rate-display');
        const exchangeRateValue = document.getElementById('exchange-rate-value');
        
        if (currentCurrency !== 'USD' && currentCurrencyRate) {
            if (exchangeRateDisplay) {
                exchangeRateDisplay.style.display = 'block';
            }
            if (exchangeRateValue) {
                exchangeRateValue.textContent = `1 USD = ${currentCurrencyRate.toFixed(4)} ${currentCurrency}`;
            }
        } else {
            if (exchangeRateDisplay) {
                exchangeRateDisplay.style.display = 'none';
            }
        }
    }
    
    function updateTotal() {
        // Subtotal is already in current currency, no need to convert
        const subtotal = parseFloat(CHECKOUT_CONVERTED_SUBTOTAL || CHECKOUT_BASE_SUBTOTAL || '{{ $subtotal }}');
        const comboDiscount = parseFloat(CHECKOUT_BULK_DISCOUNT || 0);
        const discount = parseFloat(CHECKOUT_DISCOUNT || 0);
        const tip = selectedTipAmount;
        
        // Convert tip from USD to current currency
        const convertedTip = convertFromUSD(tip, currentCurrency);
        
        // Get shipping cost - read from the display element which should have been updated
        const shippingCostEl = document.getElementById('checkout-shipping-cost');
        let shippingCost = 0;
        if (shippingCostEl) {
            // Extract numeric value from text (handles currency symbols)
            const shippingText = shippingCostEl.textContent.trim();
            shippingCost = parseFloat(shippingText.replace(/[^0-9.-]/g, '')) || 0;
            console.log('💰 updateTotal - Shipping cost from display:', shippingCost, 'text:', shippingText);
        }
        
        const total = subtotal - comboDiscount - discount + convertedTip + shippingCost;
        
        console.log('💰 updateTotal calculation:', {
            subtotal: subtotal,
            tip: convertedTip,
            shippingCost: shippingCost,
            total: total
        });
        
        // Update tip line visibility
        const tipLine = document.getElementById('tip-line');
        const tipAmountDisplay = document.querySelector('.tip-amount-display');
        const totalDisplay = document.getElementById('checkout-total') || document.querySelector('.total-display');
        
        if (tip > 0) {
            if (tipLine) tipLine.style.display = 'flex';
            if (tipAmountDisplay) tipAmountDisplay.textContent = formatPrice(convertedTip, currentCurrency);
        } else {
            if (tipLine) tipLine.style.display = 'none';
        }
        
        // Update total
        if (totalDisplay) {
            totalDisplay.textContent = formatPrice(total, currentCurrency);
            console.log('✅ Total updated to:', formatPrice(total, currentCurrency));
        }
        
        // Store tip amount for form submission (in USD)
        const tipInput = document.getElementById('tip_amount');
        if (tipInput) {
            tipInput.value = tip;
        }
    }
    
    // Checkout shipping calculation functions - define in DOMContentLoaded
    // Get products with categories - will be initialized after checkoutItemsData is available
    let checkoutProducts = @json($products);
    
    /**
     * Get base subtotal for shipping calculation
     */
    function getCheckoutBaseSubtotal() {
        // Use CHECKOUT_BASE_SUBTOTAL if available
        if (typeof CHECKOUT_BASE_SUBTOTAL !== 'undefined' && CHECKOUT_BASE_SUBTOTAL > 0) {
            return CHECKOUT_BASE_SUBTOTAL;
        }
        
        // Fallback: calculate from products
        let baseSubtotal = 0;
        checkoutProducts.forEach(item => {
            const lineTotal = parseFloat(item.total || (item.cart_item && item.cart_item.price ? item.cart_item.price * (item.quantity || 1) : 0) || 0);
            let baseLineTotal = CHECKOUT_CURRENCY !== 'USD' && CHECKOUT_CURRENCY_RATE > 0 
                ? lineTotal / CHECKOUT_CURRENCY_RATE 
                : lineTotal;
            baseSubtotal += baseLineTotal;
        });
        
        return baseSubtotal;
    }
    
    /**
     * Get cart items data for shipping calculation
     */
    function getCheckoutCartItems() {
        return checkoutProducts.map(item => {
            const product = item.product || {};
            const cartItem = item.cart_item || {};
            
            // Get categories from product - handle different data structures
            let categories = [];
            if (product.categories) {
                if (Array.isArray(product.categories)) {
                    categories = product.categories;
                } else if (typeof product.categories === 'object') {
                    // If it's an object, try to get values
                    categories = Object.values(product.categories);
                }
            }
            
            return {
                id: cartItem.id || item.id,
                quantity: cartItem.quantity || item.quantity || 1,
                price: parseFloat(cartItem.price || item.total || 0),
                product: {
                    id: product.id,
                    categories: categories,
                },
                customizations: cartItem.customizations || {}
            };
        });
    }
    
    /**
     * Calculate shipping cost for checkout items
     */
    function calculateCheckoutShippingCost(zoneId = null) {
        const cartItems = getCheckoutCartItems();
        const baseSubtotal = getCheckoutBaseSubtotal();
        
        console.log('🚚 calculateCheckoutShippingCost called with zoneId:', zoneId, 'type:', typeof zoneId);
        console.log('📦 Cart items:', cartItems.length);
        console.log('💰 Base subtotal:', baseSubtotal);
        
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
        
        // Filter rates by zone if zoneId is provided
        // Priority: Current domain rates > Other domain rates (all domains are considered)
        // When zoneId is null (no country selected), use all rates to allow default rate fallback
        let availableRates = SHIPPING_RATES;
        let domainSpecificRates = [];
        let generalDomainRates = [];
        let currentZoneName = null; // Initialize zone name variable for use throughout function
        
        console.log('📊 Total shipping rates available:', SHIPPING_RATES.length);
        
        if (zoneId !== null && zoneId !== undefined && zoneId !== '') {
            // Extract zone_id from value if format is "zone_id:country_code"
            let actualZoneId = zoneId;
            if (typeof zoneId === 'string' && zoneId.includes(':')) {
                actualZoneId = zoneId.split(':')[0];
                console.log('📝 Extracted zoneId from format "zone_id:country_code":', actualZoneId);
            }
            
            // Parse to integer if it's a numeric string (but not if it's a general zone)
            if (typeof actualZoneId === 'string' && !actualZoneId.startsWith('general_') && !isNaN(actualZoneId)) {
                actualZoneId = parseInt(actualZoneId);
                console.log('📝 Parsed zoneId to integer:', actualZoneId);
            }
            
            console.log('📍 Using actualZoneId for calculation:', actualZoneId, 'type:', typeof actualZoneId);
            
            // Determine zone name for display (set before filtering rates)
            if (typeof actualZoneId === 'string' && actualZoneId.startsWith('general_')) {
                // Extract zone name from zoneId (e.g., 'general_euro' -> 'Euro')
                currentZoneName = actualZoneId.replace('general_', '').split('_').map(word => 
                    word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
                ).join(' ');
            } else {
                // Regular zone: get name from SHIPPING_ZONES or SHIPPING_ZONES_WITH_COUNTRIES
                const parsedZoneId = typeof actualZoneId === 'string' && !isNaN(actualZoneId) 
                    ? parseInt(actualZoneId) 
                    : actualZoneId;
                let zoneInfo = SHIPPING_ZONES.find(z => z.id === parsedZoneId);
                if (!zoneInfo && SHIPPING_ZONES_WITH_COUNTRIES) {
                    zoneInfo = SHIPPING_ZONES_WITH_COUNTRIES.find(z => z.id === parsedZoneId);
                }
                currentZoneName = zoneInfo ? zoneInfo.name : null;
            }
            
            // Check if it's a general domain zone (starts with 'general_')
            if (typeof actualZoneId === 'string' && actualZoneId.startsWith('general_')) {
                // Extract zone name from zoneId (e.g., 'general_euro' -> 'Euro')
                const zoneName = actualZoneId.replace('general_', '').split('_').map(word => 
                    word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
                ).join(' ');
                
                // Filter rates with matching zone_name (all domains)
                const normalizeZoneName = (name) => name ? name.toLowerCase().trim().replace(/\s+/g, ' ') : '';
                const normalizedZoneName = normalizeZoneName(zoneName);
                
                const matchingRates = SHIPPING_RATES.filter(r => {
                    if (!r.zone_name) return false;
                    const normalizedRateZoneName = normalizeZoneName(r.zone_name);
                    return normalizedRateZoneName === normalizedZoneName || 
                           normalizedRateZoneName.includes(normalizedZoneName) ||
                           normalizedZoneName.includes(normalizedRateZoneName);
                });
                
                availableRates = matchingRates;
                domainSpecificRates = matchingRates;
                generalDomainRates = [];
                
                // If no rates found with matching zone_name, fallback to all general domain rates (zone_id = null)
            if (availableRates.length === 0) {
                    const allGeneralRates = SHIPPING_RATES.filter(r => r.zone_id === null);
                    if (allGeneralRates.length > 0) {
                        availableRates = allGeneralRates;
                        domainSpecificRates = allGeneralRates;
                        generalDomainRates = [];
                    }
                }
            } else {
                // Regular zone: filter by zone_id
                const parsedZoneId = typeof actualZoneId === 'string' && !isNaN(actualZoneId) 
                    ? parseInt(actualZoneId) 
                    : actualZoneId;
                
                // Get zone name from SHIPPING_ZONES or SHIPPING_ZONES_WITH_COUNTRIES for matching by name
                let zoneInfo = SHIPPING_ZONES.find(z => z.id === parsedZoneId);
                if (!zoneInfo && SHIPPING_ZONES_WITH_COUNTRIES) {
                    zoneInfo = SHIPPING_ZONES_WITH_COUNTRIES.find(z => z.id === parsedZoneId);
                }
                const zoneName = zoneInfo ? zoneInfo.name : null;
                
                console.log(`📍 Zone info for zone ${parsedZoneId}:`, {
                    zoneName: zoneName,
                    zoneInfo: zoneInfo
                });
                
                // Helper function to normalize zone names for matching
                const normalizeZoneName = (name) => name ? name.toLowerCase().trim().replace(/\s+/g, ' ') : '';
                const normalizedZoneName = zoneName ? normalizeZoneName(zoneName) : null;
                
                // Find rates by zone_id first, then zone_name
                domainSpecificRates = SHIPPING_RATES.filter(r => {
                    if (r.zone_id === parsedZoneId) return true;
                    if (normalizedZoneName && r.zone_name) {
                        const normalizedRateZoneName = normalizeZoneName(r.zone_name);
                        return normalizedRateZoneName === normalizedZoneName || 
                               normalizedRateZoneName.includes(normalizedZoneName) ||
                               normalizedZoneName.includes(normalizedRateZoneName);
                    }
                    return false;
                });
                generalDomainRates = [];
                
                // Priority: zone-matching rates
                // IMPORTANT: Only include rates that match the zone_id (strict matching)
                // Filter out rates that don't match zone_id (only keep zone_name matches if zone_id matches too)
                const strictDomainSpecificRates = domainSpecificRates.filter(r => {
                    // Keep if zone_id matches (strict)
                    if (r.zone_id === parsedZoneId) return true;
                    // Also keep if zone_name matches AND zone_id is null (general rates for this zone name)
                    if (normalizedZoneName && r.zone_name && r.zone_id === null) {
                        const normalizedRateZoneName = normalizeZoneName(r.zone_name);
                        return normalizedRateZoneName === normalizedZoneName || 
                               normalizedRateZoneName.includes(normalizedZoneName) ||
                               normalizedZoneName.includes(normalizedRateZoneName);
                    }
                    return false;
                });
                
                const strictGeneralDomainRates = generalDomainRates.filter(r => {
                    // Keep if zone_id matches (strict)
                    if (r.zone_id === parsedZoneId) return true;
                    // Also keep if zone_name matches AND zone_id is null (general rates for this zone name)
                    if (normalizedZoneName && r.zone_name && r.zone_id === null) {
                        const normalizedRateZoneName = normalizeZoneName(r.zone_name);
                        return normalizedRateZoneName === normalizedZoneName || 
                               normalizedRateZoneName.includes(normalizedZoneName) ||
                               normalizedZoneName.includes(normalizedRateZoneName);
                    }
                    return false;
                });
                
                console.log(`🔍 Strict filtering - Domain-specific: ${strictDomainSpecificRates.length}, General domain: ${strictGeneralDomainRates.length}`);
                
                // Sort rates to ensure consistent selection when multiple rates exist
                if (strictDomainSpecificRates.length > 0) {
                    // Sort domain-specific rates by first_item_cost (ascending) to prefer lower cost rates
                    availableRates = strictDomainSpecificRates.sort((a, b) => 
                        (a.first_item_cost || 0) - (b.first_item_cost || 0)
                    );
                } else if (strictGeneralDomainRates.length > 0) {
                    // Sort general domain rates by first_item_cost (ascending) to prefer lower cost rates
                    availableRates = strictGeneralDomainRates.sort((a, b) => 
                        (a.first_item_cost || 0) - (b.first_item_cost || 0)
                    );
                } else {
                    // If no strict matches, fallback to original filtered rates
                    if (domainSpecificRates.length > 0) {
                        availableRates = domainSpecificRates.sort((a, b) => 
                            (a.first_item_cost || 0) - (b.first_item_cost || 0)
                        );
                    } else if (generalDomainRates.length > 0) {
                        availableRates = generalDomainRates.sort((a, b) => 
                            (a.first_item_cost || 0) - (b.first_item_cost || 0)
                        );
                    } else {
                        availableRates = [];
                    }
                }
                
                console.log(`📊 Available rates after filtering: ${availableRates.length}`);
                if (availableRates.length > 0) {
                    console.log('   Sorted rates:', availableRates.map(r => ({
                        id: r.id,
                        name: r.name,
                        zone_id: r.zone_id,
                        zone_name: r.zone_name,
                        first_item_cost: r.first_item_cost
                    })));
                }
                
                // If no rates found for this specific zone, include general domain rates (zone_id = null) as fallback
                if (availableRates.length === 0) {
                    console.log(`⚠️ No rates found for zone ${parsedZoneId}, trying general rates (zone_id = null)`);
                    const generalRates = SHIPPING_RATES.filter(r => r.zone_id === null);
                    console.log(`🔍 General rates (zone_id = null):`, generalRates.length);
                    if (generalRates.length > 0) {
                        availableRates = generalRates;
                        domainSpecificRates = generalRates;
                        generalDomainRates = [];
                        console.log(`✅ Using general rates: ${generalRates.length}`);
                    }
                }
            }
            
            // If a specific zone is selected but no rates exist for it, use any available rate as fallback
            if (availableRates.length === 0) {
                // Try to find any rate with matching zone_name as fallback (any domain)
                const parsedZoneId = typeof actualZoneId === 'string' && !isNaN(actualZoneId) 
                    ? parseInt(actualZoneId) 
                    : actualZoneId;
                let zoneName = null;
                if (typeof actualZoneId === 'string' && actualZoneId.startsWith('general_')) {
                    zoneName = actualZoneId.replace('general_', '').split('_').map(word => 
                        word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
                    ).join(' ');
                } else {
                    let zoneInfo = SHIPPING_ZONES.find(z => z.id === parsedZoneId);
                    if (!zoneInfo && SHIPPING_ZONES_WITH_COUNTRIES) {
                        zoneInfo = SHIPPING_ZONES_WITH_COUNTRIES.find(z => z.id === parsedZoneId);
                    }
                    zoneName = zoneInfo ? zoneInfo.name : null;
                }
                
                // Try to find rates with matching zone_name (any domain)
                let fallbackRates = SHIPPING_RATES.filter(r => 
                    r.zone_name && zoneName && 
                    r.zone_name.toLowerCase() === zoneName.toLowerCase()
                );
                
                // If still no rates, use any rate with matching zone_id (any domain)
                if (fallbackRates.length === 0) {
                    fallbackRates = SHIPPING_RATES.filter(r => r.zone_id === parsedZoneId);
                }
                
                // If still no rates, use any available rate (first one)
                if (fallbackRates.length === 0 && SHIPPING_RATES.length > 0) {
                    fallbackRates = [SHIPPING_RATES[0]]; // Use first available rate
                }
                
                if (fallbackRates.length > 0) {
                    availableRates = fallbackRates;
                    domainSpecificRates = fallbackRates;
                    generalDomainRates = [];
                }
            }
        } else {
            domainSpecificRates = SHIPPING_RATES;
            generalDomainRates = [];
            availableRates = SHIPPING_RATES;
        }
        
        // Group items by category
        const itemsByCategory = {};
        let totalItems = 0;
        
        cartItems.forEach(item => {
            const product = item.product || {};
            const categories = product.categories || [];
            
            // Get first category ID (primary category)
            let categoryId = null;
            if (categories && categories.length > 0) {
                const firstCategory = categories[0];
                categoryId = firstCategory.id || (typeof firstCategory === 'object' ? firstCategory.category_id : null);
            }
            
            const key = categoryId || 'general';
            
            if (!itemsByCategory[key]) {
                itemsByCategory[key] = {
                    categoryId: categoryId,
                    items: [],
                    quantity: 0
                };
            }
            
            itemsByCategory[key].items.push(item);
            itemsByCategory[key].quantity += item.quantity;
            totalItems += item.quantity;
        });
        
        // Calculate shipping cost for each category group
        let totalShippingCost = 0;
        let shippingRateUsed = null;
        let shippingName = null;
        let zoneName = null;
        let allGroupsHaveRate = true;
        
        console.log(`📦 Items grouped by category:`, Object.keys(itemsByCategory).length, 'groups');
        console.log(`📊 Available rates for selection:`, availableRates.length);
        console.log(`   - Domain-specific: ${domainSpecificRates.length}`);
        console.log(`   - General domain: ${generalDomainRates.length}`);
        
        Object.values(itemsByCategory).forEach(group => {
            const categoryId = group.categoryId;
            const quantity = group.quantity;
            
            // Find shipping rate for this category
            // Logic similar to cart/index.blade.php - prioritize rates in availableRates (already filtered by zone)
            let rate = null;
            
            // First, try to find rate specific to this category with all conditions (from availableRates)
            if (categoryId && availableRates.length > 0) {
                rate = availableRates.find(r => 
                    r.category_id === categoryId && 
                    (!r.min_items || quantity >= r.min_items) &&
                    (!r.max_items || quantity <= r.max_items) &&
                    (!r.min_order_value || baseSubtotal >= r.min_order_value) &&
                    (!r.max_order_value || baseSubtotal <= r.max_order_value)
                );
            }
            
            // If no category-specific rate with conditions, try category-specific rate without quantity/order value conditions
            if (!rate && categoryId && availableRates.length > 0) {
                rate = availableRates.find(r => r.category_id === categoryId);
            }
            
            // If no category-specific rate, try general rate (category_id is null) with all conditions
            if (!rate && availableRates.length > 0) {
                rate = availableRates.find(r => 
                    r.category_id === null &&
                    (!r.min_items || quantity >= r.min_items) &&
                    (!r.max_items || quantity <= r.max_items) &&
                    (!r.min_order_value || baseSubtotal >= r.min_order_value) &&
                    (!r.max_order_value || baseSubtotal <= r.max_order_value)
                );
            }
            
            // If no general rate with conditions, try any general rate (category_id is null)
            if (!rate && availableRates.length > 0) {
                // Log all available rates for debugging
                console.log(`🔍 Searching for general rate in ${availableRates.length} available rates:`, 
                    availableRates.map(r => ({
                        id: r.id,
                        name: r.name,
                        zone_id: r.zone_id,
                        zone_name: r.zone_name,
                        category_id: r.category_id,
                        first_item_cost: r.first_item_cost
                    }))
                );
                
                rate = availableRates.find(r => r.category_id === null);
                if (rate) {
                    console.log(`✅ Found general rate (no category) from availableRates:`, {
                        rateId: rate.id,
                        rateName: rate.name,
                        zone_id: rate.zone_id,
                        zone_name: rate.zone_name,
                        first_item_cost: rate.first_item_cost,
                        expectedZoneId: parsedZoneId,
                        expectedZoneName: zoneName
                    });
                    
                    // Verify rate belongs to correct zone
                    if (rate.zone_id !== parsedZoneId && rate.zone_id !== null) {
                        console.warn(`⚠️ WARNING: Selected rate ${rate.id} has zone_id ${rate.zone_id} but expected zone_id is ${parsedZoneId}`);
                    }
                } else {
                    console.log(`❌ No general rate found in availableRates`);
                }
            }
            
            // If still no rate found, use default shipping rate (if it meets conditions and matches zone)
            if (!rate && DEFAULT_SHIPPING_RATE) {
                const defaultRate = DEFAULT_SHIPPING_RATE;
                
                // Extract actual zone ID for comparison
                let actualZoneIdForComparison = zoneId;
                if (zoneId !== null) {
                    if (typeof zoneId === 'string' && zoneId.includes(':')) {
                        actualZoneIdForComparison = zoneId.split(':')[0];
                    }
                    // Parse to integer if it's a numeric string
                    if (typeof actualZoneIdForComparison === 'string' && !isNaN(actualZoneIdForComparison)) {
                        actualZoneIdForComparison = parseInt(actualZoneIdForComparison);
                    }
                }
                
                // Check if default rate meets the conditions and zone
                // Allow default rate if: zoneId is null OR default rate zone matches OR default rate is general (zone_id = null)
                // If availableRates is empty, be more lenient with zone matching
                let zoneMatches = false;
                if (availableRates.length === 0) {
                    // When no rates exist for the zone, allow default rate regardless of zone (as final fallback)
                    zoneMatches = true;
                } else {
                    // Normal zone matching when rates exist
                    zoneMatches = zoneId === null || 
                                 defaultRate.zone_id === actualZoneIdForComparison || 
                                 defaultRate.zone_id === null; // General domain rate can be used for any zone
                }
                
                // When availableRates is empty, be more lenient with conditions (use default rate as last resort)
                const meetsConditions = zoneMatches && (
                    availableRates.length === 0 
                        ? true // When no rates available, use default rate regardless of quantity/order value conditions
                        : (
                    (!defaultRate.min_items || quantity >= defaultRate.min_items) &&
                    (!defaultRate.max_items || quantity <= defaultRate.max_items) &&
                    (!defaultRate.min_order_value || baseSubtotal >= defaultRate.min_order_value) &&
                            (!defaultRate.max_order_value || baseSubtotal <= defaultRate.max_order_value)
                        )
                );
                
                if (meetsConditions) {
                    rate = defaultRate;
                }
            }
            
            // Priority 6: If still no rate, use first available rate from availableRates
            if (!rate && availableRates.length > 0) {
                rate = availableRates[0]; // Use first available rate
            }
            
            // Priority 7: If still no rate, use first rate from all SHIPPING_RATES
            if (!rate && SHIPPING_RATES.length > 0) {
                rate = SHIPPING_RATES[0]; // Use first rate as final fallback
            }
            
            // Always use a rate if available (never return unavailable)
            if (rate) {
                // Verify rate belongs to correct zone (if zoneId is specified)
                let actualZoneIdForVerification = zoneId;
                if (zoneId !== null) {
                    if (typeof zoneId === 'string' && zoneId.includes(':')) {
                        actualZoneIdForVerification = zoneId.split(':')[0];
                    }
                    if (typeof actualZoneIdForVerification === 'string' && !isNaN(actualZoneIdForVerification)) {
                        actualZoneIdForVerification = parseInt(actualZoneIdForVerification);
                    }
                    
                    // If rate doesn't match zone and zone is specified, try to find another rate
                    if (rate.zone_id !== null && rate.zone_id !== actualZoneIdForVerification) {
                        console.warn(`⚠️ Rate ${rate.id} has zone_id ${rate.zone_id} but expected ${actualZoneIdForVerification}, trying to find correct rate...`);
                        // Try to find rate with correct zone_id from availableRates
                        const correctRate = availableRates.find(r => 
                            r.zone_id === actualZoneIdForVerification &&
                            (categoryId ? r.category_id === categoryId : r.category_id === null)
                        );
                        if (correctRate) {
                            console.log(`✅ Found correct rate ${correctRate.id} for zone ${actualZoneIdForVerification}`);
                            rate = correctRate;
                        }
                    }
                }
                
                const groupCost = rate.first_item_cost + (quantity - 1) * rate.additional_item_cost;
                totalShippingCost += groupCost;
                
                console.log(`✅ Rate found for category ${categoryId || 'general'}:`, {
                    rateId: rate.id,
                    rateName: rate.name,
                    zone_id: rate.zone_id,
                    zoneName: rate.zone_name,
                    firstItemCost: rate.first_item_cost,
                    additionalItemCost: rate.additional_item_cost,
                    quantity: quantity,
                    groupCost: groupCost,
                    expectedZoneId: actualZoneIdForVerification
                });
                
                if (!shippingRateUsed || (categoryId && rate.category_id === categoryId)) {
                    shippingRateUsed = rate;
                    shippingName = rate.name;
                    // Priority: use currentZoneName (zone selected from dropdown) over rate.zone_name
                    zoneName = currentZoneName || rate.zone_name;
                }
            } else {
                console.log(`❌ No rate found for category ${categoryId || 'general'}, quantity: ${quantity}`);
                allGroupsHaveRate = false;
            }
        });
        
        // If no rates found for any group, try to use default rate or first available rate
        if (!allGroupsHaveRate || (totalShippingCost === 0 && !shippingRateUsed)) {
            // Try to use default rate
            if (DEFAULT_SHIPPING_RATE) {
                const defaultRate = DEFAULT_SHIPPING_RATE;
                const quantity = cartItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
                const groupCost = defaultRate.first_item_cost + (quantity - 1) * defaultRate.additional_item_cost;
                totalShippingCost = groupCost;
                shippingRateUsed = defaultRate;
                shippingName = defaultRate.name;
                // Priority: use currentZoneName (zone selected from dropdown) over defaultRate.zone_name
                zoneName = currentZoneName || defaultRate.zone_name;
            } else if (SHIPPING_RATES.length > 0) {
                // Use first available rate
                const firstRate = SHIPPING_RATES[0];
                const quantity = cartItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
                const groupCost = firstRate.first_item_cost + (quantity - 1) * firstRate.additional_item_cost;
                totalShippingCost = groupCost;
                shippingRateUsed = firstRate;
                shippingName = firstRate.name;
                // Priority: use currentZoneName (zone selected from dropdown) over firstRate.zone_name
                zoneName = currentZoneName || firstRate.zone_name;
            } else {
                // Only return unavailable if absolutely no rates exist
            return {
                cost: 0,
                costConverted: 0,
                rate: null,
                name: null,
                zoneId: zoneId,
                    zoneName: currentZoneName,
                available: false
            };
            }
        }
        
        // Convert to current currency if needed
        const costConverted = CHECKOUT_CURRENCY !== 'USD' && CHECKOUT_CURRENCY_RATE > 0
            ? totalShippingCost * CHECKOUT_CURRENCY_RATE
            : totalShippingCost;
        
        // Final zoneName: prioritize currentZoneName (zone selected from dropdown) over zoneName from rate
        const finalZoneName = currentZoneName || zoneName;
        
        console.log('💰 Final shipping calculation:', {
            totalShippingCost: totalShippingCost,
            costConverted: costConverted,
            currency: CHECKOUT_CURRENCY,
            rateUsed: shippingRateUsed ? {
                id: shippingRateUsed.id,
                name: shippingRateUsed.name,
                zone_name: shippingRateUsed.zone_name
            } : null,
            zoneName: finalZoneName,
            zoneId: zoneId
        });
        
        return {
            cost: totalShippingCost,
            costConverted: costConverted,
            rate: shippingRateUsed,
            name: shippingName || 'Standard Shipping',
            zoneId: zoneId,
            zoneName: finalZoneName,
            available: true
        };
    }
    
    /**
     * Update shipping cost display
     * Similar to updateShippingZone in cart/index.blade.php
     */
    function __pressonGetCheckoutTotalValue() {
        const totalEl = document.getElementById('checkout-total') || document.querySelector('.total-display');
        if (!totalEl) return 0;
        const raw = (totalEl.textContent || '').trim();
        return parseFloat(raw.replace(/[^0-9.-]/g, '')) || 0;
    }

window.__PRESSONNailRetentionFreeShipActive = window.__PRESSONNailRetentionFreeShipActive ?? false;

    window.__PRESSONNailLastShippingTier = window.__PRESSONNailLastShippingTier ?? null;
    window.__PRESSONNailTrackAddShippingInfo = function(shippingTier) {
        if (typeof dataLayer === 'undefined') return;
        if (!shippingTier) return;

        // Prevent duplicate pushes when user triggers multiple recalculations.
        if (window.__PRESSONNailLastShippingTier === shippingTier) return;
        window.__PRESSONNailLastShippingTier = shippingTier;

        const items = window.PRESSONNail_CHECKOUT_GA4_ITEMS || [];
        const totalValue = __pressonGetCheckoutTotalValue();

        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            event: 'add_shipping_info',
            ecommerce: {
                currency: '{{ $currency ?? "USD" }}',
                value: Number(totalValue) || 0,
                shipping_tier: shippingTier,
                items
            }
        });
    };

    window.__PRESSONNailLastPaymentType = window.__PRESSONNailLastPaymentType ?? null;
    window.__PRESSONNailTrackAddPaymentInfo = function(paymentType) {
        if (typeof dataLayer === 'undefined') return;
        if (!paymentType) return;

        if (window.__PRESSONNailLastPaymentType === paymentType) return;
        window.__PRESSONNailLastPaymentType = paymentType;

        const items = window.PRESSONNail_CHECKOUT_GA4_ITEMS || [];
        const totalValue = __pressonGetCheckoutTotalValue();

        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            event: 'add_payment_info',
            ecommerce: {
                currency: '{{ $currency ?? "USD" }}',
                value: Number(totalValue) || 0,
                payment_type: paymentType,
                items
            }
        });
    };

    function updateCheckoutShippingDisplay(zoneId = null) {
        console.log('🔄 updateCheckoutShippingDisplay called with zoneId:', zoneId);
        
        // Force recalculation by clearing any cached values
        const shippingInfo = calculateCheckoutShippingCost(zoneId);
        const shippingCost = shippingInfo.costConverted;
        
        console.log('💰 Shipping info calculated:', {
            cost: shippingInfo.cost,
            costConverted: shippingCost,
            zoneName: shippingInfo.zoneName,
            available: shippingInfo.available
        });
        
        const shippingCostEl = document.getElementById('checkout-shipping-cost');
        const shippingLabelEl = document.getElementById('checkout-shipping-label');
        const shippingCostInput = document.getElementById('shipping_cost');
        const shippingZoneIdInput = document.getElementById('shipping_zone_id');
        const retentionFreeshipInput = document.getElementById('retention_free_shipping');
        const retentionFreeShipActive = !!(
            window.__PRESSONNailRetentionFreeShipActive ||
            (retentionFreeshipInput && retentionFreeshipInput.value === '1')
        );

        // Retention popup accepted free shipping: keep shipping = 0 and prevent zone recalculation overwrite.
        if (retentionFreeShipActive) {
            window.__PRESSONNailRetentionFreeShipActive = true;
            if (shippingCostEl) {
                shippingCostEl.textContent = formatPrice(0, CHECKOUT_CURRENCY);
                shippingCostEl.classList.remove('text-red-600');
            }
            if (shippingLabelEl) {
                shippingLabelEl.textContent = 'Free Shipping';
                shippingLabelEl.classList.remove('text-red-600');
            }
            if (shippingCostInput) {
                shippingCostInput.value = '0';
            }
            if (shippingZoneIdInput) {
                shippingZoneIdInput.value = zoneId || '';
            }
            updateTotal();
            window.__PRESSONNailTrackAddShippingInfo('Free Shipping');
            return;
        }

        // Free shipping: based on subtotal BEFORE discount (USD)
        const qualifiesFreeShip = (Number(CHECKOUT_BASE_SUBTOTAL) || 0) >= (Number(CHECKOUT_FREE_SHIP_THRESHOLD_USD) || 150);
        if (qualifiesFreeShip) {
            if (shippingCostEl) {
                shippingCostEl.textContent = formatPrice(0, CHECKOUT_CURRENCY);
                shippingCostEl.classList.remove('text-red-600');
            }
            if (shippingLabelEl) {
                shippingLabelEl.textContent = 'Free Shipping';
                shippingLabelEl.classList.remove('text-red-600');
            }
            if (shippingCostInput) {
                shippingCostInput.value = '0';
            }
            if (shippingZoneIdInput) {
                shippingZoneIdInput.value = zoneId || '';
            }
            updateTotal();
            window.__PRESSONNailTrackAddShippingInfo(shippingLabelEl ? shippingLabelEl.textContent.trim() : 'Free Shipping');
            return;
        }
        
        // Check if shipping is available
        if (shippingInfo.available === false) {
            if (shippingCostEl) {
                shippingCostEl.textContent = 'N/A';
                shippingCostEl.classList.add('text-red-600');
            }
            
            if (shippingLabelEl) {
                shippingLabelEl.textContent = 'Shipping not available for this area';
                shippingLabelEl.classList.add('text-red-600');
            }
            
            // Set shipping cost to 0
            if (shippingCostInput) {
                shippingCostInput.value = '0';
            }
        } else {
            if (shippingCostEl) {
                shippingCostEl.textContent = formatPrice(shippingCost, CHECKOUT_CURRENCY);
                shippingCostEl.classList.remove('text-red-600');
            }
            
            if (shippingLabelEl) {
                // Prioritize zoneName from shippingInfo (which comes from currentZoneName or rate.zone_name)
                const displayZoneName = shippingInfo.zoneName || shippingInfo.name || null;
                shippingLabelEl.textContent = `Shipping${displayZoneName ? ` (${displayZoneName})` : ''}`;
                shippingLabelEl.classList.remove('text-red-600');
            }
            
            // Store shipping cost and zone ID in hidden inputs
            if (shippingCostInput) {
                shippingCostInput.value = shippingInfo.cost; // Store in USD
            }
        }
        
        if (shippingZoneIdInput) {
            shippingZoneIdInput.value = zoneId || '';
        }
        
        // Update total - this will recalculate the total with new shipping cost
        updateTotal();
        window.__PRESSONNailTrackAddShippingInfo(shippingLabelEl ? shippingLabelEl.textContent.trim() : shippingInfo.name || 'Shipping');
    }

    // Discount mode toggle (volume vs promo) - server-side recalculates and page reloads.
    (function () {
        var btnVol = document.getElementById('checkout-mode-volume');
        var btnPromo = document.getElementById('checkout-mode-promo');
        if (!btnVol || !btnPromo) return;

        function setMode(mode) {
            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            var retentionInput = document.getElementById('retention_free_shipping');
            var retentionFreeShipping = retentionInput && retentionInput.value === '1' ? 1 : 0;
            var promoInput = document.getElementById('checkout-promo-input');
            try {
                if (promoInput) {
                    sessionStorage.setItem(CHECKOUT_PROMO_DRAFT_KEY, (promoInput.value || '').trim());
                }
            } catch (e) {}
            fetch(CHECKOUT_DISCOUNT_MODE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify({ mode: mode, retention_free_shipping: retentionFreeShipping })
            }).then(function () {
                window.location.reload();
            }).catch(function () {
                window.location.reload();
            });
        }

        btnVol.addEventListener('click', function () { setMode('volume'); });
        btnPromo.addEventListener('click', function () { setMode('promo'); });
    })();

    // Checkout promo code apply/remove
    (function () {
        var input = document.getElementById('checkout-promo-input');
        var applyBtn = document.getElementById('checkout-promo-apply');
        var removeBtn = document.getElementById('checkout-promo-remove');
        var message = document.getElementById('checkout-promo-message');
        if (!input || !applyBtn) return;

        try {
            var savedCode = sessionStorage.getItem(CHECKOUT_PROMO_DRAFT_KEY) || '';
            if (!input.value && savedCode) {
                input.value = savedCode;
            }
        } catch (e) {}

        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        function showMessage(text, isError) {
            if (!message) return;
            message.textContent = text;
            message.className = 'text-xs ' + (isError ? 'text-red-600' : 'text-emerald-600');
        }

        applyBtn.addEventListener('click', function () {
            var code = (input.value || '').trim();
            if (!code) {
                showMessage('Please enter a promo code.', true);
                return;
            }

            applyBtn.disabled = true;
            fetch(CHECKOUT_APPLY_PROMO_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: code })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    try {
                        sessionStorage.removeItem(CHECKOUT_PROMO_DRAFT_KEY);
                    } catch (e) {}
                    window.location.reload();
                    return;
                }
                applyBtn.disabled = false;
                showMessage(data.message || 'Invalid or expired promo code.', true);
            })
            .catch(function () {
                applyBtn.disabled = false;
                showMessage('Something went wrong.', true);
            });
        });

        if (input) {
            input.addEventListener('input', function () {
                try {
                    sessionStorage.setItem(CHECKOUT_PROMO_DRAFT_KEY, (input.value || '').trim());
                } catch (e) {}
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyBtn.click();
                }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                removeBtn.disabled = true;
                fetch(CHECKOUT_REMOVE_PROMO_URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        try {
                            sessionStorage.removeItem(CHECKOUT_PROMO_DRAFT_KEY);
                        } catch (e) {}
                        window.location.reload();
                        return;
                    }
                    removeBtn.disabled = false;
                    showMessage(data.message || 'Cannot remove promo code right now.', true);
                })
                .catch(function () {
                    removeBtn.disabled = false;
                    showMessage('Something went wrong.', true);
                });
            });
        }
    })();
    
    /**
     * Get zone ID from country code
     */
    function getZoneIdFromCountry(countryCode) {
        if (!countryCode) return null;
        
        // First try to get zone_id from selected option's data attribute
        const countrySelect = document.getElementById('country');
        if (countrySelect) {
            const selectedOption = countrySelect.options[countrySelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.zoneId) {
                const zoneIdFromData = parseInt(selectedOption.dataset.zoneId);
                if (!isNaN(zoneIdFromData)) {
                    return zoneIdFromData;
                }
            }
        }
        
        // Fallback: use COUNTRY_TO_ZONE_MAP
        return COUNTRY_TO_ZONE_MAP[countryCode.toUpperCase()] || null;
    }
    
    /**
     * Update shipping zone from country selection
     * Similar to updateShippingZone in cart/index.blade.php
     */
    function updateShippingFromCountry() {
        const countrySelect = document.getElementById('country');
        
        if (!countrySelect) return;
        
        const countryCode = countrySelect.value;
        let zoneId = null;
        
        console.log('🌍 Country selected:', countryCode);
        
        // Get zone from country if country is selected
        if (countryCode) {
            // First try to get from option's data attribute
            const selectedOption = countrySelect.options[countrySelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.zoneId) {
                const zoneIdFromData = selectedOption.dataset.zoneId;
                // Check if format is "zone_id:country_code" (like in cart page)
                if (typeof zoneIdFromData === 'string' && zoneIdFromData.includes(':')) {
                    zoneId = zoneIdFromData; // Keep full format for consistency
                } else {
                    const parsed = parseInt(zoneIdFromData);
                    if (!isNaN(parsed)) {
                        zoneId = parsed;
                    }
                }
                if (zoneId) {
                    console.log('✅ Zone ID from data attribute:', zoneId);
                }
            }
            
            // If not found, try from map
            if (!zoneId) {
                const mappedZoneId = getZoneIdFromCountry(countryCode);
                if (mappedZoneId) {
                    zoneId = mappedZoneId;
                    console.log('✅ Zone ID from map:', zoneId);
                }
            }
        }
        
        // If no zone found from country, use default shipping rate zone
        if (!zoneId && DEFAULT_SHIPPING_RATE && DEFAULT_SHIPPING_RATE.zone_id) {
            zoneId = DEFAULT_SHIPPING_RATE.zone_id;
            console.log('⚠️ Using default shipping rate zone:', zoneId);
        }
        
        // If still no zone, use default shipping zone ID
        if (!zoneId) {
            zoneId = DEFAULT_SHIPPING_ZONE_ID;
            console.log('⚠️ Using DEFAULT_SHIPPING_ZONE_ID:', zoneId);
        }
        
        console.log('📦 Final zone ID for shipping calculation:', zoneId);
        console.log('🔄 Recalculating shipping cost for country:', countryCode);
        
        // Extract zone_id from value if format is "zone_id:country_code" (like cart page)
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
        
        // Update shipping zone ID input
        const shippingZoneIdInput = document.getElementById('shipping_zone_id');
        if (shippingZoneIdInput) {
            shippingZoneIdInput.value = actualZoneId || '';
        }
        
        // Save selected zone to localStorage (save the full value including country code if available)
        if (zoneId) {
            localStorage.setItem('selectedShippingZoneId', zoneId);
        }
        
        // Force recalculation by passing zoneId explicitly
        // This ensures shipping cost is recalculated even if zoneId is the same
        // Use actualZoneId (parsed) for calculation, but keep original zoneId for display
        console.log('🔄 Calling updateCheckoutShippingDisplay with actualZoneId:', actualZoneId);
        updateCheckoutShippingDisplay(actualZoneId);
        
        // Force a small delay to ensure DOM is updated
        setTimeout(() => {
            console.log('🔄 Re-checking shipping after delay...');
            updateCheckoutShippingDisplay(actualZoneId);
        }, 100);
    }
    
    // Initialize shipping cost on page load
    function initializeCheckoutShipping() {
        var retentionFreeshipInput = document.getElementById('retention_free_shipping');
        try {
            if (sessionStorage.getItem('checkout_retention_free_shipping') === '1') {
                window.__PRESSONNailRetentionFreeShipActive = true;
                if (retentionFreeshipInput) retentionFreeshipInput.value = '1';
            }
        } catch (e) {}

        let selectedZoneId = null;
        
        // Check if country is selected and get zone from country
        const countrySelect = document.getElementById('country');
        if (countrySelect && countrySelect.value) {
            // First try to get from option's data attribute
            const selectedOption = countrySelect.options[countrySelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.zoneId) {
                selectedZoneId = parseInt(selectedOption.dataset.zoneId);
                if (isNaN(selectedZoneId)) {
                    selectedZoneId = null;
                }
            }
            
            // If not found, try from map
            if (!selectedZoneId) {
            selectedZoneId = getZoneIdFromCountry(countrySelect.value);
            }
        }
        
        // If no zone found from country, use default shipping rate zone
        if (!selectedZoneId && DEFAULT_SHIPPING_RATE && DEFAULT_SHIPPING_RATE.zone_id) {
            selectedZoneId = DEFAULT_SHIPPING_RATE.zone_id;
        }
        
        // If still no zone, use default shipping zone ID
        if (!selectedZoneId) {
            selectedZoneId = DEFAULT_SHIPPING_ZONE_ID;
        }
        
        // Update shipping zone ID input
        const shippingZoneIdInput = document.getElementById('shipping_zone_id');
        if (shippingZoneIdInput) {
            shippingZoneIdInput.value = selectedZoneId || '';
        }
        
        // Calculate and display shipping cost
        updateCheckoutShippingDisplay(selectedZoneId);
    }
    
    // Add event listener for country change
    const countrySelect = document.getElementById('country');
    if (countrySelect) {
        console.log('✅ Country select element found, attaching change listener');
        countrySelect.addEventListener('change', function(e) {
            console.log('🌍 Country dropdown changed to:', e.target.value);
            updateShippingFromCountry();
        });
        
        // Also listen for input event (for better compatibility)
        countrySelect.addEventListener('input', function(e) {
            console.log('🌍 Country dropdown input changed to:', e.target.value);
            updateShippingFromCountry();
        });
    } else {
        console.error('❌ Country select element not found!');
    }
    
    // Update checkoutProducts with checkoutItemsData if available (has categories)
    // This will be called after checkoutItemsData is defined (after DOMContentLoaded)
    function updateCheckoutProducts() {
        if (typeof checkoutItemsData !== 'undefined' && Array.isArray(checkoutItemsData) && checkoutItemsData.length > 0) {
            checkoutProducts = checkoutItemsData;
        }
    }
    
    // Initialize shipping on page load
    setTimeout(() => {
        updateCheckoutProducts(); // Update products with categories if available
        initializeCheckoutShipping();
    }, 500);
});
</script>

@php
    $checkoutItems = [];
    foreach ($products as $item) {
        // Get categories from product
        $categories = $item['product']->categories ?? collect();
        if (!($categories instanceof Collection)) {
            $categories = collect($categories);
        }
        
        $checkoutProduct = $item['product'];
        $checkoutMedia = $checkoutProduct->media ?? $checkoutProduct->getEffectiveMedia();
        $checkoutItems[] = [
            'id' => $item['cart_item']->id,
            'quantity' => $item['cart_item']->quantity,
            'price' => (float) $item['cart_item']->price,
            'product' => [
                'id' => $checkoutProduct->id,
                'name' => $checkoutProduct->name,
                'sku' => $checkoutProduct->sku ?? null,
                'variants' => $checkoutProduct->variants,
                'media' => $checkoutMedia,
                'primary_image_alt' => $checkoutProduct->altForMediaItem(
                    (is_array($checkoutMedia) && isset($checkoutMedia[0])) ? $checkoutMedia[0] : [],
                    null,
                    0
                ),
                'base_price' => (float) ($checkoutProduct->base_price ?? 0),
                'price' => (float) ($checkoutProduct->price ?? 0),
                'template' => $checkoutProduct->template ? ['base_price' => (float) $checkoutProduct->template->base_price] : null,
                'categories' => $categories->map(function($cat) {
                    return [
                        'id' => $cat->id ?? null,
                        'name' => $cat->name ?? null,
                        'category_id' => $cat->id ?? null,
                    ];
                })->toArray(),
            ],
            'selected_variant' => $item['cart_item']->selected_variant,
            'customizations' => $item['cart_item']->customizations,
        ];
    }
@endphp

<!-- Build checkoutItemsData from server-side products for modal editing -->
<script>
const checkoutItemsData = @json($checkoutItems);
const checkoutCsrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function openCheckoutEditCartModal(cartItemId) {
    const ci = checkoutItemsData.find(i => i.id === cartItemId);
    if (!ci) { alert('Cart item not found'); return; }
    const modal = document.getElementById('checkoutEditCartModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    const content = document.getElementById('checkoutEditCartModalContent');
    content.innerHTML = buildCheckoutEditContent(ci);
    window.__checkoutEditingCtx = {
        id: cartItemId,
        item: ci,
        variants: (ci.product && ci.product.variants) ? ci.product.variants : [],
        originalCustomizations: ci.customizations || {}
    };
}

function closeCheckoutEditCartModal() {
    const modal = document.getElementById('checkoutEditCartModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function buildCheckoutEditContent(ci) {
    const product = ci.product;
    const variants = product.variants || [];
    const selectedVariant = ci.selected_variant || {};
    const customizations = ci.customizations || {};
    const img = getCheckoutProductImage(product);
    const imgAltEsc = String(getCheckoutProductImageAlt(product)).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
    const total = (parseFloat(ci.price) * ci.quantity).toFixed(2);
    return `
        <div class="space-y-6">
            <div class="flex gap-4">
                <img src="${img}" alt="${imgAltEsc}" class="w-24 h-24 object-cover rounded-lg">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">${product.name}</h3>
                    <p class="text-slate-600">${CHECKOUT_CURRENCY_SYMBOL}${parseFloat(ci.price).toFixed(2)} each</p>
                </div>
            </div>
            ${variants.length ? `
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Variants</label>
                <div class="space-y-2">${buildCheckoutVariantOptions(variants, selectedVariant)}</div>
            </div>` : ''}
            ${Object.keys(customizations).length ? `
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Customizations</label>
                <div class="space-y-3">
                    ${buildCheckoutCustomizationInputs(customizations)}
                </div>
            </div>` : ''}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Quantity</label>
                <div class="flex items-center gap-3">
                    <button type="button" id="checkoutModalQtyDec${ci.id}" onclick="updateCheckoutModalQty(${ci.id}, -1)" class="w-10 h-10 rounded-lg border border-primary/20 flex items-center justify-center hover:bg-primary/5 transition-colors" ${ci.quantity<=1?'disabled':''}>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </button>
                    <span class="text-xl font-semibold" id="checkoutModalQty${ci.id}">${ci.quantity}</span>
                    <button type="button" onclick="updateCheckoutModalQty(${ci.id}, 1)" class="w-10 h-10 rounded-lg border border-primary/20 flex items-center justify-center hover:bg-primary/5 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
            </div>
            <div class="border-t pt-4">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold text-gray-900">Total</span>
                    <span class="text-2xl font-bold text-primary" id="checkoutModalTotal${ci.id}">${CHECKOUT_CURRENCY_SYMBOL}${total}</span>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button onclick="saveCheckoutCartChanges(${ci.id})" class="flex-1 bg-primary hover:brightness-110 text-white font-bold py-3 rounded-xl transition-colors">Save Changes</button>
                <button onclick="closeCheckoutEditCartModal()" class="px-6 py-3 border-2 border-primary/20 hover:border-primary text-slate-700 font-medium rounded-xl transition-colors">Cancel</button>
            </div>
        </div>`;
}

function buildCheckoutVariantOptions(variants, selectedVariant) {
    const groups = {};
    variants.forEach(v => { if (v.attributes) Object.keys(v.attributes).forEach(k => { groups[k] = groups[k]||new Set(); groups[k].add(v.attributes[k]); }); });
    return Object.keys(groups).map(k => {
        const values = Array.from(groups[k]);
        const sel = selectedVariant && selectedVariant.attributes ? selectedVariant.attributes[k] : '';
        return `
        <div>
            <label class="block text-sm text-gray-600 mb-1">${k.charAt(0).toUpperCase()+k.slice(1)}</label>
                <select class="w-full border-2 border-primary/20 rounded-lg px-4 py-2 focus:border-primary focus:outline-none" id="checkout-variant-${k}" onchange="updateCheckoutModalTotal()">
                ${values.map(v => `<option value="${v}" ${v===sel?'selected':''}>${v}</option>`).join('')}
            </select>
        </div>`;
    }).join('');
}

function buildCheckoutCustomizationInputs(customizations) {
    var html = '';
    if (!customizations) return html;
    Object.keys(customizations).forEach(function(k){
        var v = customizations[k] || {};
        var value = v && v.value ? String(v.value).replace(/"/g, '&quot;') : '';
        html += '<div class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-center">'
             + '<div class="sm:col-span-2"><span class="text-sm text-slate-600">' + k + '</span></div>'
             + '<div class="sm:col-span-3">'
             + '<input type="text" class="w-full border-2 border-primary/20 rounded-lg px-3 py-2 checkout-customization-input" data-label="' + k + '" value="' + value + '" oninput="updateCheckoutModalTotal()" title="' + value + '" />'
             + '</div>'
             + '</div>';
    });
    return html;
}

function getCheckoutProductImage(product) {
    const media = product && product.media && product.media.length ? product.media[0] : null;
    if (!media) return '/images/placeholder.jpg';
    if (typeof media === 'string') return media;
    if (media.url) return media.url; if (media.path) return media.path; return '/images/placeholder.jpg';
}

function getCheckoutProductImageAlt(product) {
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

function updateCheckoutModalQty(id, delta) {
    const el = document.getElementById('checkoutModalQty' + id);
    if (!el) return;
    const current = parseInt(String(el.textContent).trim(), 10) || 1;
    const newQty = current + (parseInt(delta, 10) || 0);
    if (newQty < 1) return;
    el.textContent = String(newQty);
    const decBtn = document.getElementById('checkoutModalQtyDec' + id);
    if (decBtn) decBtn.disabled = newQty <= 1;
    updateCheckoutModalTotal();
}

function updateCheckoutModalTotal() {
    const ctx = window.__checkoutEditingCtx; if (!ctx) return; const id = ctx.id; const item = ctx.item; const qty = parseInt(document.getElementById('checkoutModalQty'+id)?.textContent || '1');
    // selected variant
    const attrs = {}; (ctx.variants||[]).forEach(v=>{ if(v.attributes){ Object.keys(v.attributes).forEach(k=>{ const sel=document.getElementById('checkout-variant-'+k); if(sel) attrs[k]=sel.value;});}});
    const match = (ctx.variants||[]).find(v=>v.attributes && Object.keys(attrs).every(k=>String(v.attributes[k])===String(attrs[k])));
    let unitPrice = 0;
    if (match && match.price!=null && match.price!=='') { const pv=parseFloat(match.price); if(!isNaN(pv)) unitPrice=pv; }
    if (!unitPrice) {
        const p=item.product||{}; const candidates=[p.price,p.base_price,(p.template||{}).base_price,item.price]; for(const c of candidates){ const v=parseFloat(c); if(!isNaN(v)){ unitPrice=v; break; } }
    }
    // customizations keep original price
    const customMap={}; document.querySelectorAll('.checkout-customization-input').forEach(inp=>{ const label=inp.dataset.label; const value=inp.value||''; const orig=ctx.originalCustomizations&&ctx.originalCustomizations[label]; const price=orig&&orig.price?parseFloat(orig.price)||0:0; if(value.trim()!==''){ customMap[label]={value:value.trim(),price}; }});
    let custTotal=0; Object.values(customMap).forEach(c=>{ custTotal+=parseFloat(c.price)||0; });
    const total = (unitPrice + custTotal) * qty; const td=document.getElementById('checkoutModalTotal'+id); if (td) td.textContent=CHECKOUT_CURRENCY_SYMBOL+total.toFixed(2);
}

function saveCheckoutCartChanges(cartItemId) {
    const ctx = window.__checkoutEditingCtx; if (!ctx || ctx.id!==cartItemId) return; const item=ctx.item; const qty=parseInt(document.getElementById('checkoutModalQty'+cartItemId)?.textContent||'1');
    const attrs={}; (ctx.variants||[]).forEach(v=>{ if(v.attributes){ Object.keys(v.attributes).forEach(k=>{ const sel=document.getElementById('checkout-variant-'+k); if(sel) attrs[k]=sel.value;});}});
    const match=(ctx.variants||[]).find(v=>v.attributes && Object.keys(attrs).every(k=>String(v.attributes[k])===String(attrs[k])));
    const selectedVariant = match ? { id: match.id, attributes: match.attributes, price: match.price } : (Object.keys(attrs).length ? { attributes: attrs } : null);
    const customizations={}; document.querySelectorAll('.checkout-customization-input').forEach(inp=>{ const label=inp.dataset.label; const value=inp.value||''; const orig=ctx.originalCustomizations&&ctx.originalCustomizations[label]; const price=orig&&orig.price?parseFloat(orig.price)||0:0; if(value.trim()!==''){ customizations[label]={ value:value.trim(), price }; }});
    let unitPrice=0; if (selectedVariant && selectedVariant.price!=null && selectedVariant.price!==''){ const v=parseFloat(selectedVariant.price); if(!isNaN(v)) unitPrice=v; }
    if (!unitPrice){ const p=item.product||{}; const candidates=[p.price,p.base_price,(p.template||{}).base_price,item.price]; for(const c of candidates){ const v=parseFloat(c); if(!isNaN(v)){ unitPrice=v; break; } } }
    Object.values(customizations).forEach(c=>{ unitPrice += parseFloat(c.price)||0; });
    fetch(`/api/cart/update/${cartItemId}`, {
        method:'PUT', headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN': checkoutCsrfToken },
        body: JSON.stringify({ quantity: qty, selected_variant: selectedVariant, customizations: customizations, price: unitPrice })
    }).then(r=>r.json()).then(data=>{ if(data.success){ window.location.reload(); } else { alert('Failed to update cart item'); }}).catch(err=>{ console.error(err); alert('An error occurred'); });
}

</script>

<script>
(function () {
    var POPUP_SESSION_KEY = 'checkout_popup_shown';
    var GIFT_NOTE_TEXT = '[FREE GIFT] Eligible order >= 150 USD. Please include 1 free gift.';
    var popupLocked = false;
    var timers = [];
    var listeners = [];

    function hasPopupShownInSession() {
        try {
            return sessionStorage.getItem(POPUP_SESSION_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function markPopupShownInSession() {
        try {
            sessionStorage.setItem(POPUP_SESSION_KEY, '1');
        } catch (e) {}
    }

    function cleanupPopupTriggers() {
        timers.forEach(function (id) { clearTimeout(id); });
        timers = [];
        listeners.forEach(function (entry) {
            entry.target.removeEventListener(entry.event, entry.handler, entry.options || false);
        });
        listeners = [];
    }

    function addListener(target, event, handler, options) {
        target.addEventListener(event, handler, options || false);
        listeners.push({ target: target, event: event, handler: handler, options: options || false });
    }

    function getCheckoutTotalInUsd() {
        var totalEl = document.getElementById('checkout-total') || document.querySelector('.total-display');
        var totalDisplay = totalEl ? parseFloat((totalEl.textContent || '').replace(/[^0-9.-]/g, '')) : 0;
        if (!isFinite(totalDisplay) || totalDisplay < 0) totalDisplay = 0;

        var currency = (typeof CHECKOUT_CURRENCY !== 'undefined' ? CHECKOUT_CURRENCY : 'USD') || 'USD';
        var rate = Number(typeof CHECKOUT_CURRENCY_RATE !== 'undefined' ? CHECKOUT_CURRENCY_RATE : 1) || 1;
        if (currency === 'USD') return totalDisplay;
        if (rate <= 0) return totalDisplay;
        return totalDisplay / rate;
    }

    function ensureGiftNote() {
        var notesEl = document.getElementById('notes');
        if (!notesEl) return;
        var current = (notesEl.value || '').trim();
        if (current.indexOf(GIFT_NOTE_TEXT) !== -1) return;
        notesEl.value = current ? (current + '\n' + GIFT_NOTE_TEXT) : GIFT_NOTE_TEXT;
    }

    function applyFreeShippingNow() {
        var shippingCostEl = document.getElementById('checkout-shipping-cost');
        var shippingLabelEl = document.getElementById('checkout-shipping-label');
        var shippingCostInput = document.getElementById('shipping_cost');
        var retentionFreeshipInput = document.getElementById('retention_free_shipping');
        var totalEl = document.getElementById('checkout-total') || document.querySelector('.total-display');
        var shippingBefore = 0;
        var totalBefore = 0;

        if (shippingCostEl) {
            shippingBefore = parseFloat((shippingCostEl.textContent || '').replace(/[^0-9.-]/g, '')) || 0;
        }
        if (totalEl) {
            totalBefore = parseFloat((totalEl.textContent || '').replace(/[^0-9.-]/g, '')) || 0;
        }

        if (shippingCostEl && typeof formatPrice === 'function') {
            shippingCostEl.textContent = formatPrice(0, (typeof CHECKOUT_CURRENCY !== 'undefined' ? CHECKOUT_CURRENCY : 'USD'));
            shippingCostEl.classList.remove('text-red-600');
        }
        if (shippingLabelEl) {
            shippingLabelEl.textContent = 'Free Shipping';
            shippingLabelEl.classList.remove('text-red-600');
        }
        if (shippingCostInput) {
            shippingCostInput.value = '0';
        }
        if (retentionFreeshipInput) {
            retentionFreeshipInput.value = '1';
        }
        window.__PRESSONNailRetentionFreeShipActive = true;
        try {
            sessionStorage.setItem('checkout_retention_free_shipping', '1');
        } catch (e) {}

        // updateTotal() is scoped in another script block; adjust total directly here
        // so user sees immediate change when clicking "Continue checkout".
        if (totalEl && typeof formatPrice === 'function') {
            var totalAfter = Math.max(0, totalBefore - shippingBefore);
            totalEl.textContent = formatPrice(totalAfter, (typeof CHECKOUT_CURRENCY !== 'undefined' ? CHECKOUT_CURRENCY : 'USD'));
        }
    }

    function applyRetentionOffer(popupType, triggerSource) {
        if (popupType === 'gift') {
            ensureGiftNote();
        } else {
            applyFreeShippingNow();
        }
        console.log('[Checkout Retention] offer applied', {
            popupType: popupType,
            triggerSource: triggerSource
        });
    }

    window.showPromoPopup = function (triggerSource) {
        if (popupLocked || hasPopupShownInSession()) return;
        popupLocked = true;
        markPopupShownInSession();
        cleanupPopupTriggers();

        var totalUsd = getCheckoutTotalInUsd();
        var isGift = totalUsd >= 150;
        var popupType = isGift ? 'gift' : 'free_shipping';
        var source = triggerSource || 'unknown';
        window.__checkoutRetentionPopupSource = source;
        var sourceInput = document.getElementById('retention_popup_source');
        if (sourceInput) {
            sourceInput.value = source;
        }
        console.log('[Checkout Retention] popup shown', {
            popupType: popupType,
            triggerSource: source,
            orderTotalUsd: totalUsd
        });

        var title = isGift ? 'Free Gift for This Order' : 'Free Shipping Offer';
        var text = isGift
            ? 'Your order qualifies for 1 free gift.'
            : 'Complete checkout now to receive free shipping on this order.';

        if (typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function') {
            Swal.fire({
                icon: 'info',
                title: title,
                text: text,
                confirmButtonText: 'Continue checkout',
                confirmButtonColor: '#f0427c',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(function (result) {
                if (result && result.isConfirmed) {
                    applyRetentionOffer(popupType, source);
                }
            });
        } else {
            alert(title + '\n' + text);
            applyRetentionOffer(popupType, source);
        }
    };

    if (hasPopupShownInSession()) {
        return;
    }

    var ua = navigator.userAgent || '';
    var isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua);
    var pageEnteredAt = Date.now();

    function fireFirstTrigger(source) {
        if (popupLocked || hasPopupShownInSession()) return;
        window.showPromoPopup(source);
    }

    if (!isMobile) {
        var onMouseMove = function (event) {
            if (event && event.clientY <= 0) {
                fireFirstTrigger('desktop_exit_intent');
            }
        };
        addListener(document, 'mousemove', onMouseMove);
        timers.push(setTimeout(function () {
            fireFirstTrigger('desktop_5min_timer');
        }, 5 * 60 * 1000));
        return;
    }

    var onScroll = function () {
        if (popupLocked || hasPopupShownInSession()) return;
        if (Date.now() - pageEnteredAt > 60 * 1000) return;

        var footer = document.querySelector('footer');
        var threshold = document.documentElement.scrollHeight - window.innerHeight - 120;
        if (footer) {
            var rect = footer.getBoundingClientRect();
            if (rect.top <= window.innerHeight) {
                fireFirstTrigger('mobile_fast_scroll_footer');
                return;
            }
        }
        if (window.scrollY >= threshold) {
            fireFirstTrigger('mobile_fast_scroll_footer');
        }
    };
    addListener(window, 'scroll', onScroll, { passive: true });

    timers.push(setTimeout(function () {
        fireFirstTrigger('mobile_1min_timer');
    }, 60 * 1000));
    timers.push(setTimeout(function () {
        fireFirstTrigger('mobile_5min_timer');
    }, 5 * 60 * 1000));
})();
</script>

<!-- Modal for editing cart items (duplicate removed - use single modal above) -->


@endsection