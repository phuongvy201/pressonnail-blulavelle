<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to blulavelle Newsletter</title>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f6f6;
        }
        .container {
            max-width: 600px;
            margin: 24px auto;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(240, 66, 124, 0.12);
        }
        .header {
            background: linear-gradient(135deg, #f0427c 0%, #e03a70 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.95;
            font-size: 16px;
            font-weight: 500;
        }
        .content {
            padding: 36px 30px;
        }
        .welcome-message {
            text-align: center;
            margin-bottom: 28px;
        }
        .welcome-message h2 {
            color: #1e293b;
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 12px 0;
        }
        .welcome-message p {
            color: #64748b;
            font-size: 16px;
            margin: 0;
        }
        .benefits {
            background: #fdf2f7;
            border: 1px solid rgba(240, 66, 124, 0.15);
            border-radius: 12px;
            padding: 24px;
            margin: 28px 0;
        }
        .benefits h3 {
            color: #be185d;
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 18px 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 14px;
        }
        .benefit-item:last-child {
            margin-bottom: 0;
        }
        .benefit-icon {
            width: 36px;
            height: 36px;
            background: rgba(240, 66, 124, 0.12);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 14px;
            flex-shrink: 0;
            font-size: 18px;
        }
        .benefit-text {
            color: #475569;
            font-size: 15px;
            font-weight: 500;
        }
        .cta-section {
            text-align: center;
            margin: 28px 0 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #f0427c 0%, #e03a70 100%);
            color: white !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 4px 14px rgba(240, 66, 124, 0.35);
        }
        .cta-button:hover {
            opacity: 0.95;
        }
        .footer {
            background: #f8f6f6;
            padding: 28px 30px;
            text-align: center;
            border-top: 1px solid #fce7ef;
        }
        .footer p {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #64748b;
        }
        .footer a {
            color: #f0427c;
            text-decoration: none;
            font-weight: 600;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .unsubscribe {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
        .unsubscribe p {
            margin: 4px 0;
            font-size: 12px;
        }
        .unsubscribe a {
            color: #94a3b8;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome to blulavelle!</h1>
            <p>You're now part of our exclusive community</p>
        </div>

        <div class="content">
            <div class="welcome-message">
                <h2>Thank you for subscribing!</h2>
                <p>We're excited to have you join our community of creative minds and design enthusiasts.</p>
            </div>

            <div class="benefits">
                <h3>What you'll get</h3>
                <div class="benefit-item">
                    <div class="benefit-icon">🎨</div>
                    <div class="benefit-text">Exclusive design tips and tutorials</div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">💎</div>
                    <div class="benefit-text">Early access to new products and features</div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">🎁</div>
                    <div class="benefit-text">Special discounts and promotional offers</div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">📰</div>
                    <div class="benefit-text">Industry news and trend updates</div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">👥</div>
                    <div class="benefit-text">Community highlights and success stories</div>
                </div>
            </div>

            <div class="cta-section">
                <a href="{{ route('products.index') }}" class="cta-button">Explore Our Products</a>
            </div>
        </div>

        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>Stay connected with us on social media</p>
            <div class="unsubscribe">
                <p>You received this email because you subscribed to our newsletter.</p>
                <p>
                    <a href="{{ route('newsletter.unsubscribe', ['email' => $email]) }}">Unsubscribe</a> &nbsp;·&nbsp;
                    <a href="{{ route('page.show', 'privacy-policy') }}">Privacy Policy</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
