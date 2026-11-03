<?php

namespace App\Models;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

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
     * One cart-wide rate from the summed quantity of every eligible line.
     * Different products and variants add together. Tiers are not cumulative.
     *
     * @param int $totalQty Eligible quantity across the cart, not one product
     */
    public static function getComboDiscountPercentForQty(int $totalQty): float
    {
        $rules = self::getComboDiscountRules();

        $qty = (int) $totalQty;
        if ($qty < 1 || empty($rules)) {
            return 0.0;
        }

        $best = 0.0;
        foreach ($rules as $rule) {
            if ($qty >= $rule['min_qty']) {
                $best = max($best, $rule['percent']);
            }
        }

        return max(0.0, min(95.0, $best));
    }

    /** @return array<int, array{min_qty: int, percent: float}> */
    public static function getComboDiscountRules(): array
    {
        $raw = Settings::get('pricing.bulk_discounts');
        $rules = [];

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $rules = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $rules = $raw;
        }

        $normalized = [];
        foreach ($rules as $rule) {
            $minQty = (int) ($rule['min_qty'] ?? 0);
            $percent = (float) ($rule['percent'] ?? 0);
            if ($minQty > 0 && $percent > 0) {
                $normalized[$minQty] = max($normalized[$minQty] ?? 0, min(95.0, $percent));
            }
        }

        ksort($normalized);
        $result = [];
        foreach ($normalized as $minQty => $percent) {
            $result[] = ['min_qty' => (int) $minQty, 'percent' => (float) $percent];
        }

        return $result !== [] ? $result : self::defaultVolumeDiscountTiers();
    }

    /** @return array<int, array{min_qty: int, percent: float}> */
    public static function defaultVolumeDiscountTiers(): array
    {
        return [
            ['min_qty' => 2, 'percent' => 15.0],
            ['min_qty' => 3, 'percent' => 17.0],
            ['min_qty' => 5, 'percent' => 18.0],
        ];
    }

    /**
     * Product ids an admin excluded from the global quantity promotion.
     * Gift cards are always ineligible and do not need to be listed here.
     *
     * @return array<int, int>
     */
    public static function volumeDiscountExcludedProductIds(): array
    {
        $raw = Settings::get('pricing.volume_discount_excluded_product_ids');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $raw);
        }
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    public static function productIsVolumeEligible(?Product $product): bool
    {
        if (! $product || (bool) $product->is_gift_card) {
            return false;
        }

        return ! in_array((int) $product->id, self::volumeDiscountExcludedProductIds(), true);
    }

    public static function lineIsVolumeEligible(self $item): bool
    {
        return self::productIsVolumeEligible($item->product);
    }

    public static function eligibleQuantity(iterable $items): int
    {
        $quantity = 0;
        foreach ($items as $item) {
            if ($item instanceof self && self::lineIsVolumeEligible($item)) {
                $quantity += (int) $item->quantity;
            }
        }

        return $quantity;
    }

    public static function eligibleSubtotal(iterable $items): float
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            if ($item instanceof self && self::lineIsVolumeEligible($item)) {
                $subtotal += $item->getTotalPriceWithCustomizations();
            }
        }

        return $subtotal;
    }

    /**
     * @return array{percent: float, amount: float}
     */
    public static function volumeDiscountFor(int $eligibleQuantity, float $eligibleSubtotal, string $discountMode): array
    {
        if ($discountMode !== 'volume') {
            return ['percent' => 0.0, 'amount' => 0.0];
        }

        $percent = self::getComboDiscountPercentForQty($eligibleQuantity);

        return [
            'percent' => $percent,
            'amount' => $percent > 0 ? round($eligibleSubtotal * ($percent / 100), 2) : 0.0,
        ];
    }

    /**
     * Preview on a product page. $eligibleCartQuantity already includes this
     * product when it is in the cart, so $proposedQuantity is only the extra
     * amount the shopper is about to add.
     */
    public static function projectedEligibleQuantity(int $eligibleCartQuantity, int $proposedQuantity, bool $productEligible): int
    {
        $base = max(0, $eligibleCartQuantity);
        $proposed = $productEligible ? max(0, $proposedQuantity) : 0;

        return $base + $proposed;
    }

    public static function forCurrentShopper(): Collection
    {
        $sessionId = session()->getId();
        $userId = Auth::id();

        return static::query()
            ->with('product:id,is_gift_card')
            ->where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->get();
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
