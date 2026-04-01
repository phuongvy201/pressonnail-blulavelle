<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\RecentProductView;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $sessionId = session()->getId();
        $userId = Auth::id();

        // Get cart items from database
        $cartItems = Cart::with(['product.shop', 'product.template', 'product.variants', 'variant'])
            ->where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->get();

        $cartItems->each(function ($item) {
            if ($item->product) {
                $item->product->hydrateForCartDisplay();
            }
        });

        // Calculate totals (without tax) including customizations
        $subtotal = $cartItems->sum(function ($item) {
            return $item->getTotalPriceWithCustomizations();
        });

        // Choose ONE discount mode: volume OR promo (freeship can stack with either).
        $discountMode = session('discount_mode', 'volume');
        if (session()->has('applied_promo_code_id')) {
            $discountMode = 'promo';
            session(['discount_mode' => 'promo']);
        }

        $bulkDiscountPercent = 0.0;
        $bulkDiscount = 0.0;
        if ($discountMode === 'volume') {
            // Combo discount (quantity tiers) - applied on TOTAL cart quantity
            $totalQty = (int) $cartItems->sum('quantity');
            $bulkDiscountPercent = Cart::getComboDiscountPercentForQty($totalQty);
            $bulkDiscount = $bulkDiscountPercent > 0 ? round((float) $subtotal * ($bulkDiscountPercent / 100), 2) : 0.0;
        }

        $subtotalAfterBulk = max(0, (float) $subtotal - (float) $bulkDiscount);

        // Get current domain and currency (for shipping and promo)
        $currentDomain = CurrencyService::getCurrentDomain();
        $currency = CurrencyService::getCurrencyForDomain() ?? 'USD';
        $currencyRate = CurrencyService::getCurrencyRateForDomain();
        if (!$currencyRate || $currencyRate == 1.0) {
            $defaultRates = [
                'USD' => 1.0,
                'GBP' => 0.79,
                'EUR' => 0.92,
                'CAD' => 1.35,
                'AUD' => 1.52,
                'JPY' => 150.0,
                'CNY' => 7.2,
                'HKD' => 7.8,
                'SGD' => 1.34,
            ];
            $currencyRate = $defaultRates[$currency] ?? 1.0;
        }

        // Calculate shipping using ShippingCalculator
        $shipping = 0;
        $shippingDetails = null;

        if (!$cartItems->isEmpty()) {
            // Prepare cart items for shipping calculation
            // Shipping calculator expects USD prices, so we need to convert back to USD
            $items = $cartItems->map(function ($item) use ($currency, $currencyRate) {
                // Convert price back to USD for shipping calculation
                $priceInUSD = $currency !== 'USD' ? $item->price / $currencyRate : $item->price;
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $priceInUSD,
                ];
            });
            
            // Determine country from currency (same logic as products/show.blade.php)
            // Priority: currency -> domain name -> default to US
            $currencyToCountry = [
                'USD' => 'US',
                'GBP' => 'GB',
                'CAD' => 'CA',
                'MXN' => 'MX',
                'VND' => 'VN',
                'EUR' => 'DE'
            ];
            
            $shippingCountry = $currencyToCountry[$currency] ?? 'US';
            
            // If domain is available, try to get country from domain name
            if ($currentDomain) {
                $domainToCountry = [
                    'mx' => 'MX',
                    'mexico' => 'MX',
                    'us' => 'US',
                    'usa' => 'US',
                    'united-states' => 'US',
                    'gb' => 'GB',
                    'uk' => 'GB',
                    'united-kingdom' => 'GB',
                    'ca' => 'CA',
                    'canada' => 'CA',
                    'vn' => 'VN',
                    'vietnam' => 'VN',
                    'de' => 'DE',
                    'germany' => 'DE',
                    'eu' => 'DE',
                    'europe' => 'DE'
                ];
                
                $domainLower = strtolower($currentDomain);
                foreach ($domainToCountry as $domainKey => $countryCode) {
                    if (strpos($domainLower, $domainKey) !== false) {
                        $shippingCountry = $countryCode;
                        break;
                    }
                }
            }
            
            // Calculate shipping with domain parameter to prioritize default rate
            $calculator = new ShippingCalculator();
            $shippingResult = $calculator->calculateShipping($items, $shippingCountry, $currentDomain);

            if ($shippingResult['success']) {
                $shippingUSD = $shippingResult['total_shipping'];
                $shippingDetails = $shippingResult;

                // Convert shipping from USD to current currency
                $shipping = $currency !== 'USD'
                    ? CurrencyService::convertFromUSDWithRate($shippingUSD, $currency, $currencyRate)
                    : $shippingUSD;
            }
        }

        // Free shipping if subtotal BEFORE discount (USD) >= threshold
        $baseSubtotalUsdBeforeDiscount = $currency !== 'USD' ? ((float) $subtotal / $currencyRate) : (float) $subtotal;
        $shipping = $baseSubtotalUsdBeforeDiscount >= Cart::FREE_SHIPPING_THRESHOLD_USD ? 0 : $shipping;

        // Promo code discount (session) - only when in promo mode
        $discount = 0.0;
        $appliedPromoCode = null;
        $promoId = session('applied_promo_code_id');
        if ($discountMode === 'promo' && $promoId && $subtotal > 0) {
            $subtotalUsd = $currency !== 'USD' ? (float) $subtotal / $currencyRate : (float) $subtotal;
            $promo = PromoCode::find($promoId);
            if ($promo && $promo->isValidForSubtotal($subtotalUsd)) {
                $discountUsd = $promo->calculateDiscountUsd($subtotalUsd);
                $discount = $currency !== 'USD' ? $discountUsd * $currencyRate : $discountUsd;
                $appliedPromoCode = $promo->code;
            } else {
                session()->forget(['applied_promo_code_id', 'applied_promo_code']);
                session(['discount_mode' => 'volume']);
                $discountMode = 'volume';
            }
        }

        $total = $subtotalAfterBulk - $discount + $shipping;

        // Get current domain for shipping zone filtering (already retrieved above)
        // $currentDomain is already set from shipping calculation above

        // Get all active shipping zones for the delivery modal
        $shippingZones = ShippingZone::active()
            ->ordered()
            ->with(['activeShippingRates' => function ($query) {
                $query->ordered();
            }])
            ->get();

        // Filter zones by domain and get default zone
        $availableZones = $shippingZones;
        $defaultZone = null;

        if ($currentDomain && $availableZones->isNotEmpty()) {
            // Sort zones: domain matching zones first
            $availableZones = $availableZones->sortBy(function ($zone) use ($currentDomain) {
                return $zone->domain === $currentDomain ? 0 : 1;
            });

            // Find default zone for current domain
            $defaultZone = $availableZones->first(function ($zone) use ($currentDomain) {
                return $zone->domain === $currentDomain;
            });
        }

        // If no default zone found, use first available zone
        if (!$defaultZone && $availableZones->isNotEmpty()) {
            $defaultZone = $availableZones->first();
        }

        // Recently viewed products (for fragment on cart page)
        $recentlyViewedProducts = collect();
        if (Auth::id()) {
            $recentIds = RecentProductView::getRecentProductIds(Auth::id(), 10, null);
            if ($recentIds->isNotEmpty()) {
                $recentlyViewedProducts = Product::whereIn('id', $recentIds->all())
                    ->availableForDisplay()
                    ->with(['shop', 'template'])
                    ->get()
                    ->sortBy(fn ($p) => array_search($p->id, $recentIds->toArray()));
            }
        }

        return view('cart.index', compact(
            'cartItems',
            'subtotal',
            'bulkDiscount',
            'bulkDiscountPercent',
            'subtotalAfterBulk',
            'discountMode',
            'shipping',
            'discount',
            'appliedPromoCode',
            'total',
            'shippingDetails',
            'shippingZones',
            'availableZones',
            'currentDomain',
            'defaultZone',
            'recentlyViewedProducts'
        ));
    }
}
