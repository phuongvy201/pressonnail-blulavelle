<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New live chat message</title>
</head>
<body style="margin:0;padding:24px 0;background:#f0f7ff;font-family:Arial,Helvetica,sans-serif;color:#334155;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px;text-align:center;background:#0195FE;color:#ffffff;">
                            <p style="margin:0;font-size:14px;opacity:.9;">{{ config('app.name') }}</p>
                            <p style="margin:8px 0 0 0;font-size:24px;font-weight:700;">New live chat message</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 16px 0;">Hi {{ $conversation->customer_name }},</p>
                            <p style="margin:0 0 16px 0;">You have a new reply in your live chat:</p>
                            <div style="background:#f8fafc;border-left:4px solid #0195FE;padding:14px 16px;border-radius:6px;">
                                <p style="margin:0;white-space:pre-wrap;color:#0f172a;">{{ $message->body }}</p>
                            </div>
                            <p style="margin:20px 0 0 0;text-align:center;">
                                <a href="{{ config('app.url') }}" style="display:inline-block;background:#0195FE;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:700;">
                                    Continue this chat
                                </a>
                            </p>
                            <p style="margin:16px 0 0 0;color:#64748b;font-size:13px;">
                                Open the site and enter the same name and email to see your full chat history.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px;background:#f8fafc;border-top:1px solid #e5e7eb;text-align:center;">
                            <p style="margin:0;color:#64748b;font-size:12px;">This is an automated email from {{ config('app.name') }}.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
