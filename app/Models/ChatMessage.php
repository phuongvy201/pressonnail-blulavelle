<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    public const SENDER_CUSTOMER = 'customer';
    public const SENDER_BOT = 'bot';
    public const SENDER_ADMIN = 'admin';
    public const SENDER_SYSTEM = 'system';

    protected $fillable = [
        'conversation_id',
        'is_from_customer',
        'sender_type',
        'body',
        'metadata',
        'intent',
        'confidence',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'is_from_customer' => 'boolean',
        'metadata' => 'array',
        'confidence' => 'decimal:4',
    ];

    public function senderType(): string
    {
        if (filled($this->sender_type)) {
            return (string) $this->sender_type;
        }

        return $this->is_from_customer ? self::SENDER_CUSTOMER : self::SENDER_ADMIN;
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }
}
