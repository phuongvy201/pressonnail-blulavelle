<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BuildsApiListResponse;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    use BuildsApiListResponse;

    public function index(Request $request): JsonResponse
    {
        $filters = Validator::make([
            'page' => $this->apiQueryParam($request, 'page') ?? 1,
            'perPage' => $this->apiQueryParam($request, 'perPage', 'per_page') ?? 24,
            'search' => $this->apiQueryParam($request, 'search'),
            'sortBy' => $this->apiQueryParam($request, 'sortBy', 'sort') ?? 'name',
        ], [
            'page' => ['integer', 'min:1'],
            'perPage' => ['integer', 'min:1', 'max:48'],
            'search' => ['nullable', 'string', 'max:200'],
            'sortBy' => ['nullable', Rule::in(['name', 'newest', 'rating', 'products'])],
        ])->validate();

        $page = (int) $filters['page'];
        $perPage = (int) $filters['perPage'];

        $query = Shop::query()
            ->active()
            ->withCount(['products as products_count' => fn ($q) => $q->where('status', 'active')]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('shop_name', 'like', "%{$search}%")
                    ->orWhere('shop_description', 'like', "%{$search}%");
            });
        }

        match ($filters['sortBy']) {
            'newest' => $query->latest(),
            'rating' => $query->orderByDesc('rating'),
            'products' => $query->orderByDesc('products_count'),
            default => $query->orderBy('shop_name'),
        };

        $shops = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();

        $items = $shops->getCollection()
            ->map(fn (Shop $shop) => $this->transformShopListItem($shop))
            ->values();

        return $this->apiListResponse($shops, $items);
    }

    private function transformShopListItem(Shop $shop): array
    {
        return [
            'id' => $shop->id,
            'name' => $shop->shop_name,
            'slug' => $shop->shop_slug,
            'description' => $shop->shop_description,
            'logo' => $shop->shop_logo,
            'banner' => $shop->shop_banner,
            'city' => $shop->shop_city,
            'country' => $shop->shop_country,
            'verified' => (bool) $shop->verified,
            'rating' => $shop->rating !== null ? (float) $shop->rating : null,
            'productsCount' => (int) ($shop->products_count ?? 0),
            'url' => route('shops.show', ['shop' => $shop->shop_slug]),
        ];
    }
}
