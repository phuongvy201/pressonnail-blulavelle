<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SearchService
{
    /**
     * Tìm sản phẩm: name, description, category, template, variant (variant_name + attributes).
     * Multi-word: mỗi từ phải khớp ít nhất một trường (gần đúng, "lux" khớp "luxury").
     * Ưu tiên category: nếu từ khóa trùng tên category thì sản phẩm thuộc category đó lên trước.
     */
    public function buildProductSearchQuery(
        string $query,
        array $filters = [],
        bool $paginate = false,
        int $perPage = 12,
        int $limit = 6
    ) {
        $terms = $this->splitSearchTerms($query);
        if (empty($terms)) {
            return $paginate
                ? Product::availableForDisplay()->whereRaw('1 = 0')->paginate($perPage)
                : Product::availableForDisplay()->whereRaw('1 = 0')->limit($limit)->get();
        }

        $q = Product::with(['template.category', 'shop', 'variants'])
            ->availableForDisplay();

        // Mỗi từ phải khớp ít nhất một trong: name, description, template, category, variant
        foreach ($terms as $term) {
            $q->where(function (Builder $b) use ($term) {
                $b->where('products.name', 'like', "%{$term}%")
                    ->orWhere('products.description', 'like', "%{$term}%")
                    ->orWhereHas('template', function (Builder $t) use ($term) {
                        $t->where('name', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%");
                    })
                    ->orWhereHas('template.category', function (Builder $c) use ($term) {
                        $c->where('name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('variants', function (Builder $v) use ($term) {
                        $v->where('variant_name', 'like', "%{$term}%")
                            ->orWhereRaw('CAST(attributes AS CHAR) LIKE ?', ['%' . $term . '%']);
                    });
            });
        }

        // Filter: color, shape, size (từ variant attributes)
        if (!empty($filters['color'])) {
            $q->whereHas('variants', function (Builder $v) use ($filters) {
                $this->whereVariantAttribute($v, ['Color', 'color'], $filters['color']);
            });
        }
        if (!empty($filters['shape'])) {
            $q->whereHas('variants', function (Builder $v) use ($filters) {
                $this->whereVariantAttribute($v, ['Shape', 'shape'], $filters['shape']);
            });
        }
        if (!empty($filters['size'])) {
            $q->whereHas('variants', function (Builder $v) use ($filters) {
                $this->whereVariantAttribute($v, ['Size', 'size'], $filters['size']);
            });
        }

        // Filter: price
        if (isset($filters['price_min']) && $filters['price_min'] !== '' && is_numeric($filters['price_min'])) {
            $min = (float) $filters['price_min'];
            $q->where(function (Builder $b) use ($min) {
                $b->where('products.price', '>=', $min)
                    ->orWhereHas('variants', fn(Builder $v) => $v->where('price', '>=', $min));
            });
        }
        if (isset($filters['price_max']) && $filters['price_max'] !== '' && is_numeric($filters['price_max'])) {
            $max = (float) $filters['price_max'];
            $q->where(function (Builder $b) use ($max) {
                $b->where('products.price', '<=', $max)
                    ->orWhereHas('variants', fn(Builder $v) => $v->where('price', '<=', $max));
            });
        }

        // Ưu tiên category trùng với từ khóa (order by category match)
        $categoryIds = Category::where(function (Builder $c) use ($terms) {
            foreach ($terms as $term) {
                $c->orWhere('name', 'like', "%{$term}%");
            }
        })->pluck('id');

        if ($categoryIds->isNotEmpty()) {
            $ids = $categoryIds->toArray();
            $placeholders = implode(',', array_map('intval', $ids));
            $q->orderByRaw(
                "CASE WHEN EXISTS (SELECT 1 FROM product_templates pt WHERE pt.id = products.template_id AND pt.category_id IN ({$placeholders})) THEN 0 ELSE 1 END"
            );
        }

        $q->orderBy('products.name');

        if ($paginate) {
            return $q->paginate($perPage);
        }
        return $q->limit($limit)->get();
    }

    /**
     * Đếm sản phẩm với cùng điều kiện search + filter (dùng cho counts).
     */
    public function countProducts(string $query, array $filters = []): int
    {
        $terms = $this->splitSearchTerms($query);
        if (empty($terms)) {
            return 0;
        }

        $q = Product::availableForDisplay();

        foreach ($terms as $term) {
            $q->where(function (Builder $b) use ($term) {
                $b->where('products.name', 'like', "%{$term}%")
                    ->orWhere('products.description', 'like', "%{$term}%")
                    ->orWhereHas('template', function (Builder $t) use ($term) {
                        $t->where('name', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%");
                    })
                    ->orWhereHas('template.category', function (Builder $c) use ($term) {
                        $c->where('name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('variants', function (Builder $v) use ($term) {
                        $v->where('variant_name', 'like', "%{$term}%")
                            ->orWhereRaw('CAST(attributes AS CHAR) LIKE ?', ['%' . $term . '%']);
                    });
            });
        }

        if (!empty($filters['color'])) {
            $q->whereHas('variants', function (Builder $v) use ($filters) {
                $this->whereVariantAttribute($v, ['Color', 'color'], $filters['color']);
            });
        }
        if (!empty($filters['shape'])) {
            $q->whereHas('variants', function (Builder $v) use ($filters) {
                $this->whereVariantAttribute($v, ['Shape', 'shape'], $filters['shape']);
            });
        }
        if (!empty($filters['size'])) {
            $q->whereHas('variants', function (Builder $v) use ($filters) {
                $this->whereVariantAttribute($v, ['Size', 'size'], $filters['size']);
            });
        }
        if (isset($filters['price_min']) && $filters['price_min'] !== '' && is_numeric($filters['price_min'])) {
            $min = (float) $filters['price_min'];
            $q->where(function (Builder $b) use ($min) {
                $b->where('products.price', '>=', $min)
                    ->orWhereHas('variants', fn(Builder $v) => $v->where('price', '>=', $min));
            });
        }
        if (isset($filters['price_max']) && $filters['price_max'] !== '' && is_numeric($filters['price_max'])) {
            $max = (float) $filters['price_max'];
            $q->where(function (Builder $b) use ($max) {
                $b->where('products.price', '<=', $max)
                    ->orWhereHas('variants', fn(Builder $v) => $v->where('price', '<=', $max));
            });
        }

        return $q->count();
    }

    private function splitSearchTerms(string $query): array
    {
        $words = preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_filter(array_map('trim', $words), fn($w) => strlen($w) >= 1));
    }

    /**
     * Filter variant by attribute key (Color/color, Shape/shape, Size/size).
     * MySQL: JSON_EXTRACT(attributes, '$.Color') = "Pink" or LIKE.
     */
    private function whereVariantAttribute(Builder $v, array $keys, string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }
        $v->where(function (Builder $b) use ($keys, $value) {
            foreach ($keys as $key) {
                $b->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(attributes, ?))) LIKE ?', ['$.' . $key, '%' . Str::lower($value) . '%']);
            }
        });
    }

    /**
     * Lấy danh sách giá trị filter (màu, shape, size) từ variants trong DB (cho search hiện tại).
     */
    public function getAvailableFilters(string $query): array
    {
        $terms = $this->splitSearchTerms($query);
        $productIds = Product::availableForDisplay()
            ->when(!empty($terms), function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function (Builder $b) use ($term) {
                        $b->where('products.name', 'like', "%{$term}%")
                            ->orWhere('products.description', 'like', "%{$term}%")
                            ->orWhereHas('template', fn(Builder $t) => $t->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"))
                            ->orWhereHas('template.category', fn(Builder $c) => $c->where('name', 'like', "%{$term}%"))
                            ->orWhereHas('variants', fn(Builder $v) => $v->where('variant_name', 'like', "%{$term}%")->orWhereRaw('CAST(attributes AS CHAR) LIKE ?', ['%' . $term . '%']));
                    });
                }
            })
            ->pluck('id');

        if ($productIds->isEmpty()) {
            return ['colors' => [], 'shapes' => [], 'sizes' => []];
        }

        $variants = \App\Models\ProductVariant::whereIn('product_id', $productIds)
            ->get();

        $colors = [];
        $shapes = [];
        $sizes = [];

        foreach ($variants as $v) {
            $attrs = $v->attributes ?? [];
            if (is_string($attrs)) {
                $attrs = json_decode($attrs, true) ?? [];
            }
            foreach (['Color', 'color'] as $k) {
                if (!empty($attrs[$k])) {
                    $colors[Str::lower(trim($attrs[$k]))] = trim($attrs[$k]);
                }
            }
            foreach (['Shape', 'shape'] as $k) {
                if (!empty($attrs[$k])) {
                    $shapes[Str::lower(trim($attrs[$k]))] = trim($attrs[$k]);
                }
            }
            foreach (['Size', 'size'] as $k) {
                if (!empty($attrs[$k])) {
                    $sizes[Str::lower(trim($attrs[$k]))] = trim($attrs[$k]);
                }
            }
        }

        return [
            'colors' => array_values(array_unique($colors)),
            'shapes' => array_values(array_unique($shapes)),
            'sizes' => array_values(array_unique($sizes)),
        ];
    }
}
