<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Product;
use App\Support\CollectionKeywordRules;
use Illuminate\Support\Collection as SupportCollection;

class CollectionAutoAssignService
{
    /** @var SupportCollection<int, Collection>|null */
    protected ?SupportCollection $eligibleCollections = null;

    /**
     * @return array{scanned: int, attached: int, collections: int}
     */
    public function syncProduct(Product $product): array
    {
        if ($product->status !== 'active') {
            return ['scanned' => 0, 'attached' => 0, 'collections' => 0];
        }

        $attached = 0;
        $collections = $this->eligibleCollections();

        foreach ($collections as $collection) {
            if (! $this->appliesToProduct($collection, $product)) {
                continue;
            }
            if (! $this->productMatches($collection, $product)) {
                continue;
            }
            if ($this->attachIfMissing($collection, $product)) {
                $attached++;
            }
        }

        return [
            'scanned' => $collections->count(),
            'attached' => $attached,
            'collections' => $collections->count(),
        ];
    }

    /**
     * @return array{scanned: int, attached: int}
     */
    public function syncCollection(Collection $collection): array
    {
        if (! CollectionKeywordRules::isEnabledForCollection($collection)) {
            return ['scanned' => 0, 'attached' => 0];
        }

        $scanned = 0;
        $attached = 0;

        Product::query()
            ->where('status', 'active')
            ->select(['id', 'shop_id', 'name', 'description', 'meta_keywords', 'status'])
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($collection, &$scanned, &$attached) {
                foreach ($products as $product) {
                    $scanned++;
                    if (! $this->appliesToProduct($collection, $product)) {
                        continue;
                    }
                    if (! $this->productMatches($collection, $product)) {
                        continue;
                    }
                    if ($this->attachIfMissing($collection, $product)) {
                        $attached++;
                    }
                }
            });

        return ['scanned' => $scanned, 'attached' => $attached];
    }

    public function productMatches(Collection $collection, Product $product): bool
    {
        $rules = is_array($collection->auto_rules) ? $collection->auto_rules : [];
        $keywords = CollectionKeywordRules::keywordsFromRules($rules);

        if ($keywords === []) {
            return false;
        }

        $haystack = $this->productHaystack($product, $rules);

        foreach ($keywords as $keyword) {
            if ($keyword === '') {
                continue;
            }
            if (mb_strpos($haystack, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }

    public function appliesToProduct(Collection $collection, Product $product): bool
    {
        if ($collection->shop_id === null) {
            return true;
        }

        return (int) $collection->shop_id === (int) $product->shop_id;
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    protected function productHaystack(Product $product, array $rules): string
    {
        $fields = CollectionKeywordRules::matchInFromRules($rules);
        $parts = [];

        foreach ($fields as $field) {
            $value = (string) ($product->{$field} ?? '');
            if ($field === 'description') {
                $value = strip_tags($value);
            }
            $parts[] = $value;
        }

        return mb_strtolower(trim(implode(' ', $parts)));
    }

    protected function attachIfMissing(Collection $collection, Product $product): bool
    {
        if ($collection->products()->where('products.id', $product->id)->exists()) {
            return false;
        }

        $collection->products()->attach($product->id, ['sort_order' => 0]);

        return true;
    }

    /**
     * @return SupportCollection<int, Collection>
     */
    protected function eligibleCollections(): SupportCollection
    {
        if ($this->eligibleCollections !== null) {
            return $this->eligibleCollections;
        }

        $this->eligibleCollections = Collection::query()
            ->where('status', 'active')
            ->whereNotNull('auto_rules')
            ->get()
            ->filter(fn (Collection $collection) => CollectionKeywordRules::isEnabled(
                is_array($collection->auto_rules) ? $collection->auto_rules : []
            ))
            ->values();

        return $this->eligibleCollections;
    }

    public function forgetCache(): void
    {
        $this->eligibleCollections = null;
    }
}
