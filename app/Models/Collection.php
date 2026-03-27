<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_id',
        'name',
        'slug',
        'description',
        'image',
        'type',
        'auto_rules',
        'status',
        'sort_order',
        'featured',
        'admin_approved',
        'admin_notes',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'auto_rules' => 'array',
        'featured' => 'boolean',
        'admin_approved' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the user who created this collection
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shop this collection belongs to
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get products in this collection
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_collection')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get active products in this collection
     */
    public function activeProducts(): BelongsToMany
    {
        return $this->products()->where('status', 'active');
    }

    /**
     * Get products count
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Get active products count
     */
    public function getActiveProductsCountAttribute(): int
    {
        return $this->activeProducts()->count();
    }

    /**
     * Generate slug from name
     */
    public static function generateSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->when($excludeId, function ($query, $id) {
            return $query->where('id', '!=', $id);
        })->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Scope for active collections
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for featured collections
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope for manual collections
     */
    public function scopeManual($query)
    {
        return $query->where('type', 'manual');
    }

    /**
     * Scope for automatic collections
     */
    public function scopeAutomatic($query)
    {
        return $query->where('type', 'automatic');
    }

    /**
     * Scope for user's collections
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for shop's collections
     */
    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope for admin approved collections
     */
    public function scopeApproved($query)
    {
        return $query->where('admin_approved', true);
    }

    /**
     * Scope for pending approval collections
     */
    public function scopePending($query)
    {
        return $query->where('admin_approved', false);
    }

    /**
     * Check if collection can be edited by user
     */
    public function canEdit($user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        // Admin có toàn quyền.
        if ($user->hasRole('admin')) {
            return true;
        }

        // Seller được quyền chỉnh sửa collection để thêm/sắp xếp sản phẩm của mình.
        if ($user->hasRole('seller')) {
            return true;
        }

        // Chủ sở hữu collection.
        return $this->user_id === $user->id;
    }

    /**
     * Delete permission should remain strict to avoid accidental shared data loss.
     */
    public function canDelete($user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin') || $this->user_id === $user->id;
    }

    /**
     * Check if collection is automatic
     */
    public function isAutomatic(): bool
    {
        return $this->type === 'automatic';
    }

    /**
     * Check if collection is manual
     */
    public function isManual(): bool
    {
        return $this->type === 'manual';
    }

    /**
     * Check if collection is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if collection is featured
     */
    public function isFeatured(): bool
    {
        return $this->featured === true;
    }

    /**
     * Check if collection is admin approved
     */
    public function isApproved(): bool
    {
        return $this->admin_approved === true;
    }

    /**
     * Check if collection is pending approval
     */
    public function isPending(): bool
    {
        return $this->admin_approved === false;
    }
}
