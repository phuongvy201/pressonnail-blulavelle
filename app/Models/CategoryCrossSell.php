<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryCrossSell extends Model
{
    protected $fillable = [
        'source_category_id',
        'target_category_id',
        'priority',
    ];

    protected $casts = [
        'source_category_id' => 'integer',
        'target_category_id' => 'integer',
        'priority' => 'integer',
    ];

    public function sourceCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'source_category_id');
    }

    public function targetCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'target_category_id');
    }
}
