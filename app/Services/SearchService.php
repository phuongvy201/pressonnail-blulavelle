<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductEmbedding;
use App\Services\Search\EmbeddingService;
use App\Services\Search\SearchText;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SearchService
{
    public function __construct(
        private readonly SearchText $text,
        private readonly EmbeddingService $embeddings,
    ) {}

    /**
     * Hybrid product search used by the storefront and suggestions:
     * diacritic-insensitive keyword/fuzzy matching first, semantic
     * embedding similarity as a rerank layer when available.
     */
    public function buildProductSearchQuery(
        string $query,
        array $filters = [],
        bool $paginate = false,
        int $perPage = 12,
        int $limit = 6
    ): Collection|LengthAwarePaginator {
        $candidateLimit = max(
            (int) config('search.candidate_limit', 300),
            $paginate ? $perPage * 5 : $limit,
        );
        $ranked = $this->searchProducts($query, $filters, $candidateLimit);

        if ($paginate) {
            $page = Paginator::resolveCurrentPage();
            $items = $ranked->slice(($page - 1) * $perPage, $perPage)->values();

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $ranked->count(),
                $perPage,
                $page,
            );
        }

        return $ranked->take($limit)->values();
    }

    /**
     * @return Collection<int, Product>
     */
    public function searchProducts(string $query, array $filters = [], int $limit = 6, string $scope = 'all'): Collection
    {
        $concepts = $this->text->concepts($query);
        if ($concepts === []) {
            return collect();
        }

        $base = $this->baseQuery($filters);
        $candidates = $this->keywordCandidates(clone $base, $concepts, $scope);
        $semanticScores = $this->semanticScores(clone $base, $query);

        if ($semanticScores !== []) {
            $semanticCandidates = (clone $base)
                ->whereIn('products.id', array_keys($semanticScores))
                ->get();
            $candidates = $candidates->concat($semanticCandidates)->unique('id')->values();
        }

        $scored = $candidates
            ->map(function (Product $product) use ($concepts, $semanticScores, $scope) {
                $keywordScore = $this->keywordScore($product, $concepts, $scope);
                $semanticScore = (float) ($semanticScores[$product->id] ?? 0.0);
                $hasSemantic = array_key_exists($product->id, $semanticScores);
                $finalScore = $hasSemantic
                    ? (1 - (float) config('search.semantic_weight', 0.35)) * $keywordScore
                        + (float) config('search.semantic_weight', 0.35) * max(0.0, $semanticScore)
                    : $keywordScore;

                $product->setAttribute('search_score', round($finalScore, 4));
                $product->setAttribute('search_semantic_score', round(max(0.0, $semanticScore), 4));

                return ['product' => $product, 'score' => $finalScore, 'keyword' => $keywordScore, 'semantic' => $semanticScore];
            })
            ->filter(function (array $row) {
                return $row['keyword'] >= 0.2
                    || ($row['semantic'] >= 0.35 && $row['score'] >= 0.12);
            })
            ->sortBy([
                ['score', 'desc'],
                ['keyword', 'desc'],
            ])
            ->take($limit)
            ->values();

        return $scored->map(fn (array $row) => $row['product'])->values();
    }

    /**
     * @return list<int>
     */
    public function rankedProductIds(string $query, array $filters = [], int $limit = 200, string $scope = 'all'): array
    {
        return $this->searchProducts($query, $filters, $limit, $scope)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function countProducts(string $query, array $filters = []): int
    {
        return count($this->rankedProductIds($query, $filters, max(200, (int) config('search.candidate_limit', 300))));
    }

    private function baseQuery(array $filters): Builder
    {
        $query = Product::with(['template.category', 'shop', 'variants'])
            ->availableForDisplay();

        $this->applyFilters($query, $filters);

        return $query;
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['color'])) {
            $query->whereHas('variants', function (Builder $variant) use ($filters) {
                $this->whereVariantAttribute($variant, ['Color', 'color'], (string) $filters['color']);
            });
        }
        if (! empty($filters['shape'])) {
            $query->whereHas('variants', function (Builder $variant) use ($filters) {
                $this->whereVariantAttribute($variant, ['Shape', 'shape'], (string) $filters['shape']);
            });
        }
        if (! empty($filters['size'])) {
            $query->whereHas('variants', function (Builder $variant) use ($filters) {
                $this->whereVariantAttribute($variant, ['Size', 'size'], (string) $filters['size']);
            });
        }
        if (isset($filters['price_min']) && $filters['price_min'] !== '' && is_numeric($filters['price_min'])) {
            $min = (float) $filters['price_min'];
            $query->where(function (Builder $builder) use ($min) {
                $builder->where('products.price', '>=', $min)
                    ->orWhereHas('variants', fn (Builder $variant) => $variant->where('price', '>=', $min));
            });
        }
        if (isset($filters['price_max']) && $filters['price_max'] !== '' && is_numeric($filters['price_max'])) {
            $max = (float) $filters['price_max'];
            $query->where(function (Builder $builder) use ($max) {
                $builder->where('products.price', '<=', $max)
                    ->orWhereHas('variants', fn (Builder $variant) => $variant->where('price', '<=', $max));
            });
        }
    }

    /**
     * @param  list<list<string>>  $concepts
     * @return Collection<int, Product>
     */
    private function keywordCandidates(Builder $base, array $concepts, string $scope): Collection
    {
        $terms = [];
        foreach ($concepts as $alternatives) {
            foreach ($alternatives as $term) {
                $terms[$term] = $term;
            }
        }
        $terms = array_values($terms);
        if ($terms === []) {
            return collect();
        }

        return $base
            ->where(function (Builder $builder) use ($terms, $scope) {
                foreach ($terms as $term) {
                    $like = '%'.$term.'%';
                    $builder->orWhere(function (Builder $termQuery) use ($like, $scope) {
                        if ($scope === 'all' || $scope === 'name') {
                            $termQuery->orWhere('products.name', 'like', $like);
                        }
                        if ($scope === 'all' || $scope === 'description') {
                            $termQuery->orWhere('products.description', 'like', $like);
                        }
                        if ($scope === 'all') {
                            $termQuery->orWhere('products.color', 'like', $like)
                                ->orWhere('products.pattern', 'like', $like)
                                ->orWhere('products.material', 'like', $like)
                                ->orWhere('products.meta_keywords', 'like', $like)
                                ->orWhereHas('template', function (Builder $template) use ($like) {
                                    $template->where('name', 'like', $like)
                                        ->orWhere('description', 'like', $like);
                                })
                                ->orWhereHas('template.category', fn (Builder $category) => $category->where('name', 'like', $like))
                                ->orWhereHas('variants', function (Builder $variant) use ($like) {
                                    $variant->where('variant_name', 'like', $like)
                                        ->orWhereRaw('CAST(attributes AS CHAR) LIKE ?', [$like]);
                                });
                        }
                        if ($scope === 'all' || $scope === 'shop') {
                            $termQuery->orWhereHas('shop', fn (Builder $shop) => $shop->where('name', 'like', $like));
                        }
                    });
                }
            })
            ->limit(max(20, (int) config('search.candidate_limit', 300)))
            ->get();
    }

    /**
     * @return array<int, float> product id => cosine similarity
     */
    private function semanticScores(Builder $base, string $query): array
    {
        if (! $this->embeddings->enabled()) {
            return [];
        }

        $queryVector = $this->embeddings->embed($query);
        if ($queryVector === null) {
            return [];
        }

        $productIds = (clone $base)->select('products.id');
        $rows = ProductEmbedding::query()
            ->where('model', $this->embeddings->model())
            ->whereIn('product_id', $productIds)
            ->get();

        $scores = [];
        foreach ($rows as $row) {
            if (is_array($row->vector) && $row->vector !== []) {
                $scores[(int) $row->product_id] = $this->embeddings->cosine($queryVector, $row->vector);
            }
        }

        arsort($scores);

        return array_slice($scores, 0, 50, true);
    }

    /**
     * @param  list<list<string>>  $concepts
     */
    private function keywordScore(Product $product, array $concepts, string $scope): float
    {
        $fields = $this->productFields($product, $scope);
        if ($fields === [] || $concepts === []) {
            return 0.0;
        }

        $maxWeight = max(array_column($fields, 'weight'));
        $total = 0.0;
        foreach ($concepts as $alternatives) {
            $best = 0.0;
            foreach ($fields as $field) {
                $score = $this->text->conceptScore($alternatives, $field['text'], $field['tokens']);
                $best = max($best, $score * ($field['weight'] / $maxWeight));
            }
            $total += $best;
        }

        return $total / count($concepts);
    }

    /**
     * @return list<array{text: string, tokens: list<string>, weight: int}>
     */
    private function productFields(Product $product, string $scope): array
    {
        $raw = [];
        if ($scope === 'all' || $scope === 'name') {
            $raw[] = [(string) $product->name, 5];
        }
        if ($scope === 'all') {
            $variantText = $product->variants
                ->map(fn ($variant) => trim((string) $variant->variant_name.' '.json_encode($variant->attributes ?? [])))
                ->implode(' ');
            $raw[] = [$variantText, 4];
            $raw[] = [(string) ($product->color ?? ''), 4];
            $raw[] = [(string) ($product->meta_keywords ?? ''), 3];
            $raw[] = [(string) ($product->pattern ?? ''), 3];
            $raw[] = [(string) ($product->material ?? ''), 3];
            $raw[] = [(string) ($product->template?->category?->name ?? ''), 3];
            $raw[] = [(string) ($product->template?->name ?? ''), 2];
        }
        if ($scope === 'all' || $scope === 'shop') {
            $raw[] = [(string) ($product->shop?->name ?? ''), 2];
        }
        if ($scope === 'all' || $scope === 'description') {
            $raw[] = [strip_tags((string) $product->getEffectiveDescription()), 1];
        }

        return collect($raw)
            ->filter(fn (array $field) => trim((string) $field[0]) !== '')
            ->map(fn (array $field) => [
                'text' => $this->text->normalize((string) $field[0]),
                'tokens' => $this->text->tokens((string) $field[0]),
                'weight' => (int) $field[1],
            ])
            ->values()
            ->all();
    }

    private function whereVariantAttribute(Builder $variant, array $keys, string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }
        $variant->where(function (Builder $builder) use ($keys, $value) {
            foreach ($keys as $key) {
                $builder->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(attributes, ?))) LIKE ?', ['$.'.$key, '%'.Str::lower($value).'%']);
            }
        });
    }

    /**
     * Filter values (colour, shape, size) available among smart-search results.
     */
    public function getAvailableFilters(string $query): array
    {
        $productIds = $this->rankedProductIds($query, [], max(100, (int) config('search.candidate_limit', 300)));

        if ($productIds === []) {
            return ['colors' => [], 'shapes' => [], 'sizes' => []];
        }

        $variants = \App\Models\ProductVariant::whereIn('product_id', $productIds)->get();

        $colors = [];
        $shapes = [];
        $sizes = [];

        foreach ($variants as $variant) {
            $attrs = $variant->attributes ?? [];
            if (is_string($attrs)) {
                $attrs = json_decode($attrs, true) ?? [];
            }
            foreach (['Color', 'color'] as $key) {
                if (! empty($attrs[$key])) {
                    $colors[Str::lower(trim((string) $attrs[$key]))] = trim((string) $attrs[$key]);
                }
            }
            foreach (['Shape', 'shape'] as $key) {
                if (! empty($attrs[$key])) {
                    $shapes[Str::lower(trim((string) $attrs[$key]))] = trim((string) $attrs[$key]);
                }
            }
            foreach (['Size', 'size'] as $key) {
                if (! empty($attrs[$key])) {
                    $sizes[Str::lower(trim((string) $attrs[$key]))] = trim((string) $attrs[$key]);
                }
            }
        }

        return [
            'colors' => array_values($colors),
            'shapes' => array_values($shapes),
            'sizes' => array_values($sizes),
        ];
    }
}
