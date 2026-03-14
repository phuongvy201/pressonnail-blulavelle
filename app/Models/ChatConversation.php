<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    protected $fillable = [
        'customer_user_id',
        'guest_email',
        'guest_name',
        'guest_session_id',
        'seller_id',
        'status',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id')->orderBy('id');
    }

    public function getCustomerNameAttribute(): string
    {
        if ($this->customer_user_id) {
            return $this->customer?->name ?? 'Customer';
        }
        return $this->guest_name ?? $this->guest_email ?? 'Guest';
    }

    public function getCustomerEmailAttribute(): ?string
    {
        if ($this->customer_user_id) {
            return $this->customer?->email;
        }
        return $this->guest_email;
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
