<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiUploadAsset extends Model
{
    use SoftDeletes;

    public const PURPOSE_VIRTUAL_TRY_ON_HAND = 'virtual_try_on_hand';

    public const PURPOSE_VIRTUAL_TRY_ON_OUTPUT = 'virtual_try_on_output';

    /** Pre-uploaded reference hand photos that customers can try designs on when they have no hand photo of their own. */
    public const PURPOSE_VIRTUAL_TRY_ON_SAMPLE_HAND = 'virtual_try_on_sample_hand';

    /** Purposes that any authenticated or guest caller may reference — public assets with no owner. */
    public const PUBLIC_PURPOSES = [
        self::PURPOSE_VIRTUAL_TRY_ON_SAMPLE_HAND,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_UPLOADING = 'uploading';

    public const STATUS_READY = 'ready';

    public const STATUS_DELETED = 'deleted';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'public_id',
        'user_id',
        'guest_token',
        'purpose',
        'mime_type',
        'expected_size',
        'checksum_sha256',
        'disk',
        'path',
        'width',
        'height',
        'byte_size',
        'status',
        'upload_token',
        'upload_expires_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'upload_expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'expected_size' => 'integer',
            'byte_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $asset) {
            if (! $asset->public_id) {
                $prefix = match ($asset->purpose) {
                    self::PURPOSE_VIRTUAL_TRY_ON_OUTPUT => 'asset_out_',
                    self::PURPOSE_VIRTUAL_TRY_ON_SAMPLE_HAND => 'asset_sample_',
                    default => 'asset_hand_',
                };
                $asset->public_id = $prefix.Str::lower((string) Str::ulid());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY && $this->path && ! $this->trashed();
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->upload_expires_at && $this->status === self::STATUS_PENDING && $this->upload_expires_at->isPast());
    }
}
