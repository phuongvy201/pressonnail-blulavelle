<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Application Status - {{ config('app.name') }}</title>
    <style>
        body { font-family: 'Plus Jakarta Sans', Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; background: #f8f6f6; }
        .wrap { max-width: 560px; margin: 24px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(240,66,124,.12); }
        .header { background: linear-gradient(135deg, #f0427c 0%, #e03a70 100%); color: #fff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 800; }
        .content { padding: 28px; }
        .footer { background: #f8f6f6; padding: 16px 28px; text-align: center; color: #64748b; font-size: 13px; border-top: 1px solid #fce7ef; }
        a { color: #f0427c; font-weight: 600; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1>Seller Application Status</h1>
    </div>
    <div class="content">
        <h2 style="margin:0 0 12px; color:#1e293b; font-size:18px;">Hi {{ $application->name }},</h2>
        @if($status === 'approved')
            <p>Your seller application has been <strong>approved</strong>. You can log in and start selling.</p>
            @if(!empty($credentials))
                <p><strong>Account details:</strong></p>
                <ul>
                    <li><strong>Login email:</strong> {{ $credentials['email'] }}</li>
                    <li><strong>Temporary password:</strong> {{ $credentials['password'] }}</li>
                </ul>
                <p>Login here: <a href="{{ route('login') }}">{{ route('login') }}</a></p>
                <p>Please change your password after first login.</p>
            @else
                <p>You can use your existing account to log in: <a href="{{ route('login') }}">{{ route('login') }}</a></p>
            @endif
        @else
            <p>Your seller application has been <strong>reviewed</strong> and was not approved at this time.</p>
            @if($reason)
                <p><strong>Notes:</strong> {{ $reason }}</p>
            @endif
        @endif
        <p style="margin-top: 16px;"><strong>Application details:</strong></p>
        <ul style="color:#475569;">
            <li><strong>Name:</strong> {{ $application->name }}</li>
            <li><strong>Email:</strong> {{ $application->email }}</li>
            @if($application->phone)
                <li><strong>Phone:</strong> {{ $application->phone }}</li>
            @endif
            @if($application->store_name)
                <li><strong>Store / Brand:</strong> {{ $application->store_name }}</li>
            @endif
            <li><strong>Main categories:</strong> {{ $application->product_categories }}</li>
        </ul>
        @if($application->message)
            <p><strong>Notes from you:</strong></p>
            <p style="white-space: pre-wrap; background:#fdf2f7; padding:12px; border-radius:8px; border-left:4px solid #f0427c;">{{ $application->message }}</p>
        @endif
        <p style="margin-top: 20px;">Thank you for choosing blulavelle.</p>
    </div>
    <div class="footer">
        <p style="margin:0;">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</div>
</body>
</html>
