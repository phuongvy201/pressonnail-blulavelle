<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Collection;
use App\Models\Shop;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\RecentProductView;
use App\Models\OrderItem;
use App\Models\Review;
use App\Services\TikTokEventsService;
use App\Services\CurrencyService;
use App\Support\ReferenceNailSizeChart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of all active products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Product::with(['shop', 'template.category', 'variants'])
            ->availableForDisplay();

        // Filter by collection (supports new query param: collection_id)
        if ($request->filled('collection_id')) {
            $query->whereHas('collections', function ($q) use ($request) {
                $q->where('collections.id', $request->collection_id);
            });
        } elseif ($request->filled('category')) {
            // Backward compatibility (older links may still use category)
            $query->whereHas('template', function ($q) use ($request) {
                $q->where('category_id', $request->category);
            });
        }

        // Filter by shop
        if ($request->filled('shop')) {
            $query->where('shop_id', $request->shop);
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where(function ($q) use ($request) {
                $q->where('price', '>=', $request->min_price)
                    ->orWhereHas('template', function ($templateQuery) use ($request) {
                        $templateQuery->where('base_price', '>=', $request->min_price)
                            ->whereNull('products.price');
                    });
            });
        }
        if ($request->filled('max_price')) {
            $query->where(function ($q) use ($request) {
                $q->where('price', '<=', $request->max_price)
                    ->orWhereHas('template', function ($templateQuery) use ($request) {
                        $templateQuery->where('base_price', '<=', $request->max_price)
                            ->whereNull('products.price');
                    });
            });
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('shop', function ($shopQuery) use ($search) {
                        $shopQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Sort functionality
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate(20)->withQueryString();

        // Get filter data
        $collections = Collection::active()
            ->where('admin_approved', true)
            ->orderBy('name', 'asc')
            ->get();
        $shops = Shop::where('shop_status', 'active')->get();

        // Get breadcrumb data
        $breadcrumbs = [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Products', 'url' => route('products.index')]
        ];

        if ($request->filled('collection_id')) {
            $collection = Collection::find($request->collection_id);
            if ($collection) {
                $breadcrumbs[] = ['name' => $collection->name, 'url' => route('products.index', ['collection_id' => $collection->id])];
            }
        } elseif ($request->filled('category')) {
            // Backward compatibility
            $category = \App\Models\Category::find($request->category);
            if ($category) {
                $breadcrumbs[] = ['name' => $category->name, 'url' => route('products.index', ['category' => $category->id])];
            }
        }

        $recentlyViewedProducts = collect();
        if (Auth::check()) {
            $recentIds = RecentProductView::getRecentProductIds(Auth::id(), 10, null);
            if ($recentIds->isNotEmpty()) {
                $recentlyViewedProducts = Product::whereIn('id', $recentIds)
                    ->availableForDisplay()
                    ->with(['shop', 'template'])
                    ->get()
                    ->sortBy(fn($p) => array_search($p->id, $recentIds->toArray()));
            }
        }

        return view('products.index', compact('products', 'collections', 'shops', 'breadcrumbs', 'recentlyViewedProducts'));
    }

    /**
     * Display the specified product.
     *
     * @param  string  $slug
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Request $request, $slug)
    {
        // Get product and require all display conditions (segment số: coi như id — dùng khi slug DB trống / link export)
        $product = Product::query()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug);
                if (ctype_digit((string) $slug)) {
                    $q->orWhere('id', (int) $slug);
                }
            })
            ->availableForDisplay()
            ->with(['shop', 'template.category', 'template.variants', 'variants', 'collections', 'approvedReviews' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }])
            ->firstOrFail();

        if (Auth::check()) {
            RecentProductView::recordView(Auth::id(), $product->id);
        }

        // Shop is available and active if we reach here
        $shopAvailable = true;

        $recentlyViewedProducts = collect();
        if (Auth::check()) {
            $recentIds = RecentProductView::getRecentProductIds(Auth::id(), 10, $product->id);
            if ($recentIds->isNotEmpty()) {
                $recentlyViewedProducts = Product::whereIn('id', $recentIds)
                    ->availableForDisplay()
                    ->with(['shop', 'template'])
                    ->get()
                    ->sortBy(fn($p) => array_search($p->id, $recentIds->toArray()));
            }
        }

        // Related products from the same category (for "You may also like" fallback / other sections)
        $relatedProducts = Product::whereHas('template', function ($q) use ($product) {
            $q->where('category_id', $product->template->category_id);
        })
            ->where('id', '!=', $product->id)
            ->availableForDisplay()
            ->with(['shop', 'template'])
            ->limit(8)
            ->get();

        // You may also: ưu tiên sản phẩm cùng collection; không có collection thì cùng category
        $collectionIds = $product->collections->pluck('id')->toArray();
        $youMayAlsoProducts = collect();
        if (!empty($collectionIds)) {
            $youMayAlsoProducts = Product::whereHas('collections', function ($q) use ($collectionIds) {
                $q->whereIn('collections.id', $collectionIds);
            })
                ->where('id', '!=', $product->id)
                ->availableForDisplay()
                ->with(['shop', 'template'])
                ->limit(8)
                ->get();
        }
        if ($youMayAlsoProducts->isEmpty() && $product->template && $product->template->category_id) {
            $youMayAlsoProducts = Product::whereHas('template', function ($q) use ($product) {
                $q->where('category_id', $product->template->category_id);
            })
                ->where('id', '!=', $product->id)
                ->availableForDisplay()
                ->with(['shop', 'template'])
                ->limit(8)
                ->get();
        }

        // Get breadcrumb data
        $breadcrumbs = [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Products', 'url' => route('products.index')]
        ];

        if ($product->template->category) {
            $breadcrumbs[] = ['name' => $product->template->category->name, 'url' => route('products.index', ['category' => $product->template->category->id])];
        }
        $breadcrumbs[] = ['name' => $product->name, 'url' => ''];

        // Get shipping zones that have rates for this product's category
        $categoryId = $product->template->category_id ?? null;
        $shippingZones = ShippingRate::getZonesForCategory($categoryId);

        $defaultZone = $shippingZones->first();
        $availableZones = $shippingZones;

        if ($availableZones->isEmpty()) {
            $availableZones = ShippingZone::active()->ordered()->get();
            if ($availableZones->isNotEmpty() && !$defaultZone) {
                $defaultZone = $availableZones->first();
            }
        }

        $this->trackTikTokViewContent($request, $product);

        $hasCompletedOrderForReview = false;
        $canSubmitReview = false;
        $userExistingReview = null;
        if (Auth::check()) {
            $userId = Auth::id();
            $hasCompletedOrderForReview = OrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('order', function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                        ->whereIn('status', ['completed', 'delivered']);
                })
                ->exists();
            $userExistingReview = Review::query()
                ->where('product_id', $product->id)
                ->where('user_id', $userId)
                ->latest()
                ->first();
            $canSubmitReview = $hasCompletedOrderForReview && !$userExistingReview;
        }

        // Review nổi bật của shop: ưu tiên sản phẩm khác; nếu không có thì cùng shop nhưng không trùng review đã list phía trên
        $shopSpotlightReviews = collect();
        if ($product->shop_id) {
            $alreadyListedReviewIds = $product->approvedReviews->pluck('id')->filter()->all();
            $shopSpotlightReviews = Review::query()
                ->approved()
                ->whereHas('product', function ($q) use ($product) {
                    $q->where('shop_id', $product->shop_id)
                        ->where('id', '!=', $product->id);
                })
                ->with(['product' => function ($q) {
                    $q->select('id', 'name', 'slug');
                }])
                ->orderByDesc('show_on_home')
                ->orderByDesc('rating')
                ->orderByDesc('created_at')
                ->limit(9)
                ->get();

            if ($shopSpotlightReviews->isEmpty()) {
                $shopSpotlightReviews = Review::query()
                    ->approved()
                    ->whereHas('product', function ($q) use ($product) {
                        $q->where('shop_id', $product->shop_id);
                    })
                    ->when($alreadyListedReviewIds !== [], fn ($q) => $q->whereNotIn('id', $alreadyListedReviewIds))
                    ->with(['product' => function ($q) {
                        $q->select('id', 'name', 'slug');
                    }])
                    ->orderByDesc('show_on_home')
                    ->orderByDesc('rating')
                    ->orderByDesc('created_at')
                    ->limit(9)
                    ->get();
            }
        }

        $sizeChartTable = ReferenceNailSizeChart::table();

        return view('products.show', compact(
            'product',
            'relatedProducts',
            'recentlyViewedProducts',
            'youMayAlsoProducts',
            'breadcrumbs',
            'shopAvailable',
            'shippingZones',
            'defaultZone',
            'availableZones',
            'categoryId',
            'hasCompletedOrderForReview',
            'canSubmitReview',
            'userExistingReview',
            'shopSpotlightReviews',
            'sizeChartTable'
        ));
    }

    /**
     * Customer submits a review for a purchased product.
     */
    public function storeReview(Request $request, string $slug)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $product = Product::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $hasCompletedOrderForReview = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->whereIn('status', ['completed', 'delivered']);
            })
            ->exists();
        if (!$hasCompletedOrderForReview) {
            return back()->with('error', 'You can submit a review only after completing an order that includes this product.');
        }

        $alreadyReviewed = Review::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->exists();
        if ($alreadyReviewed) {
            return back()->with('error', 'You have already submitted a review for this product.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:120',
            'review_text' => 'required|string|max:2000',
        ]);

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'customer_name' => (string) ($user->name ?? 'Customer'),
            'customer_email' => (string) ($user->email ?? ''),
            'rating' => (int) $validated['rating'],
            'title' => $validated['title'] ?? null,
            'review_text' => $validated['review_text'],
            'is_verified_purchase' => true,
            'is_approved' => true,
        ]);

        return back()->with('success', 'Thank you! Your review has been submitted successfully.');
    }

    /**
     * Calculate shipping cost for a product
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateShippingCost(Request $request)
    {
        $request->validate([
            'zone_id' => 'required|integer|exists:shipping_zones,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'quantity' => 'nullable|integer|min:1',
            'product_price' => 'nullable|numeric|min:0',
        ]);

        $zoneId = $request->input('zone_id');
        $categoryId = $request->input('category_id');
        $quantity = $request->input('quantity', 1);
        $productPrice = $request->input('product_price', 0);

        $shippingRate = ShippingRate::active()
            ->forZone($zoneId)
            ->forCategory($categoryId)
            ->ordered()
            ->get()
            ->first(function ($rate) use ($quantity, $productPrice) {
                return $rate->isApplicable($quantity, $productPrice);
            });

        if (!$shippingRate) {
            return response()->json([
                'success' => false,
                'message' => 'No shipping rate found for this zone and category',
                'shipping_cost' => 0,
            ]);
        }

        // Calculate shipping cost
        $shippingCostUSD = $shippingRate->calculateCost($quantity);

        // Get current currency and rate
        $currentCurrency = currency();
        $currentCurrencyRate = currency_rate() ?? 1.0;

        // Convert to current currency
        $shippingCost = $currentCurrency !== 'USD'
            ? \App\Services\CurrencyService::convertFromUSDWithRate($shippingCostUSD, $currentCurrency, $currentCurrencyRate)
            : $shippingCostUSD;

        // Get zone info
        $zone = ShippingZone::find($zoneId);

        return response()->json([
            'success' => true,
            'shipping_cost' => round($shippingCost, 2),
            'shipping_cost_usd' => round($shippingCostUSD, 2),
            'currency' => $currentCurrency,
            'zone_name' => $zone->name ?? 'Unknown',
            'rate_name' => $shippingRate->name,
            'first_item_cost' => $shippingRate->first_item_cost,
            'additional_item_cost' => $shippingRate->additional_item_cost,
        ]);
    }

    /**
     * Trả HTML fragment cho block "Recently Viewed". Guest gửi ids=1,2,3; user đăng nhập lấy từ DB.
     */
    public function recentlyViewedFragment(Request $request)
    {
        $limit = min(10, (int) $request->get('limit', 12));
        $products = collect();

        if (Auth::check()) {
            $recentIds = RecentProductView::getRecentProductIds(Auth::id(), $limit, (int) $request->get('exclude_id', 0) ?: null);
            if ($recentIds->isNotEmpty()) {
                $products = Product::whereIn('id', $recentIds)
                    ->availableForDisplay()
                    ->with(['shop', 'template'])
                    ->get()
                    ->sortBy(fn($p) => array_search($p->id, $recentIds->toArray()));
            }
        } else {
            $ids = $request->get('ids');
            $excludeId = (int) $request->get('exclude_id', 0);
            if ($ids) {
                $ids = is_array($ids) ? $ids : array_map('intval', array_filter(explode(',', $ids)));
                if ($excludeId > 0) {
                    $ids = array_values(array_filter($ids, fn($id) => $id !== $excludeId));
                }
                $ids = array_slice(array_unique($ids), 0, $limit);
                if (!empty($ids)) {
                    $products = Product::whereIn('id', $ids)
                        ->availableForDisplay()
                        ->with(['shop', 'template'])
                        ->get()
                        ->sortBy(fn($p) => array_search($p->id, $ids));
                }
            }
        }

        $html = view('products.partials.recently-viewed-fragment', [
            'products' => $products,
            'limit' => $limit,
        ])->render();

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function trackTikTokViewContent(Request $request, Product $product): void
    {
        /** @var TikTokEventsService $tikTok */
        $tikTok = app(TikTokEventsService::class);

        if (!$tikTok->enabled()) {
            return;
        }

        $user = Auth::user();

        $tikTok->track(
            'ViewContent',
            [
                'value' => round($product->price ?? $product->base_price ?? 0, 2),
                'currency' => 'USD',
                'content_type' => 'product',
                'content_id' => (string) $product->id,
                'content_name' => $product->name,
                'contents' => [[
                    'content_id' => (string) $product->id,
                    'content_type' => 'product',
                    'content_name' => $product->name,
                    'price' => round($product->price ?? $product->base_price ?? 0, 2),
                    'quantity' => 1,
                ]],
                'description' => optional($product->template)->description ?? $product->description,
            ],
            $request,
            [
                'email' => $user?->email,
                'phone' => $user?->phone,
                'external_id' => $user?->id,
            ],
            [
                'page' => [
                    'url' => $request->fullUrl(),
                ],
            ]
        );
    }
}
