<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
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
