<?php

namespace App\Services;

use App\Services\CurrencyService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    private $clientId;
    private $clientSecret;
    private $mode;
    private $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
        $this->mode = config('services.paypal.mode', 'sandbox');

        // Set base URL based on mode
        $this->baseUrl = $this->mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Get PayPal OAuth access token
     */
    private function getAccessToken()
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post($this->baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials'
            ]);

        if ($response->failed()) {
            Log::error('PayPal Auth Failed', ['response' => $response->json()]);
            throw new \Exception('Failed to authenticate with PayPal');
        }

        return $response->json()['access_token'];
    }

    /**
     * Get product name using SKU to avoid copyright issues
     * 
     * @param object|null $product The product object
     * @param int $index The index of the product (1-based) as fallback
     * @return string Product name using SKU or fallback to "Item #X"
     */
    private function getGenericProductName($product = null, $index = 1)
    {
        // Use SKU if available, otherwise fallback to Item #X
        if ($product && isset($product->sku) && !empty($product->sku)) {
            return $product->sku;
        }
        return "Item #{$index}";
    }

    /**
     * Check if currency is supported by PayPal
     * PayPal supports: USD, EUR, GBP, CAD, AUD, JPY, CNY, HKD, SGD, etc.
     */
    private function isPayPalSupportedCurrency(string $currency): bool
    {
        $supportedCurrencies = [
            'USD',
            'EUR',
            'GBP',
            'CAD',
            'AUD',
            'JPY',
            'CNY',
            'HKD',
            'SGD',
            'NZD',
            'CHF',
            'DKK',
            'PLN',
            'NOK',
            'SEK',
            'MXN',
            'BRL',
            'RUB',
            'INR',
            'KRW',
            'THB'
        ];

        return in_array(strtoupper($currency), $supportedCurrencies);
    }

    /**
     * Get currency for PayPal payment (fallback to USD if not supported)
     */
    private function getPayPalCurrency(): string
    {
        $currency = CurrencyService::getCurrentCurrency();

        if ($this->isPayPalSupportedCurrency($currency)) {
            return $currency;
        }

        // Fallback to USD if currency not supported
        Log::warning('PayPal currency not supported, using USD', [
            'requested_currency' => $currency
        ]);

        return 'USD';
    }

    /**
     * Create a PayPal payment
     */
    public function createPayment($order, $items)
    {
        $accessToken = $this->getAccessToken();

        // Get PayPal currency (with fallback to USD if not supported)
        $paypalCurrency = $this->getPayPalCurrency();
        $isCurrencyUSD = ($paypalCurrency === 'USD');

        // Build items first to calculate accurate subtotal
        $paypalItems = [];
        $itemsSubtotal = 0;
        $itemIndex = 1;

        foreach ($items as $item) {
            // Calculate unit price from total (total / quantity)
            // This ensures we use the actual cart price (with variants/customizations)
            $unitPriceUSD = (float)$item['total'] / (int)$item['quantity'];

            // Convert to PayPal currency if needed
            if (!$isCurrencyUSD) {
                $unitPriceUSD = CurrencyService::convertFromUSD($unitPriceUSD);
            }

            // Format based on currency (some currencies don't use decimals)
            $decimals = in_array($paypalCurrency, ['JPY', 'KRW']) ? 0 : 2;
            $formattedUnitPrice = number_format($unitPriceUSD, $decimals, '.', '');

            $paypalItems[] = [
                'name' => $this->getGenericProductName($item['product'], $itemIndex),
                'sku' => $item['product']->sku ?? $item['product']->id,
                'price' => $formattedUnitPrice,
                'currency' => $paypalCurrency,
                'quantity' => (int)$item['quantity']
            ];

            // Calculate subtotal from formatted prices to match PayPal's rounding
            $itemsSubtotal += (float)$formattedUnitPrice * (int)$item['quantity'];
            $itemIndex++;
        }

        // Convert amounts if needed
        $subtotalUSD = $itemsSubtotal;
        $taxUSD = (float)$order->tax_amount;
        $shippingUSD = (float)$order->shipping_cost;

        if (!$isCurrencyUSD) {
            $subtotalUSD = CurrencyService::convertFromUSD($itemsSubtotal);
            $taxUSD = CurrencyService::convertFromUSD($taxUSD);
            $shippingUSD = CurrencyService::convertFromUSD($shippingUSD);
        }

        // Format amounts - use calculated subtotal to avoid rounding errors
        $decimals = in_array($paypalCurrency, ['JPY', 'KRW']) ? 0 : 2;
        $subtotal = number_format($subtotalUSD, $decimals, '.', '');
        $tax = number_format($taxUSD, $decimals, '.', '');
        $shipping = number_format($shippingUSD, $decimals, '.', '');
        $total = number_format($subtotalUSD + $taxUSD + $shippingUSD, $decimals, '.', '');

        // Build payment data
        $paymentData = [
            'intent' => 'sale',
            'payer' => [
                'payment_method' => 'paypal'
            ],
            'transactions' => [
                [
                    'amount' => [
                        'total' => $total,
                        'currency' => $paypalCurrency,
                        'details' => [
                            'subtotal' => $subtotal,
                            'tax' => $tax,
                            'shipping' => $shipping
                        ]
                    ],
                    'description' => 'Order #' . $order->order_number,
                    'invoice_number' => $order->order_number,
                    'item_list' => [
                        'items' => $paypalItems
                    ]
                ]
            ],
            'redirect_urls' => [
                'return_url' => route('checkout.paypal.success'),
                'cancel_url' => route('checkout.paypal.cancel')
            ]
        ];

        // Create payment via API
        $response = Http::withToken($accessToken)
            ->post($this->baseUrl . '/v1/payments/payment', $paymentData);

        if ($response->failed()) {
            Log::error('PayPal Payment Creation Failed', [
                'response' => $response->json(),
                'payment_data' => $paymentData
            ]);
            throw new \Exception('Failed to create PayPal payment: ' . ($response->json()['message'] ?? 'Unknown error'));
        }

        $payment = $response->json();

        // Create a mock payment object with the approval URL
        $approvalUrl = collect($payment['links'])->firstWhere('rel', 'approval_url')['href'] ?? null;

        if (!$approvalUrl) {
            throw new \Exception('PayPal approval URL not found');
        }

        // Return payment data
        return (object)[
            'id' => $payment['id'],
            'approval_url' => $approvalUrl,
            'state' => $payment['state']
        ];
    }

    /**
     * Execute a PayPal payment
     */
    public function executePayment($paymentId, $payerId)
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post($this->baseUrl . "/v1/payments/payment/{$paymentId}/execute", [
                'payer_id' => $payerId
            ]);

        if ($response->failed()) {
            Log::error('PayPal Payment Execution Failed', [
                'payment_id' => $paymentId,
                'payer_id' => $payerId,
                'response' => $response->json()
            ]);
            throw new \Exception('Failed to execute PayPal payment');
        }

        return $response->json();
    }

    /**
     * Capture/Verify a PayPal order (for SDK payments)
     */
    public function capturePayment($orderId)
    {
        $accessToken = $this->getAccessToken();

        // First, get order details to verify
        $orderResponse = Http::withToken($accessToken)
            ->get($this->baseUrl . "/v2/checkout/orders/{$orderId}");

        if ($orderResponse->failed()) {
            Log::error('PayPal Order Verification Failed', [
                'order_id' => $orderId,
                'response' => $orderResponse->json()
            ]);
            throw new \Exception('Failed to verify PayPal order');
        }

        $order = $orderResponse->json();

        Log::info('PayPal Order Details', [
            'order_id' => $orderId,
            'status' => $order['status'] ?? 'unknown',
            'order_data' => $order
        ]);

        // If order is already approved, capture it
        if (isset($order['status']) && $order['status'] === 'APPROVED') {
            $captureResponse = Http::withToken($accessToken)
                ->post($this->baseUrl . "/v2/checkout/orders/{$orderId}/capture");

            if ($captureResponse->failed()) {
                Log::error('PayPal Order Capture Failed', [
                    'order_id' => $orderId,
                    'response' => $captureResponse->json()
                ]);
                throw new \Exception('Failed to capture PayPal order');
            }

            $capturedOrder = $captureResponse->json();

            Log::info('PayPal Order Captured', [
                'order_id' => $orderId,
                'status' => $capturedOrder['status'] ?? 'unknown'
            ]);

            return (object)[
                'status' => $capturedOrder['status'] ?? 'UNKNOWN',
                'id' => $capturedOrder['id'] ?? $orderId,
                'data' => $capturedOrder
            ];
        }

        // If order is already completed, return it
        if (isset($order['status']) && $order['status'] === 'COMPLETED') {
            return (object)[
                'status' => 'COMPLETED',
                'id' => $order['id'] ?? $orderId,
                'data' => $order
            ];
        }

        // For other statuses, still return the order object but with actual status
        return (object)[
            'status' => $order['status'] ?? 'UNKNOWN',
            'id' => $order['id'] ?? $orderId,
            'data' => $order
        ];
    }

    /**
     * Add tracking information to a PayPal order (requires capture_id)
     */
    public function addTracking(string $paypalOrderId, string $captureId, string $trackingNumber, string $carrier, bool $notifyPayer = false, array $items = []): array
    {
        $accessToken = $this->getAccessToken();

        $payload = [
            'capture_id' => $captureId,
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
            'notify_payer' => $notifyPayer,
        ];

        if (!empty($items)) {
            $payload['items'] = $items;
        }

        $response = Http::withToken($accessToken)
            ->post($this->baseUrl . "/v2/checkout/orders/{$paypalOrderId}/track", $payload);

        if ($response->failed()) {
            Log::error('PayPal Add Tracking Failed', [
                'order_id' => $paypalOrderId,
                'capture_id' => $captureId,
                'tracking_number' => $trackingNumber,
                'http_status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new \Exception('Failed to add tracking to PayPal order');
        }

        $body = $response->json() ?? [];
        Log::info('PayPal Add Tracking OK', [
            'paypal_order_id' => $paypalOrderId,
            'capture_id' => $captureId,
            'tracking_number' => $trackingNumber,
            'http_status' => $response->status(),
            'response' => $body,
        ]);

        return $body;
    }

    /**
     * Verify PayPal order without throwing exceptions (safe verification)
     */
    public function verifyOrderSafely($orderId)
    {
        try {
            return $this->capturePayment($orderId);
        } catch (\Exception $e) {
            Log::warning('PayPal Order Verification Failed (Safe Mode)', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            // Return a generic response instead of throwing
            return (object)[
                'status' => 'VERIFICATION_FAILED',
                'id' => $orderId,
                'error' => $e->getMessage()
            ];
        }
    }
}
