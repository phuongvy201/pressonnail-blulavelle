<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateClickEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'affiliate_id',
        'ref_code',
        'landing_path',
        'product_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'referrer_host',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
