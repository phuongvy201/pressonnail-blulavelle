<?php

namespace App\Services;

use App\Models\CategoryCrossSell;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CrossSellService
{
    public function getCrossSellData(Product $product, int $limitPerSection = 6): array
    {
        $limitPerSection = max(1, min($limitPerSection, 10));

        return Cache::remember(
            $this->cacheKey($product->id, $limitPerSection),
            now()->addMinutes(15),
            function () use ($product, $limitPerSection) {
                $product->loadMissing('template.category');

                $categoryBased = $this->getCategoryBased($product, $limitPerSection);
                $usedIds = $categoryBased->pluck('id')->all();

                $similar = $this->getSimilarProducts($product, $limitPerSection, $usedIds);
                $usedIds = array_values(array_unique(array_merge($usedIds, $similar->pluck('id')->all())));

                $trending = $this->getTrendingProducts($product, $limitPerSection, $usedIds);

                $merged = $categoryBased
                    ->concat($similar)
                    ->concat($trending)
                    ->unique('id')
                    ->take($limitPerSection)
                    ->values();

                return [
                    'product_id' => $product->id,
                    'source_category_id' => $product->template?->category_id,
                    'complete_your_set' => $categoryBased,
                    'you_may_also_like' => $similar,
                    'trending' => $trending,
                    'merged' => $merged,
                ];
            }
        );
    }

    private function getCategoryBased(Product $product, int $limit, array $excludeIds = []): Collection
    {
        $sourceCategoryId = $product->template?->category_id;
        if (!$sourceCategoryId) {
            return collect();
        }

        $mapping = CategoryCrossSell::query()
            ->where('source_category_id', $sourceCategoryId)
            ->orderBy('priority')
            ->get(['target_category_id', 'priority']);

        if ($mapping->isEmpty()) {
            return collect();
        }

        $basePrice = (float) ($product->price ?? $product->template?->base_price ?? 0);
        $result = collect();

        foreach ($mapping as $rule) {
            if ($result->count() >= $limit) {
                break;
            }

            $take = $rule->priority <= 10 ? 3 : ($rule->priority <= 30 ? 2 : 1);
            $remaining = $limit - $result->count();

            $items = Product::query()
                ->availableForDisplay()
                ->with(['shop', 'template'])
                ->where('id', '!=', $product->id)
                ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
                ->whereHas('template', function ($query) use ($rule) {
                    $query->where('category_id', $rule->target_category_id);
                })
                ->orderByRaw(
                    'CASE WHEN COALESCE(products.price, 999999) <= ? THEN 0 ELSE 1 END ASC',
                    [$basePrice]
                )
                ->orderBy('products.price', 'asc')
                ->orderByDesc('products.created_at')
                ->limit(min($take, $remaining))
                ->get();

            $excludeIds = array_values(array_unique(array_merge($excludeIds, $items->pluck('id')->all())));
            $result = $result->concat($items);
        }

        // Fill remaining slots from all mapped target categories to reach requested limit.
        if ($result->count() < $limit) {
            $remaining = $limit - $result->count();
            $targetCategoryIds = $mapping->pluck('target_category_id')->unique()->values()->all();

            $fillItems = Product::query()
                ->availableForDisplay()
                ->with(['shop', 'template'])
                ->where('id', '!=', $product->id)
                ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
                ->whereHas('template', function ($query) use ($targetCategoryIds) {
                    $query->whereIn('category_id', $targetCategoryIds);
                })
                ->orderByRaw(
                    'CASE WHEN COALESCE(products.price, 999999) <= ? THEN 0 ELSE 1 END ASC',
                    [$basePrice]
                )
                ->orderBy('products.price', 'asc')
                ->orderByDesc('products.created_at')
                ->limit($remaining)
                ->get();

            $result = $result->concat($fillItems);
        }

        $ranked = $result->unique('id')->take($limit)->values();
        $ranked->each(function (Product $item, int $index) {
            $item->setAttribute('cross_sell_rank', $index + 1);
        });

        return $ranked;
    }

    private function getSimilarProducts(Product $product, int $limit, array $excludeIds = []): Collection
    {
        $sourceCategoryId = $product->template?->category_id;
        if (!$sourceCategoryId) {
            return collect();
        }

        $basePrice = (float) ($product->price ?? $product->template?->base_price ?? 0);

        return Product::query()
            ->availableForDisplay()
            ->with(['shop', 'template'])
            ->where('id', '!=', $product->id)
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->whereHas('template', function ($query) use ($sourceCategoryId) {
                $query->where('category_id', $sourceCategoryId);
            })
            ->orderByRaw(
                'CASE WHEN COALESCE(products.price, 999999) <= ? THEN 0 ELSE 1 END ASC',
                [$basePrice]
            )
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->values();
    }

    private function getTrendingProducts(Product $product, int $limit, array $excludeIds = []): Collection
    {
        $basePrice = (float) ($product->price ?? $product->template?->base_price ?? 0);

        $ids = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as sold_qty')
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['processing', 'completed', 'delivered'])
                    ->where('created_at', '>=', now()->subDays(30));
            })
            ->where('product_id', '!=', $product->id)
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('product_id', $excludeIds))
            ->groupBy('product_id')
            ->orderByDesc('sold_qty')
            ->limit($limit * 3)
            ->pluck('product_id')
            ->all();

        if (empty($ids)) {
            return collect();
        }

        $products = Product::query()
            ->availableForDisplay()
            ->with(['shop', 'template'])
            ->whereIn('id', $ids)
            ->orderByRaw(
                'CASE WHEN COALESCE(products.price, 999999) <= ? THEN 0 ELSE 1 END ASC',
                [$basePrice]
            )
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        return $products->values();
    }

    private function cacheKey(int $productId, int $limit): string
    {
        return "cross_sell:product:{$productId}:limit:{$limit}";
    }
}
