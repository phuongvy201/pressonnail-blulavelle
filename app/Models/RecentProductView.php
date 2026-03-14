<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecentProductView extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'product_id', 'viewed_at'];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    const MAX_PER_USER = 20;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Ghi nhận user vừa xem product. Giữ tối đa MAX_PER_USER bản ghi mới nhất.
     */
    public static function recordView(int $userId, int $productId): void
    {
        static::updateOrInsert(
            ['user_id' => $userId, 'product_id' => $productId],
            ['viewed_at' => now()]
        );

        $orderIds = static::where('user_id', $userId)
            ->orderByDesc('viewed_at')
            ->skip(self::MAX_PER_USER)
            ->take(500)
            ->pluck('id');
        if ($orderIds->isNotEmpty()) {
            static::whereIn('id', $orderIds)->delete();
        }
    }

    /**
     * Lấy danh sách product_id đã xem gần nhất (mới nhất trước), loại trừ productId nếu cần.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function getRecentProductIds(int $userId, int $limit = 10, ?int $excludeProductId = null): \Illuminate\Support\Collection
    {
        $ids = static::where('user_id', $userId)
            ->orderByDesc('viewed_at')
            ->limit($excludeProductId ? $limit + 20 : $limit)
            ->pluck('product_id');

        if ($excludeProductId !== null) {
            $ids = $ids->filter(fn ($id) => (int) $id !== $excludeProductId)->take($limit)->values();
        }

        return $ids->take($limit)->values();
    }
}
