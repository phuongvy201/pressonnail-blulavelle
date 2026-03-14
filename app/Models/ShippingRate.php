<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = [
        'shipping_zone_id',
        'category_id',
        'name',
        'description',
        'delivery_min_days',
        'delivery_max_days',
        'delivery_note',
        'first_item_cost',
        'additional_item_cost',
        'min_items',
        'max_items',
        'min_order_value',
        'max_order_value',
        'max_weight',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'first_item_cost' => 'decimal:2',
        'additional_item_cost' => 'decimal:2',
        'min_items' => 'integer',
        'max_items' => 'integer',
        'min_order_value' => 'decimal:2',
        'max_order_value' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'delivery_min_days' => 'integer',
        'delivery_max_days' => 'integer',
    ];

    /**
     * Get the shipping zone for this rate
     */
    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    /**
     * Get the category for this rate (if applicable)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Calculate shipping cost for given number of items
     * 
     * @param int $itemCount Number of items in order
     * @return float Total shipping cost
     */
    public function calculateCost(int $itemCount): float
    {
        if ($itemCount <= 0) {
            return 0;
        }

        // First item cost already includes all fees (shipping + label)
        $cost = $this->first_item_cost;

        // Additional items only pay additional shipping cost
        if ($itemCount > 1) {
            $cost += ($itemCount - 1) * $this->additional_item_cost;
        }

        return $cost;
    }

    /**
     * Check if this rate is applicable for the given conditions
     * 
     * @param int $itemCount
     * @param float $orderValue
     * @return bool
     */
    public function isApplicable(int $itemCount, float $orderValue = 0): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check item count constraints
        if ($this->min_items !== null && $itemCount < $this->min_items) {
            return false;
        }

        if ($this->max_items !== null && $itemCount > $this->max_items) {
            return false;
        }

        // Check order value constraints
        if ($this->min_order_value !== null && $orderValue < $this->min_order_value) {
            return false;
        }

        if ($this->max_order_value !== null && $orderValue > $this->max_order_value) {
            return false;
        }

        return true;
    }

    /**
     * Scope query to only include active rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to filter by shipping zone
     */
    public function scopeForZone($query, int $zoneId)
    {
        return $query->where('shipping_zone_id', $zoneId);
    }

    /**
     * Scope query to filter by category
     */
    public function scopeForCategory($query, ?int $categoryId)
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId)
                ->orWhereNull('category_id'); // Include general rates
        });
    }

    /**
     * Scope query to order by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_default', 'desc')->orderBy('sort_order')->orderBy('first_item_cost');
    }

    /**
     * Scope query to only include default rates
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get default shipping rate (optionally by zone and category)
     *
     * @param int|null $zoneId Optional zone ID to filter by
     * @param int|null $categoryId Optional category ID to filter by
     * @return self|null Default rate, or null if not found
     */
    public static function getDefaultRate(?int $zoneId = null, ?int $categoryId = null): ?self
    {
        $query = self::where('is_active', true);

        if ($zoneId) {
            $query->where('shipping_zone_id', $zoneId);
        }

        if ($categoryId !== null) {
            $query->forCategory($categoryId);
        }

        $defaultRate = (clone $query)->where('is_default', true)->ordered()->first();
        if ($defaultRate) {
            return $defaultRate;
        }

        return $query->ordered()->first();
    }

    /**
     * Get all active shipping rates (optionally by zone and category)
     *
     * @param int|null $zoneId Optional zone ID to filter by
     * @param int|null $categoryId Optional category ID to filter by
     * @return \Illuminate\Support\Collection
     */
    public static function getRates(?int $zoneId = null, ?int $categoryId = null): \Illuminate\Support\Collection
    {
        $query = self::where('is_active', true);

        if ($zoneId) {
            $query->where('shipping_zone_id', $zoneId);
        }

        if ($categoryId !== null) {
            $query->forCategory($categoryId);
        }

        $rates = $query->ordered()->get();
        return $rates->sortBy(fn ($rate) => $rate->is_default ? 0 : 1)->values();
    }

    /**
     * Set this rate as default (unsets other defaults for same zone and category)
     *
     * @return bool
     */
    public function setAsDefault(): bool
    {
        $query = self::where('id', '!=', $this->id);

        if ($this->shipping_zone_id) {
            $query->where('shipping_zone_id', $this->shipping_zone_id);
        }

        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        } else {
            $query->whereNull('category_id');
        }

        $query->update(['is_default' => false]);

        $this->is_default = true;
        return $this->save();
    }

    /**
     * Unset this rate as default
     * 
     * @return bool
     */
    public function unsetAsDefault(): bool
    {
        $this->is_default = false;
        return $this->save();
    }

    /**
     * Get shipping zones that have rates for a specific category
     *
     * @param int|null $categoryId Category ID (null returns empty collection)
     * @return \Illuminate\Support\Collection Collection of ShippingZone models
     */
    public static function getZonesForCategory(?int $categoryId): \Illuminate\Support\Collection
    {
        if ($categoryId === null) {
            return collect();
        }

        $zoneIds = self::active()
            ->where('category_id', $categoryId)
            ->distinct()
            ->pluck('shipping_zone_id');

        if ($zoneIds->isEmpty()) {
            return collect();
        }

        return \App\Models\ShippingZone::whereIn('id', $zoneIds)
            ->active()
            ->ordered()
            ->get();
    }
}
