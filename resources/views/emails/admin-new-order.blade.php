<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order - {{ $order->order_number }}</title>
</head>
<body style="margin:0;padding:24px 0;background:#2c4a63;font-family:Arial,Helvetica,sans-serif;color:#334155;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <td align="center">
                <table role="presentation" width="680" cellpadding="0" cellspacing="0" style="max-width:680px;background:#ffffff;border-radius:6px;overflow:hidden;">
                    <tr>
                        <td style="padding:18px 24px 8px 24px;text-align:center;">
                            <p style="margin:0;color:#0195FE;font-size:14px;font-weight:700;">{{ config('app.name') }} Admin</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 24px 16px 24px;text-align:center;border-bottom:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:44px;line-height:1;color:#0195FE;font-weight:700;">New Order Alert</p>
                            <p style="margin:10px 0 0 0;color:#64748b;">Order #{{ $order->order_number }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 24px 0 24px;">
                            <p style="margin:0;color:#0195FE;font-size:26px;font-weight:700;text-align:center;">Order Information</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#f8fafc;border:1px solid #e5e7eb;">
                                <tr><td style="padding:9px 12px;color:#64748b;">Order ID</td><td style="padding:9px 12px;text-align:right;color:#0f172a;">#{{ $order->order_number }}</td></tr>
                                <tr><td style="padding:9px 12px;color:#64748b;">Order Date</td><td style="padding:9px 12px;text-align:right;color:#0f172a;">{{ $order->created_at->format('M d, Y H:i') }}</td></tr>
                                <tr><td style="padding:9px 12px;color:#64748b;">Total</td><td style="padding:9px 12px;text-align:right;color:#0f172a;">${{ number_format($order->total_amount, 2) }}</td></tr>
                                <tr><td style="padding:9px 12px;color:#64748b;">Payment Status</td><td style="padding:9px 12px;text-align:right;color:#0f172a;">{{ ucfirst($order->payment_status) }}</td></tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:14px 24px 0 24px;">
                            <p style="margin:0;color:#0195FE;font-size:26px;font-weight:700;text-align:center;">Customer Information</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 24px;">
                            <p style="margin:0 0 5px 0;">Name: {{ $order->customer_name }}</p>
                            <p style="margin:0 0 5px 0;">Email: {{ $order->customer_email }}</p>
                            <p style="margin:0;">Phone: {{ $order->customer_phone ?: 'N/A' }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:14px 24px 0 24px;">
                            <p style="margin:0;color:#0195FE;font-size:26px;font-weight:700;text-align:center;">Shipping Address</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 24px;">
                            <p style="margin:0;">{{ $order->shipping_address }}, {{ $order->city }}{{ $order->state ? ', '.$order->state : '' }}, {{ $order->postal_code }}, {{ $order->country }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 24px 6px 24px;">
                            <p style="margin:0;color:#0195FE;font-size:26px;font-weight:700;text-align:center;">Order Items</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                @foreach($order->items as $item)
                                    @php
                                        $productOptions = is_string($item->product_options) ? json_decode($item->product_options, true) : ($item->product_options ?? []);
                                        $variantName = data_get($productOptions, 'selected_variant.variant_name');
                                        $media = $item->product ? $item->product->getEffectiveMedia() : [];
                                        $imageUrl = null;
                                        if ($media && count($media) > 0) {
                                            if (is_string($media[0])) {
                                                $imageUrl = $media[0];
                                            } elseif (is_array($media[0])) {
                                                $imageUrl = $media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? null;
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;" valign="top" width="110">
                                            @if($imageUrl)
                                                <img src="{{ $imageUrl }}" alt="{{ $item->product_name }}" width="96" height="96" style="display:block;border:1px solid #dbeafe;border-radius:4px;object-fit:cover;">
                                            @else
                                                <div style="width:96px;height:96px;border:1px solid #dbeafe;border-radius:4px;background:#f1f5f9;line-height:96px;text-align:center;color:#94a3b8;font-size:12px;">
                                                    No Image
                                                </div>
                                            @endif
                                        </td>
                                        <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;" valign="top">
                                            <p style="margin:0;font-size:16px;color:#0f172a;font-weight:700;">{{ $item->product_name }}</p>
                                            <p style="margin:6px 0 0 0;color:#64748b;">SKU: {{ $item->product->sku ?? 'N/A' }}</p>
                                            <p style="margin:4px 0 0 0;color:#64748b;">Variant: {{ $variantName ?: 'N/A' }}</p>
                                            <p style="margin:4px 0 0 0;color:#64748b;">Quantity: {{ $item->quantity }}</p>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:14px 24px 0 24px;">
                            <p style="margin:0;color:#0195FE;font-size:26px;font-weight:700;text-align:center;">Discount / Promotion</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 24px;">
                            <p style="margin:0 0 5px 0;">Promo Code: {{ $order->promo_code ?: 'N/A' }}</p>
                            <p style="margin:0;">Discount: ${{ number_format($order->discount_amount ?? 0, 2) }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:14px 24px 0 24px;">
                            <p style="margin:0;color:#0195FE;font-size:26px;font-weight:700;text-align:center;">Customer Note</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 24px;">
                            <p style="margin:0;">{{ $order->notes ?: 'N/A' }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 24px 26px 24px;border-top:1px solid #e5e7eb;text-align:center;">
                            <p style="margin:0 0 10px 0;color:#0f172a;">👉 View &amp; Process Order</p>
                            <p style="margin:0;">
                                <a href="{{ route('admin.orders.show', $order) }}" style="display:inline-block;padding:10px 16px;border-radius:4px;background:#0195FE;color:#ffffff;text-decoration:none;font-weight:700;">Open Order in Admin</a>
                            </p>
                            <p style="margin:14px 0 0 0;color:#334155;">Please process this order as soon as possible.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
