<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BuildsApiListResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WishlistController as StorefrontWishlistController;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    use BuildsApiListResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) ($this->apiQueryParam($request, 'perPage', 'per_page') ?? 24), 1), 50);
        $paginator = Wishlist::getWishlistItems(Auth::id(), session()->getId(), $perPage);

        $items = $paginator->getCollection()
            ->map(fn ($wishlistItem) => $this->formatItem($wishlistItem))
            ->values()
            ->all();

        $response = $this->apiListResponse($paginator, $items);
        $data = $response->getData(true);
        $data['count'] = $paginator->total();

        return response()->json($data);
    }

    public function add(Request $request): JsonResponse
    {
        $this->requireProductId($request);

        return $this->camelizeResponse(app(StorefrontWishlistController::class)->add($request));
    }

    public function remove(Request $request): JsonResponse
    {
        $this->requireProductId($request);

        return $this->camelizeResponse(app(StorefrontWishlistController::class)->remove($request));
    }

    public function toggle(Request $request): JsonResponse
    {
        $this->requireProductId($request);

        return $this->camelizeResponse(app(StorefrontWishlistController::class)->toggle($request));
    }

    public function count(): JsonResponse
    {
        return $this->camelizeResponse(app(StorefrontWishlistController::class)->count());
    }

    public function check(Request $request): JsonResponse
    {
        if (! $request->filled('product_ids') && is_array($request->input('productIds'))) {
            $request->merge(['product_ids' => $request->input('productIds')]);
        }

        $request->validate([
            'productIds' => ['required_without:product_ids', 'array'],
            'product_ids' => ['required_without:productIds', 'array'],
        ]);

        $this->normalizeProductId($request);

        return $this->camelizeResponse(app(StorefrontWishlistController::class)->check($request));
    }

    private function requireProductId(Request $request): void
    {
        $request->validate([
            'productId' => ['required_without:product_id', 'integer'],
            'product_id' => ['required_without:productId', 'integer'],
        ]);

        $this->normalizeProductId($request);
    }

    public function clear(): JsonResponse
    {
        return $this->camelizeResponse(app(StorefrontWishlistController::class)->clear());
    }

    private function normalizeProductId(Request $request): void
    {
        if (! $request->filled('product_id') && $request->filled('productId')) {
            $request->merge(['product_id' => $request->input('productId')]);
        }

        if (! $request->filled('product_ids') && is_array($request->input('productIds'))) {
            $request->merge(['product_ids' => $request->input('productIds')]);
        }
    }

    private function camelizeResponse(JsonResponse $response): JsonResponse
    {
        $data = $response->getData(true);

        if (array_key_exists('wishlist_count', $data)) {
            $data['count'] = $data['wishlist_count'];
            unset($data['wishlist_count']);
        }

        if (array_key_exists('wishlist_items', $data)) {
            $data['productIds'] = $data['wishlist_items'];
            unset($data['wishlist_items']);
        }

        $response->setData($data);

        return $response;
    }

    private function formatItem(Wishlist $wishlistItem): array
    {
        $product = $wishlistItem->product;

        return [
            'id' => $wishlistItem->id,
            'productId' => $wishlistItem->product_id,
            'addedAt' => $wishlistItem->created_at?->toIso8601String(),
            'product' => $product ? [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => (float) $product->getEffectivePrice(),
                'primaryImage' => $this->resolveProductImage($product),
                'inStock' => $product->hasStock(),
            ] : null,
        ];
    }

    private function resolveProductImage(Product $product): ?string
    {
        $image = $product->primary_image;

        if (is_string($image)) {
            return $image;
        }

        if (is_array($image)) {
            return $image['url'] ?? $image['src'] ?? $image['path'] ?? null;
        }

        return null;
    }
}
