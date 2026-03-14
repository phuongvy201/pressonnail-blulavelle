<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Seller Application - {{ config('app.name') }}</title>
    <style>
        body { font-family: 'Plus Jakarta Sans', Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; background: #f8f6f6; }
        .wrap { max-width: 560px; margin: 24px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(240,66,124,.12); }
        .header { background: linear-gradient(135deg, #f0427c 0%, #e03a70 100%); color: #fff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 800; }
        .content { padding: 28px; }
        .row { padding: 8px 0; border-bottom: 1px solid #fce7ef; }
        .row:last-child { border-bottom: none; }
        .footer { background: #f8f6f6; padding: 16px 28px; text-align: center; color: #64748b; font-size: 13px; border-top: 1px solid #fce7ef; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1>New Seller Application</h1>
    </div>
    <div class="content">
        <div class="row"><strong>Name:</strong> {{ $data['name'] }}</div>
        <div class="row"><strong>Email:</strong> {{ $data['email'] }}</div>
        @if(!empty($data['phone']))
            <div class="row"><strong>Phone:</strong> {{ $data['phone'] }}</div>
        @endif
        @if(!empty($data['store_name']))
            <div class="row"><strong>Store / Brand:</strong> {{ $data['store_name'] }}</div>
        @endif
        @if(!empty($data['website']))
            <div class="row"><strong>Website:</strong> {{ $data['website'] }}</div>
        @endif
        <div class="row"><strong>Product categories:</strong> {{ $data['product_categories'] }}</div>
        @if(!empty($data['marketplaces']))
            <div class="row"><strong>Selling on marketplaces:</strong> {{ $data['marketplaces'] }}</div>
        @endif
        @if(!empty($data['experience']))
            <div class="row"><strong>Experience:</strong> {{ $data['experience'] }}</div>
        @endif
        @if(!empty($data['message']))
            <div style="margin-top: 16px;">
                <strong>Notes:</strong>
                <p style="white-space: pre-wrap; margin: 8px 0 0; background:#fdf2f7; padding:12px; border-radius:8px; border-left:4px solid #f0427c;">{{ $data['message'] }}</p>
            </div>
        @endif
    </div>
    <div class="footer">
        <p style="margin:0;">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</div>
</body>
</html>
