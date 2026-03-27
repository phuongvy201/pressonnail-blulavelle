<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'customer_name',
        'customer_email',
        'rating',
        'review_text',
        'image_url',
        'title',
        'is_verified_purchase',
        'is_approved',
        'show_on_home',
    ];

    protected $casts = [
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'show_on_home' => 'boolean',
        'rating' => 'integer',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /** Reviews có ảnh để hiển thị trên trang chủ (testimonials) */
    public function scopeWithImage($query)
    {
        return $query->whereNotNull('image_url')->where('image_url', '!=', '');
    }

    /** Reviews được ghim hiển thị trên trang chủ */
    public function scopePinnedToHome($query)
    {
        return $query->where('show_on_home', true);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return $this->user ? $this->user->name : $this->customer_name;
    }

    // Helper methods
    public function isFromUser(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Resolve image URL for frontend display.
     * Supports absolute URLs and relative storage paths.
     */
    public function getImageUrlForDisplayAttribute(): ?string
    {
        $value = is_string($this->image_url) ? trim($this->image_url) : '';
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, 'data:')) {
            return $value;
        }

        if (str_starts_with($value, '/storage/')) {
            return asset(ltrim($value, '/'));
        }

        if (str_starts_with($value, 'storage/')) {
            return asset($value);
        }

        return asset('storage/' . ltrim($value, '/'));
    }
}
