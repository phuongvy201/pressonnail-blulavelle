<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Support Ticket - {{ config('app.name') }}</title>
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
            font-size: 24px;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #fff;
            color: #f0427c;
            margin-top: 10px;
        }
        .content { padding: 24px 30px; }
        .section { margin: 20px 0; }
        .section h3 {
            color: #f0427c;
            margin: 0 0 12px;
            font-size: 16px;
            border-bottom: 2px solid #e03a70;
            padding-bottom: 6px;
        }
        .info-box {
            background: #fdf2f7;
            border-left: 4px solid #f0427c;
            padding: 16px;
            border-radius: 8px;
        }
        .row { padding: 8px 0; border-bottom: 1px solid #fce7ef; }
        .row:last-child { border-bottom: none; }
        .label { width: 140px; display: inline-block; color: #666; font-weight: 600; }
        .message {
            white-space: pre-wrap;
            border: 1px solid #fce7ef;
            background: #fdf2f7;
            padding: 12px;
            border-radius: 8px;
        }
        .footer {
            background: #f8f6f6;
            padding: 18px 24px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
            border-top: 1px solid #fce7ef;
        }
        .cta {
            display: inline-block;
            padding: 12px 20px;
            background: linear-gradient(135deg, #f0427c 0%, #e03a70 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📩 New Support Ticket</h1>
            <div class="badge">{{ config('app.name') }}</div>
        </div>
        <div class="content">
            <div class="section">
                <h3>🧾 Ticket Information</h3>
                <div class="info-box">
                    <div class="row">
                        <span class="label">Subject:</span>
                        <span>{{ $data['subject'] ?? '' }}</span>
                    </div>
                    <div class="row">
                        <span class="label">Name:</span>
                        <span>{{ $data['name'] ?? '' }}</span>
                    </div>
                    <div class="row">
                        <span class="label">Email:</span>
                        <span>{{ $data['email'] ?? '' }}</span>
                    </div>
                    @if(!empty($data['order_number']))
                    <div class="row">
                        <span class="label">Order No.:</span>
                        <span>{{ $data['order_number'] }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <div class="section">
                <h3>💬 Message</h3>
                <div class="message">{{ $data['message'] ?? '' }}</div>
            </div>
            <div class="section" style="text-align:center;">
                <a href="{{ config('app.url') }}" class="cta">Go to Dashboard</a>
            </div>
            <div class="section" style="background:#fdf2f7; padding:14px; border-radius:8px; border-left:4px solid #f0427c;">
                <p style="margin:0; color:#be185d; font-size:14px;">
                    If you need to reply, contact: <a href="mailto:{{ config('mail.from.address') }}" style="color:#f0427c; font-weight:600;">{{ config('mail.from.address') }}</a>
                </p>
            </div>
        </div>
        <div class="footer">
            <p style="margin:0 0 8px 0;"><strong>{{ config('app.name') }}</strong></p>
            <p style="margin:0; font-size:12px; color:#94a3b8;">This is an automated notification from your site.<br>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
