<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
    ];

    /**
     * Get the user that owns the wishlist item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that is in the wishlist.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get wishlist items for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get wishlist items for a specific session.
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Check if a product is in wishlist for a user.
     */
    public static function isInWishlist($productId, $userId = null, $sessionId = null)
    {
        $query = static::where('product_id', $productId);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        return $query->exists();
    }

    /**
     * Add product to wishlist.
     */
    public static function addToWishlist($productId, $userId = null, $sessionId = null)
    {
        // Check if already exists
        if (static::isInWishlist($productId, $userId, $sessionId)) {
            return false;
        }

        return static::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'product_id' => $productId,
        ]);
    }

    /**
     * Remove product from wishlist.
     */
    public static function removeFromWishlist($productId, $userId = null, $sessionId = null)
    {
        $query = static::where('product_id', $productId);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        return $query->delete();
    }

    /**
     * Get wishlist items for user or session.
     */
    public static function getWishlistItems($userId = null, $sessionId = null, $perPage = 12)
    {
        $query = static::with(['product' => function ($q) {
            $q->with(['template.category', 'shop'])
                ->withCount('approvedReviews')
                ->withAvg('approvedReviews', 'rating');
        }]);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        return $query->paginate($perPage);
    }

    /**
     * Transfer wishlist from session to user when user logs in.
     */
    public static function transferSessionToUser($sessionId, $userId)
    {
        return static::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->update(['user_id' => $userId, 'session_id' => null]);
    }
}
