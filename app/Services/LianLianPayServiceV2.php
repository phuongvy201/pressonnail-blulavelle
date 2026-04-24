<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use lianlianpay\v3sdk\core\PaySDK;
use lianlianpay\v3sdk\model\Address;
use lianlianpay\v3sdk\model\Card;
use lianlianpay\v3sdk\model\Customer;
use lianlianpay\v3sdk\model\MerchantOrder;
use lianlianpay\v3sdk\model\PayRequest;
use lianlianpay\v3sdk\model\Product;
use lianlianpay\v3sdk\model\RequestPaymentData;
use lianlianpay\v3sdk\model\Shipping;
use lianlianpay\v3sdk\model\TerminalData;
use lianlianpay\v3sdk\service\Payment;
use lianlianpay\v3sdk\service\Notification;

class LianLianPayServiceV2
{
    protected $merchantId;
    protected $subMerchantId;
    protected $publicKey;
    protected $privateKey;
    protected $sandbox;
    protected $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('lianlian.merchant_id');
        $this->subMerchantId = config('lianlian.sub_merchant_id');
        $this->publicKey = config('lianlian.public_key');
        $this->privateKey = config('lianlian.private_key');
        $this->sandbox = config('lianlian.sandbox');
        $this->baseUrl = $this->sandbox ? config('lianlian.sandbox_url') : config('lianlian.production_url');
    }

    /**
     * Create payment request using proper SDK models
     * Tận dụng tối đa SDK LianLian Pay
     */
    public function createPayment(Order $order)
    {
        Log::info('🔧 LIANLIAN PAY CREATE PAYMENT (Simplified)', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => $order->total_amount
        ]);

        try {
            // 1️⃣ Khởi tạo SDK và khóa
            $paySdk = PaySDK::getInstance();
            $paySdk->init($this->sandbox);
            $paySdk->setKey($this->privateKey, $this->publicKey);

            // 2️⃣ Tạo đối tượng PayRequest
            $payRequest = new PayRequest();
            $payRequest->merchant_id = $this->merchantId;
            $payRequest->biz_code = 'EC';
            $payRequest->country = $order->country ?? 'US';

            // URL callback & redirect
            $payRequest->redirect_url = url('/payment/lianlian/return');
            $payRequest->notification_url = url('/payment/lianlian/webhook-v2');

            // 3️⃣ Sinh mã giao dịch duy nhất
            $time = now()->format('YmdHis');
            $merchantTransactionId = 'Order-' . $time;
            $payRequest->merchant_transaction_id = $merchantTransactionId;
            $payRequest->payment_method = 'inter_credit_card';

            // 4️⃣ Khách hàng & địa chỉ
            $address = new Address();
            $address->city = $order->city ?? 'N/A';
            $address->country = $order->country ?? 'US';
            $address->line1 = $order->shipping_address ?? 'N/A';
            $address->postal_code = $order->postal_code ?? '00000';
            $address->state = $order->state ?? '';

            $customer = new Customer();
            $customer->address = $address;
            $customer->customer_type = 'I';
            $customer->full_name = $order->customer_name;
            $customer->first_name = $this->getFirstName($order->customer_name);
            $customer->last_name = $this->getLastName($order->customer_name);
            $customer->email = $order->customer_email;
            $payRequest->customer = $customer;

            // 5️⃣ Sản phẩm (chỉ lấy 1 dòng demo hoặc loop đơn giản)
            $products = [];
            $itemIndex = 1;
            // Load product relationship if not already loaded
            if (!$order->relationLoaded('items.product')) {
                $order->load('items.product');
            }
            foreach ($order->items as $item) {
                $productModel = $item->product; // Get the actual Product model
                $product = new Product();
                $product->category = 'general';
                // Use SKU from product if available, otherwise fallback
                $product->name = $this->getGenericProductName($productModel, $itemIndex);
                $product->price = $item->unit_price;
                $product->product_id = (string)$item->product_id;
                $product->quantity = $item->quantity;
                $product->shipping_provider = 'DHL';
                // Use SKU from product if available, otherwise fallback to product_id
                $product->sku = $productModel && $productModel->sku ? $productModel->sku : ('SKU-' . $item->product_id);
                $product->url = url('/products/' . $item->product_id);
                $products[] = $product;
                $itemIndex++;
            }

            // 6️⃣ Thông tin vận chuyển
            $shipping = new Shipping();
            $shipping->address = $address;
            $shipping->name = $order->customer_name;
            $shipping->phone = $order->customer_phone ?? '';
            $shipping->cycle = '48h';

            // 7️⃣ Merchant Order
            $merchantOrder = new MerchantOrder();
            $merchantOrder->merchant_order_id = $merchantTransactionId;
            $merchantOrder->merchant_order_time = $time;
            $merchantOrder->order_amount = $order->total_amount;
            $merchantOrder->order_currency_code = 'USD';
            $merchantOrder->order_description = 'Order from Bluprinter';
            $merchantOrder->products = $products;
            $merchantOrder->shipping = $shipping;
            $payRequest->merchant_order = $merchantOrder;

            // 8️⃣ Dữ liệu thẻ (nếu có trong session)
            $cardInfo = session('lianlian_card_info');
            if ($cardInfo) {
                $paymentData = new RequestPaymentData();
                $card = new Card();
                $card->holder_name = $cardInfo['holder_name'];

                // Check if using tokenization or manual card entry
                if (isset($cardInfo['card_token']) && $cardInfo['card_token']) {
                    // Using tokenization (from iframe binding card)
                    $card->card_token = $cardInfo['card_token'];
                    Log::info('Using card token for payment', ['token' => substr($cardInfo['card_token'], 0, 10) . '...']);
                } else {
                    // Manual card entry (fallback)
                    $card->card_no = str_replace(' ', '', $cardInfo['card_no']);
                    $card->card_type = $cardInfo['card_type'];
                    $card->card_expiration_year = '20' . substr($cardInfo['card_expiration'], 3, 2);
                    $card->card_expiration_month = substr($cardInfo['card_expiration'], 0, 2);
                    $card->cvv = $cardInfo['cvv'];
                }

                $paymentData->card = $card;
                $payRequest->payment_data = $paymentData;
            }



            // 9️⃣ Log JSON request
            Log::info('LianLian Pay Request JSON', [
                'request' => json_encode($payRequest, JSON_PRETTY_PRINT)
            ]);

            // 🔟 Gửi yêu cầu thanh toán
            $payment = new Payment();
            $payResponse = $payment->pay($payRequest, $this->privateKey, $this->publicKey);

            Log::info('LianLian Pay Response', [
                'response' => json_encode($payResponse, JSON_PRETTY_PRINT)
            ]);

            return $payResponse;
        } catch (\Exception $e) {
            Log::error('❌ LianLian Pay Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Payment creation failed: ' . $e->getMessage());
        }
    }


    /**
     * Get payment token for iframe
     */
    public function getPaymentToken()
    {
        try {
            // Initialize SDK với keys
            $paySdk = PaySDK::getInstance();
            $paySdk->init($this->sandbox);
            $paySdk->setKey($this->privateKey, $this->publicKey);

            // Log token request
            Log::info('LianLian Pay Token Request:', [
                'merchant_id' => $this->merchantId,
                'sandbox_mode' => $this->sandbox,
                'timestamp' => now()->format('YmdHis')
            ]);

            // Tạo payment object và gọi get_token method
            $payment = new Payment();
            $payTokenResponse = $payment->get_token($this->merchantId, $this->privateKey, $this->publicKey);

            // Log response theo format SDK
            $tokenResponseJson = json_encode($payTokenResponse, JSON_PRETTY_PRINT);
            Log::info('LianLian Pay Token Response JSON:', ['response' => $tokenResponseJson]);

            // Log chi tiết response
            Log::info('LianLian Pay Token Response', [
                'return_code' => $payTokenResponse['return_code'] ?? 'No return code',
                'return_message' => $payTokenResponse['return_message'] ?? 'No message',
                'trace_id' => $payTokenResponse['trace_id'] ?? 'No trace ID',
                'sign_verify' => $payTokenResponse['sign_verify'] ?? false,
                'token' => isset($payTokenResponse['order']) ? substr($payTokenResponse['order'], 0, 10) . '...' : 'No token'
            ]);

            return $payTokenResponse;
        } catch (\Exception $e) {
            Log::error('LianLian Pay Token Error', [
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Token generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Query payment status
     */
    public function queryPayment($merchantTransactionId)
    {
        $transactionId = null;

        try {
            // Handle both Order object and transaction ID string
            if (is_object($merchantTransactionId)) {
                $transactionId = $merchantTransactionId->payment_transaction_id ?? $merchantTransactionId->id;
            } else {
                $transactionId = $merchantTransactionId;
            }

            if (!$transactionId) {
                throw new \Exception('No transaction ID provided');
            }

            $paySdk = PaySDK::getInstance();
            $paySdk->init($this->sandbox);

            $payment = new Payment();

            // Suppress PHP warnings/errors from SDK logging issues
            set_error_handler(function ($errno, $errstr) {
                // Ignore "Array to string conversion" from SDK logging
                if (strpos($errstr, 'Array to string conversion') !== false) {
                    return true;
                }
                return false;
            });

            try {
                $queryResponse = $payment->pay_query(
                    $this->merchantId,
                    $transactionId,
                    $this->privateKey,
                    $this->publicKey
                );
            } finally {
                restore_error_handler();
            }

            // Convert response to array if it's an object
            $responseArray = is_array($queryResponse) ? $queryResponse : json_decode(json_encode($queryResponse), true);

            Log::info('LianLian Pay Query Response', [
                'transaction_id' => $transactionId,
                'response' => $responseArray
            ]);

            return $responseArray;
        } catch (\Exception $e) {
            Log::error('LianLian Pay Query Error', [
                'transaction_id' => $transactionId ?? null,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Payment query failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook($notifyBody, $signature)
    {
        try {
            $notification = new Notification();
            $notifyData = $notification->payment_notify($notifyBody, $signature, $this->publicKey);

            Log::info('LianLian Pay Webhook Verified', [
                'notify_data' => $notifyData
            ]);

            return $notifyData;
        } catch (\Exception $e) {
            Log::error('LianLian Pay Webhook Verification Error', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Process payment status update
     */
    public function processPaymentStatus($notifyBody)
    {
        try {
            $data = json_decode($notifyBody, true);

            if (!$data) {
                Log::warning('Invalid notification body:', ['body' => $notifyBody]);
                return;
            }

            $merchantTransactionId = $data['merchant_transaction_id'] ?? null;
            $paymentStatus = $data['payment_data']['payment_status'] ?? null;
            $llTransactionId = $data['ll_transaction_id'] ?? null;

            Log::info('Processing payment status update:', [
                'merchant_transaction_id' => $merchantTransactionId,
                'payment_status' => $paymentStatus,
                'll_transaction_id' => $llTransactionId
            ]);

            if (!$merchantTransactionId || !$paymentStatus) {
                Log::warning('Missing required fields in notification:', [
                    'merchant_transaction_id' => $merchantTransactionId,
                    'payment_status' => $paymentStatus
                ]);
                return;
            }

            // Extract timestamp from merchant_transaction_id (format: "Order-20251001094538")
            $timestamp = substr($merchantTransactionId, 6); // Remove "Order-" prefix
            $orderTime = \Carbon\Carbon::createFromFormat('YmdHis', $timestamp);
            $startTime = $orderTime->copy()->subMinutes(10);
            $endTime = $orderTime->copy()->addMinutes(10);

            // Find order in time range
            $order = Order::whereBetween('created_at', [$startTime, $endTime])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$order) {
                $order = Order::whereBetween('created_at', [$startTime, $endTime])
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            if (!$order) {
                Log::warning('Order not found for merchant_transaction_id:', [
                    'merchant_transaction_id' => $merchantTransactionId
                ]);
                return;
            }

            // Update order status based on payment status
            switch ($paymentStatus) {
                case 'PS': // Payment Success
                    $order->update([
                        'status' => 'paid',
                        'payment_status' => 'completed',
                        'paid_at' => now(),
                        'payment_transaction_id' => $llTransactionId
                    ]);
                    Log::info("Order {$order->id} status updated to paid (PS)");

                    // Clear cart from database for logged-in users only
                    // For guest users, cart will be cleared in frontend (checkout.success.blade.php)
                    if ($order->user_id) {
                        \App\Models\Cart::where('user_id', $order->user_id)->delete();
                        Log::info('🗑️ Cart cleared after LianLian Pay webhook success', [
                            'order_id' => $order->id,
                            'user_id' => $order->user_id
                        ]);
                    } else {
                        Log::info('ℹ️ Guest order - cart will be cleared in frontend', [
                            'order_id' => $order->id
                        ]);
                    }

                    // Send order confirmation email to customer and admin
                    $adminEmail = 'support@blulavelle.com';
                    try {
                        \Illuminate\Support\Facades\Mail::to($order->customer_email)
                            ->send(new \App\Mail\OrderConfirmation($order));
                        Log::info('📧 Order confirmation email sent (webhook)', [
                            'order_number' => $order->order_number,
                            'email' => $order->customer_email
                        ]);

                        if ($adminEmail) {
                            \Illuminate\Support\Facades\Mail::to($adminEmail)
                                ->send(new \App\Mail\NewOrderAdminNotification($order));
                            Log::info('📧 Admin new-order email sent (webhook)', [
                                'order_number' => $order->order_number,
                                'email' => $adminEmail
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('❌ Failed to send order confirmation email (webhook)', [
                            'order_number' => $order->order_number,
                            'email' => $order->customer_email,
                            'admin_email' => $adminEmail,
                            'error' => $e->getMessage()
                        ]);
                    }
                    break;

                case 'PP': // Payment Processing
                    $order->update(['payment_status' => 'processing']);
                    Log::info("Order {$order->id} payment processing (PP)");
                    break;

                case 'WP': // Waiting Payment
                    $order->update(['payment_status' => 'pending']);
                    Log::info("Order {$order->id} waiting payment (WP)");
                    break;

                case 'declined':
                case 'failed':
                case 'cancelled':
                    $order->update([
                        'status' => 'failed',
                        'payment_status' => 'failed'
                    ]);
                    Log::info("Order {$order->id} status updated to failed ({$paymentStatus})");
                    break;

                case 'timeout':
                case 'expired':
                    $order->update([
                        'status' => 'expired',
                        'payment_status' => 'expired'
                    ]);
                    Log::info("Order {$order->id} status updated to expired");
                    break;

                default:
                    Log::info("Order {$order->id} unknown payment status: {$paymentStatus}");
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Error processing payment status:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get generic product name to avoid copyright issues
     * 
     * @param int $index The index of the product (1-based)
     * @return string Generic product name like "Item #1", "Item #2", etc.
     */
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
     * Get first name from full name
     */
    private function getFirstName($fullName)
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? 'Customer';
    }

    /**
     * Get last name from full name
     */
    private function getLastName($fullName)
    {
        $parts = explode(' ', trim($fullName));
        return count($parts) > 1 ? end($parts) : '';
    }
}
