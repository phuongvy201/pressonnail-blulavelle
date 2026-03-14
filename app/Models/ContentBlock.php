<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentBlock extends Model
{
    protected $fillable = ['block_key', 'content'];

    protected $casts = [
        'content' => 'array',
    ];

    /**
     * Get merged content with defaults (DB overrides default).
     */
    public static function getContent(string $blockKey, array $default = []): array
    {
        $block = static::where('block_key', $blockKey)->first();
        if (!$block || !is_array($block->content)) {
            return $default;
        }
        return array_merge($default, $block->content);
    }
}
