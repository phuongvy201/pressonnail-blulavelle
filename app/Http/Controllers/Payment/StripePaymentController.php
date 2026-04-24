<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ShippingCalculator;
use App\Services\TikTokEventsService;
use App\Services\CurrencyService;
use App\Mail\OrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;

class StripePaymentController extends Controller
{
    public function __construct()
    {
        // Set Stripe API Key
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create Payment Intent for Stripe
     */
    public function createPaymentIntent(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'amount' => 'required|numeric|min:0.5',
                'currency' => 'nullable|string|size:3',
            ]);

            $amount = $request->amount;
            $currency = $request->currency ?? 'usd';

            // Create Payment Intent
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($amount * 100), // Stripe expects amount in cents
                'currency' => strtolower($currency),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'integration_check' => 'accept_a_payment',
                ],
            ]);

            return response()->json([
                'success' => true,
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Payment Intent Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process Stripe Payment
     */
    public function processPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'payment_intent_id' => 'required|string',
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'customer_phone' => 'nullable|string|max:20',
                'shipping_address' => 'required|string|max:500',
                'city' => 'required|string|max:100',
                'state' => 'nullable|string|max:100',
                'postal_code' => 'required|string|max:20',
                'country' => 'required|string|max:2',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Retrieve Payment Intent from Stripe
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            // Check payment status
            if ($paymentIntent->status !== 'succeeded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has not been completed yet.',
                ], 400);
            }

            // Get cart items from database (same as CheckoutController)
            $sessionId = session()->getId();
            $userId = Auth::id();

            $cartItems = \App\Models\Cart::with(['product.shop', 'product.template'])
                ->where(function ($query) use ($sessionId, $userId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })
                ->get();

            // Debug cart contents
            Log::info('Stripe Payment - Cart Debug', [
                'cart_items' => $cartItems->toArray(),
                'cart_count' => $cartItems->count(),
                'session_id' => $sessionId,
                'user_id' => $userId,
            ]);

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty. Please add products to cart first.',
                ], 400);
            }

            // Calculate totals
            $subtotal = 0;
            $orderItems = [];

            foreach ($cartItems as $item) {
                $product = $item->product;
                if (!$product) {
                    continue;
                }

                $price = $item->price; // Use actual price from cart (includes variant pricing)
                $quantity = $item->quantity;
                $total = $price * $quantity;

                $subtotal += $total;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_description' => $product->description,
                    'unit_price' => $price,
                    'quantity' => $quantity,
                    'total_price' => $total,
                    'product_options' => [
                        'selected_variant' => $item->selected_variant,
                        'customizations' => $item->customizations,
                    ],
                ];
            }

            $eventContents = collect($orderItems)->map(function ($item) {
                return [
                    'content_id' => (string) $item['product_id'],
                    'content_type' => 'product',
                    'content_name' => $item['product_name'],
                    'quantity' => (int) $item['quantity'],
                    'price' => round((float) $item['unit_price'], 2),
                ];
            })->values()->toArray();
            $itemsCount = collect($orderItems)->sum('quantity');

            // Calculate shipping using ShippingCalculator (same as CheckoutController)
            $items = $cartItems->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price, // Use actual price from cart (includes variant pricing)
                ];
            });

            $calculator = new ShippingCalculator();
            $shippingDetails = $calculator->calculateShipping($items, $validated['country']);

            if (!$shippingDetails['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipping calculation failed: ' . $shippingDetails['message'],
                ], 400);
            }

            $shippingCost = $shippingDetails['total_shipping'];
            $taxAmount = 0; // No tax
            $tipAmount = $validated['tip_amount'] ?? 0; // Get tip amount
            $totalAmount = $subtotal + $shippingCost + $taxAmount + $tipAmount;

            // Verify amount matches
            $paidAmount = $paymentIntent->amount / 100; // Convert from cents
            if (abs($paidAmount - $totalAmount) > 0.01) {
                Log::warning('Stripe payment amount mismatch', [
                    'paid' => $paidAmount,
                    'expected' => $totalAmount,
                ]);
            }

            /** @var TikTokEventsService $tikTok */
            $tikTok = app(TikTokEventsService::class);
            $userPayload = [
                'email' => $validated['customer_email'],
                'phone' => $validated['customer_phone'] ?? null,
                'external_id' => $userId,
            ];
            $commonProperties = [
                'value' => round($totalAmount, 2),
                'currency' => 'USD',
                'content_type' => 'product',
                'contents' => $eventContents,
                'num_items' => $itemsCount,
            ];

            if ($tikTok->enabled()) {
                $tikTok->track(
                    'AddPaymentInfo',
                    array_merge($commonProperties, [
                        'description' => 'Payment info submitted via Stripe',
                    ]),
                    $request,
                    $userPayload
                );
            }

            // Get currency from request or domain config
            $orderCurrency = $validated['currency'] ?? CurrencyService::getCurrencyForDomain();
            if (!$orderCurrency || $orderCurrency === 'USD') {
                // Fallback to country-based currency
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
                ];
                $orderCurrency = $countryToCurrency[strtoupper($validated['country'])] ?? 'USD';
            }

            // Create order
            $order = Order::create([
                'user_id' => $userId,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'shipping_address' => $validated['shipping_address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country'],
                'notes' => $validated['notes'],
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'tip_amount' => $tipAmount,
                'total_amount' => $totalAmount,
                'currency' => $orderCurrency,
                'payment_method' => 'stripe',
                'payment_status' => 'paid',
                'status' => 'processing',
                'payment_id' => $paymentIntent->id,
                'payment_transaction_id' => $paymentIntent->id,
                'paid_at' => now()
            ]);

            // Create order items
            foreach ($orderItems as $itemData) {
                $order->items()->create($itemData);
            }

            if ($tikTok->enabled()) {
                $orderUserPayload = array_merge($userPayload, [
                    'external_id' => $order->user_id ?? $userId,
                ]);

                $tikTok->track(
                    'PlaceAnOrder',
                    array_merge($commonProperties, [
                        'description' => sprintf('Order %s placed via Stripe', $order->order_number),
                    ]),
                    $request,
                    $orderUserPayload
                );

                $tikTok->track(
                    'Purchase',
                    array_merge($commonProperties, [
                        'description' => sprintf('Order %s purchased via Stripe', $order->order_number),
                    ]),
                    $request,
                    $orderUserPayload
                );
            }

            // Clear cart from database (same as CheckoutController)
            $sessionId = session()->getId();
            $userId = Auth::id();

            \App\Models\Cart::where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })->delete();

            // Log successful payment
            Log::info('Stripe payment successful', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $totalAmount,
            ]);

            // Send order confirmation email to customer and admin
            $adminEmail = 'support@blulavelle.com';
            try {
                Mail::to($order->customer_email)->send(new OrderConfirmation($order));
                Log::info('📧 Order confirmation email sent (Stripe controller)', [
                    'order_number' => $order->order_number,
                    'email' => $order->customer_email
                ]);

                if ($adminEmail) {
                    Mail::to($adminEmail)->send(new \App\Mail\NewOrderAdminNotification($order));
                    Log::info('📧 Admin new-order email sent (Stripe controller)', [
                        'order_number' => $order->order_number,
                        'email' => $adminEmail
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ Failed to send order confirmation email (Stripe controller)', [
                    'order_number' => $order->order_number,
                    'email' => $order->customer_email,
                    'admin_email' => $adminEmail,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'order_number' => $order->order_number,
                'message' => 'Payment successful!',
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe process payment error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Stripe Webhook
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            if ($webhookSecret) {
                $event = Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $webhookSecret
                );
            } else {
                $event = json_decode($payload);
            }

            // Handle the event
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $this->handlePaymentIntentSucceeded($paymentIntent);
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    $this->handlePaymentIntentFailed($paymentIntent);
                    break;

                case 'charge.refunded':
                    $charge = $event->data->object;
                    $this->handleChargeRefunded($charge);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event: ' . $event->type);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Handle successful payment intent
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        $order = Order::where('payment_id', $paymentIntent->id)->first();

        if ($order && $order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'order_status' => 'processing',
            ]);

            Log::info('Payment intent succeeded for order: ' . $order->order_number);
        }
    }

    /**
     * Handle failed payment intent
     */
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        $order = Order::where('payment_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'failed',
                'order_status' => 'cancelled',
            ]);

            Log::warning('Payment intent failed for order: ' . $order->order_number);
        }
    }

    /**
     * Handle charge refunded
     */
    protected function handleChargeRefunded($charge)
    {
        // Find order by payment intent ID
        if (isset($charge->payment_intent)) {
            $order = Order::where('payment_id', $charge->payment_intent)->first();

            if ($order) {
                $order->update([
                    'payment_status' => 'refunded',
                    'order_status' => 'refunded',
                ]);

                Log::info('Charge refunded for order: ' . $order->order_number);
            }
        }
    }
}
