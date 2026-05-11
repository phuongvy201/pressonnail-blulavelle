<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gift Card Usage Confirmation</title>
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
                        <td style="padding:6px 24px 16px 24px;text-align:center;border-bottom:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:32px;line-height:1.1;color:#0195FE;font-weight:700;">Gift Card Used</p>
                            <p style="margin:10px 0 0 0;color:#64748b;">Order #{{ $order->order_number }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 24px;">
                            <p style="margin:0 0 10px 0;color:#0f172a;font-size:16px;">You used <strong>{{ number_format($usedAmount, 2) }} {{ $giftCard->currency }}</strong> from your gift card.</p>
                            <p style="margin:0;color:#0f172a;font-size:16px;">Remaining balance: <strong>{{ number_format($remainingBalance, 2) }} {{ $giftCard->currency }}</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 24px 20px 24px;">
                            <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:6px;padding:12px 14px;">
                                <p style="margin:0 0 6px 0;color:#64748b;">Gift Card Code</p>
                                <p style="margin:0;color:#0f172a;font-size:20px;font-weight:700;letter-spacing:1.2px;">{{ $giftCard->code }}</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px;background:#f8fafc;border-top:1px solid #e5e7eb;text-align:center;">
                            <p style="margin:0;color:#64748b;font-size:13px;">This is an automated email from {{ config('app.name') }}.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
