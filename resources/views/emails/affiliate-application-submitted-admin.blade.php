<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New affiliate application</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1e293b; max-width: 560px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 1.25rem; margin: 0 0 16px;">New KOC / affiliate application</h1>
    <p style="margin: 0 0 16px;">Someone submitted the creator affiliate form.</p>
    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
        <tr><td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Name</strong></td><td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">{{ $affiliateApplication->full_name }}</td></tr>
        <tr><td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Email</strong></td><td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">{{ $affiliateApplication->email }}</td></tr>
        <tr><td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Phone</strong></td><td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">{{ $affiliateApplication->phone ?: '—' }}</td></tr>
        <tr><td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Proposed ref code</strong></td><td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><code>{{ $affiliateApplication->proposed_ref_code }}</code></td></tr>
        <tr><td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Submitted</strong></td><td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">{{ $affiliateApplication->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td></tr>
    </table>
    @if($affiliateApplication->social_links)
        <p style="margin: 16px 0 4px;"><strong>Social / links</strong></p>
        <p style="margin: 0; white-space: pre-wrap; font-size: 0.875rem;">{{ $affiliateApplication->social_links }}</p>
    @endif
    @if($affiliateApplication->message)
        <p style="margin: 16px 0 4px;"><strong>Message</strong></p>
        <p style="margin: 0; white-space: pre-wrap; font-size: 0.875rem;">{{ $affiliateApplication->message }}</p>
    @endif
    <p style="margin: 24px 0 0;">
        <a href="{{ route('admin.affiliate-applications.show', $affiliateApplication) }}" style="display: inline-block; background: #0195fe; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600;">Open in admin</a>
    </p>
    <p style="margin: 16px 0 0; font-size: 0.75rem; color: #64748b;">{{ config('app.name') }}</p>
</body>
</html>
