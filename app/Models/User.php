<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    // Relationships
    public function shop(): HasOne
    {
        return $this->hasOne(Shop::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function affiliate(): HasOne
    {
        return $this->hasOne(Affiliate::class);
    }

    public function affiliateApplications(): HasMany
    {
        return $this->hasMany(AffiliateApplication::class);
    }

    public function latestAffiliateApplication(): HasOne
    {
        return $this->hasOne(AffiliateApplication::class)->latestOfMany();
    }

    public function hasActiveAffiliate(): bool
    {
        return $this->affiliate()->where('is_active', true)->exists();
    }

    public function hasPendingAffiliateApplication(): bool
    {
        return $this->affiliateApplications()
            ->where('status', AffiliateApplication::STATUS_PENDING)
            ->exists();
    }

    /**
     * Application to show on creator status page (pending first, then latest).
     */
    public function affiliateApplicationForStatus(): ?AffiliateApplication
    {
        $pending = $this->affiliateApplications()
            ->where('status', AffiliateApplication::STATUS_PENDING)
            ->latest('id')
            ->first();

        if ($pending) {
            return $pending;
        }

        $latest = $this->affiliateApplications()->latest('id')->first();

        if ($latest) {
            return $latest;
        }

        return AffiliateApplication::query()
            ->where('status', AffiliateApplication::STATUS_PENDING)
            ->whereRaw('LOWER(email) = ?', [strtolower($this->email)])
            ->where(function ($query) {
                $query->whereNull('user_id')->orWhere('user_id', $this->id);
            })
            ->latest('id')
            ->first();
    }

    /** Creator dashboard & affiliate tools (approved + active profile). */
    public function canAccessCreatorAffiliateFeatures(): bool
    {
        return $this->hasActiveAffiliate();
    }

    // Helper methods
    public function hasShop(): bool
    {
        return $this->shop()->exists();
    }

    public function getShop()
    {
        return $this->shop;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'roles',
        'phone',
        'avatar',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'google_id',
        'facebook_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Sync roles when saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($user) {
            if ($user->isDirty('roles')) {
                $user->syncRoles($user->roles ?? []);
            }
        });
    }
}
