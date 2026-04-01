<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Update - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Segoe UI', Tahoma, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f6f6;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(240, 66, 124, 0.12);
            overflow: hidden;
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
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 30px;
        }
        .order-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .order-info h3 {
            margin: 0 0 15px 0;
            color: #f0427c;
            font-size: 18px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #64748b;
        }
        .info-value {
            font-weight: 500;
            color: #1e293b;
        }
        .tracking-section {
            background: #ecfdf5;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .tracking-number {
            font-size: 24px;
            font-weight: bold;
            color: #059669;
            background: white;
            padding: 10px 20px;
            border-radius: 6px;
            display: inline-block;
            margin: 10px 0;
            border: 2px dashed #10b981;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-shipped {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .status-processing {
            background: #fef3c7;
            color: #d97706;
        }
        .status-delivered {
            background: #dcfce7;
            color: #16a34a;
        }
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #f0427c 0%, #e03a70 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            opacity: 0.95;
        }
        .footer {
            background: #f8f6f6;
            padding: 20px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
            border-top: 1px solid #fce7ef;
        }
        .items-list {
            margin: 20px 0;
        }
        .item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin: 10px 0;
        }
        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            margin-right: 15px;
            object-fit: cover;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: 600;
            margin: 0 0 5px 0;
        }
        .item-meta {
            font-size: 14px;
            color: #64748b;
        }
        .item-price {
            font-weight: 600;
            color: #059669;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Order Update</h1>
            <p>Your order status has been updated</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p>Hello {{ $order->customer_name }},</p>
            
            @if($trackingNumber)
                <div class="tracking-section">
                    <h3 style="margin: 0 0 15px 0; color: #059669;">🚚 Your Order Has Been Shipped!</h3>
                    <p style="margin: 0 0 15px 0;">You can now track your package using the tracking number below:</p>
                    <div class="tracking-number">{{ $trackingNumber }}</div>
                    <p style="margin: 15px 0 0 0; font-size: 14px; color: #64748b;">
                        You can track your package on the carrier's website or mobile app.
                    </p>
                </div>
            @endif

            <!-- Order Information -->
            <div class="order-info">
                <h3>Order Details</h3>
                <div class="info-row">
                    <span class="info-label">Order Number:</span>
                    <span class="info-value">{{ $order->order_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Date:</span>
                    <span class="info-value">{{ $order->created_at->format('M d, Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-{{ $order->status }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value">${{ number_format($order->total_amount, 2) }}</span>
                </div>
                @if($trackingNumber)
                    <div class="info-row">
                        <span class="info-label">Tracking Number:</span>
                        <span class="info-value" style="color: #059669; font-weight: bold;">{{ $trackingNumber }}</span>
                    </div>
                @endif
            </div>

            <!-- Order Items -->
            <div class="items-list">
                <h3 style="color: #f0427c; margin-bottom: 15px;">Order Items</h3>
                @foreach($order->items as $item)
                    <div class="item">
                        @php
                            $media = $item->product ? $item->product->getEffectiveMedia() : [];
                            $imageUrl = null;
                            $emailItemImgAlt = $item->product_name;
                            if ($media && count($media) > 0 && $item->product) {
                                $emailItemImgAlt = $item->product->altForMediaItem($media[0], $item->product_name, 0);
                                if (is_string($media[0])) {
                                    $imageUrl = $media[0];
                                } elseif (is_array($media[0])) {
                                    $imageUrl = $media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? null;
                                }
                            }
                        @endphp
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}" 
                                 alt="{{ $emailItemImgAlt }}" 
                                 class="item-image">
                        @else
                            <div class="item-image" style="background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b;">
                                📦
                            </div>
                        @endif
                        <div class="item-details">
                            <div class="item-name">{{ $item->product_name }}</div>
                            <div class="item-meta">
                                Quantity: {{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }}
                                @if($item->product && $item->product->shop)
                                    • Shop: {{ $item->product->shop->shop_name }}
                                @endif
                            </div>
                        </div>
                        <div class="item-price">${{ number_format($item->total_price, 2) }}</div>
                    </div>
                @endforeach
            </div>

            <!-- Shipping Address -->
            <div class="order-info">
                <h3>Shipping Address</h3>
                <p style="margin: 0; line-height: 1.6;">
                    {{ $order->customer_name }}<br>
                    {{ $order->shipping_address }}<br>
                    {{ $order->city }}, {{ $order->state }} {{ $order->postal_code }}<br>
                    {{ $order->country }}
                </p>
            </div>

            @if($order->notes)
                <div class="order-info">
                    <h3>Additional Notes</h3>
                    <p style="margin: 0; font-style: italic;">{{ $order->notes }}</p>
                </div>
            @endif

            <p style="margin-top: 30px;">
                If you have any questions about your order, please don't hesitate to contact us.
            </p>

            <p>Thank you for your business!</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This email was sent regarding your order {{ $order->order_number }}.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
