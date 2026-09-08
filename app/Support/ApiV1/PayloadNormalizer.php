<?php

namespace App\Support\ApiV1;

class PayloadNormalizer
{
    /**
     * Field names treated as money in major units (USD) → convert to integer minor units.
     *
     * @var list<string>
     */
    private const MONEY_KEYS = [
        'price',
        'sale_price',
        'salePrice',
        'compare_at_price',
        'compareAtPrice',
        'original_price',
        'originalPrice',
        'unit_price',
        'unitPrice',
        'line_total',
        'lineTotal',
        'subtotal',
        'total',
        'tax',
        'tax_amount',
        'taxAmount',
        'shipping',
        'shipping_amount',
        'shippingAmount',
        'shipping_cost',
        'shippingCost',
        'discount',
        'discount_amount',
        'discountAmount',
        'promo_discount',
        'promoDiscount',
        'gift_card_amount',
        'giftCardAmount',
        'gift_card_balance',
        'giftCardBalance',
        'amount',
        'balance',
        'converted_subtotal',
        'convertedSubtotal',
        'converted_total',
        'convertedTotal',
        'converted_tax',
        'convertedTax',
        'converted_shipping',
        'convertedShipping',
        'converted_discount',
        'convertedDiscount',
        'grand_total',
        'grandTotal',
        'paid_amount',
        'paidAmount',
        'refund_amount',
        'refundAmount',
    ];

    public static function moneyToMinor(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $out[$key] = self::moneyToMinor($item);
                continue;
            }

            if (is_string($key) && self::isMoneyKey($key) && self::isNumericMoney($item)) {
                $out[$key] = ApiResponse::toMinor($item);
                continue;
            }

            $out[$key] = $item;
        }

        return $out;
    }

    private static function isMoneyKey(string $key): bool
    {
        if (in_array($key, self::MONEY_KEYS, true)) {
            return true;
        }

        // converted_* money fields from multi-currency cart
        if (str_starts_with($key, 'converted_') || str_starts_with($key, 'converted')) {
            $tail = preg_replace('/^converted_?/i', '', $key) ?? '';

            return $tail !== '' && self::isMoneyKey(lcfirst($tail));
        }

        return false;
    }

    private static function isNumericMoney(mixed $value): bool
    {
        return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value));
    }
}
