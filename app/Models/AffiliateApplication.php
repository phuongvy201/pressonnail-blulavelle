<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateApplication extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'phone',
        'primary_platform',
        'follower_range',
        'follower_count',
        'content_niche',
        'proposed_ref_code',
        'social_links',
        'portfolio_links',
        'message',
        'status',
        'admin_note',
        'processed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'follower_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
