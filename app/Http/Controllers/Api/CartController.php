<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\PromoCode;
use App\Services\ShippingCalculator;
use App\Services\CurrencyService;
use App\Services\PromoCodeSendService;
use App\Services\TikTokEventsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function setDiscountMode(Request $request)
    {
        $request->validate([
            'mode' => ['required', 'string', 'in:volume,promo'],
        ]);

        $mode = $request->input('mode');

        if ($mode === 'volume') {
            // Switching to volume discount means promo cannot stack; clear promo.
            session()->forget(['applied_promo_code_id', 'applied_promo_code']);
        }

        session(['discount_mode' => $mode]);

        return response()->json([
            'success' => true,
            'mode' => $mode,
        ]);
    }
    public function add(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
                'selectedVariant' => 'nullable|array',
                'customizations' => 'nullable|array'
            ]);

            $product = Product::findOrFail($request->id);
            $sessionId = session()->getId();
            $userId = Auth::id();

            // Find existing cart item
            $cartItems = Cart::where('product_id', $request->id)
                ->where(function ($query) use ($sessionId, $userId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })
                ->get();

            // Check for exact match manually to avoid JSON encoding issues
            $existingCart = null;
            foreach ($cartItems as $item) {
                $variantMatch = $this->compareVariants($item->selected_variant, $request->selectedVariant ?? []);
                $customizationMatch = $this->compareCustomizations($item->customizations, $request->customizations ?? []);

                if ($variantMatch && $customizationMatch) {
                    $existingCart = $item;
                    break;
                }
            }

            $selectedVariant = $this->normalizeSelectedVariant($request->selectedVariant ?? []);
            $customizations = $this->normalizeCustomizations($request->customizations ?? []);

            if ($existingCart) {
                // Update quantity and price (in case variant price changed)
                $existingCart->increment('quantity', $request->quantity);
                $existingCart->update(['price' => $request->price]);
                $cartItem = $existingCart;
            } else {
                // Create new cart item with variant + customizations for correct production
                $cartItem = Cart::create([
                    'session_id' => $userId ? null : $sessionId,
                    'user_id' => $userId,
                    'product_id' => $request->id,
                    'variant_id' => $selectedVariant['id'] ?? null,
                    'quantity' => (int) $request->quantity,
                    'price' => (float) $request->price,
                    'selected_variant' => $selectedVariant ?: null,
                    'customizations' => $customizations ?: null,
                ]);
            }

            Log::info('Item added to cart', [
                'cart_id' => $cartItem->id,
                'product_id' => $request->id,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'quantity' => $cartItem->quantity
            ]);

            $this->trackTikTokAddToCartEvent($request, $product, $cartItem->quantity, $request->price);

            // Gửi email promo cho user đã đăng nhập (throttle 24h)
            if ($userId) {
                $user = Auth::user();
                if ($user && $user->email) {
                    app(PromoCodeSendService::class)->sendForTrigger(
                        $user->email,
                        PromoCodeSendService::TRIGGER_ADD_TO_CART,
                        $userId,
                        true
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'cart_item' => $cartItem->load('product')
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding item to cart', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart'
            ], 500);
        }
    }

    public function get(Request $request)
    {
        try {
            $sessionId = session()->getId();
            $userId = Auth::id();

            $cartItems = Cart::with(['product.shop', 'product.template', 'variant'])
                ->where(function ($query) use ($sessionId, $userId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })
                ->get();

            // Transform cart items to include media
            $cartItems->each(function ($item) {
                if ($item->product) {
                    $item->product->media = $item->product->getEffectiveMedia();
                }
            });

            $totalItems = $cartItems->sum('quantity');
            $totalPrice = $cartItems->sum(function ($item) {
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
                $bulkDiscountPercent = (float) Cart::getComboDiscountPercentForQty((int) $totalItems);
                $bulkDiscount = $bulkDiscountPercent > 0 ? round((float) $totalPrice * ($bulkDiscountPercent / 100), 2) : 0.0;
            }

            // Get currency and rate
            $currency = CurrencyService::getCurrencyForDomain() ?? 'USD';
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

            // Calculate summary (without tax)
            // Note: $totalPrice is already in the current currency (prices in cart are already converted)
            $subtotal = $totalPrice;
            $subtotalAfterBulk = max(0, $subtotal - $bulkDiscount);
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

                // Calculate shipping
                $currentDomain = CurrencyService::getCurrentDomain();
                $country = $request->get('country') ?? 'US';
                
                $calculator = new ShippingCalculator();
                $shippingResult = $calculator->calculateShipping($items, $country, $currentDomain);

                if ($shippingResult['success']) {
                    $shipping = $shippingResult['total_shipping'];
                    $shippingDetails = $shippingResult;
                }
            }

            // Convert shipping from USD to current currency (shipping is always calculated in USD)
            $convertedShipping = $currency !== 'USD' ? CurrencyService::convertFromUSDWithRate($shipping, $currency, $currencyRate) : $shipping;

            // Subtotal is already in current currency
            $convertedSubtotal = $subtotal;
            $convertedSubtotalAfterBulk = $subtotalAfterBulk;
            // Free shipping should be based on subtotal BEFORE combo discount (USD)
            $subtotalUsdBeforeDiscount = $currency !== 'USD' ? $subtotal / $currencyRate : $subtotal;
            $subtotalUsd = $currency !== 'USD' ? $subtotalAfterBulk / $currencyRate : $subtotalAfterBulk;

            // Promo code discount (stored in session) - only when in promo mode
            $discount = 0.0;
            $appliedPromoCode = null;
            $promoId = session('applied_promo_code_id');
            if ($discountMode === 'promo' && $promoId && $subtotalUsd > 0) {
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

            $total = $convertedSubtotalAfterBulk - $discount + $convertedShipping;
            $convertedTotal = $total;

            // Free shipping if subtotal BEFORE discount (USD) >= threshold
            if ($subtotalUsdBeforeDiscount >= Cart::FREE_SHIPPING_THRESHOLD_USD) {
                $shipping = 0;
                $convertedShipping = 0;
                $total = $convertedSubtotalAfterBulk - $discount + $convertedShipping;
                $convertedTotal = $total;
            }

            return response()->json([
                'success' => true,
                'cart_items' => $cartItems,
                'total_items' => $totalItems,
                'total_price' => $totalPrice,
                'summary' => [
                    'discount_mode' => $discountMode,
                    'subtotal' => $subtotal,
                    'bulk_discount' => $bulkDiscount,
                    'bulk_discount_percent' => $bulkDiscountPercent,
                    'subtotal_after_bulk_discount' => $subtotalAfterBulk,
                    'shipping' => $shipping,
                    'discount' => $discount,
                    'total' => $total,
                    'converted_subtotal' => $convertedSubtotal,
                    'converted_bulk_discount' => $bulkDiscount,
                    'converted_bulk_discount_percent' => $bulkDiscountPercent,
                    'converted_subtotal_after_bulk_discount' => $convertedSubtotalAfterBulk,
                    'converted_shipping' => $convertedShipping,
                    'converted_discount' => $discount,
                    'converted_total' => $convertedTotal,
                    'applied_promo_code' => $appliedPromoCode,
                ],
                'shipping_details' => $shippingDetails,
                'currency' => $currency,
                'currency_rate' => $currencyRate,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting cart', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get cart'
            ], 500);
        }
    }

    /**
     * Apply promo code. Stores in session; cart get() will apply discount.
     */
    public function applyPromo(Request $request)
    {
        $request->validate(['code' => 'required|string|max:64']);
        $code = strtoupper(trim($request->code));
        if ($code === '') {
            return response()->json(['success' => false, 'message' => 'Please enter a promo code.'], 422);
        }

        $promo = PromoCode::whereRaw('UPPER(TRIM(code)) = ?', [$code])->first();
        if (!$promo) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired promo code.'], 422);
        }

        $sessionId = session()->getId();
        $userId = Auth::id();
        $cartItems = Cart::where(function ($query) use ($sessionId, $userId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->get();

        $subtotal = $cartItems->sum(fn ($item) => $item->getTotalPriceWithCustomizations());
        $currency = CurrencyService::getCurrencyForDomain() ?? 'USD';
        $currencyRate = CurrencyService::getCurrencyRateForDomain() ?: 1.0;
        $subtotalUsd = $currency !== 'USD' ? $subtotal / $currencyRate : $subtotal;

        if (!$promo->isValidForSubtotal($subtotalUsd)) {
            if (!$promo->isValid()) {
                return response()->json(['success' => false, 'message' => 'This promo code is no longer valid.'], 422);
            }
            if ($promo->min_order_value !== null && $subtotalUsd < (float) $promo->min_order_value) {
                $minDisplay = $currency !== 'USD' ? (float) $promo->min_order_value * $currencyRate : (float) $promo->min_order_value;
                return response()->json([
                    'success' => false,
                    'message' => 'Minimum order for this code is ' . number_format($minDisplay, 2) . ' (before shipping).',
                ], 422);
            }
            return response()->json(['success' => false, 'message' => 'Invalid or expired promo code.'], 422);
        }

        session([
            'applied_promo_code_id' => $promo->id,
            'applied_promo_code' => $promo->code,
            // Promo cannot stack with volume discount; selecting promo mode.
            'discount_mode' => 'promo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promo code applied.',
            'applied_promo_code' => $promo->code,
        ]);
    }

    /**
     * Remove applied promo code from session.
     */
    public function removePromo()
    {
        session()->forget(['applied_promo_code_id', 'applied_promo_code']);
        // When promo removed, default back to volume discount.
        session(['discount_mode' => 'volume']);
        return response()->json(['success' => true, 'message' => 'Promo code removed.']);
    }

    public function show($id)
    {
        try {
            $sessionId = session()->getId();
            $userId = Auth::id();

            $cartItem = Cart::with(['product.shop', 'product.template', 'product.variants', 'variant'])
                ->where('id', $id)
                ->where(function ($query) use ($sessionId, $userId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })
                ->firstOrFail();

            // Transform cart item to include media
            if ($cartItem->product) {
                $cartItem->product->media = $cartItem->product->getEffectiveMedia();
            }

            return response()->json([
                'success' => true,
                'cart_item' => $cartItem
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting cart item', [
                'error' => $e->getMessage(),
                'cart_item_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get cart item'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
                'selected_variant' => 'nullable|array',
                'customizations' => 'nullable|array',
                'price' => 'nullable|numeric|min:0'
            ]);

            $sessionId = session()->getId();
            $userId = Auth::id();

            $cartItem = Cart::where('id', $id)
                ->where(function ($query) use ($sessionId, $userId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })
                ->firstOrFail();

            $updateData = [
                'quantity' => $request->quantity
            ];

            // Update variant if provided
            if ($request->has('selected_variant')) {
                $updateData['selected_variant'] = $request->selected_variant;
                $updateData['variant_id'] = $request->selected_variant['id'] ?? null;
            }

            // Update customizations if provided
            if ($request->has('customizations')) {
                $updateData['customizations'] = $request->customizations;
            }

            // Update unit price if provided (price includes variant and customization unit total)
            if ($request->has('price')) {
                $updateData['price'] = $request->price;
            }

            $cartItem->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Cart item updated successfully',
                'cart_item' => $cartItem->load('product')
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating cart item', [
                'error' => $e->getMessage(),
                'cart_item_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart item'
            ], 500);
        }
    }

    public function remove($id)
    {
        try {
            $sessionId = session()->getId();
            $userId = Auth::id();

            $cartItem = Cart::where('id', $id)
                ->where(function ($query) use ($sessionId, $userId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })
                ->firstOrFail();

            $cartItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error removing cart item', [
                'error' => $e->getMessage(),
                'cart_item_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove cart item'
            ], 500);
        }
    }

    public function clear()
    {
        try {
            $sessionId = session()->getId();
            $userId = Auth::id();

            Cart::where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing cart', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart'
            ], 500);
        }
    }

    public function sync(Request $request)
    {
        try {
            $request->validate([
                'cart_items' => 'required|array',
                'cart_items.*.id' => 'required|exists:products,id',
                'cart_items.*.quantity' => 'required|integer|min:1',
                'cart_items.*.price' => 'required|numeric|min:0'
            ]);

            $userId = Auth::id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must be logged in to sync cart'
                ], 401);
            }

            // Clear existing cart for this user
            Cart::where('user_id', $userId)->delete();

            // Add items from localStorage
            foreach ($request->cart_items as $item) {
                Cart::create([
                    'user_id' => $userId,
                    'product_id' => $item['id'],
                    'variant_id' => $item['selectedVariant']['id'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'selected_variant' => $item['selectedVariant'] ?? null,
                    'customizations' => $item['customizations'] ?? null
                ]);
            }

            Log::info('Cart synced for user', [
                'user_id' => $userId,
                'items_count' => count($request->cart_items)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cart synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncing cart', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync cart'
            ], 500);
        }
    }

    /**
     * Compare two variant objects for equality
     */
    /**
     * Normalize selectedVariant from request (id + attributes) for storage.
     */
    private function normalizeSelectedVariant($input): array
    {
        if (! is_array($input) || empty($input)) {
            return [];
        }
        $id = isset($input['id']) ? (is_numeric($input['id']) ? (int) $input['id'] : $input['id']) : null;
        $attributes = isset($input['attributes']) && is_array($input['attributes'])
            ? $input['attributes']
            : [];
        return [
            'id' => $id,
            'attributes' => $attributes,
        ];
    }

    /**
     * Normalize customizations to [ label => [ 'value' => ..., 'price' => float ] ] for storage.
     */
    private function normalizeCustomizations($input): array
    {
        if (! is_array($input) || empty($input)) {
            return [];
        }
        $out = [];
        foreach ($input as $label => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            // Support legacy shape: [ { label, value, price }, ... ]
            if (is_int($label) && isset($entry['label'])) {
                $label = (string) $entry['label'];
            }

            $value = $entry['value'] ?? $entry['text'] ?? '';
            $price = isset($entry['price']) ? (float) $entry['price'] : 0.0;
            $out[(string) $label] = ['value' => (string) $value, 'price' => $price];
        }
        return $out;
    }

    private function compareVariants($variant1, $variant2): bool
    {
        $var1 = is_array($variant1) ? $variant1 : (is_object($variant1) ? json_decode(json_encode($variant1), true) : []);
        $var2 = is_array($variant2) ? $variant2 : (is_object($variant2) ? json_decode(json_encode($variant2), true) : []);
        $var1 = $var1 ?: [];
        $var2 = $var2 ?: [];

        if (empty($var1) && empty($var2)) {
            return true;
        }
        if (isset($var1['attributes']) && isset($var2['attributes'])) {
            ksort($var1['attributes']);
            ksort($var2['attributes']);
            return $var1['attributes'] === $var2['attributes'];
        }
        return $var1 === $var2;
    }

    /**
     * Compare two customization objects for equality
     */
    private function compareCustomizations($custom1, $custom2): bool
    {
        $raw1 = is_array($custom1) ? $custom1 : (is_object($custom1) ? json_decode(json_encode($custom1), true) : []);
        $raw2 = is_array($custom2) ? $custom2 : (is_object($custom2) ? json_decode(json_encode($custom2), true) : []);

        $c1 = $this->normalizeCustomizations($raw1);
        $c2 = $this->normalizeCustomizations($raw2);

        if (empty($c1) && empty($c2)) {
            return true;
        }
        if (empty($c1) || empty($c2)) {
            return false;
        }

        ksort($c1);
        ksort($c2);
        return $c1 === $c2;
    }

    private function trackTikTokAddToCartEvent(Request $request, Product $product, int $quantity, float $unitPrice): void
    {
        /** @var TikTokEventsService $tikTok */
        $tikTok = app(TikTokEventsService::class);

        if (!$tikTok->enabled()) {
            return;
        }

        $user = Auth::user();

        $tikTok->track(
            'AddToCart',
            [
                'value' => round($unitPrice * $quantity, 2),
                'currency' => 'USD',
                'contents' => [[
                    'content_id' => (string) $product->id,
                    'content_type' => 'product',
                    'content_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => round($unitPrice, 2),
                ]],
                'description' => optional($product->template)->name ?? $product->name,
            ],
            $request,
            [
                'email' => $user?->email,
                'phone' => $user?->phone,
                'external_id' => $user?->id,
            ],
            [
                'page' => [
                    'url' => $request->headers->get('referer'),
                ],
            ]
        );
    }
}
