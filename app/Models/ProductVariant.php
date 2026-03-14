<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'template_id',
        'product_id',
        'variant_name',
        'attributes',
        'sku',
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
        return $this->belongsTo(ProductTemplate::class, 'template_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Helper methods
    public function getFinalPrice(): float
    {
        // Use variant price if set, otherwise use product price, otherwise use template price
        return $this->price ?? $this->product->price ?? $this->template->base_price;
    }
}
