<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order verification code</title>
</head>
<body style="margin:0;padding:24px 0;background:#f0f7ff;font-family:Arial,Helvetica,sans-serif;color:#334155;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px;text-align:center;background:#0195FE;color:#ffffff;">
                            <p style="margin:0;font-size:14px;opacity:.9;">{{ config('app.name') }}</p>
                            <p style="margin:8px 0 0 0;font-size:24px;font-weight:700;">Verify your order lookup</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 16px 0;">Hi {{ $customerName }},</p>
                            <p style="margin:0 0 16px 0;">
                                Use this code to view order <strong>{{ $orderNumber }}</strong> in the BluLavelle app.
                                It expires in 10 minutes.
                            </p>
                            <p style="margin:0 0 16px 0;text-align:center;font-size:32px;letter-spacing:8px;font-weight:700;color:#0f172a;">
                                {{ $code }}
                            </p>
                            <p style="margin:0;color:#64748b;font-size:13px;">
                                If you did not request this, you can ignore this email. Do not share the code with anyone.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
