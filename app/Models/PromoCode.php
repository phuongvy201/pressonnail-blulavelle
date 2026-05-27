<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_value',
        'max_uses',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'affiliate_id',
        'send_on_trigger',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'affiliate_id' => 'integer',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Generate a unique promo code (uppercase, avoids ambiguous chars 0/O, 1/I).
     */
    public static function generateUniqueCode(int $length = 8, int $maxAttempts = 25): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $charsMax = strlen($chars) - 1;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $suffix = '';
            for ($i = 0; $i < $length; $i++) {
                $suffix .= $chars[random_int(0, $charsMax)];
            }
            $code = 'PON-' . $suffix;

            if (!static::whereRaw('UPPER(TRIM(code)) = ?', [strtoupper($code)])->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Could not generate a unique promo code.');
    }

    /**
     * Whether the code is valid (active, within date range, under max uses).
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->starts_at && Carbon::now()->lt($this->starts_at)) {
            return false;
        }
        if ($this->expires_at && Carbon::now()->gt($this->expires_at)) {
            return false;
        }
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }
        return true;
    }

    /**
     * Whether the code can be applied for the given cart subtotal (USD).
     */
    public function isValidForSubtotal(float $subtotalUsd): bool
    {
        if (!$this->isValid()) {
            return false;
        }
        if ($this->min_order_value !== null && $subtotalUsd < (float) $this->min_order_value) {
            return false;
        }
        return true;
    }

    /**
     * Discount amount in USD for a cart subtotal in USD.
     */
    public function calculateDiscountUsd(float $subtotalUsd): float
    {
        if (!$this->isValidForSubtotal($subtotalUsd)) {
            return 0.0;
        }
        $value = (float) $this->value;
        if ($this->type === 'percentage') {
            return round($subtotalUsd * ($value / 100), 2);
        }
        return min($value, $subtotalUsd);
    }
}
