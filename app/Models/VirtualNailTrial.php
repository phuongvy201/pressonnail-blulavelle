<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VirtualNailTrial extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid',
        'user_id',
        'session_id',
        'product_id',
        'nail_shape',
        'nail_length',
        'hand_image_path',
        'result_image_path',
        'status',
        'provider',
        'error_message',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $trial) {
            if ($trial->uuid === null || $trial->uuid === '') {
                $trial->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isOwnedBy(?int $userId, ?string $sessionId): bool
    {
        if ($userId !== null && (int) $this->user_id === $userId) {
            return true;
        }

        return $sessionId !== null
            && $this->session_id !== null
            && hash_equals((string) $this->session_id, $sessionId);
    }

    public function markProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markCompleted(string $resultPath, ?string $provider): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'result_image_path' => $resultPath,
            'provider' => $provider,
            'error_message' => null,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => Str::limit($message, 500),
            'completed_at' => now(),
        ]);
    }
}
