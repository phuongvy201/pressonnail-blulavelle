<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - {{ $order->order_number }}</title>
</head>
<body style="margin:0;padding:24px 0;background:#2c4a63;font-family:Arial,Helvetica,sans-serif;color:#334155;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:6px;overflow:hidden;">
                    <tr>
                        <td style="padding:18px 24px 8px 24px;text-align:center;">
                            <p style="margin:0;color:#0195FE;font-size:14px;font-weight:700;">{{ config('app.name') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 24px 16px 24px;text-align:center;border-bottom:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:46px;line-height:1;color:#0195FE;font-weight:700;">Order Confirmation</p>
                            <p style="margin:10px 0 0 0;color:#64748b;">Order #{{ $order->order_number }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 24px 0 24px;text-align:center;">
                            <p style="margin:0 0 8px 0;font-size:26px;color:#0f172a;">Thank you, {{ $order->customer_name }}</p>
                            <p style="margin:0 0 6px 0;color:#475569;">We’ve received your order and it is now being processed.</p>
                            <p style="margin:0;color:#64748b;">Order date: {{ $order->created_at->format('M d, Y H:i') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 24px 6px 24px;">
                            <p style="margin:0;color:#0195FE;font-size:28px;font-weight:700;text-align:center;">Order Details</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                @foreach($order->items as $item)
                                    @php
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
                                            <p style="margin:8px 0 0 0;color:#64748b;">Quantity: {{ $item->quantity }}</p>
                                            <p style="margin:4px 0 0 0;color:#64748b;">Price: ${{ number_format($item->unit_price, 2) }}</p>
                                            <p style="margin:8px 0 0 0;color:#0195FE;font-weight:700;">Line total: ${{ number_format($item->total_price, 2) }}</p>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px 0 24px;">
                            <p style="margin:0;color:#0195FE;font-size:28px;font-weight:700;text-align:center;">Order Total</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#f8fafc;border:1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding:10px 14px;color:#64748b;">Subtotal</td>
                                    <td style="padding:10px 14px;text-align:right;color:#0f172a;">${{ number_format($order->subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 14px;color:#64748b;">Discount</td>
                                    <td style="padding:10px 14px;text-align:right;color:#0f172a;">-${{ number_format($order->discount_amount ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 14px;color:#64748b;">Shipping</td>
                                    <td style="padding:10px 14px;text-align:right;color:#0f172a;">{{ (float) $order->shipping_cost > 0 ? '$'.number_format($order->shipping_cost, 2) : 'Free' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;border-top:1px solid #e5e7eb;color:#0195FE;font-size:24px;font-weight:700;">Total</td>
                                    <td style="padding:12px 14px;border-top:1px solid #e5e7eb;text-align:right;color:#0195FE;font-size:24px;font-weight:700;">${{ number_format($order->total_amount, 2) }}</td>
                                </tr>
                            </table>
                            <p style="margin:14px 0 0 0;color:#64748b;">Payment Method: {{ ucfirst(str_replace('_', ' ', $order->payment_method ?: 'N/A')) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 24px 26px 24px;border-top:1px solid #e5e7eb;text-align:center;">
                            <p style="margin:0;color:#334155;">Your order is currently being prepared. We’ll send you another email once it has been shipped.</p>
                            <p style="margin:12px 0 0 0;color:#334155;">If you have any questions, feel free to contact us.</p>
                            <p style="margin:18px 0 0 0;font-size:30px;color:#0f172a;">We hope to see you again,</p>
                            <p style="margin:4px 0 0 0;color:#0195FE;font-size:28px;font-weight:700;">Blu Lavelle Team</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

