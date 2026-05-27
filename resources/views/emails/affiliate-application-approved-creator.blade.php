@php
    use App\Support\CreatorPortal;
    $shopUrl = rtrim((string) config('creator.shop_url', config('app.url')), '/');
    $referralUrl = $shopUrl.'?ref='.urlencode($affiliate->code);
    $dashboardUrl = CreatorPortal::dashboardUrl();
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application approved</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1e293b; max-width: 560px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 1.25rem; margin: 0 0 16px;">Congratulations, {{ $affiliateApplication->full_name }}!</h1>
    <p style="margin: 0 0 16px;">Your KOC / affiliate application for <strong>{{ config('app.name') }}</strong> has been <strong style="color: #15803d;">approved</strong>. Your creator account is now active.</p>

    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; margin: 0 0 16px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Affiliate code</strong></td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><code>{{ $affiliate->code }}</code></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Tier</strong></td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">{{ ucfirst($affiliate->tier ?? 'basic') }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Referral link</strong></td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; word-break: break-all;">
                <a href="{{ $referralUrl }}" style="color: #0195fe;">{{ $referralUrl }}</a>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 16px; font-size: 0.875rem;">Share your referral link to earn commission on qualifying orders. Log in to the creator portal to view analytics, promo codes, and sample requests.</p>

    <p style="margin: 0 0 8px;">
        <a href="{{ $dashboardUrl }}" style="display: inline-block; background: #0195fe; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600;">Open creator dashboard</a>
    </p>

    <p style="margin: 24px 0 0; font-size: 0.75rem; color: #64748b;">
        If you have questions, reply to this email or contact our support team.<br>
        {{ config('app.name') }}
    </p>
</body>
</html>
