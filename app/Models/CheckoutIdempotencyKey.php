<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutIdempotencyKey extends Model
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'key',
        'request_hash',
        'status',
        'http_status',
        'response_body',
        'order_id',
        'user_id',
        'session_id',
        'locked_until',
        'expires_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'locked_until' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isLockActive(): bool
    {
        return $this->status === self::STATUS_PROCESSING
            && $this->locked_until !== null
            && $this->locked_until->isFuture();
    }
}
