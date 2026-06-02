<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Create a new product via API
     */
    public function create(Request $request)
    {
        // Public endpoint: no API token required

        // Validate request data - support multipart form data with URLs
        $validationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_id' => 'required|exists:product_templates,id',
            'price' => 'nullable|numeric|min:0',
            'shop_id' => 'nullable|exists:shops,id',
            'quantity' => 'nullable|integer|min:0',
        ];

        // Handle media_urls from form data
        $mediaUrls = [];

        // Try different ways to get media URLs
        if ($request->has('media_urls') && is_array($request->input('media_urls'))) {
            // If it's already an array
            $mediaUrls = $request->input('media_urls');
        } else {
            // Try indexed format
            $urlIndex = 0;
            while ($request->has("media_urls[$urlIndex]")) {
                $mediaUrls[] = $request->input("media_urls[$urlIndex]");
                $urlIndex++;
            }
        }


        // Validate media URLs
        if (empty($mediaUrls)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one media URL is required',
                'errors' => [
                    'media_urls' => ['At least one media URL is required']
                ]
            ], 422)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        }

        // Validate each URL
        foreach ($mediaUrls as $index => $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid URL format',
                    'errors' => [
                        "media_urls.$index" => ['The URL must be a valid URL']
                    ]
                ], 422)
                    ->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
            }
        }
        // No upper-limit for media URLs (can send any number)

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        }

        try {
            // Get template with all relationships
            $template = ProductTemplate::with(['category', 'attributes', 'variants'])
                ->findOrFail($request->template_id);

            // Generate unique slug
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Handle media - download and upload URLs to S3
            $processedMediaUrls = [];

            foreach ($mediaUrls as $index => $url) {
                try {
                    // Download file from URL
                    $fileContent = file_get_contents($url);
                    if ($fileContent === false) {
                        Log::warning("Failed to download file from URL: $url");
                        continue;
                    }

                    // Get file info
                    $urlInfo = parse_url($url);
                    $pathInfo = pathinfo($urlInfo['path'] ?? '');
                    $extension = $pathInfo['extension'] ?? 'jpg';

                    // Determine content type
                    $contentType = $this->getContentTypeFromExtension($extension);

                    // Generate unique filename
                    $fileName = time() . '_' . Str::random(10) . '_' . $index . '.' . $extension;

                    // Determine folder based on content type
                    $folder = strpos($contentType, 'video/') === 0 ? 'products/videos' : 'products/images';
                    $filePath = $folder . '/' . $fileName;

                    // Upload to S3
                    $uploaded = Storage::disk('s3')->put($filePath, $fileContent);

                    if ($uploaded) {
                        $imageUrl = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $filePath;
                        $processedMediaUrls[] = $imageUrl;
                        Log::info("Successfully uploaded URL to S3", [
                            'original_url' => $url,
                            's3_url' => $imageUrl,
                            'file_path' => $filePath
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to process URL: $url - " . $e->getMessage());
                    continue;
                }
            }

            // Determine shop_id with priority order (no token):
            // 1. Explicit shop_id in request
            // 2. Template's shop_id
            // 3. System default shop from config (fallback 1)
            $shopId = $request->shop_id
                ?? $template->shop_id
                ?? config('api.default_shop_id', 1);

            // Prepare product data - copy thông tin từ template (giống hmtik)
            $productData = [
                'name' => $request->name,
                'slug' => $slug,
                'template_id' => $request->template_id,
                'shop_id' => $shopId,
                'status' => 'active',
                'created_by' => 'api',

                // Copy từ template nếu không được cung cấp
                'description' => $request->description ?? $template->description,
                'price' => $request->price ?? $template->base_price,

                // Media: Ưu tiên media mới upload, fallback về template media
                'media' => !empty($processedMediaUrls) ? $processedMediaUrls : ($template->media ?? []),

                // Quantity mặc định
                'quantity' => $request->quantity ?? 999,
            ];

            // Create product
            $product = Product::create($productData);

            // Copy variants from template (giống hmtik)
            $createdVariants = [];
            if ($template->variants && $template->variants->count() > 0) {
                Log::info("Found {$template->variants->count()} variants in template", [
                    'template_id' => $template->id
                ]);

                foreach ($template->variants as $templateVariant) {
                    try {
                        // Generate unique SKU từ variant_name và product_id
                        // TemplateVariant không có sku field, nên tạo từ variant_name
                        $baseSku = Str::slug($templateVariant->variant_name);
                        if (empty($baseSku)) {
                            $baseSku = 'variant';
                        }
                        $uniqueSku = $baseSku . '-' . $product->id;

                        // Ensure SKU is truly unique by checking database
                        $counter = 1;
                        while (\App\Models\ProductVariant::where('sku', $uniqueSku)->exists()) {
                            $uniqueSku = $baseSku . '-' . $product->id . '-' . $counter;
                            $counter++;
                        }

                        $variantData = [
                            'product_id' => $product->id,
                            'template_id' => $template->id,
                            'variant_name' => $templateVariant->variant_name,
                            'attributes' => $templateVariant->attributes ?? [],
                            'sku' => $uniqueSku, // Truly unique SKU
                            'price' => $templateVariant->price ?? $template->base_price ?? 0,
                            'quantity' => $request->quantity ?? 999,
                            'media' => $templateVariant->media ?? $template->media ?? [],
                        ];

                        $variant = \App\Models\ProductVariant::create($variantData);
                        $createdVariants[] = [
                            'id' => $variant->id,
                            'variant_name' => $variant->variant_name,
                            'attributes' => $variant->attributes,
                            'sku' => $variant->sku,
                            'price' => $variant->price,
                            'quantity' => $variant->quantity,
                        ];

                        Log::info("Created product variant", [
                            'variant_id' => $variant->id,
                            'product_id' => $product->id,
                            'sku' => $variant->sku
                        ]);
                    } catch (\Exception $e) {
                        Log::error("Failed to create variant from template", [
                            'template_variant_id' => $templateVariant->id,
                            'product_id' => $product->id,
                            'error' => $e->getMessage()
                        ]);
                        // Continue với variant tiếp theo thay vì dừng lại
                        continue;
                    }
                }
            } else {
                Log::info("Template has no variants", [
                    'template_id' => $template->id
                ]);
            }

            // No token usage tracking (public endpoint)

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product_id' => $product->id,
                'product_url' => route('products.show', $product->slug),
            ], 201)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        }
    }

    /**
     * Chi tiết sản phẩm theo ID (tương thích API cũ).
     */
    public function show(int $id)
    {
        $product = Product::query()
            ->availableForDisplay()
            ->with($this->detailRelations())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transformDetail($product),
        ]);
    }

    /**
     * Chi tiết sản phẩm theo slug (PWA / mobile).
     */
    public function showBySlug(string $slug)
    {
        $product = Product::query()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug);
                if (ctype_digit((string) $slug)) {
                    $q->orWhere('id', (int) $slug);
                }
            })
            ->availableForDisplay()
            ->with($this->detailRelations())
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $this->transformDetail($product),
        ]);
    }

    /**
     * Danh sách sản phẩm hiển thị trên storefront.
     */
    public function index(Request $request)
    {
        $perPage = min(48, max(1, (int) $request->get('per_page', 18)));
        $products = $this->buildCatalogQuery($request)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $products->getCollection()
                ->map(fn (Product $product) => $this->transformListItem($product))
                ->values(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    private function buildCatalogQuery(Request $request)
    {
        $query = Product::with(['shop', 'template.category', 'variants'])
            ->availableForDisplay();

        if ($request->filled('collection_id')) {
            $query->whereHas('collections', function ($q) use ($request) {
                $q->where('collections.id', $request->collection_id);
            });
        } elseif ($request->filled('category')) {
            $query->whereHas('template', function ($q) use ($request) {
                $q->where('category_id', $request->category);
            });
        }

        if ($request->filled('shop')) {
            $query->where('shop_id', $request->shop);
        }

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

        $sortBy = $request->get('sort', 'newest');
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
            default:
                $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    private function detailRelations(): array
    {
        return [
            'shop',
            'template.category',
            'variants',
            'collections',
            'approvedReviews' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
        ];
    }

    private function transformListItem(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => (float) $product->getEffectivePrice(),
            'list_price' => (float) ($product->list_price ?? $product->template?->list_price ?? 0),
            'primary_image' => $this->resolvePrimaryImageUrl($product),
            'category_id' => $product->template?->category_id,
            'category_name' => $product->template?->category?->name,
            'average_rating' => round($product->getAverageRating(), 1),
            'reviews_count' => $product->getTotalReviews(),
            'in_stock' => $product->hasStock(),
            'url' => route('products.show', ['slug' => $product->slug]),
            'shop' => $product->shop ? [
                'id' => $product->shop->id,
                'name' => $product->shop->name,
            ] : null,
        ];
    }

    private function transformDetail(Product $product): array
    {
        return [
            ...$this->transformListItem($product),
            'description' => $product->getEffectiveDescription(),
            'media' => $this->transformMediaList($product),
            'quantity' => (int) $product->quantity,
            'variants' => $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'variant_name' => $variant->variant_name,
                'attributes' => $variant->attributes ?? [],
                'sku' => $variant->sku,
                'price' => (float) $variant->getFinalPrice(),
                'list_price' => (float) ($variant->list_price ?? 0),
                'quantity' => (int) $variant->quantity,
                'media' => $this->transformMediaList($product, is_array($variant->media) ? $variant->media : []),
            ])->values(),
            'collections' => $product->collections->map(fn ($collection) => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $collection->slug,
            ])->values(),
            'reviews' => $product->approvedReviews->map(fn ($review) => [
                'id' => $review->id,
                'customer_name' => $review->customer_name,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'review_text' => $review->review_text,
                'image_url' => $review->image_url,
                'is_verified_purchase' => (bool) $review->is_verified_purchase,
                'created_at' => $review->created_at?->toIso8601String(),
            ])->values(),
            'rating_breakdown' => $product->getRatingBreakdown(),
            'created_at' => $product->created_at?->toIso8601String(),
        ];
    }

    /**
     * URL ảnh đại diện (ưu tiên webp).
     */
    private function resolvePrimaryImageUrl(Product $product): ?string
    {
        foreach ($product->getEffectiveMedia() as $item) {
            $type = $this->resolveMediaType($item);
            if ($type === 'video') {
                if (is_array($item)) {
                    $poster = trim((string) ($item['poster'] ?? ''));
                    if ($poster !== '') {
                        return $poster;
                    }
                }
                continue;
            }
            $url = $this->resolveMediaUrl($item, 'image');
            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>|null  $media
     * @return array<int, array{url: string, alt: string, type: string}>
     */
    private function transformMediaList(Product $product, ?array $media = null): array
    {
        $items = [];
        foreach ($media ?? $product->getEffectiveMedia() as $index => $item) {
            $type = $this->resolveMediaType($item);
            $url = $this->resolveMediaUrl($item, $type);
            if ($url === null) {
                continue;
            }
            $items[] = [
                'url' => $url,
                'alt' => $this->resolveMediaAlt($product, $item, (int) $index),
                'type' => $type,
            ];
        }

        return $items;
    }

    private function resolveMediaType(mixed $mediaItem): string
    {
        if (is_array($mediaItem)) {
            $type = strtolower(trim((string) ($mediaItem['type'] ?? '')));
            if ($type === 'video') {
                return 'video';
            }
        }

        if (is_string($mediaItem) && $this->looksLikeVideoUrl($mediaItem)) {
            return 'video';
        }

        return 'image';
    }

    private function resolveMediaUrl(mixed $mediaItem, ?string $type = null): ?string
    {
        $type ??= $this->resolveMediaType($mediaItem);

        if (is_string($mediaItem)) {
            $url = trim($mediaItem);

            return $url !== '' ? $url : null;
        }

        if (! is_array($mediaItem)) {
            return null;
        }

        if ($type === 'video') {
            foreach (['url', 'path'] as $key) {
                $url = trim((string) ($mediaItem[$key] ?? ''));
                if ($url !== '') {
                    return $url;
                }
            }

            return null;
        }

        foreach (['webp', 'url', 'path'] as $key) {
            $url = trim((string) ($mediaItem[$key] ?? ''));
            if ($url !== '') {
                return $url;
            }
        }

        return null;
    }

    private function looksLikeVideoUrl(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return (bool) preg_match('/\.(mp4|webm|mov|avi|ogg|ogv|m4v)$/i', $path);
    }

    private function resolveMediaAlt(Product $product, mixed $mediaItem, int $index): string
    {
        if (is_array($mediaItem)) {
            $alt = trim((string) ($mediaItem['keywords'] ?? $mediaItem['alt'] ?? ''));
            if ($alt !== '') {
                return Str::limit($alt, 500, '');
            }
        }

        return $product->altForMediaItem($mediaItem, (string) ($product->name ?? ''), $index);
    }

    /**
     * Validate API token
     */
    // API token validation removed (public endpoints)

    /**
     * Get content type from file extension
     */
    private function getContentTypeFromExtension($extension)
    {
        $contentTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'avi' => 'video/avi',
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
        ];

        return $contentTypes[strtolower($extension)] ?? 'image/jpeg';
    }
}
