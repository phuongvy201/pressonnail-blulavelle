<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Cart;
use App\Models\ShippingZone;
use App\Services\PayPalService;
use App\Services\ShippingCalculator;
use App\Services\TikTokEventsService;
use App\Services\CurrencyService;
use App\Mail\OrderConfirmation;
use App\Services\PromoCodeSendService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $sessionId = session()->getId();
        $userId = Auth::id();

        // Get cart items from database
        $cartItems = Cart::with(['product.shop', 'product.template', 'product.variants'])
            ->where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Calculate totals
        $subtotal = 0;
        $products = [];

        foreach ($cartItems as $item) {
            $itemTotal = $item->getTotalPriceWithCustomizations();
            $subtotal += $itemTotal;

            $products[] = [
                'product' => $item->product,
                'cart_item' => $item, // Add cart item to access price and customizations
                'quantity' => $item->quantity,
                'total' => $itemTotal
            ];
        }

        // Get currency and rate first for shipping conversion
        $currency = CurrencyService::getCurrencyForDomain();
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

        // Determine default country from currency
        $currencyToCountry = [
            'USD' => 'US',
            'GBP' => 'GB',
            'CAD' => 'CA',
            'MXN' => 'MX',
            'VND' => 'VN',
            'EUR' => 'DE'
        ];
        $defaultCountry = $currencyToCountry[$currency] ?? 'US';
        
        $shippingCost = 0;
        $shippingDetails = session()->get('shipping_details');

        // Calculate default shipping for determined country if not in session
        if (!$shippingDetails) {
            // Prepare items for calculator - convert prices back to USD for shipping calculation
            $items = $cartItems->map(function ($item) use ($currency, $currencyRate) {
                // Use cart item price (already in current currency) and convert back to USD
                // Cart item price includes variant pricing but not customizations (for shipping calculation)
                $priceInUSD = $currency !== 'USD' ? ($item->price / $currencyRate) : $item->price;
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $priceInUSD,
                ];
            });

            $calculator = new ShippingCalculator();
            $shippingDetails = $calculator->calculateShipping($items, $defaultCountry);

            if ($shippingDetails['success']) {
                $shippingCostUSD = $shippingDetails['total_shipping'];
                // Convert shipping cost from USD to current currency
                $shippingCost = $currency !== 'USD'
                    ? CurrencyService::convertFromUSDWithRate($shippingCostUSD, $currency, $currencyRate)
                    : $shippingCostUSD;
                session()->put('shipping_details', $shippingDetails);
            }
        } else {
            $shippingCostUSD = $shippingDetails['total_shipping'] ?? 0;
            // Convert shipping cost from USD to current currency
            $shippingCost = $currency !== 'USD'
                ? CurrencyService::convertFromUSDWithRate($shippingCostUSD, $currency, $currencyRate)
                : $shippingCostUSD;
        }

        // Store original shipping cost in USD before any currency changes
        $originalShippingCostUSD = $shippingCostUSD ?? 0;

        // Apply freeship logic in checkout index view as well
        // Check freeship based on base USD amount (100 USD)
        $baseSubtotal = $currency !== 'USD' ? $subtotal / $currencyRate : $subtotal;
        $qualifiesForFreeShipping = $baseSubtotal >= 100;
        $originalShippingCost = $shippingCost;
        $shippingCost = $qualifiesForFreeShipping ? 0 : $originalShippingCost;

        $taxAmount = 0; // No tax
        $total = $subtotal + $shippingCost;

        // Nếu domain không có currency config, lấy từ country
        $currencyChanged = false;
        if (!$currency || $currency === 'USD') {
            $defaultCountry = $shippingDetails['country'] ?? 'US';
            $currencyFromCountry = $this->getCurrencyFromCountry($defaultCountry);
            // Chỉ dùng currency từ country nếu domain không có config hoặc domain là USD
            $domainCurrency = CurrencyService::getCurrencyForDomain();
            if (!$domainCurrency || $domainCurrency === 'USD') {
                $newCurrency = $currencyFromCountry;
            } else {
                $newCurrency = $domainCurrency;
            }

            if ($newCurrency !== $currency) {
                $currencyChanged = true;
                $currency = $newCurrency;
            }

            // Update currency rate if currency changed
            if ($currency !== 'USD') {
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
            }
        }

        // Subtotal is already in current currency (prices in cart are already converted)
        // If currency changed, convert shipping from USD again, otherwise use already converted shippingCost
        $convertedSubtotal = $subtotal; // Already converted
        if ($currencyChanged && $originalShippingCostUSD > 0) {
            // Currency changed, convert from USD again
            $convertedShipping = $currency !== 'USD'
                ? CurrencyService::convertFromUSDWithRate($originalShippingCostUSD, $currency, $currencyRate)
                : $originalShippingCostUSD;
            // Re-apply freeship logic
            $convertedShipping = $qualifiesForFreeShipping ? 0 : $convertedShipping;
        } else {
            // Currency didn't change, shippingCost is already converted
            $convertedShipping = $shippingCost;
        }
        // Promo code discount (from session – applied on cart page), use final currency
        $discount = 0.0;
        $appliedPromoCode = null;
        $baseSubtotalForPromo = $currency !== 'USD' ? $subtotal / $currencyRate : $subtotal;
        $promoId = session('applied_promo_code_id');
        if ($promoId && $baseSubtotalForPromo > 0) {
            $promo = PromoCode::find($promoId);
            if ($promo && $promo->isValidForSubtotal($baseSubtotalForPromo)) {
                $discountUsd = $promo->calculateDiscountUsd($baseSubtotalForPromo);
                $discount = $currency !== 'USD'
                    ? CurrencyService::convertFromUSDWithRate($discountUsd, $currency, $currencyRate)
                    : $discountUsd;
                $appliedPromoCode = $promo->code;
            } else {
                session()->forget(['applied_promo_code_id', 'applied_promo_code']);
            }
        }

        $total = $subtotal - $discount + $shippingCost;
        $convertedTotal = $convertedSubtotal - $discount + $convertedShipping;

        // Log shipping calculation for debugging
        Log::info('🚚 CHECKOUT CONTROLLER - Shipping Calculation', [
            'originalShippingCostUSD' => $originalShippingCostUSD,
            'shippingCost' => $shippingCost,
            'convertedShipping' => $convertedShipping,
            'currency' => $currency,
            'currencyRate' => $currencyRate,
            'currencyChanged' => $currencyChanged,
            'subtotal' => $subtotal,
            'convertedSubtotal' => $convertedSubtotal,
            'baseSubtotal' => $baseSubtotal,
            'qualifiesForFreeShipping' => $qualifiesForFreeShipping,
            'note' => $currencyChanged ? 'Currency changed, converted from USD again' : 'Currency unchanged, using already converted shippingCost',
            'source' => 'CheckoutController::index'
        ]);

        // Get all active shipping zones for the delivery modal
        $shippingZones = ShippingZone::active()
            ->ordered()
            ->with(['activeShippingRates' => function ($query) {
                $query->ordered();
            }])
            ->get();

        $availableZones = $shippingZones;
        $defaultZone = $availableZones->isNotEmpty() ? $availableZones->first() : null;

        $eventItems = collect($products)->map(function ($item) {
            return [
                'id' => $item['product']->id,
                'name' => $item['product']->name,
                'quantity' => $item['quantity'],
                'price' => $item['cart_item']->getUnitPriceWithCustomizations(),
            ];
        })->values()->toArray();

        $this->trackTikTokCheckoutEvent(
            $request,
            'InitiateCheckout',
            $eventItems,
            $total,
            [
                'description' => sprintf('Checkout started - subtotal %.2f, shipping %.2f', $subtotal, $shippingCost),
            ]
        );

        return view('checkout.index', compact(
            'products',
            'cartItems',
            'subtotal',
            'shippingCost',
            'taxAmount',
            'discount',
            'appliedPromoCode',
            'total',
            'currency',
            'currencyRate',
            'convertedSubtotal',
            'convertedShipping',
            'convertedTotal',
            'shippingDetails',
            'shippingZones',
            'availableZones',
            'defaultZone',
            'defaultCountry'
        ));
    }

    public function process(Request $request)
    {
        Log::info('🔍 CHECKOUT PROCESS STARTED', [
            'method' => $request->method(),
            'url' => $request->url(),
            'payment_method' => $request->input('payment_method'),
            'data' => $request->all()
        ]);

        // Add detailed validation logging for debugging
        Log::info('🔍 VALIDATION DATA CHECK', [
            'customer_name' => $request->input('customer_name'),
            'customer_email' => $request->input('customer_email'),
            'shipping_address' => $request->input('shipping_address'),
            'city' => $request->input('city'),
            'postal_code' => $request->input('postal_code'),
            'country' => $request->input('country'),
            'has_paypal_order_id' => $request->has('paypal_order_id'),
            'paypal_order_id' => $request->input('paypal_order_id'),
            'has_card_token' => $request->has('card_token'),
            'card_token_length' => $request->has('card_token') ? strlen($request->input('card_token')) : 0,
            'has_payment_intent_id' => $request->has('payment_intent_id'),
            'payment_intent_id' => $request->input('payment_intent_id'),
        ]);

        $validationRules = [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'shipping_address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'payment_method' => 'required|in:paypal,lianlian_pay,stripe',
        ];

        // Add PayPal SDK specific validation if present
        if ($request->has('paypal_order_id')) {
            $validationRules['paypal_order_id'] = 'required|string|max:255';
            $validationRules['paypal_payer_id'] = 'required|string|max:255';
        }

        // Add LianLian Pay specific validation if present
        if ($request->has('card_token')) {
            $validationRules['card_token'] = 'required|string|max:255';
        }

        // Add Stripe specific validation if present
        if ($request->has('payment_intent_id')) {
            $validationRules['payment_intent_id'] = 'required|string|max:255';
        }

        // Check if this is an AJAX/JSON request
        $isAjaxRequest = $request->wantsJson() || $request->ajax() || $request->expectsJson();

        try {
            $request->validate($validationRules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('🔍 VALIDATION FAILED', [
                'errors' => $e->errors(),
                'input_data' => $request->all(),
                'is_ajax' => $isAjaxRequest
            ]);

            // Always return JSON for AJAX requests
            if ($isAjaxRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            throw $e;
        }

        $sessionId = session()->getId();
        $userId = Auth::id();

        // Wrap entire processing in try-catch to ensure JSON responses for AJAX requests
        try {
            // Get cart items from database
            $cartItems = Cart::with(['product.shop', 'product.template'])
                ->where(function ($query) use ($sessionId, $userId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })
                ->get();

            if ($cartItems->isEmpty()) {
                Log::warning('🛒 Cart is empty during checkout', [
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'payment_method' => $request->payment_method,
                    'is_ajax' => $isAjaxRequest
                ]);
                
                if ($isAjaxRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your cart is empty. Please add items to your cart before checkout.',
                        'error' => 'cart_empty'
                    ], 400);
                }
                return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
            }

            // Get currency and rate for conversion
            $orderCurrency = $request->currency ?? CurrencyService::getCurrencyForDomain();
            if (!$orderCurrency || $orderCurrency === 'USD') {
                // Fallback to country-based currency if domain doesn't have config
                $orderCurrency = $this->getCurrencyFromCountry($request->country);
            }

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
                $currencyRate = $defaultRates[$orderCurrency] ?? 1.0;
            }

            // Calculate totals
            $subtotal = 0;
            
            // PRIORITY: Use shipping_cost and shipping_zone_id from request if provided
            // This ensures we use the exact shipping cost calculated on the frontend based on selected country/zone
            $originalShippingCostUSD = null;
            $shippingZoneId = null;
            $shippingDetails = null;
            $useRequestShippingCost = false;
            
            if ($request->has('shipping_cost') && $request->shipping_cost !== null && $request->shipping_cost !== '' && $request->shipping_cost > 0) {
                $originalShippingCostUSD = (float) $request->shipping_cost;
                $shippingZoneId = $request->shipping_zone_id ?? null;
                $useRequestShippingCost = true;
                
                Log::info('🚚 Using shipping cost from request', [
                    'shipping_cost_usd' => $originalShippingCostUSD,
                    'shipping_zone_id' => $shippingZoneId,
                    'country' => $request->country
                ]);
                
                // Still calculate shipping details for order items breakdown
                // But use the zone_id from request if provided
                $items = $cartItems->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                });

                $calculator = new ShippingCalculator();
                
                // If we have zone_id from request, we need to find country from zone or use provided country
                // For now, use the country from request and let calculator find the zone
                // But we'll override the total_shipping with the value from request
                $shippingDetails = $calculator->calculateShipping($items, $request->country);
                
                // Override total_shipping with the value from request
                if ($shippingDetails['success']) {
                    $shippingDetails['total_shipping'] = $originalShippingCostUSD;
                    // Override zone_id if provided in request
                    if ($shippingZoneId) {
                        $shippingDetails['zone_id'] = $shippingZoneId;
                    }
                } else {
                    // If calculation failed but we have shipping_cost from request,
                    // create a basic shipping details structure
                    // This should rarely happen, but ensures we can proceed
                    $shippingDetails = [
                        'success' => true,
                        'zone_id' => $shippingZoneId,
                        'zone_name' => null,
                        'country' => $request->country,
                        'total_shipping' => $originalShippingCostUSD,
                        'items' => [],
                        'is_default' => false
                    ];
                    
                    // Distribute shipping cost evenly across items
                    $itemsCount = $cartItems->sum('quantity');
                    $costPerItem = $itemsCount > 0 ? ($originalShippingCostUSD / $itemsCount) : 0;
                    
                    foreach ($cartItems as $cartItem) {
                        $shippingDetails['items'][] = [
                            'product_id' => $cartItem->product_id,
                            'product_name' => $cartItem->product->name ?? 'Unknown',
                            'quantity' => $cartItem->quantity,
                            'shipping_cost' => $costPerItem * $cartItem->quantity,
                            'total_item_shipping' => $costPerItem * $cartItem->quantity,
                            'is_first_item' => false,
                            'shipping_rate_id' => null,
                            'shipping_rate_name' => 'Calculated',
                            'is_default' => false
                        ];
                    }
                }
            } else {
                // Fallback: Get shipping details from session (calculated earlier via AJAX)
                $shippingDetails = session()->get('shipping_details');

                // If no shipping details, calculate now
                if (!$shippingDetails || !isset($shippingDetails['success']) || !$shippingDetails['success']) {
                    // Prepare items for calculator
                    $items = $cartItems->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'price' => $item->price, // Use the actual price from cart (includes variant pricing)
                        ];
                    });

                    $calculator = new ShippingCalculator();
                    $shippingDetails = $calculator->calculateShipping($items, $request->country);

                    if (!$shippingDetails['success']) {
                        return back()->withInput()->withErrors(['shipping' => $shippingDetails['message']]);
                    }
                }
                
                $originalShippingCostUSD = $shippingDetails['total_shipping']; // Shipping cost in USD
                $shippingZoneId = $shippingDetails['zone_id'] ?? null;
                
                Log::info('🚚 Calculated shipping cost from session/calculator', [
                    'shipping_cost_usd' => $originalShippingCostUSD,
                    'shipping_zone_id' => $shippingZoneId,
                    'country' => $request->country
                ]);
            }
            $products = [];

            foreach ($cartItems as $item) {
                $itemTotal = $item->getTotalPriceWithCustomizations();
                $subtotal += $itemTotal;

                $products[] = [
                    'product' => $item->product,
                    'cart_item' => $item, // Add cart item to access price and customizations
                    'quantity' => $item->quantity,
                    'total' => $itemTotal
                ];
            }

            // Check freeship based on base USD amount (100 USD)
            // Convert subtotal back to USD to check freeship threshold
            $baseSubtotalUSD = $orderCurrency !== 'USD' ? ($subtotal / $currencyRate) : $subtotal;
            $qualifiesForFreeShipping = $baseSubtotalUSD >= 100;

            // Convert shipping cost from USD to order currency
            $convertedShippingCost = $orderCurrency !== 'USD'
                ? CurrencyService::convertFromUSDWithRate($originalShippingCostUSD, $orderCurrency, $currencyRate)
                : $originalShippingCostUSD;

            // Apply freeship logic
            $shippingCost = $qualifiesForFreeShipping ? 0 : $convertedShippingCost;

            $taxAmount = 0; // No tax
            $tipAmount = $request->tip_amount ?? 0; // Get tip amount from request (already in USD, will be converted if needed)

            // Convert tip from USD to order currency if needed
            $convertedTipAmount = $orderCurrency !== 'USD' && $tipAmount > 0
                ? CurrencyService::convertFromUSDWithRate($tipAmount, $orderCurrency, $currencyRate)
                : $tipAmount;

            // Promo discount (same session as cart)
            $orderDiscount = 0.0;
            $orderPromoCode = null;
            $promoId = session('applied_promo_code_id');
            if ($promoId && $baseSubtotalUSD > 0) {
                $promo = PromoCode::find($promoId);
                if ($promo && $promo->isValidForSubtotal($baseSubtotalUSD)) {
                    $discountUsd = $promo->calculateDiscountUsd($baseSubtotalUSD);
                    $orderDiscount = $orderCurrency !== 'USD'
                        ? CurrencyService::convertFromUSDWithRate($discountUsd, $orderCurrency, $currencyRate)
                        : $discountUsd;
                    $orderPromoCode = $promo->code;
                }
            }

            $total = $subtotal - $orderDiscount + $shippingCost + $convertedTipAmount;

            // Log freeship application and currency conversion for debugging
            Log::info('🚚 CHECKOUT PROCESS - Shipping & Currency Conversion', [
                'subtotal' => $subtotal,
                'subtotal_currency' => $orderCurrency,
                'baseSubtotalUSD' => $baseSubtotalUSD,
                'originalShippingCostUSD' => $originalShippingCostUSD,
                'convertedShippingCost' => $convertedShippingCost,
                'qualifiesForFreeShipping' => $qualifiesForFreeShipping,
                'finalShippingCost' => $shippingCost,
                'tipAmountUSD' => $tipAmount,
                'convertedTipAmount' => $convertedTipAmount,
                'totalAmount' => $total,
                'currency' => $orderCurrency,
                'currencyRate' => $currencyRate,
                'note' => 'All amounts in order currency except baseSubtotalUSD and originalShippingCostUSD'
            ]);

            $eventItems = collect($products)->map(function ($item) {
                return [
                    'id' => $item['product']->id,
                    'name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'price' => $item['cart_item']->getUnitPriceWithCustomizations(),
                ];
            })->values()->toArray();

            $this->trackTikTokCheckoutEvent(
                $request,
                'AddPaymentInfo',
                $eventItems,
                $total,
                [
                    'description' => sprintf('Payment info submitted via %s', $request->payment_method),
                ],
                [
                    'email' => $request->customer_email,
                    'phone' => $request->customer_phone,
                    'external_id' => $userId,
                ]
            );

            // Create order with converted amounts
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => Auth::id(),
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'subtotal' => $subtotal, // Already in order currency
                'tax_amount' => $taxAmount,
                'discount_amount' => $orderDiscount,
                'promo_code' => $orderPromoCode,
                'shipping_cost' => $shippingCost, // Converted to order currency
                'tip_amount' => $convertedTipAmount, // Converted to order currency
                'total_amount' => $total, // All in order currency
                'currency' => $orderCurrency,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
            ]);

            if ($orderPromoCode && $promoId) {
                PromoCode::where('id', $promoId)->increment('used_count');
                session()->forget(['applied_promo_code_id', 'applied_promo_code']);
            }

            $this->trackTikTokCheckoutEvent(
                $request,
                'PlaceAnOrder',
                $eventItems,
                $total,
                [
                    'content_id' => $order->order_number,
                    'content_name' => 'Order',
                    'description' => sprintf('Order %s placed via %s', $order->order_number, $request->payment_method),
                ],
                [
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                    'external_id' => $order->user_id ?? $userId,
                ]
            );

            // Calculate adjustment ratio if using shipping cost from request
            $calculatedTotalShipping = collect($shippingDetails['items'] ?? [])->sum('shipping_cost');
            $adjustmentRatio = ($calculatedTotalShipping > 0 && $useRequestShippingCost) 
                ? ($originalShippingCostUSD / $calculatedTotalShipping) 
                : 1.0;

            // Create order items with shipping details
            foreach ($products as $item) {
                // Find shipping details for this product
                $itemShipping = collect($shippingDetails['items'] ?? [])->firstWhere('product_id', $item['product']->id);

                // Get item shipping cost in USD
                // Apply adjustment ratio if using shipping cost from request
                $itemShippingCostUSD = ($itemShipping && isset($itemShipping['shipping_cost'])) 
                    ? ($itemShipping['shipping_cost'] * $adjustmentRatio)
                    : 0;

                // Convert item shipping cost from USD to order currency
                $itemShippingCostConverted = $orderCurrency !== 'USD' && $itemShippingCostUSD > 0
                    ? CurrencyService::convertFromUSDWithRate($itemShippingCostUSD, $orderCurrency, $currencyRate)
                    : $itemShippingCostUSD;

                // Apply freeship logic to individual item shipping cost
                $itemShippingCost = $qualifiesForFreeShipping ? 0 : $itemShippingCostConverted;
                $shippingNotes = $itemShipping ? "Rate: {$itemShipping['shipping_rate_name']}" : null;

                // Add freeship note if applicable
                if ($qualifiesForFreeShipping) {
                    $shippingNotes = $shippingNotes ? $shippingNotes . " (FREESHIP Applied)" : "FREESHIP Applied";
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'product_description' => $item['product']->description,
                    'unit_price' => $item['cart_item']->getUnitPriceWithCustomizations(), // Use unit price with customizations
                    'quantity' => $item['quantity'],
                    'total_price' => $item['total'],
                    'product_options' => [
                        'selected_variant' => $item['cart_item']->selected_variant,
                        'customizations' => $item['cart_item']->customizations,
                    ],
                    'shipping_cost' => $itemShippingCost, // Converted to order currency
                    'is_first_item' => $itemShipping['is_first_item'] ?? false,
                    'shipping_notes' => $shippingNotes,
                ]);
            }

            // NOTE: Không xóa cart ở đây vì user chưa thanh toán!
            // Cart sẽ được xóa trong paypalSuccess() hoặc lianlianSuccess() sau khi payment thành công

            Log::info('Order created, waiting for payment confirmation', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_method' => $request->payment_method,
                'user_id' => $userId,
                'session_id' => $sessionId
            ]);

            // Handle payment based on method first, then check AJAX for non-PayPal SDK requests
            if ($request->payment_method === 'paypal') {
                Log::info('PayPal Payment Method Detected', [
                    'has_paypal_order_id' => $request->has('paypal_order_id'),
                    'has_paypal_payer_id' => $request->has('paypal_payer_id'),
                    'paypal_order_id' => $request->input('paypal_order_id'),
                    'paypal_payer_id' => $request->input('paypal_payer_id')
                ]);

                // Check if this is from PayPal SDK (has paypal_order_id)
                if ($request->has('paypal_order_id') && $request->has('paypal_payer_id')) {
                    // This is from PayPal SDK - payment already completed on client side
                    try {
                        Log::info('PayPal SDK Payment Processing', [
                            'order_id' => $order->id,
                            'paypal_order_id' => $request->paypal_order_id,
                            'paypal_payer_id' => $request->paypal_payer_id
                        ]);

                        // For PayPal SDK payments, the payment was already captured on the client side
                        // We should update the order status immediately since we have valid order_id and payer_id

                        Log::info('Updating order for PayPal SDK payment', [
                            'order_id' => $order->id,
                            'current_payment_status' => $order->payment_status,
                            'current_status' => $order->status,
                            'paypal_order_id' => $request->paypal_order_id
                        ]);

                        // Update order status - follow LianLian Pay pattern (simple and direct)
                        $order->update([
                            'payment_status' => 'paid',
                            'status' => 'processing',
                            'payment_id' => $request->paypal_order_id,
                            'payment_transaction_id' => $request->paypal_payer_id,
                            'paid_at' => now()
                        ]);

                        // Try to fetch capture_id for tracking
                        try {
                            $paypalService = new PayPalService();
                            $captureResult = $paypalService->capturePayment($request->paypal_order_id);
                            $captureId = null;
                            if (!empty($captureResult->data['purchase_units'][0]['payments']['captures'][0]['id'])) {
                                $captureId = $captureResult->data['purchase_units'][0]['payments']['captures'][0]['id'];
                            }
                            if ($captureId) {
                                $order->paypal_capture_id = $captureId;
                                $order->save();
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to fetch PayPal capture id for SDK order', [
                                'order_id' => $order->id,
                                'paypal_order_id' => $request->paypal_order_id,
                                'error' => $e->getMessage()
                            ]);
                        }

                        Log::info('✅ PayPal Order Status Updated (following LianLian Pay pattern)', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'payment_status' => 'paid',
                            'status' => 'processing',
                            'paypal_order_id' => $request->paypal_order_id,
                            'paypal_payer_id' => $request->paypal_payer_id
                        ]);

                        // Clear cart after successful order status update (CRITICAL)
                        Log::info('🛒 Clearing cart after PayPal payment status update', [
                            'user_id' => $userId,
                            'session_id' => $sessionId
                        ]);

                        $deletedCartCount = Cart::where(function ($query) use ($sessionId, $userId) {
                            if ($userId) {
                                $query->where('user_id', $userId);
                            } else {
                                $query->where('session_id', $sessionId);
                            }
                        })->delete();

                        Log::info('🛒 Cart deletion result', [
                            'deleted_cart_items' => $deletedCartCount,
                            'user_id' => $userId,
                            'session_id' => $sessionId,
                            'order_id' => $order->id
                        ]);

                        // Verify cart is actually empty
                        $remainingCartItems = Cart::where(function ($query) use ($sessionId, $userId) {
                            if ($userId) {
                                $query->where('user_id', $userId);
                            } else {
                                $query->where('session_id', $sessionId);
                            }
                        })->count();

                        Log::info('🛒 Cart verification after deletion', [
                            'remaining_cart_items' => $remainingCartItems,
                            'user_id' => $userId,
                            'session_id' => $sessionId
                        ]);

                        $this->trackTikTokCheckoutEvent(
                            $request,
                            'Purchase',
                            $eventItems,
                            $total,
                            [
                                'content_id' => $order->order_number,
                                'content_name' => 'Order',
                                'description' => sprintf('Order %s purchased via PayPal SDK', $order->order_number),
                            ],
                            [
                                'email' => $order->customer_email,
                                'phone' => $order->customer_phone,
                                'external_id' => $order->user_id ?? $userId,
                            ]
                        );

                        // Clear shipping session
                        Session::forget('shipping_details');

                        // Try to verify with PayPal API (optional, for logging - AFTER cart clearing)
                        try {
                            $paypalService = new PayPalService();
                            $payment = $paypalService->verifyOrderSafely($request->paypal_order_id);

                            Log::info('PayPal API Verification Result', [
                                'order_id' => $order->id,
                                'payment_status_from_api' => $payment ? $payment->status : 'null',
                                'verification_safe' => true
                            ]);
                        } catch (\Exception $verifyException) {
                            Log::warning('PayPal API verification failed but continuing', [
                                'order_id' => $order->id,
                                'error' => $verifyException->getMessage()
                            ]);
                        }

                        // Always return JSON for AJAX requests (PayPal SDK always sends AJAX)
                        if ($isAjaxRequest || $request->has('paypal_order_id')) {
                            Log::info('💳 PayPal SDK AJAX Response - SENDING TO FRONTEND', [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'wants_json' => $request->wantsJson(),
                                'ajax' => $request->ajax(),
                                'is_ajax_request' => $isAjaxRequest,
                                'has_paypal_order_id' => $request->has('paypal_order_id'),
                                'response_type' => 'json',
                                'cart_cleared' => true,
                                'order_status_updated' => true
                            ]);

                            $response = response()->json([
                                'success' => true,
                                'message' => 'Payment completed successfully',
                                'order_number' => $order->order_number,
                                'payment_completed' => true
                            ]);

                            Log::info('💳 PayPal Response Being Sent', [
                                'response_content' => $response->getContent(),
                                'order_number' => $order->order_number
                            ]);

                            return $response;
                        }

                        Log::info('💳 PayPal SDK Redirect Response', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'response_type' => 'redirect'
                        ]);

                        return redirect()->route('checkout.success', $order->order_number)
                            ->with('success', 'Payment completed successfully!');
                    } catch (\Exception $e) {
                        Log::error('PayPal SDK Payment Error', [
                            'order_id' => $order->id ?? null,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'paypal_order_id' => $request->paypal_order_id,
                            'is_ajax' => $isAjaxRequest
                        ]);

                        // Always return JSON for PayPal SDK requests (they are always AJAX)
                        if ($isAjaxRequest || $request->has('paypal_order_id')) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Payment processing failed: ' . $e->getMessage(),
                                'error' => config('app.debug') ? $e->getMessage() : 'Payment processing failed'
                            ], 400);
                        }

                        return redirect()->back()->with('error', 'Payment processing failed: ' . $e->getMessage());
                    }
                } else {
                    // This is the old PayPal flow (redirect-based)
                    try {
                        $paypalService = new PayPalService();
                        $payment = $paypalService->createPayment($order, $products);

                        // Store order in session for PayPal callback
                        Session::put('pending_order', $order->id);

                        // Redirect to PayPal approval URL
                        return redirect($payment->approval_url);
                    } catch (\Exception $e) {
                        Log::error('PayPal Initialization Error', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                        return redirect()->back()->with('error', 'Payment initialization failed: ' . $e->getMessage());
                    }
                }
            } elseif ($request->payment_method === 'stripe') {
                Log::info('🎯 STRIPE PAYMENT SECTION ENTERED', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'has_payment_intent_id' => $request->has('payment_intent_id')
                ]);

                // Check if payment intent ID is provided (from Stripe Elements)
                if ($request->has('payment_intent_id')) {
                    try {
                        Log::info('💳 Stripe Payment Processing', [
                            'order_id' => $order->id,
                            'payment_intent_id' => $request->payment_intent_id
                        ]);

                        // For Stripe payments, the payment was already confirmed on the client side
                        // We should update the order status immediately since we have valid payment_intent_id

                        Log::info('Updating order for Stripe payment', [
                            'order_id' => $order->id,
                            'current_payment_status' => $order->payment_status,
                            'current_status' => $order->status,
                            'payment_intent_id' => $request->payment_intent_id
                        ]);

                        // Update order status - follow PayPal pattern (simple and direct)
                        $order->update([
                            'payment_status' => 'paid',
                            'status' => 'processing',
                            'payment_id' => $request->payment_intent_id,
                            'payment_transaction_id' => $request->payment_intent_id,
                            'paid_at' => now()
                        ]);

                        Log::info('✅ Stripe Order Status Updated', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'payment_status' => 'paid',
                            'status' => 'processing',
                            'payment_intent_id' => $request->payment_intent_id
                        ]);

                        // Clear cart after successful order status update
                        Log::info('🛒 Clearing cart after Stripe payment status update', [
                            'user_id' => $userId,
                            'session_id' => $sessionId
                        ]);

                        $deletedCartCount = Cart::where(function ($query) use ($sessionId, $userId) {
                            if ($userId) {
                                $query->where('user_id', $userId);
                            } else {
                                $query->where('session_id', $sessionId);
                            }
                        })->delete();

                        Log::info('🛒 Cart deletion result', [
                            'deleted_cart_items' => $deletedCartCount,
                            'user_id' => $userId,
                            'session_id' => $sessionId,
                            'order_id' => $order->id
                        ]);

                        // Clear shipping session
                        Session::forget('shipping_details');

                        // Send order confirmation email to customer and admin
                        $adminEmail = config('mail.from.address');
                        try {
                            Mail::to($order->customer_email)->send(new OrderConfirmation($order));
                            Log::info('📧 Order confirmation email sent for Stripe payment', [
                                'order_number' => $order->order_number,
                                'email' => $order->customer_email
                            ]);

                            if ($adminEmail) {
                                Mail::to($adminEmail)->send(new OrderConfirmation($order));
                                Log::info('📧 Admin new-order email sent for Stripe payment', [
                                    'order_number' => $order->order_number,
                                    'email' => $adminEmail
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error('❌ Failed to send order confirmation email for Stripe payment', [
                                'order_number' => $order->order_number,
                                'email' => $order->customer_email,
                                'admin_email' => $adminEmail,
                                'error' => $e->getMessage()
                            ]);
                        }

                        // Always return JSON for AJAX requests (Stripe always sends AJAX)
                        if ($isAjaxRequest || $request->has('payment_intent_id')) {
                            Log::info('💳 Stripe AJAX Response - SENDING TO FRONTEND', [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'wants_json' => $request->wantsJson(),
                                'ajax' => $request->ajax(),
                                'is_ajax_request' => $isAjaxRequest,
                                'has_payment_intent_id' => $request->has('payment_intent_id'),
                                'response_type' => 'json',
                                'cart_cleared' => true,
                                'order_status_updated' => true
                            ]);

                            $response = response()->json([
                                'success' => true,
                                'message' => 'Payment completed successfully',
                                'order_number' => $order->order_number,
                                'payment_completed' => true
                            ]);

                            Log::info('💳 Stripe Response Being Sent', [
                                'response_content' => $response->getContent(),
                                'order_number' => $order->order_number
                            ]);

                            return $response;
                        }

                        Log::info('💳 Stripe Redirect Response', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'response_type' => 'redirect'
                        ]);

                        return redirect()->route('checkout.success', $order->order_number)
                            ->with('success', 'Payment completed successfully!');
                    } catch (\Exception $e) {
                        Log::error('Stripe Payment Error', [
                            'order_id' => $order->id ?? null,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'payment_intent_id' => $request->payment_intent_id,
                            'is_ajax' => $isAjaxRequest
                        ]);

                        // Always return JSON for Stripe requests (they are always AJAX)
                        if ($isAjaxRequest || $request->has('payment_intent_id')) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Payment processing failed: ' . $e->getMessage(),
                                'error' => config('app.debug') ? $e->getMessage() : 'Payment processing failed'
                            ], 400);
                        }

                        return redirect()->back()->with('error', 'Payment processing failed: ' . $e->getMessage());
                    }
                } else {
                    // No payment intent ID provided - this is normal for initial order creation
                    Log::info('📝 Stripe Order Created (Waiting for Payment)', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => 'Order created, waiting for payment processing'
                    ]);

                    // Store order in session for Stripe callback
                    Session::put('pending_order', $order->id);

                    return response()->json([
                        'success' => true,
                        'message' => 'Order created successfully. Please complete payment.',
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'total_amount' => $order->total_amount,
                        'payment_method' => $order->payment_method,
                        'payment_pending' => true
                    ]);
                }
            } elseif ($request->payment_method === 'lianlian_pay') {
                Log::info('🎯 LIANLIAN PAY SECTION ENTERED', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);

                Log::info('🔍 CHECKOUT CONTROLLER LIANLIAN PAY FLOW', [
                    'order_id' => $order->id,
                    'has_card_token' => $request->has('card_token'),
                    'request_data' => $request->all()
                ]);

                // Check if card token is provided (from iframe binding card)
                if ($request->has('card_token')) {
                    // Store order in session for LianLian Pay callback
                    Session::put('pending_order', $order->id);
                    // Store card token and card type for payment processing
                    session([
                        'lianlian_card_info' => [
                            'card_token' => $request->card_token,
                            'card_type' => $request->card_type ?? 'PAYMENT_TOKEN', // PAYMENT_TOKEN or LIANLIAN_TOKEN
                            'holder_name' => $order->customer_name,
                            'billing_address' => [
                                'line1' => $order->shipping_address,
                                'line2' => '',
                                'city' => $order->city,
                                'state' => $order->state ?? '',
                                'postal_code' => $order->postal_code,
                                'country' => $order->country,
                            ]
                        ]
                    ]);

                    // Create payment using LianLian Pay
                    try {
                        $lianLianPayService = new \App\Services\LianLianPayServiceV2();
                        $paymentResponse = $lianLianPayService->createPayment($order);

                        // Check if payment was successful
                        if (isset($paymentResponse['return_code']) && $paymentResponse['return_code'] !== 'SUCCESS') {
                            Log::error('LianLian Pay Payment Failed', [
                                'order_id' => $order->id,
                                'return_code' => $paymentResponse['return_code'],
                                'return_message' => $paymentResponse['return_message'] ?? 'Unknown error',
                                'response' => $paymentResponse
                            ]);

                            return response()->json([
                                'success' => false,
                                'error' => 'Payment failed',
                                'message' => $paymentResponse['return_message'] ?? 'Payment processing failed',
                                'return_code' => $paymentResponse['return_code']
                            ], 400);
                        }

                        // Check if 3DS authentication is required
                        $requires3DS = false;
                        $threeDSecureUrl = null;

                        if (
                            isset($paymentResponse['order']['3ds_status']) &&
                            $paymentResponse['order']['3ds_status'] === 'CHALLENGE' &&
                            isset($paymentResponse['order']['payment_url'])
                        ) {
                            $requires3DS = true;
                            $threeDSecureUrl = $paymentResponse['order']['payment_url'];
                        } elseif (isset($paymentResponse['3ds_url'])) {
                            $requires3DS = true;
                            $threeDSecureUrl = $paymentResponse['3ds_url'];
                        } elseif (isset($paymentResponse['redirect_url'])) {
                            $requires3DS = true;
                            $threeDSecureUrl = $paymentResponse['redirect_url'];
                        }

                        // Update order with payment transaction ID
                        $transactionId = $paymentResponse['order']['ll_transaction_id']
                            ?? $paymentResponse['merchant_transaction_id']
                            ?? null;

                        // Check payment status từ response
                        $paymentStatus = $paymentResponse['order']['payment_data']['payment_status'] ?? null;

                        Log::info('CheckoutController Payment Status Check', [
                            'order_id' => $order->id,
                            'payment_status_code' => $paymentStatus,
                            'transaction_id' => $transactionId,
                            'return_code' => $paymentResponse['return_code']
                        ]);

                        // Nếu payment_status = "PS" (Payment Success), mark order as paid ngay và xóa cart
                        if ($paymentStatus === 'PS') {
                            $order->update([
                                'payment_method' => 'lianlian_pay',
                                'payment_transaction_id' => $transactionId,
                                'payment_status' => 'paid',
                                'status' => 'processing',
                                'paid_at' => now()
                            ]);

                            Log::info('Payment Completed Immediately (PS) in CheckoutController', [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'transaction_id' => $transactionId
                            ]);

                            // Xóa cart ngay khi payment thành công - theo pattern từ PayPal
                            Log::info('🛒 Clearing cart after LianLian Pay immediate success', [
                                'user_id' => $userId,
                                'session_id' => $sessionId,
                                'order_id' => $order->id
                            ]);

                            $deletedCartCount = Cart::where(function ($query) use ($sessionId, $userId) {
                                if ($userId) {
                                    $query->where('user_id', $userId);
                                } else {
                                    $query->where('session_id', $sessionId);
                                }
                            })->delete();

                            Log::info('🛒 LianLian Pay cart deletion result', [
                                'deleted_cart_items' => $deletedCartCount,
                                'user_id' => $userId,
                                'session_id' => $sessionId,
                                'order_id' => $order->id
                            ]);

                            // Clear shipping session
                            Session::forget('shipping_details');

                            // Send order confirmation email to customer and admin
                            $adminEmail = config('mail.from.address');
                            try {
                                Mail::to($order->customer_email)->send(new OrderConfirmation($order));
                                Log::info('📧 Order confirmation email sent for immediate LianLian payment', [
                                    'order_number' => $order->order_number,
                                    'email' => $order->customer_email
                                ]);

                                if ($adminEmail) {
                                    Mail::to($adminEmail)->send(new OrderConfirmation($order));
                                    Log::info('📧 Admin new-order email sent for immediate LianLian payment', [
                                        'order_number' => $order->order_number,
                                        'email' => $adminEmail
                                    ]);
                                }
                            } catch (\Exception $e) {
                                Log::error('❌ Failed to send order confirmation email for LianLian payment', [
                                    'order_number' => $order->order_number,
                                    'email' => $order->customer_email,
                                    'admin_email' => $adminEmail,
                                    'error' => $e->getMessage()
                                ]);
                            }

                            // Return success with payment_completed flag
                            return response()->json([
                                'success' => true,
                                'requires_3ds' => $requires3DS,
                                'redirect_url' => $threeDSecureUrl,
                                'transaction_id' => $paymentResponse['merchant_transaction_id'] ?? null,
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'payment_completed' => true, // Flag để frontend biết payment đã thành công
                                'payment_status' => 'paid',
                                'data' => $paymentResponse
                            ]);
                        } else {
                            // Nếu chưa paid, set pending - theo cách cũ
                            $order->update([
                                'payment_method' => 'lianlian_pay',
                                'payment_transaction_id' => $transactionId,
                                'payment_status' => 'pending'
                            ]);

                            Log::info('Payment Still Pending', [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'payment_status_code' => $paymentStatus,
                                'transaction_id' => $transactionId
                            ]);
                        }

                        // Return JSON response for frontend cho trường hợp payment chưa thành công ngay
                        return response()->json([
                            'success' => true,
                            'requires_3ds' => $requires3DS,
                            'redirect_url' => $threeDSecureUrl,
                            'transaction_id' => $paymentResponse['merchant_transaction_id'] ?? null,
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'payment_completed' => false, // Payment chưa thành công
                            'payment_status' => 'pending',
                            'data' => $paymentResponse
                        ]);
                    } catch (\Exception $e) {
                        Log::error('LianLian Pay Creation Error', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);

                        return response()->json([
                            'success' => false,
                            'error' => 'Payment creation failed',
                            'message' => $e->getMessage()
                        ], 500);
                    }
                } else {
                    // No card token provided - this is normal for initial order creation
                    // The order will be updated when payment is processed
                    Log::info('📝 LianLian Pay Order Created (Waiting for Payment)', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => 'Order created, waiting for payment processing'
                    ]);

                    // Store order in session for LianLian Pay callback
                    Session::put('pending_order', $order->id);

                    return response()->json([
                        'success' => true,
                        'message' => 'Order created successfully. Please complete payment.',
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'total_amount' => $order->total_amount,
                        'payment_method' => $order->payment_method,
                        'payment_pending' => true
                    ]);
                }
            }

            // For other payment methods (should not reach here normally)
            // Cart will be cleared in respective success callbacks

            // Handle AJAX requests for non-PayPal SDK requests (like LianLian Pay redirect)
            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'payment_method' => $order->payment_method
                ]);
            }

            return redirect()->route('checkout.success', $order->order_number);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('❌ CHECKOUT PROCESS EXCEPTION', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'is_ajax' => $isAjaxRequest,
                'request_data' => $request->all()
            ]);

            // Always return JSON for AJAX requests, even on exceptions
            if ($isAjaxRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing your order: ' . $e->getMessage(),
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }

            // For non-AJAX requests, redirect with error
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while processing your order. Please try again.');
        }
    }

    public function processLianLianPayment(Request $request)
    {
        try {
            Log::info('🔄 LianLian Pay Payment Processing Started', [
                'request_data' => $request->all()
            ]);

            // Get order from session
            $orderId = Session::get('pending_order');
            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Order not found',
                    'message' => 'Please try again'
                ], 400);
            }

            $order = Order::findOrFail($orderId);

            Log::info('📋 Order Found for LianLian Pay Processing', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'has_card_token' => $request->has('card_token')
            ]);

            // Check if card token is provided
            if (!$request->has('card_token')) {
                return response()->json([
                    'success' => false,
                    'error' => 'No card token provided',
                    'message' => 'Please enter your card information'
                ], 400);
            }

            // Store card token and card type for payment processing
            session([
                'lianlian_card_info' => [
                    'card_token' => $request->card_token,
                    'card_type' => $request->card_type ?? 'PAYMENT_TOKEN',
                    'holder_name' => $order->customer_name,
                    'billing_address' => [
                        'line1' => $order->shipping_address,
                        'line2' => '',
                        'city' => $order->city,
                        'state' => $order->state ?? '',
                        'postal_code' => $order->postal_code,
                        'country' => $order->country,
                    ]
                ]
            ]);

            // Create payment using LianLian Pay
            $lianLianPayService = new \App\Services\LianLianPayServiceV2();
            $paymentResponse = $lianLianPayService->createPayment($order);

            Log::info('💳 LianLian Pay Payment Response', [
                'order_id' => $order->id,
                'response' => $paymentResponse
            ]);

            // Check if payment was successful
            if (isset($paymentResponse['return_code']) && $paymentResponse['return_code'] !== 'SUCCESS') {
                Log::error('❌ LianLian Pay Payment Failed', [
                    'order_id' => $order->id,
                    'return_code' => $paymentResponse['return_code'],
                    'return_message' => $paymentResponse['return_message'] ?? 'Unknown error'
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Payment failed',
                    'message' => $paymentResponse['return_message'] ?? 'Payment processing failed',
                    'return_code' => $paymentResponse['return_code']
                ], 400);
            }

            // Check if 3DS authentication is required
            $requires3DS = false;
            $threeDSecureUrl = null;

            if (
                isset($paymentResponse['order']['3ds_status']) &&
                $paymentResponse['order']['3ds_status'] === 'CHALLENGE' &&
                isset($paymentResponse['order']['payment_url'])
            ) {
                $requires3DS = true;
                $threeDSecureUrl = $paymentResponse['order']['payment_url'];
            } elseif (isset($paymentResponse['3ds_url'])) {
                $requires3DS = true;
                $threeDSecureUrl = $paymentResponse['3ds_url'];
            } elseif (isset($paymentResponse['redirect_url'])) {
                $requires3DS = true;
                $threeDSecureUrl = $paymentResponse['redirect_url'];
            }

            // Update order with payment transaction ID
            $transactionId = $paymentResponse['order']['ll_transaction_id']
                ?? $paymentResponse['merchant_transaction_id']
                ?? null;

            // Check payment status from response
            $paymentStatus = $paymentResponse['order']['payment_data']['payment_status'] ?? null;

            Log::info('🔍 LianLian Pay Payment Status Check', [
                'order_id' => $order->id,
                'payment_status_code' => $paymentStatus,
                'transaction_id' => $transactionId,
                'return_code' => $paymentResponse['return_code']
            ]);

            // Handle different payment statuses
            if ($paymentStatus === 'SUCCESS' || $paymentStatus === 'COMPLETED' || $paymentStatus === 'PS') {
                // Payment successful - update order status
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'payment_id' => $transactionId,
                    'payment_transaction_id' => $transactionId,
                    'paid_at' => now()
                ]);

                Log::info('✅ LianLian Pay Payment Completed Successfully', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'transaction_id' => $transactionId,
                    'payment_status' => $paymentStatus
                ]);

                // Clear cart from database
                $sessionId = session()->getId();
                $userId = Auth::id();
                Cart::where(function ($query) use ($sessionId, $userId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })->delete();

                // Clear session
                Session::forget('pending_order');
                Session::forget('lianlian_card_info');

                return response()->json([
                    'success' => true,
                    'message' => 'Payment completed successfully',
                    'order_number' => $order->order_number,
                    'payment_completed' => true
                ]);
            } elseif ($requires3DS) {
                // 3DS authentication required
                Log::info('🔐 LianLian Pay 3DS Authentication Required', [
                    'order_id' => $order->id,
                    '3ds_url' => $threeDSecureUrl
                ]);

                return response()->json([
                    'success' => true,
                    'requires_3ds' => true,
                    '3ds_url' => $threeDSecureUrl,
                    'order_id' => $order->id,
                    'message' => '3DS authentication required'
                ]);
            } else {
                // Payment pending or other status
                Log::info('⏳ LianLian Pay Payment Pending', [
                    'order_id' => $order->id,
                    'payment_status' => $paymentStatus,
                    'transaction_id' => $transactionId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment is being processed',
                    'order_id' => $order->id,
                    'payment_pending' => true
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ LianLian Pay Payment Processing Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function success($orderNumber)
    {
        $order = Order::with(['items.product'])->where('order_number', $orderNumber)->firstOrFail();

        // Gửi email promo "thank you" (mỗi đơn chỉ gửi 1 lần)
        if ($order->customer_email) {
            app(PromoCodeSendService::class)->sendThankYouIfNotSent($order->customer_email, $order);
        }

        // Get currency and rate for display
        $currency = $order->currency ?? CurrencyService::getCurrencyForDomain() ?? 'USD';
        $currencyRate = CurrencyService::getCurrencyRateForDomain();

        // If no rate from domain, use default rates
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

        // Order amounts are already stored in the order's currency
        // If display currency matches order currency, use order amounts directly (no conversion)
        $orderCurrency = $order->currency ?? 'USD';

        if ($currency === $orderCurrency) {
            // Same currency, no conversion needed - amounts are already in correct currency
            $convertedSubtotal = $order->subtotal;
            $convertedShipping = $order->shipping_cost;
            $convertedTax = $order->tax_amount;
            $convertedTip = $order->tip_amount;
            $convertedTotal = $order->total_amount;
        } else {
            // Different currency, need to convert from order currency to display currency
            // Order amounts are in order currency, convert to display currency
            // First: convert from order currency to USD
            $orderCurrencyRate = CurrencyService::getCurrencyRateForDomain() ?? 1.0;
            if ($orderCurrency !== 'USD' && $orderCurrencyRate != 1.0) {
                // Convert from order currency to USD
                $subtotalUSD = $order->subtotal / $orderCurrencyRate;
                $shippingUSD = $order->shipping_cost / $orderCurrencyRate;
                $taxUSD = $order->tax_amount / $orderCurrencyRate;
                $tipUSD = $order->tip_amount / $orderCurrencyRate;
                $totalUSD = $order->total_amount / $orderCurrencyRate;
            } else {
                // Order is already in USD
                $subtotalUSD = $order->subtotal;
                $shippingUSD = $order->shipping_cost;
                $taxUSD = $order->tax_amount;
                $tipUSD = $order->tip_amount;
                $totalUSD = $order->total_amount;
            }

            // Second: convert from USD to display currency
            $convertedSubtotal = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($subtotalUSD, $currency, $currencyRate) : $subtotalUSD;
            $convertedShipping = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($shippingUSD, $currency, $currencyRate) : $shippingUSD;
            $convertedTax = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($taxUSD, $currency, $currencyRate) : $taxUSD;
            $convertedTip = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($tipUSD, $currency, $currencyRate) : $tipUSD;
            $convertedTotal = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($totalUSD, $currency, $currencyRate) : $totalUSD;
        }

        return view('checkout.success', compact('order', 'currency', 'currencyRate', 'convertedSubtotal', 'convertedShipping', 'convertedTax', 'convertedTip', 'convertedTotal'));
    }

    /**
     * Download order receipt as PDF
     */
    public function downloadReceipt($orderNumber, Request $request)
    {
        $order = Order::with(['items.product'])->where('order_number', $orderNumber)->firstOrFail();

        // Determine country/language for receipt
        $country = $order->country ?? 'US';
        $locale = $this->getLocaleFromCountry($country);

        // Get currency and rate for receipt
        $currency = $order->currency ?? CurrencyService::getCurrencyForDomain() ?? 'USD';
        $currencyRate = CurrencyService::getCurrencyRateForDomain();

        // If no rate from domain, use default rates
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

        // Order amounts are already stored in the order's currency
        // If display currency matches order currency, use order amounts directly (no conversion)
        $orderCurrency = $order->currency ?? 'USD';

        if ($currency === $orderCurrency) {
            // Same currency, no conversion needed - amounts are already in correct currency
            $convertedSubtotal = $order->subtotal;
            $convertedShipping = $order->shipping_cost;
            $convertedTax = $order->tax_amount;
            $convertedTip = $order->tip_amount;
            $convertedTotal = $order->total_amount;
        } else {
            // Different currency, need to convert from order currency to display currency
            // Order amounts are in order currency, convert to display currency
            // First: convert from order currency to USD
            $orderCurrencyRate = CurrencyService::getCurrencyRateForDomain() ?? 1.0;
            if ($orderCurrency !== 'USD' && $orderCurrencyRate != 1.0) {
                // Convert from order currency to USD
                $subtotalUSD = $order->subtotal / $orderCurrencyRate;
                $shippingUSD = $order->shipping_cost / $orderCurrencyRate;
                $taxUSD = $order->tax_amount / $orderCurrencyRate;
                $tipUSD = $order->tip_amount / $orderCurrencyRate;
                $totalUSD = $order->total_amount / $orderCurrencyRate;
            } else {
                // Order is already in USD
                $subtotalUSD = $order->subtotal;
                $shippingUSD = $order->shipping_cost;
                $taxUSD = $order->tax_amount;
                $tipUSD = $order->tip_amount;
                $totalUSD = $order->total_amount;
            }

            // Second: convert from USD to display currency
            $convertedSubtotal = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($subtotalUSD, $currency, $currencyRate) : $subtotalUSD;
            $convertedShipping = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($shippingUSD, $currency, $currencyRate) : $shippingUSD;
            $convertedTax = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($taxUSD, $currency, $currencyRate) : $taxUSD;
            $convertedTip = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($tipUSD, $currency, $currencyRate) : $tipUSD;
            $convertedTotal = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($totalUSD, $currency, $currencyRate) : $totalUSD;
        }

        // Set locale for receipt
        app()->setLocale($locale);

        try {
            // Generate PDF receipt
            $pdf = Pdf::loadView('checkout.receipt', [
                'order' => $order,
                'country' => $country,
                'locale' => $locale,
                'currency' => $currency,
                'currencyRate' => $currencyRate,
                'convertedSubtotal' => $convertedSubtotal,
                'convertedShipping' => $convertedShipping,
                'convertedTax' => $convertedTax,
                'convertedTip' => $convertedTip,
                'convertedTotal' => $convertedTotal,
            ])->setPaper('a4', 'portrait');

            $filename = 'receipt-' . $order->order_number . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('PDF generation failed: ' . $e->getMessage(), [
                'order_number' => $orderNumber,
                'error' => $e->getTraceAsString()
            ]);

            // Fallback: return error or HTML
            return back()->with('error', 'Failed to generate PDF receipt. Please try again.');
        }
    }

    /**
     * Show order receipt as HTML page (view/print in browser).
     */
    public function showReceipt($orderNumber)
    {
        $order = Order::with(['items.product'])->where('order_number', $orderNumber)->firstOrFail();
        $country = $order->country ?? 'US';
        $locale = $this->getLocaleFromCountry($country);
        $currency = $order->currency ?? CurrencyService::getCurrencyForDomain() ?? 'USD';
        $currencyRate = CurrencyService::getCurrencyRateForDomain();

        if (!$currencyRate || $currencyRate == 1.0) {
            $defaultRates = ['USD' => 1.0, 'GBP' => 0.79, 'EUR' => 0.92, 'CAD' => 1.35, 'AUD' => 1.52, 'JPY' => 150.0, 'CNY' => 7.2, 'HKD' => 7.8, 'SGD' => 1.34];
            $currencyRate = $defaultRates[$currency] ?? 1.0;
        }

        $orderCurrency = $order->currency ?? 'USD';
        if ($currency === $orderCurrency) {
            $convertedSubtotal = $order->subtotal;
            $convertedShipping = $order->shipping_cost;
            $convertedTax = $order->tax_amount;
            $convertedTip = $order->tip_amount;
            $convertedTotal = $order->total_amount;
        } else {
            $orderCurrencyRate = CurrencyService::getCurrencyRateForDomain() ?? 1.0;
            if ($orderCurrency !== 'USD' && $orderCurrencyRate != 1.0) {
                $subtotalUSD = $order->subtotal / $orderCurrencyRate;
                $shippingUSD = $order->shipping_cost / $orderCurrencyRate;
                $taxUSD = $order->tax_amount / $orderCurrencyRate;
                $tipUSD = $order->tip_amount / $orderCurrencyRate;
                $totalUSD = $order->total_amount / $orderCurrencyRate;
            } else {
                $subtotalUSD = $order->subtotal;
                $shippingUSD = $order->shipping_cost;
                $taxUSD = $order->tax_amount;
                $tipUSD = $order->tip_amount;
                $totalUSD = $order->total_amount;
            }
            $convertedSubtotal = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($subtotalUSD, $currency, $currencyRate) : $subtotalUSD;
            $convertedShipping = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($shippingUSD, $currency, $currencyRate) : $shippingUSD;
            $convertedTax = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($taxUSD, $currency, $currencyRate) : $taxUSD;
            $convertedTip = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($tipUSD, $currency, $currencyRate) : $tipUSD;
            $convertedTotal = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($totalUSD, $currency, $currencyRate) : $totalUSD;
        }

        app()->setLocale($locale);

        return view('checkout.receipt', [
            'order' => $order,
            'country' => $country,
            'locale' => $locale,
            'currency' => $currency,
            'currencyRate' => $currencyRate,
            'convertedSubtotal' => $convertedSubtotal,
            'convertedShipping' => $convertedShipping,
            'convertedTax' => $convertedTax,
            'convertedTip' => $convertedTip,
            'convertedTotal' => $convertedTotal,
        ]);
    }

    /**
     * Get locale from country code
     */
    /**
     * Get currency code from country code
     */
    private function getCurrencyFromCountry(string $country): string
    {
        $countryToCurrency = [
            'US' => 'USD',
            'GB' => 'GBP',
            'CA' => 'CAD',
            'AU' => 'AUD',
            'NZ' => 'NZD',
            'JP' => 'JPY',
            'CN' => 'CNY',
            'HK' => 'HKD',
            'SG' => 'SGD',
            'EU' => 'EUR',
            'DE' => 'EUR',
            'FR' => 'EUR',
            'IT' => 'EUR',
            'ES' => 'EUR',
            'NL' => 'EUR',
            'BE' => 'EUR',
            'AT' => 'EUR',
            'PT' => 'EUR',
            'IE' => 'EUR',
            'FI' => 'EUR',
            'GR' => 'EUR',
        ];

        return $countryToCurrency[strtoupper($country)] ?? 'USD';
    }

    private function getLocaleFromCountry($country)
    {
        $countryLocaleMap = [
            'US' => 'en',
            'GB' => 'en',
            'VN' => 'vi',
            'CN' => 'zh',
            'JP' => 'ja',
            'KR' => 'ko',
            'FR' => 'fr',
            'DE' => 'de',
            'ES' => 'es',
            'IT' => 'it',
            'PT' => 'pt',
            'RU' => 'ru',
            'AR' => 'es',
            'MX' => 'es',
            'BR' => 'pt',
            'CA' => 'en',
            'AU' => 'en',
            'NZ' => 'en',
        ];

        return $countryLocaleMap[strtoupper($country)] ?? 'en';
    }

    public function paypalSuccess(Request $request)
    {
        $paymentId = $request->get('paymentId');
        $payerId = $request->get('PayerID');

        if (!$paymentId || !$payerId) {
            return redirect()->route('checkout.index')->with('error', 'Payment failed.');
        }

        try {
            $paypalService = new PayPalService();
            $payment = $paypalService->executePayment($paymentId, $payerId);

            // Get pending order from session
            $orderId = Session::get('pending_order');
            if (!$orderId) {
                return redirect()->route('checkout.index')->with('error', 'Order not found.');
            }

            $order = Order::findOrFail($orderId);

            // Update payment status
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'payment_id' => $paymentId,
                'paid_at' => now()
            ]);

            $order->load('items');
            $purchaseItems = $order->items->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->unit_price,
                ];
            })->toArray();

            // Clear session and cart from database
            $sessionId = session()->getId();
            $userId = Auth::id();

            Session::forget('pending_order');
            Cart::where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })->delete();

            // Send order confirmation email to customer and admin
            $adminEmail = config('mail.from.address');
            try {
                Mail::to($order->customer_email)->send(new OrderConfirmation($order));
                Log::info('📧 Order confirmation email sent', [
                    'order_number' => $order->order_number,
                    'email' => $order->customer_email
                ]);

                if ($adminEmail) {
                    Mail::to($adminEmail)->send(new OrderConfirmation($order));
                    Log::info('📧 Admin new-order email sent', [
                        'order_number' => $order->order_number,
                        'email' => $adminEmail
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ Failed to send order confirmation email', [
                    'order_number' => $order->order_number,
                    'email' => $order->customer_email,
                    'admin_email' => $adminEmail,
                    'error' => $e->getMessage()
                ]);
            }

            $this->trackTikTokCheckoutEvent(
                $request,
                'Purchase',
                $purchaseItems,
                (float) $order->total_amount,
                [
                    'content_id' => $order->order_number,
                    'content_name' => 'Order',
                    'description' => sprintf('Order %s purchased via PayPal', $order->order_number),
                ],
                [
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                    'external_id' => $order->user_id,
                ]
            );

            return redirect()->route('checkout.success', $order->order_number)
                ->with('success', 'Payment completed successfully!');
        } catch (\Exception $e) {
            return redirect()->route('checkout.index')->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    public function paypalCancel()
    {
        return redirect()->route('checkout.index')
            ->with('error', 'Payment was cancelled.');
    }

    public function lianlianSuccess(Request $request)
    {
        $orderNumber = $request->get('order_number');
        $transactionId = $request->get('transaction_id');

        if (!$orderNumber) {
            return redirect()->route('checkout.index')->with('error', 'Order not found.');
        }

        try {
            $order = Order::where('order_number', $orderNumber)->firstOrFail();

            // Update payment status giống PayPal
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'payment_transaction_id' => $transactionId,
                'paid_at' => now()
            ]);

            $order->load('items');
            $purchaseItems = $order->items->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->unit_price,
                ];
            })->toArray();

            // Clear session
            Session::forget('pending_order');

            // Clear cart from database after successful payment
            $sessionId = session()->getId();
            $userId = Auth::id();
            Cart::where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })->delete();

            // Send order confirmation email to customer and admin
            $adminEmail = config('mail.from.address');
            try {
                Mail::to($order->customer_email)->send(new OrderConfirmation($order));
                Log::info('📧 Order confirmation email sent', [
                    'order_number' => $order->order_number,
                    'email' => $order->customer_email
                ]);

                if ($adminEmail) {
                    Mail::to($adminEmail)->send(new OrderConfirmation($order));
                    Log::info('📧 Admin new-order email sent', [
                        'order_number' => $order->order_number,
                        'email' => $adminEmail
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ Failed to send order confirmation email', [
                    'order_number' => $order->order_number,
                    'email' => $order->customer_email,
                    'admin_email' => $adminEmail,
                    'error' => $e->getMessage()
                ]);
            }

            $this->trackTikTokCheckoutEvent(
                $request,
                'Purchase',
                $purchaseItems,
                (float) $order->total_amount,
                [
                    'content_id' => $order->order_number,
                    'content_name' => 'Order',
                    'description' => sprintf('Order %s purchased via LianLian', $order->order_number),
                ],
                [
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                    'external_id' => $order->user_id,
                ]
            );

            return redirect()->route('checkout.success', $order->order_number)
                ->with('success', 'Payment completed successfully!');
        } catch (\Exception $e) {
            return redirect()->route('checkout.index')->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    public function lianlianCancel()
    {
        return redirect()->route('checkout.index')
            ->with('error', 'Payment was cancelled.');
    }

    /**
     * Calculate shipping cost via AJAX
     */
    public function calculateShipping(Request $request)
    {
        $request->validate([
            'country' => 'required|string|size:2',
        ]);

        $sessionId = session()->getId();
        $userId = Auth::id();

        // Check if items are provided directly (from product page)
        if ($request->has('items') && is_array($request->items)) {
            $items = $request->items;
        } else {
            // Get cart items (from cart/checkout page)
            $cartItems = Cart::with(['product.template.category'])
                ->where(function ($query) use ($sessionId, $userId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            // Get currency and rate first for price conversion
            $currency = CurrencyService::getCurrencyForDomain();

            // Nếu domain không có currency config, lấy từ country
            if (!$currency || $currency === 'USD') {
                $currencyFromCountry = $this->getCurrencyFromCountry($request->country);
                // Chỉ dùng currency từ country nếu domain không có config hoặc domain là USD
                $domainCurrency = CurrencyService::getCurrencyForDomain();
                if (!$domainCurrency || $domainCurrency === 'USD') {
                    $currency = $currencyFromCountry;
                } else {
                    $currency = $domainCurrency;
                }
            }

            // Get currency rate - ưu tiên từ domain config
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

            // Prepare cart items for calculator - convert prices back to USD
            $items = $cartItems->map(function ($item) use ($currency, $currencyRate) {
                // Use cart item price (already in current currency) and convert back to USD
                $priceInUSD = $currency !== 'USD' ? ($item->price / $currencyRate) : $item->price;
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $priceInUSD,
                ];
            });
        }

        $calculator = new ShippingCalculator();
        $shippingResult = $calculator->calculateShipping($items, $request->country);

        if (!$shippingResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $shippingResult['message']
            ], 400);
        }

        // Store shipping details in session
        session()->put('shipping_details', $shippingResult);

        // Convert shipping cost to target currency (currency and rate already retrieved above for cart items)
        if (!isset($currency) || !isset($currencyRate)) {
            // Fallback if currency/rate not set (shouldn't happen for cart items, but needed for direct items)
            $currency = CurrencyService::getCurrencyForDomain();
            if (!$currency || $currency === 'USD') {
                $currency = $this->getCurrencyFromCountry($request->country);
            }
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
        }

        $convertedShipping = CurrencyService::convertFromUSDWithRate(
            $shippingResult['total_shipping'],
            $currency,
            $currencyRate
        );

        return response()->json([
            'success' => true,
            'shipping' => $shippingResult,
            'currency' => $currency,
            'currency_rate' => $currencyRate,
            'converted_shipping' => $convertedShipping
        ]);
    }

    /**
     * Get all available shipping rates for domain (default + options)
     * All rates are converted to domain's currency
     */
    public function getShippingRates(Request $request)
    {
        $request->validate([
            'country' => 'required|string|size:2',
        ]);

        $sessionId = session()->getId();
        $userId = Auth::id();

        // Get cart items
        $cartItems = Cart::with(['product.template.category'])
            ->where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 400);
        }

        $currency = CurrencyService::getCurrencyForDomain();
        $currencyRate = CurrencyService::getCurrencyRateForDomain() ?? 1.0;

        $zone = ShippingZone::findByCountry($request->country);
        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping not available for this country'
            ], 400);
        }

        $totalItems = $cartItems->sum('quantity');
        $totalValue = 0;
        foreach ($cartItems as $item) {
            $priceInUSD = $currency !== 'USD' ? ($item->price / $currencyRate) : $item->price;
            $totalValue += $priceInUSD * $item->quantity;
        }

        $allRates = \App\Models\ShippingRate::active()
            ->where('shipping_zone_id', $zone->id)
            ->ordered()
            ->get();

        // Filter rates by applicability and prepare response
        $applicableRates = [];
        $defaultRate = null;

        foreach ($allRates as $rate) {
            // Check if rate is applicable for current cart
            if (!$rate->isApplicable($totalItems, $totalValue)) {
                continue;
            }

            // Calculate shipping cost for this rate (in USD)
            $shippingCostUSD = $rate->calculateCost($totalItems);

            // Convert to domain currency
            $shippingCostConverted = CurrencyService::convertFromUSDWithRate(
                $shippingCostUSD,
                $currency,
                $currencyRate
            );

            $rateData = [
                'id' => $rate->id,
                'name' => $rate->name,
                'description' => $rate->description,
                'is_default' => (bool) $rate->is_default,
                'shipping_cost_usd' => round($shippingCostUSD, 2),
                'shipping_cost' => round($shippingCostConverted, 2),
                'first_item_cost_usd' => $rate->first_item_cost,
                'first_item_cost' => CurrencyService::convertFromUSDWithRate(
                    $rate->first_item_cost,
                    $currency,
                    $currencyRate
                ),
                'additional_item_cost_usd' => $rate->additional_item_cost,
                'additional_item_cost' => CurrencyService::convertFromUSDWithRate(
                    $rate->additional_item_cost,
                    $currency,
                    $currencyRate
                ),
            ];

            if ($rate->is_default) {
                $defaultRate = $rateData;
            } else {
                $applicableRates[] = $rateData;
            }
        }

        // Sort: default first, then others
        $rates = [];
        if ($defaultRate) {
            $rates[] = $defaultRate;
        }
        $rates = array_merge($rates, $applicableRates);

        return response()->json([
            'success' => true,
            'zone_id' => $zone->id,
            'zone_name' => $zone->name,
            'country' => $request->country,
            'currency' => $currency,
            'currency_rate' => $currencyRate,
            'rates' => $rates,
            'default_rate' => $defaultRate
        ]);
    }

    private function trackTikTokCheckoutEvent(
        Request $request,
        string $event,
        array $items,
        float $value,
        array $additionalProperties = [],
        array $userData = []
    ): void {
        /** @var TikTokEventsService $tikTok */
        $tikTok = app(TikTokEventsService::class);

        if (!$tikTok->enabled()) {
            return;
        }

        $contents = collect($items)->map(function ($item) {
            return array_filter([
                'content_id' => (string) Arr::get($item, 'id'),
                'content_type' => Arr::get($item, 'type', 'product'),
                'content_name' => Arr::get($item, 'name'),
                'quantity' => (int) Arr::get($item, 'quantity', 1),
                'price' => round((float) Arr::get($item, 'price', 0), 2),
            ], function ($value) {
                return $value !== null && $value !== '';
            });
        })->values()->toArray();

        $properties = array_merge([
            'value' => round($value, 2),
            'currency' => 'USD',
            'content_type' => 'product',
            'contents' => $contents,
            'num_items' => collect($items)->sum(function ($item) {
                return (int) Arr::get($item, 'quantity', 1);
            }),
        ], $additionalProperties);

        $defaultUser = Auth::user();

        $userPayload = array_filter([
            'email' => $userData['email'] ?? $defaultUser?->email,
            'phone' => $userData['phone'] ?? $defaultUser?->phone,
            'external_id' => $userData['external_id'] ?? ($defaultUser?->id),
        ], function ($value) {
            return $value !== null && $value !== '';
        });

        // Merge additional user fields except core ones
        $extras = Arr::except($userData, ['email', 'phone', 'external_id']);
        $userPayload = array_merge($extras, $userPayload);

        $tikTok->track($event, $properties, $request, $userPayload);
    }

    /**
     * Safely update order status with multiple fallback methods
     */
    private function updateOrderStatusSafely($order, $paymentStatus, $status, $paymentId = null, $paymentTransactionId = null, $paidAt = null)
    {
        $orderId = $order->id;
        $now = $paidAt ?? now();
        $updateSuccess = false;
        $attempts = [];

        // Method 1: Eloquent update
        try {
            $result1 = $order->update([
                'payment_status' => $paymentStatus,
                'status' => $status,
                'payment_id' => $paymentId,
                'payment_transaction_id' => $paymentTransactionId,
                'paid_at' => $now
            ]);

            $attempts['eloquent'] = $result1;

            if ($result1) {
                $order->refresh();
                $updateSuccess = ($order->payment_status === $paymentStatus && $order->status === $status);

                Log::info('✅ Order status updated via Eloquent', [
                    'order_id' => $orderId,
                    'payment_status' => $order->payment_status,
                    'status' => $order->status,
                    'success' => $updateSuccess
                ]);
            }
        } catch (\Exception $e) {
            $attempts['eloquent_error'] = $e->getMessage();
            Log::warning('⚠️ Eloquent update failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }

        // Method 2: Direct DB update if Eloquent failed
        if (!$updateSuccess) {
            try {
                $result2 = DB::table('orders')
                    ->where('id', $orderId)
                    ->update([
                        'payment_status' => $paymentStatus,
                        'status' => $status,
                        'payment_id' => $paymentId,
                        'payment_transaction_id' => $paymentTransactionId,
                        'paid_at' => $now,
                        'updated_at' => $now
                    ]);

                $attempts['db_manual'] = $result2;

                if ($result2) {
                    $order->refresh();
                    $updateSuccess = ($order->payment_status === $paymentStatus && $order->status === $status);

                    Log::info('✅ Order status updated via DB', [
                        'order_id' => $orderId,
                        'payment_status' => $order->payment_status,
                        'status' => $order->status,
                        'success' => $updateSuccess
                    ]);
                }
            } catch (\Exception $e) {
                $attempts['db_manual_error'] = $e->getMessage();
                Log::warning('⚠️ DB update failed', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Method 3: Direct assignment and save if still failed
        if (!$updateSuccess) {
            try {
                $order->payment_status = $paymentStatus;
                $order->status = $status;
                $order->payment_id = $paymentId;
                $order->payment_transaction_id = $paymentTransactionId;
                $order->paid_at = $now;

                $result3 = $order->save();
                $attempts['direct_save'] = $result3;

                if ($result3) {
                    $order->refresh();
                    $updateSuccess = ($order->payment_status === $paymentStatus && $order->status === $status);

                    Log::info('✅ Order status updated via direct save', [
                        'order_id' => $orderId,
                        'payment_status' => $order->payment_status,
                        'status' => $order->status,
                        'success' => $updateSuccess
                    ]);
                }
            } catch (\Exception $e) {
                $attempts['direct_save_error'] = $e->getMessage();
                Log::warning('⚠️ Direct save failed', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Final log
        Log::info('🔄 Order Status Update Summary', [
            'order_id' => $orderId,
            'final_success' => $updateSuccess,
            'attempts' => $attempts,
            'final_payment_status' => $order->payment_status ?? 'unknown',
            'final_status' => $order->status ?? 'unknown'
        ]);

        return $updateSuccess;
    }
}
