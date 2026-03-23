<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsSettingsController extends Controller
{
    public function edit(): View
    {
        $defaults = [
            'meta_pixel_id' => config('services.meta.pixel_id'),
            'tiktok_pixel_id' => config('services.tiktok.pixel_id'),
            'tiktok_test_event_code' => config('services.tiktok.test_event_code'),
            'google_tag_manager_id' => config('services.google.tag_manager_id'),
            'google_ads_id' => config('services.google.ads_id'),
            // GA4 (Google Analytics 4)
            'google_analytics_property_id' => config('services.google.analytics.property_id'),
            'google_analytics_credentials_path' => config('services.google.analytics.credentials_path'),
            // Theme (fallback từ config/theme.php khi DB trống)
            'header_bg' => config('theme.header_bg'),
            'header_border' => config('theme.header_border'),
            'footer_faq_bg' => config('theme.footer_faq_bg'),
            'footer_bg' => config('theme.footer_bg', '#242B3D'),
            'testimonials_bg' => config('theme.testimonials_bg'),
            'mail_logo_url' => config('theme.mail_logo_url'),
            'mail_brand_name' => config('theme.mail_brand_name'),
        ];

        $settings = [
            'meta_pixel_id' => Settings::get('analytics.meta_pixel_id', $defaults['meta_pixel_id']),
            'tiktok_pixel_id' => Settings::get('analytics.tiktok_pixel_id', $defaults['tiktok_pixel_id']),
            'tiktok_test_event_code' => Settings::get('analytics.tiktok_test_event_code', $defaults['tiktok_test_event_code']),
            'google_tag_manager_id' => Settings::get('analytics.google_tag_manager_id', $defaults['google_tag_manager_id']),
            'google_ads_id' => Settings::get('analytics.google_ads_id', $defaults['google_ads_id']),
            // GA4 (Google Analytics 4)
            'google_analytics_property_id' => Settings::get('analytics.google_analytics_property_id', $defaults['google_analytics_property_id']),
            'google_analytics_credentials_path' => Settings::get('analytics.google_analytics_credentials_path', $defaults['google_analytics_credentials_path']),
            // Theme colors (HEX/rgb... validated loosely; view will safely apply)
            'header_bg' => Settings::get('theme.header_bg', $defaults['header_bg']),
            'header_border' => Settings::get('theme.header_border', $defaults['header_border']),
            'footer_faq_bg' => Settings::get('theme.footer_faq_bg', $defaults['footer_faq_bg']),
            'footer_bg' => Settings::get('theme.footer_bg', $defaults['footer_bg']),
            'testimonials_bg' => Settings::get('theme.testimonials_bg', $defaults['testimonials_bg']),
            'mail_logo_url' => Settings::get('mail.logo_url', $defaults['mail_logo_url']),
            'mail_brand_name' => Settings::get('mail.brand_name', $defaults['mail_brand_name']),
        ];

        return view('admin.settings.analytics', compact('settings', 'defaults'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meta_pixel_id' => ['nullable', 'string', 'max:64'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:64'],
            'tiktok_test_event_code' => ['nullable', 'string', 'max:64'],
            'google_tag_manager_id' => ['nullable', 'string', 'max:64'],
            'google_ads_id' => ['nullable', 'string', 'max:64'],
            // GA4 (Google Analytics 4)
            'google_analytics_property_id' => ['nullable', 'string', 'max:64'],
            'google_analytics_credentials_path' => ['nullable', 'string', 'max:512'],
            'google_analytics_credentials' => ['nullable', 'file', 'max:10240', 'mimetypes:application/json,text/plain,application/octet-stream'],
            'header_bg' => ['nullable', 'string', 'max:64'],
            'header_border' => ['nullable', 'string', 'max:64'],
            'footer_faq_bg' => ['nullable', 'string', 'max:64'],
            'footer_bg' => ['nullable', 'string', 'max:64'],
            'testimonials_bg' => ['nullable', 'string', 'max:64'],
            'mail_logo_url' => ['nullable', 'string', 'max:512'],
            'mail_brand_name' => ['nullable', 'string', 'max:128'],
        ]);

        // If user uploaded a GA4 credentials JSON, store it into storage/app and persist the relative path.
        if ($request->hasFile('google_analytics_credentials')) {
            $file = $request->file('google_analytics_credentials');
            if ($file && $file->isValid()) {
                $storedPath = $file->storeAs('analytics/google-analytics', 'google-analytics-credentials-' . time() . '.json');
                $validated['google_analytics_credentials_path'] = $storedPath;
            }
        }

        foreach ($validated as $key => $value) {
            $namespace = match (true) {
                in_array($key, ['header_bg', 'header_border', 'footer_faq_bg', 'footer_bg', 'testimonials_bg'], true) => 'theme',
                in_array($key, ['mail_logo_url', 'mail_brand_name'], true) => 'mail',
                default => 'analytics',
            };
            if ($namespace === 'mail') {
                $settingsKey = $key === 'mail_logo_url' ? 'mail.logo_url' : 'mail.brand_name';
                Settings::set($settingsKey, $value !== null ? trim($value) : null);
                continue;
            }
            Settings::set("$namespace.$key", $value !== null ? trim($value) : null);
        }

        return redirect()
            ->route('admin.settings.analytics.edit')
                ->with('success', 'Tracking configuration updated successfully.');
    }
}
