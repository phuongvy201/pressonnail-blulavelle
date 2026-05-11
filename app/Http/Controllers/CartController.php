<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CategoryCrossSell;
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

        $discountEligibleItems = $cartItems->filter(function ($item) {
            return !((bool) optional($item->product)->is_gift_card);
        });
        $discountEligibleSubtotal = (float) $discountEligibleItems->sum(function ($item) {
            return $item->getTotalPriceWithCustomizations();
        });
        $discountEligibleQty = (int) $discountEligibleItems->sum('quantity');

        $cartContainsGiftCardProduct = $cartItems->contains(fn ($item) => (bool) optional($item->product)->is_gift_card);

        // Choose ONE discount mode: volume OR promo (freeship can stack with either).
        $discountMode = session('discount_mode', 'volume');
        if (!$cartContainsGiftCardProduct && session()->has('applied_promo_code_id')) {
            $discountMode = 'promo';
            session(['discount_mode' => 'promo']);
        }
        if ($cartContainsGiftCardProduct) {
            $discountMode = 'volume';
        }

        $bulkDiscountPercent = 0.0;
        $bulkDiscount = 0.0;
        if (!$cartContainsGiftCardProduct && $discountMode === 'volume') {
            // Combo discount: tier theo số lượng & subtotal hàng vật lý (có gift card trong giỏ — không áp volume)
            $bulkDiscountPercent = Cart::getComboDiscountPercentForQty($discountEligibleQty);
            $bulkDiscount = $bulkDiscountPercent > 0
                ? round($discountEligibleSubtotal * ($bulkDiscountPercent / 100), 2)
                : 0.0;
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

        // Promo code discount (session) — chỉ áp dụng trên subtotal hàng không phải gift card (đồng bộ Checkout + API cart)
        $discount = 0.0;
        $appliedPromoCode = null;
        $promoId = session('applied_promo_code_id');
        if (!$cartContainsGiftCardProduct && $discountMode === 'promo' && $promoId) {
            if ($discountEligibleSubtotal <= 0) {
                session()->forget(['applied_promo_code_id', 'applied_promo_code']);
                session(['discount_mode' => 'volume']);
                $discountMode = 'volume';
            } else {
                $promoEligibleUsd = $currency !== 'USD' && $currencyRate > 0
                    ? (float) $discountEligibleSubtotal / $currencyRate
                    : (float) $discountEligibleSubtotal;
                $promo = PromoCode::find($promoId);
                if ($promo && $promo->isValidForSubtotal($promoEligibleUsd)) {
                    $discountUsd = $promo->calculateDiscountUsd($promoEligibleUsd);
                    $discount = $currency !== 'USD' ? $discountUsd * $currencyRate : $discountUsd;
                    $appliedPromoCode = $promo->code;
                } else {
                    session()->forget(['applied_promo_code_id', 'applied_promo_code']);
                    session(['discount_mode' => 'volume']);
                    $discountMode = 'volume';
                }
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

        $cartMissingCrossSellProducts = $this->getMissingCrossSellProducts($cartItems, 8);

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
            'recentlyViewedProducts',
            'cartMissingCrossSellProducts'
        ));
    }

    private function getMissingCrossSellProducts($cartItems, int $limit = 8)
    {
        if ($cartItems->isEmpty()) {
            return collect();
        }

        $cartProductIds = $cartItems->pluck('product_id')->filter()->unique()->values();
        $sourceCategoryIds = $cartItems
            ->map(fn($item) => $item->product?->template?->category_id)
            ->filter()
            ->unique()
            ->values();

        if ($sourceCategoryIds->isEmpty()) {
            return collect();
        }

        $targetCategoryIds = CategoryCrossSell::query()
            ->whereIn('source_category_id', $sourceCategoryIds->all())
            ->orderBy('priority')
            ->pluck('target_category_id')
            ->unique()
            ->values();

        if ($targetCategoryIds->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->availableForDisplay()
            ->with(['shop', 'template'])
            ->whereNotIn('id', $cartProductIds->all())
            ->whereHas('template', function ($query) use ($targetCategoryIds) {
                $query->whereIn('category_id', $targetCategoryIds->all());
            })
            // Bonus: prioritize glue when it's missing in cart.
            ->orderByRaw("CASE WHEN LOWER(products.name) LIKE '%glue%' OR LOWER(products.slug) LIKE '%glue%' THEN 0 ELSE 1 END ASC")
            ->orderBy('products.price', 'asc')
            ->orderByDesc('products.created_at')
            ->limit($limit)
            ->get()
            ->values();
    }
}
