<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CheckoutAttempt extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'idempotency_key',
        'request_hash',
        'status',
        'user_id',
        'guest_token',
        'order_id',
        'payment_intent_id',
        'request_snapshot',
        'response_body',
        'error_code',
        'locked_until',
        'expires_at',
    ];

    protected $casts = [
        'request_snapshot' => 'array',
        'response_body' => 'array',
        'locked_until' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $attempt) {
            if (! $attempt->id) {
                $attempt->id = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
