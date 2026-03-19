<?php

namespace App\Models;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    /**
     * Free shipping threshold in USD (applies after combo discount).
     */
    public const FREE_SHIPPING_THRESHOLD_USD = 150.0;

    protected $fillable = [
        'session_id',
        'user_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
        'selected_variant',
        'customizations'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'selected_variant' => 'array',
        'customizations' => 'array'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // Helper methods
    public function getTotalPrice(): float
    {
        // price = đơn giá đã gồm variant + customization (frontend gửi lên)
        return (float) $this->price * $this->quantity;
    }

    /**
     * Tổng tiền dòng (đơn giá × số lượng).
     * Đơn giá trong DB đã bao gồm customization, không cộng thêm.
     */
    public function getTotalPriceWithCustomizations(): float
    {
        return (float) $this->price * $this->quantity;
    }

    /**
     * Discount percent based on combo quantity tiers (admin-configurable).
     * Applies based on TOTAL quantity in cart (combo), not per-line.
     *
     * @param int $totalQty Total quantity in cart (sum of line quantities)
     */
    public static function getComboDiscountPercentForQty(int $totalQty): float
    {
        $raw = Settings::get('pricing.bulk_discounts');
        $rules = [];

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $rules = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $rules = $raw;
        }

        $qty = (int) $totalQty;
        if ($qty < 1 || empty($rules)) {
            return 0.0;
        }

        $best = 0.0;
        foreach ($rules as $rule) {
            $minQty = (int) ($rule['min_qty'] ?? 0);
            $percent = (float) ($rule['percent'] ?? 0);
            if ($minQty > 0 && $qty >= $minQty) {
                $best = max($best, $percent);
            }
        }

        return max(0.0, min(95.0, $best));
    }

    // NOTE: Per-line bulk discount helpers removed in favor of combo discount at cart level.

    /**
     * Đơn giá 1 sản phẩm (đã gồm customization).
     */
    public function getUnitPriceWithCustomizations(): float
    {
        return (float) $this->price;
    }

    public function getDisplayName(): string
    {
        $name = $this->product->name;

        if ($this->selected_variant) {
            $attributes = [];
            foreach ($this->selected_variant as $key => $value) {
                $attributes[] = $value;
            }
            if (!empty($attributes)) {
                $name .= ' (' . implode(', ', $attributes) . ')';
            }
        }

        return $name;
    }
}
