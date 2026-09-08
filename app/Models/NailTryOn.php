<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NailTryOn extends Model
{
    use SoftDeletes;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_VALIDATING_IMAGE = 'validating_image';

    public const STATUS_DETECTING_HAND = 'detecting_hand';

    public const STATUS_RENDERING = 'rendering';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_DELETED = 'deleted';

    public const TERMINAL = [
        self::STATUS_SUCCEEDED,
        self::STATUS_FAILED,
        self::STATUS_EXPIRED,
        self::STATUS_DELETED,
    ];

    protected $fillable = [
        'public_id',
        'user_id',
        'guest_token',
        'hand_asset_id',
        'output_asset_id',
        'parent_try_on_id',
        'product_id',
        'variant_id',
        'try_on_asset_version',
        'hand_side',
        'nail_shape',
        'nail_length',
        'status',
        'error_code',
        'error_message',
        'provider',
        'idempotency_key',
        'request_hash',
        'expires_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'variant_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $job) {
            if (! $job->public_id) {
                $job->public_id = 'tryon_'.Str::lower((string) Str::ulid());
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

    public function handAsset(): BelongsTo
    {
        return $this->belongsTo(ApiUploadAsset::class, 'hand_asset_id');
    }

    public function outputAsset(): BelongsTo
    {
        return $this->belongsTo(ApiUploadAsset::class, 'output_asset_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_try_on_id');
    }

    public function isOwnedBy(?int $userId, ?string $guestToken): bool
    {
        if ($userId !== null && (int) $this->user_id === $userId) {
            return true;
        }

        return $guestToken !== null
            && $this->guest_token !== null
            && hash_equals((string) $this->guest_token, $guestToken);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL, true);
    }
}
