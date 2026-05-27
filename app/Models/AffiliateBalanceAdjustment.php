<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateBalanceAdjustment extends Model
{
    protected $fillable = [
        'affiliate_id',
        'order_id',
        'amount',
        'type',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
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
