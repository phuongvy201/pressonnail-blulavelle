<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isTopup ? 'Gift Card Top-up' : 'Gift Card Received' }}</title>
</head>
<body style="margin:0;padding:24px 0;background:#2c4a63;font-family:Arial,Helvetica,sans-serif;color:#334155;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="padding:18px 24px 8px 24px;text-align:center;">
                            <p style="margin:0;color:#0195FE;font-size:14px;font-weight:700;">{{ config('app.name') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 24px 16px 24px;text-align:center;border-bottom:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:36px;line-height:1.1;color:#0195FE;font-weight:700;">
                                {{ $isTopup ? 'Gift Card Updated' : 'You Received a Gift Card' }}
                            </p>
                            <p style="margin:10px 0 0 0;color:#64748b;">Order #{{ $order->order_number }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 24px 0 24px;text-align:center;">
                            <p style="margin:0 0 8px 0;font-size:22px;color:#0f172a;font-weight:700;">Gift Card Code</p>
                            <p style="margin:0;display:inline-block;padding:12px 18px;border:1px dashed #0195FE;border-radius:6px;background:#f0f9ff;color:#0f172a;font-size:24px;font-weight:700;letter-spacing:1.5px;">
                                {{ $giftCard->code }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#f8fafc;border:1px solid #e5e7eb;border-radius:6px;">
                                <tr>
                                    <td style="padding:10px 14px;color:#64748b;">Amount {{ $isTopup ? 'added' : 'issued' }}</td>
                                    <td style="padding:10px 14px;text-align:right;color:#0f172a;font-weight:700;">{{ number_format($amount, 2) }} {{ $giftCard->currency }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 14px;color:#64748b;">Current balance</td>
                                    <td style="padding:10px 14px;text-align:right;color:#0f172a;font-weight:700;">{{ number_format((float) $giftCard->balance, 2) }} {{ $giftCard->currency }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 14px;color:#64748b;">Order reference</td>
                                    <td style="padding:10px 14px;text-align:right;color:#0f172a;font-weight:700;">{{ $order->order_number }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if(!empty($giftMessage))
                    <tr>
                        <td style="padding:0 24px 20px 24px;">
                            <div style="background:#fff7ed;border-left:4px solid #f97316;padding:12px 14px;border-radius:4px;">
                                <p style="margin:0 0 6px 0;color:#9a3412;font-weight:700;">Message</p>
                                <p style="margin:0;color:#7c2d12;white-space:pre-line;">{{ $giftMessage }}</p>
                            </div>
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td style="padding:0 24px 24px 24px;">
                            <p style="margin:0;color:#334155;">
                                Use this code at checkout. If your order total is higher than the gift card balance, you can pay the remaining amount using Visa, PayPal, or Stripe.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 24px;background:#f8fafc;border-top:1px solid #e5e7eb;text-align:center;">
                            <p style="margin:0;color:#64748b;font-size:13px;">This is an automated email from {{ config('app.name') }}.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
