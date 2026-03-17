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
            // Theme (fallbacks handled in views)
            'header_bg' => null,
            'header_border' => null,
            'footer_faq_bg' => null,
            'footer_bg' => null,
            'testimonials_bg' => null,
        ];

        $settings = [
            'meta_pixel_id' => Settings::get('analytics.meta_pixel_id', $defaults['meta_pixel_id']),
            'tiktok_pixel_id' => Settings::get('analytics.tiktok_pixel_id', $defaults['tiktok_pixel_id']),
            'tiktok_test_event_code' => Settings::get('analytics.tiktok_test_event_code', $defaults['tiktok_test_event_code']),
            'google_tag_manager_id' => Settings::get('analytics.google_tag_manager_id', $defaults['google_tag_manager_id']),
            'google_ads_id' => Settings::get('analytics.google_ads_id', $defaults['google_ads_id']),
            // Theme colors (HEX/rgb... validated loosely; view will safely apply)
            'header_bg' => Settings::get('theme.header_bg', $defaults['header_bg']),
            'header_border' => Settings::get('theme.header_border', $defaults['header_border']),
            'footer_faq_bg' => Settings::get('theme.footer_faq_bg', $defaults['footer_faq_bg']),
            'footer_bg' => Settings::get('theme.footer_bg', $defaults['footer_bg']),
            'testimonials_bg' => Settings::get('theme.testimonials_bg', $defaults['testimonials_bg']),
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
            'header_bg' => ['nullable', 'string', 'max:64'],
            'header_border' => ['nullable', 'string', 'max:64'],
            'footer_faq_bg' => ['nullable', 'string', 'max:64'],
            'footer_bg' => ['nullable', 'string', 'max:64'],
            'testimonials_bg' => ['nullable', 'string', 'max:64'],
        ]);

        foreach ($validated as $key => $value) {
            $namespace = in_array($key, ['header_bg', 'header_border', 'footer_faq_bg', 'footer_bg', 'testimonials_bg'], true)
                ? 'theme'
                : 'analytics';
            Settings::set("$namespace.$key", $value !== null ? trim($value) : null);
        }

        return redirect()
            ->route('admin.settings.analytics.edit')
                ->with('success', 'Tracking configuration updated successfully.');
    }
}
