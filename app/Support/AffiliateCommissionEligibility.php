<?php

namespace App\Support;

class AffiliateCommissionEligibility
{
    public const ELIGIBLE = 'eligible';

    public const INELIGIBLE = 'ineligible';

    public const REASON_RETURNING_CUSTOMER = 'returning_customer';

    public const REASON_PRIOR_NON_AFFILIATE_PURCHASE = 'prior_non_affiliate_purchase';

    public const REASON_OUTSIDE_ACQUISITION_WINDOW = 'outside_acquisition_window';

    public const REASON_SELF_PURCHASE = 'self_purchase';

    public const REASON_PAYMENT_DISPUTE = 'payment_dispute';

    public const REASON_AFFILIATE_INACTIVE = 'affiliate_inactive';

    public const REASON_NO_ELIGIBLE_PRODUCTS = 'no_eligible_products';

    public const REASON_NO_COMMISSION_BASE = 'no_commission_base';

    public const REASON_ZERO_COMMISSION = 'zero_commission';

    public static function label(?string $code): string
    {
        return match ($code) {
            self::REASON_RETURNING_CUSTOMER => 'This order is outside the new-customer commission window (only orders within 14 days of the customer\'s first paid order on the store).',
            self::REASON_PRIOR_NON_AFFILIATE_PURCHASE => 'Customer already purchased on the store before (ads, organic, or direct) — their first paid order was not through an affiliate link.',
            self::REASON_OUTSIDE_ACQUISITION_WINDOW => 'More than 14 days since this customer\'s first paid order — commission window has ended.',
            self::REASON_SELF_PURCHASE => 'This order is from your own account — self-purchases do not earn commission.',
            self::REASON_PAYMENT_DISPUTE => 'Payment dispute on this order — no commission while disputed or if lost.',
            self::REASON_AFFILIATE_INACTIVE => 'Affiliate account inactive when the order was paid.',
            self::REASON_NO_ELIGIBLE_PRODUCTS => 'No affiliate-eligible products in this order.',
            self::REASON_NO_COMMISSION_BASE => 'Order total eligible for commission is zero after discounts.',
            self::REASON_ZERO_COMMISSION => 'Calculated commission amount is zero.',
            default => 'No commission for this order.',
        };
    }
}
