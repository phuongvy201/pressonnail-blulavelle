<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductTemplate extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'user_id',
        'base_price',
        'list_price',
        'description',
        'media',
        'allow_customization',
        'customizations'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'list_price' => 'decimal:2',
        'media' => 'array',
        'allow_customization' => 'boolean',
        'customizations' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'template_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(TemplateAttribute::class, 'template_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(TemplateVariant::class, 'template_id');
    }

    // Customization Methods
    public function hasCustomization(): bool
    {
        return $this->allow_customization && !empty($this->customizations);
    }

    public function getCustomizationTypes(): array
    {
        return $this->customizations ?? [];
    }

    public function getTotalCustomizationPrice(): float
    {
        if (!$this->hasCustomization()) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($this->customizations as $customization) {
            $total += (float) ($customization['price'] ?? 0);
        }

        return $total;
    }

    public function getCustomizationByType(string $type): array
    {
        return array_filter($this->customizations ?? [], function ($customization) use ($type) {
            return ($customization['type'] ?? '') === $type;
        });
    }

    public function getRequiredCustomizations(): array
    {
        return array_filter($this->customizations ?? [], function ($customization) {
            return (bool) ($customization['required'] ?? false);
        });
    }
}
