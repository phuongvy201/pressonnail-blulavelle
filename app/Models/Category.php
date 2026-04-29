<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'image',
        'featured',
        'sort_order'
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ProductTemplate::class);
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            ProductTemplate::class,
            'category_id',
            'template_id',
            'id',
            'id'
        );
    }

    public function crossSellTargets(): HasMany
    {
        return $this->hasMany(CategoryCrossSell::class, 'source_category_id')->orderBy('priority');
    }

    public function crossSellSources(): HasMany
    {
        return $this->hasMany(CategoryCrossSell::class, 'target_category_id');
    }
}
