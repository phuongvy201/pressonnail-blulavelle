@props(['url'])
@php
    $mailLogoUrl = \App\Support\Settings::get('mail.logo_url', config('theme.mail_logo_url'));
    $mailBrandName = \App\Support\Settings::get('mail.brand_name', config('theme.mail_brand_name'));
    if (empty($mailLogoUrl)) {
        $mailLogoUrl = asset('images/logo to.png');
    }
    if (empty($mailBrandName)) {
        $mailBrandName = config('app.name');
    }
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
<img src="{{ $mailLogoUrl }}" class="logo" alt="{{ e($mailBrandName) }} Logo" style="height: 75px; max-height: 75px;">
@endif
</a>
</td>
</tr>
