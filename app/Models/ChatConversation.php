<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    public const HANDLING_BOT = 'bot';
    public const HANDLING_HUMAN = 'human';

    protected $fillable = [
        'customer_user_id',
        'guest_email',
        'guest_name',
        'guest_session_id',
        'seller_id',
        'status',
        'handling_mode',
        'bot_enabled',
        'handed_off_at',
        'last_bot_intent',
        'bot_processing_at',
        'customer_last_seen_message_id',
        'customer_last_seen_at',
        'reply_email_notified_message_id',
        'reply_email_sent_at',
    ];

    protected $casts = [
        'bot_enabled' => 'boolean',
        'handed_off_at' => 'datetime',
        'bot_processing_at' => 'datetime',
        'customer_last_seen_message_id' => 'integer',
        'customer_last_seen_at' => 'datetime',
        'reply_email_notified_message_id' => 'integer',
        'reply_email_sent_at' => 'datetime',
    ];

    public function isBotHandled(): bool
    {
        return (bool) ($this->bot_enabled ?? true)
            && ($this->handling_mode ?? self::HANDLING_BOT) === self::HANDLING_BOT;
    }

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
