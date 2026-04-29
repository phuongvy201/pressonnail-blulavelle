<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CrossSellService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCrossSellController extends Controller
{
    public function show(Request $request, int $id, CrossSellService $crossSellService): JsonResponse
    {
        $limit = (int) $request->query('limit', 6);

        $product = Product::query()
            ->availableForDisplay()
            ->with(['template.category'])
            ->findOrFail($id);

        $data = $crossSellService->getCrossSellData($product, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $data['product_id'],
                'source_category_id' => $data['source_category_id'],
                'complete_your_set' => $data['complete_your_set']->map(fn(Product $item) => $this->transform($item))->values(),
                'you_may_also_like' => $data['you_may_also_like']->map(fn(Product $item) => $this->transform($item))->values(),
                'trending' => $data['trending']->map(fn(Product $item) => $this->transform($item))->values(),
                'merged' => $data['merged']->map(fn(Product $item) => $this->transform($item))->values(),
            ],
        ]);
    }

    private function transform(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => (float) ($product->price ?? $product->template?->base_price ?? 0),
            'list_price' => (float) ($product->list_price ?? $product->template?->list_price ?? 0),
            'category_id' => $product->template?->category_id,
            'category_name' => $product->template?->category?->name,
            'primary_image' => $product->primary_image,
            'cross_sell_rank' => $product->getAttribute('cross_sell_rank'),
            'url' => route('products.show', ['slug' => $product->slug]),
        ];
    }
}
