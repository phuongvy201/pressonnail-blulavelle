<?php

namespace App\Models;

use App\Models\AffiliateApplication;
use App\Support\AffiliateSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Affiliate extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'display_name',
        'phone',
        'primary_platform',
        'follower_range',
        'content_niche',
        'social_links',
        'portfolio_links',
        'payout_method',
        'payout_legal_name',
        'payout_paypal_email',
        'payout_venmo_handle',
        'payout_bank_name',
        'payout_account_holder',
        'payout_account_last4',
        'payout_routing_last4',
        'payout_routing_number',
        'payout_account_number',
        'payout_setup_completed_at',
        'tier',
        'commission_rate_override',
        'tier_locked',
        'is_active',
    ];

    protected $casts = [
        'commission_rate_override' => 'decimal:2',
        'tier_locked' => 'boolean',
        'is_active' => 'boolean',
        'payout_setup_completed_at' => 'datetime',
        'payout_routing_number' => 'encrypted',
        'payout_account_number' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function promoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function balanceAdjustments(): HasMany
    {
        return $this->hasMany(AffiliateBalanceAdjustment::class);
    }

    public function clickEvents(): HasMany
    {
        return $this->hasMany(AffiliateClickEvent::class);
    }

    public function sampleRequests(): HasMany
    {
        return $this->hasMany(AffiliateSampleRequest::class);
    }

    public function effectiveCommissionPercent(): float
    {
        if ($this->commission_rate_override !== null) {
            return (float) $this->commission_rate_override;
        }

        $rates = AffiliateSettings::tierRates();
        $tier = \App\Support\AffiliateTier::normalize($this->tier);

        return (float) ($rates[$tier] ?? $rates['basic']);
    }

    public function hasCompleteProfile(): bool
    {
        return filled($this->display_name)
            && filled($this->phone)
            && filled($this->primary_platform)
            && filled($this->content_niche);
    }

    public function hasSocialLinks(): bool
    {
        return count(self::parseLinkLines($this->social_links)) > 0;
    }

    public function hasPayoutSetup(): bool
    {
        if (! filled($this->payout_method) || ! filled($this->payout_legal_name)) {
            return false;
        }

        return match ($this->payout_method) {
            'paypal' => filled($this->payout_paypal_email),
            'bank_transfer' => filled($this->payout_bank_name)
                && filled($this->payout_account_holder)
                && filled($this->payout_routing_number)
                && filled($this->payout_account_number),
            default => false,
        };
    }

    public function canReceivePayout(): bool
    {
        return $this->hasPayoutSetup();
    }

    /**
     * @return list<string>
     */
    public static function parseLinkLines(?string $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $urls = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (filter_var($line, FILTER_VALIDATE_URL)) {
                $urls[] = $line;
            }
        }

        return $urls;
    }

    public function fillFromApplication(AffiliateApplication $application): void
    {
        $this->fill([
            'display_name' => $this->display_name ?: $application->full_name,
            'phone' => $this->phone ?: $application->phone,
            'primary_platform' => $this->primary_platform ?: $application->primary_platform,
            'follower_range' => $this->follower_range ?: $application->follower_range,
            'content_niche' => $this->content_niche ?: $application->content_niche,
            'social_links' => $this->social_links ?: $application->social_links,
            'portfolio_links' => $this->portfolio_links ?: $application->portfolio_links,
        ]);
    }

    public static function booted(): void
    {
        static::saving(function (Affiliate $affiliate): void {
            if ($affiliate->isDirty([
                'payout_method', 'payout_legal_name', 'payout_paypal_email',
                'payout_venmo_handle', 'payout_bank_name', 'payout_account_holder',
                'payout_account_last4', 'payout_routing_last4',
                'payout_routing_number', 'payout_account_number',
            ])) {
                $complete = $affiliate->hasPayoutSetup();
                $affiliate->payout_setup_completed_at = $complete ? ($affiliate->payout_setup_completed_at ?? now()) : null;
            }
        });
    }
}
