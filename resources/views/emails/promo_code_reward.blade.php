<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Promo Code</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .container { max-width: 560px; margin: 24px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #f0427c, #e03a70); color: #fff; padding: 28px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 800; }
        .content { padding: 28px; }
        .code-box { background: #fce7ef; border: 2px dashed #f0427c; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .code-box .code { font-size: 28px; font-weight: 800; letter-spacing: 4px; color: #c41e5a; }
        .code-box .hint { font-size: 13px; color: #666; margin-top: 8px; }
        .footer { background: #f8f6f6; padding: 16px 24px; text-align: center; color: #666; font-size: 13px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎟️ Your Promo Code</h1>
        @if($triggerLabel)
            <p style="margin: 8px 0 0; opacity: .95;">{{ $triggerLabel }}</p>
        @endif
    </div>
    <div class="content">
        <p>Hi there,</p>
        <p>Use this code at checkout to get your discount:</p>
        <div class="code-box">
            <div class="code">{{ $promoCode }}</div>
            <div class="hint">Enter at checkout in the "Promo Code" field</div>
        </div>
        @if($promoDescription)
            <p style="font-size: 14px; color: #555;">{{ $promoDescription }}</p>
        @endif
        <p>Thank you for being a valued customer!</p>
    </div>
    <div class="footer">
        <p style="margin: 0;"><strong>{{ config('app.name') }}</strong></p>
        <p style="margin: 6px 0 0; font-size: 12px; color: #999;">© {{ date('Y') }} {{ config('app.name') }}.</p>
    </div>
</div>
</body>
</html>
