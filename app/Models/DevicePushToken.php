<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevicePushToken extends Model
{
    protected $fillable = [
        'user_id',
        'guest_device_key',
        'guest_cart_token',
        'customer_email',
        'expo_push_token',
        'platform',
        'app_version',
        'last_registered_at',
    ];

    protected $casts = [
        'last_registered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
