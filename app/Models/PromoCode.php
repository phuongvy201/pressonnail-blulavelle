<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    ];

    /**
     * Kiểm tra code có hợp lệ không (trạng thái, thời hạn, số lần dùng).
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
     * Kiểm tra có áp dụng được cho đơn hàng (subtotal USD) không.
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
     * Tính số tiền giảm (trả về USD).
     * $subtotalUsd = subtotal của giỏ hàng tính bằng USD.
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
