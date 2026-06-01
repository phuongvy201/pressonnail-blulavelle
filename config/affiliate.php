<?php

return [
    /*
    |--------------------------------------------------------------------------
    | URL query parameter used for affiliate / commission (last-click cookie).
    | UTM parameters are stored for analytics only, not used to identify affiliates.
    |--------------------------------------------------------------------------
    */
    'ref_query_param' => env('AFFILIATE_REF_PARAM', 'ref'),

    'cookie_name' => env('AFFILIATE_COOKIE_NAME', 'pon_affiliate_ref'),

    /**
     * Cookie domain for the ref cookie (last-click).
     * Should match SESSION_DOMAIN (e.g. .example.test) so attribution works when
     * the user lands on creator.* with ?ref= and checks out on the apex domain.
     * Empty / null = host-only cookie (typical for localhost).
     */
    'cookie_domain' => env('AFFILIATE_COOKIE_DOMAIN') ?: \App\Support\SharedCookieDomain::resolve(),

    /** Last-click cookie lifetime in minutes (default 14 days). */
    'cookie_ttl_minutes' => (int) env('AFFILIATE_COOKIE_TTL_MINUTES', 14 * 24 * 60),

    /**
     * Commission only for affiliate-acquired customers: first paid order on the site must be
     * attributed to an affiliate; additional paid orders within the cookie window (14 days
     * from that first paid order) also qualify. Prior paid orders without affiliate = no commission.
     */
    'commission_new_customers_only' => (bool) env('AFFILIATE_COMMISSION_NEW_CUSTOMERS_ONLY', true),

    /** Session key for the latest UTM snapshot (marketing / analytics). */
    'utm_session_key' => 'affiliate_utm_last',

    /** Default tier for new affiliates. */
    'default_tier' => 'basic',

    /**
     * Commission % per tier (when commission_rate_override is null).
     * basic / silver / gold / diamond → 7% / 10% / 12% / 15%.
     */
    'tier_rates' => [
        'basic' => 7.0,
        'silver' => 10.0,
        'gold' => 12.0,
        'diamond' => 15.0,
    ],

    /** Rolling window (days) to count attributed paid orders for tier — refreshed monthly via cron. */
    'tier_evaluation_days' => (int) env('AFFILIATE_TIER_EVALUATION_DAYS', 30),

    /** No attributed paid order in this many days → downgrade one tier. */
    'tier_inactivity_days' => (int) env('AFFILIATE_TIER_INACTIVITY_DAYS', 60),

    /** Min attributed paid orders in evaluation window to reach tier. */
    'tier_order_thresholds' => [
        'silver' => (int) env('AFFILIATE_TIER_SILVER_ORDERS', 20),
        'gold' => (int) env('AFFILIATE_TIER_GOLD_ORDERS', 50),
        'diamond' => (int) env('AFFILIATE_TIER_DIAMOND_ORDERS', 100),
    ],

    'utm_params' => [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ],

    /**
     * When the link has no UTM (common on Instagram bio / in-app browser), infer utm_source
     * from HTTP Referer or User-Agent (e.g. Instagram in-app → utm_source=instagram).
     */
    'infer_utm_when_missing' => (bool) env('AFFILIATE_INFER_UTM_WHEN_MISSING', true),

    /**
     * Sample request quota per tier (rolling window). Rejected requests do not count.
     */
    'sample_quotas' => [
        'basic' => [
            'period_days' => (int) env('AFFILIATE_SAMPLE_QUOTA_DAYS_BASIC', 30),
            'max_requests' => (int) env('AFFILIATE_SAMPLE_QUOTA_MAX_BASIC', 1),
        ],
        'silver' => [
            'period_days' => (int) env('AFFILIATE_SAMPLE_QUOTA_DAYS_SILVER', 30),
            'max_requests' => (int) env('AFFILIATE_SAMPLE_QUOTA_MAX_SILVER', 2),
        ],
        'gold' => [
            'period_days' => (int) env('AFFILIATE_SAMPLE_QUOTA_DAYS_GOLD', 30),
            'max_requests' => (int) env('AFFILIATE_SAMPLE_QUOTA_MAX_GOLD', 3),
        ],
        'diamond' => [
            'period_days' => (int) env('AFFILIATE_SAMPLE_QUOTA_DAYS_DIAMOND', 30),
            'max_requests' => (int) env('AFFILIATE_SAMPLE_QUOTA_MAX_DIAMOND', 4),
        ],
    ],

    /** Max units per single sample request. */
    'sample_max_quantity_per_request' => (int) env('AFFILIATE_SAMPLE_MAX_QTY', 1),

    /**
     * Days after successful delivery before commission balance is eligible for payout.
     */
    'payout_delay_days_after_delivery' => (int) env('AFFILIATE_PAYOUT_DELAY_DAYS_AFTER_DELIVERY', 14),

    /**
     * Email nhận thông báo khi có đơn đăng ký affiliate / KOC từ creator portal.
     * Mặc định: MAIL_FROM_ADDRESS nếu AFFILIATE_ADMIN_NOTIFICATION_EMAIL không set.
     */
    'admin_notification_email' => (static function (): ?string {
        $explicit = trim((string) env('AFFILIATE_ADMIN_NOTIFICATION_EMAIL', ''));
        if ($explicit !== '') {
            return $explicit;
        }
        $from = trim((string) env('MAIL_FROM_ADDRESS', ''));

        return $from !== '' ? $from : null;
    })(),
];
