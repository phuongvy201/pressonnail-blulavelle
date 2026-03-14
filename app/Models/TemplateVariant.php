<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateVariant extends Model
{
    protected $fillable = [
        'template_id',
        'variant_name',
        'attributes',
        'price',
        'list_price',
        'quantity',
        'media'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'list_price' => 'decimal:2',
        'quantity' => 'integer',
        'media' => 'array',
        'attributes' => 'array',
    ];

    // Relationships
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class);
    }

    // Helper methods
    public function getFinalPrice(): float
    {
        return $this->price ?? $this->template->base_price;
    }
}
