<?php

return [
    /**
     * Full hostname of the creator portal (no scheme).
     * Example: creator.pressonnail.test or creator.example.com
     */
    'domain' => (static function (): string {
        $d = trim((string) env('CREATOR_DOMAIN', 'creator.pressonnail.test'));

        return $d !== '' ? $d : 'creator.pressonnail.test';
    })(),

    /**
     * Display name in the portal header (branding).
     */
    'portal_name' => env('CREATOR_PORTAL_NAME', 'Creator'),

    /**
     * Main storefront URL (with scheme) — used for the “Shop” button.
     */
    'shop_url' => (static function (): string {
        $s = trim((string) env('CREATOR_SHOP_URL', ''));
        if ($s !== '') {
            return $s;
        }

        return rtrim((string) env('APP_URL', 'http://localhost'), '/');
    })(),

    /**
     * CMS page slugs shown in the creator portal Legal footer and /policies/{slug}.
     * Manage content in Admin → Pages (do not change slugs without updating this list).
     */
    'affiliate_policy_slugs' => [
        'affiliate-program-terms',
        'affiliate-privacy-policy',
        'affiliate-commission-payout-policy',
        'affiliate-attribution-cookie-policy',
    ],

    /** Primary platform options (apply + post-approval profile). */
    'platforms' => [
        'tiktok' => 'TikTok',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'facebook' => 'Facebook',
        'pinterest' => 'Pinterest',
        'other' => 'Other',
    ],

    'follower_ranges' => [
        'under_1k' => 'Under 1K',
        '1k_10k' => '1K – 10K',
        '10k_50k' => '10K – 50K',
        '50k_100k' => '50K – 100K',
        '100k_500k' => '100K – 500K',
        '500k_plus' => '500K+',
    ],

    /** Payout methods available after approval. */
    'payout_methods' => [
        'paypal' => 'PayPal',
        'bank_transfer' => 'Bank transfer (US)',
    ],
];
