<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BuildsApiListResponse;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CollectionController extends Controller
{
    use BuildsApiListResponse;

    public function index(Request $request): JsonResponse
    {
        $filters = Validator::make([
            'page' => $this->apiQueryParam($request, 'page') ?? 1,
            'perPage' => $this->apiQueryParam($request, 'perPage', 'per_page') ?? 12,
            'search' => $this->apiQueryParam($request, 'search'),
            'sortBy' => $this->apiQueryParam($request, 'sortBy', 'sort') ?? 'featured',
            'shopId' => $this->apiQueryParam($request, 'shopId', 'shop_id'),
        ], [
            'page' => ['integer', 'min:1'],
            'perPage' => ['integer', 'min:1', 'max:48'],
            'search' => ['nullable', 'string', 'max:200'],
            'sortBy' => ['nullable', Rule::in(['featured', 'name', 'newest', 'oldest', 'products'])],
            'shopId' => ['nullable', 'integer', 'min:1'],
        ])->validate();

        $page = (int) $filters['page'];
        $perPage = (int) $filters['perPage'];

        $query = Collection::query()
            ->with(['shop'])
            ->active()
            ->approved()
            ->withCount(['products as products_count' => fn ($q) => $q->where('status', 'active')]);

        if (! empty($filters['shopId'])) {
            $query->where('shop_id', (int) $filters['shopId']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        match ($filters['sortBy']) {
            'name' => $query->orderBy('name'),
            'newest' => $query->latest(),
            'oldest' => $query->oldest(),
            'products' => $query->orderByDesc('products_count'),
            default => $query->orderByDesc('featured')
                ->orderBy('sort_order')
                ->latest(),
        };

        $collections = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();

        $items = $collections->getCollection()
            ->map(fn (Collection $collection) => $this->transformCollectionListItem($collection))
            ->values();

        return $this->apiListResponse($collections, $items);
    }

    private function transformCollectionListItem(Collection $collection): array
    {
        return [
            'id' => $collection->id,
            'name' => $collection->name,
            'slug' => $collection->slug,
            'description' => $collection->description,
            'image' => $collection->image,
            'featured' => (bool) $collection->featured,
            'productsCount' => (int) ($collection->products_count ?? 0),
            'url' => route('collections.show', ['slug' => $collection->slug]),
            'shop' => $collection->shop ? [
                'id' => $collection->shop->id,
                'name' => $collection->shop->name,
            ] : null,
        ];
    }
}
