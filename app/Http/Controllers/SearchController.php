<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Collection;
use App\Models\Shop;
use App\Services\SearchService;
use App\Services\TikTokEventsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Display search results (products: name, description, category, variant; multi-word; filter color/shape/size/price; category priority).
     */
    public function index(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        $type = $request->get('type', 'all');

        if (empty($type)) {
            $type = 'all';
        }

        $filters = [
            'color' => $request->get('color'),
            'shape' => $request->get('shape'),
            'size' => $request->get('size'),
            'price_min' => $request->get('price_min'),
            'price_max' => $request->get('price_max'),
        ];

        $products = collect();
        $collections = collect();
        $shops = collect();
        $totalResults = 0;
        $counts = ['products' => 0, 'collections' => 0, 'shops' => 0];
        $filterOptions = ['colors' => [], 'shapes' => [], 'sizes' => []];

        if (strlen($query) >= 2) {
            if ($type === 'all' || $type === 'products') {
                $products = $this->searchService->buildProductSearchQuery(
                    $query,
                    $filters,
                    $paginate = ($type === 'products'),
                    12,
                    6
                );
                if ($type === 'products' && method_exists($products, 'withQueryString')) {
                    $products->withQueryString();
                }
                $counts['products'] = $type === 'products'
                    ? $products->total()
                    : $this->searchService->countProducts($query, $filters);
                $totalResults += $counts['products'];
                $filterOptions = $this->searchService->getAvailableFilters($query);
            }

            if ($type === 'all' || $type === 'collections') {
                $collections = Collection::with(['shop'])
                    ->active()
                    ->approved()
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    })
                    ->when($type === 'collections', fn($q) => $q->paginate(12), fn($q) => $q->limit(6)->get());
                $totalResults += $type === 'collections' ? $collections->total() : $collections->count();
            }

            if ($type === 'all' || $type === 'shops') {
                $shops = Shop::where('shop_status', 'active')
                    ->where(function ($q) use ($query) {
                        $q->where('shop_name', 'like', "%{$query}%")
                            ->orWhere('shop_description', 'like', "%{$query}%");
                    })
                    ->when($type === 'shops', fn($q) => $q->paginate(12), fn($q) => $q->limit(6)->get());
                $totalResults += $type === 'shops' ? $shops->total() : $shops->count();
            }

            $counts['collections'] = $type === 'collections'
                ? $collections->total()
                : ($type === 'all' ? Collection::active()->approved()
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    })->count() : 0);
            $counts['shops'] = $type === 'shops'
                ? $shops->total()
                : ($type === 'all' ? Shop::where('shop_status', 'active')
                    ->where(function ($q) use ($query) {
                        $q->where('shop_name', 'like', "%{$query}%")
                            ->orWhere('shop_description', 'like', "%{$query}%");
                    })->count() : 0);

            if ($type === 'all') {
                $totalResults = $counts['products'] + $counts['collections'] + $counts['shops'];
            }

            $this->trackTikTokSearchEvent($request, $query, $products, $counts['products'] ?? 0);
        }

        return view('search.index', compact(
            'query',
            'type',
            'products',
            'collections',
            'shops',
            'totalResults',
            'counts',
            'filters',
            'filterOptions'
        ));
    }

    private function trackTikTokSearchEvent(Request $request, string $query, $products, int $productCount): void
    {
        $tikTok = app(TikTokEventsService::class);

        if (!$tikTok->enabled()) {
            return;
        }

        $items = collect($products instanceof \Illuminate\Contracts\Pagination\Paginator ? $products->items() : $products)
            ->filter()
            ->take(3)
            ->map(function ($product) {
                return [
                    'content_id' => (string) $product->id,
                    'content_type' => 'product',
                    'content_name' => $product->name,
                    'price' => round($product->price ?? $product->base_price ?? 0, 2),
                ];
            })
            ->values()
            ->toArray();

        $user = Auth::user();

        $tikTok->track(
            'Search',
            [
                'value' => 0,
                'currency' => 'USD',
                'content_type' => 'product',
                'contents' => $items,
                'search_string' => $query,
                'description' => sprintf('Search results count: %d', $productCount),
            ],
            $request,
            [
                'email' => $user?->email,
                'phone' => $user?->phone,
                'external_id' => $user?->id,
            ]
        );
    }

    /**
     * API autocomplete: products (name, description, category, variant), collections, shops; thêm gợi ý cụm từ từ tên sản phẩm.
     */
    public function suggestions(Request $request)
    {
        $query = trim((string) $request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json(['items' => [], 'phrases' => []]);
        }

        $products = $this->searchService->buildProductSearchQuery($query, [], false, 12, 5);
        $productItems = collect($products)->map(function ($product) {
            $media = $product->getEffectiveMedia();
            $img = null;
            if (!empty($media)) {
                $first = $media[0];
                $img = is_string($first) ? $first : ($first['url'] ?? $first['path'] ?? null);
            }
            return [
                'type' => 'product',
                'name' => $product->name,
                'image' => $img,
                'price' => $product->price ?? $product->base_price ?? 0,
                'url' => route('products.show', $product->slug),
            ];
        });

        $collections = Collection::active()
            ->approved()
            ->where('name', 'like', "%{$query}%")
            ->limit(3)
            ->get()
            ->map(fn($c) => [
                'type' => 'collection',
                'name' => $c->name,
                'image' => $c->image,
                'products_count' => $c->active_products_count,
                'url' => route('collections.show', $c->slug),
            ]);

        $shops = Shop::where('shop_status', 'active')
            ->where('shop_name', 'like', "%{$query}%")
            ->limit(2)
            ->get()
            ->map(fn($s) => [
                'type' => 'shop',
                'name' => $s->shop_name,
                'image' => $s->shop_logo,
                'url' => route('shops.show', $s->shop_slug ?? $s->id),
            ]);

        $phrases = $productItems->pluck('name')->unique()->take(5)->values()->all();

        return response()->json([
            'items' => $productItems->concat($collections)->concat($shops)->values()->all(),
            'phrases' => $phrases,
        ]);
    }
}
