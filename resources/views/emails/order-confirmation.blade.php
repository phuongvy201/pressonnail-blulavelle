<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f6f6;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(240, 66, 124, 0.12);
        }
        .header {
            background: linear-gradient(135deg, #f0427c 0%, #e03a70 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .success-icon {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .content {
            padding: 30px;
        }
        .order-info {
            background: #fdf2f7;
            border-left: 4px solid #f0427c;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .order-info h2 {
            margin: 0 0 10px 0;
            color: #f0427c;
            font-size: 20px;
        }
        .order-number {
            font-size: 24px;
            font-weight: bold;
            color: #e03a70;
            margin: 10px 0;
        }
        .section {
            margin: 25px 0;
        }
        .section h3 {
            color: #f0427c;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #e03a70;
            padding-bottom: 8px;
        }
        .info-row {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            display: inline-block;
            width: 140px;
        }
        .product-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #fdf2f7;
            margin: 10px 0;
            border-radius: 8px;
        }
        .product-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }
        .product-info {
            flex: 1;
        }
        .product-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .product-details {
            font-size: 14px;
            color: #666;
        }
        .product-price {
            font-weight: bold;
            color: #e03a70;
            text-align: right;
        }
        .totals {
            background: #fdf2f7;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .total-row.final {
            border-top: 2px solid #f0427c;
            padding-top: 15px;
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
            color: #f0427c;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #f0427c 0%, #e03a70 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .button:hover {
            opacity: 0.9;
        }
        .footer {
            background: #f8f6f6;
            padding: 20px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
            border-top: 1px solid #fce7ef;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="success-icon">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="3">
                    <path d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1>🎉 Order Confirmed!</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">Thank you for your purchase</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Order Info Box -->
            <div class="order-info">
                <h2>Order Details</h2>
                <div class="order-number">{{ $order->order_number }}</div>
                <p style="margin: 5px 0 0 0; color: #666;">
                    Order Date: {{ $order->created_at->format('F d, Y \a\t g:i A') }}
                </p>
                <p style="margin: 5px 0 0 0;">
                    <span class="status-badge status-{{ $order->payment_status === 'paid' ? 'paid' : 'pending' }}">
                        {{ $order->payment_status === 'paid' ? 'Payment Confirmed' : 'Payment Pending' }}
                    </span>
                </p>
            </div>

            <!-- Customer Information -->
            <div class="section">
                <h3>👤 Customer Information</h3>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span>{{ $order->customer_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span>{{ $order->customer_email }}</span>
                </div>
                @if($order->customer_phone)
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span>{{ $order->customer_phone }}</span>
                </div>
                @endif
            </div>

            <!-- Shipping Address -->
            <div class="section">
                <h3>📦 Shipping Address</h3>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span>{{ $order->shipping_address }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">City:</span>
                    <span>{{ $order->city }}</span>
                </div>
                @if($order->state)
                <div class="info-row">
                    <span class="info-label">State:</span>
                    <span>{{ $order->state }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Postal Code:</span>
                    <span>{{ $order->postal_code }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Country:</span>
                    <span>{{ $order->country }}</span>
                </div>
            </div>

            <!-- Order Items -->
            <div class="section">
                <h3>🛍️ Order Items</h3>
                @foreach($order->items as $item)
                    <div class="product-item">
                        <div class="product-info">
                            <div class="product-name">{{ $item->product_name }}</div>
                            <div class="product-details">
                                Quantity: {{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }}
                            </div>
                        </div>
                        <div class="product-price">
                            ${{ number_format($item->total_price, 2) }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Order Totals -->
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>${{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="total-row">
                    <span>Shipping:</span>
                    <span>${{ number_format($order->shipping_cost, 2) }}</span>
                </div>
                <div class="total-row">
                    <span>Tax:</span>
                    <span>${{ number_format($order->tax_amount, 2) }}</span>
                </div>
                <div class="total-row final">
                    <span>Total:</span>
                    <span>${{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="section">
                <h3>💳 Payment Information</h3>
                <div class="info-row">
                    <span class="info-label">Method:</span>
                    <span>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span>
                        <span class="status-badge status-{{ $order->payment_status === 'paid' ? 'paid' : 'pending' }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </span>
                </div>
                @if($order->payment_transaction_id)
                <div class="info-row">
                    <span class="info-label">Transaction ID:</span>
                    <span style="font-family: monospace; font-size: 12px;">{{ $order->payment_transaction_id }}</span>
                </div>
                @endif
            </div>

            <!-- What's Next -->
            <div class="section" style="background: #e3f2fd; padding: 20px; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #1976d2; border: none;">📋 What's Next?</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li style="margin: 10px 0;">We'll process your order within 1-2 business days</li>
                    <li style="margin: 10px 0;">You'll receive a shipping notification with tracking number</li>
                    <li style="margin: 10px 0;">Track your order status anytime from your account</li>
                </ul>
            </div>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}" class="button">
                    Continue Shopping
                </a>
            </div>

            <!-- Support -->
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <p style="margin: 0; color: #856404; font-size: 14px;">
                    <strong>Need Help?</strong><br>
                    If you have any questions about your order, please contact our support team at 
                    <a href="mailto:{{ config('mail.from.address') }}" style="color: #f0427c;">{{ config('mail.from.address') }}</a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 10px 0;">
                <strong>{{ config('app.name') }}</strong><br>
                Creating unique products just for you
            </p>
            <p style="margin: 0; font-size: 12px; color: #999;">
                This is an automated email. Please do not reply to this email.<br>
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>

