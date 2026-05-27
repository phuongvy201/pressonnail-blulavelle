<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    protected $fillable = [
        'affiliate_id',
        'order_id',
        'commission_base',
        'commission_rate',
        'commission_amount',
        'original_commission_base',
        'original_commission_amount',
        'currency',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'commission_base' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'original_commission_base' => 'decimal:2',
        'original_commission_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
