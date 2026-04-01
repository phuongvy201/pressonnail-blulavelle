<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Category;
use App\Models\Collection;
use App\Models\GmcConfig;
use App\Services\GoogleMerchantCenterService;
use App\Services\OpenAIService;
use App\Services\VideoThumbnailService;
use App\Support\ReferenceNailSizeChart;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class ProductController extends Controller
{
    protected function getS3BaseUrl(): string
    {
        return 'https://s3.us-east-1.amazonaws.com/image.bluprinter/';
    }

    /**
     * Upload một file media (ảnh hoặc video) lên S3. Video thì tạo poster bằng FFmpeg.
     * Trả về URL string hoặc ['type'=>'video','url'=>...,'poster'=>...].
     */
    protected function uploadOneProductMediaFile($file, string $baseSlug, int $index, ?int $timestamp = null): string|array|null
    {
        if (!$file->isValid()) {
            return null;
        }
        try {
            $timestamp = $timestamp ?? time();
            $safeSlug = Str::slug($baseSlug);
            if ($safeSlug === '') {
                $safeSlug = 'product';
            }
            $ext = strtolower((string) $file->getClientOriginalExtension());
            $fileName = $safeSlug . '-' . $timestamp . '-' . str_pad((string) $index, 2, '0', STR_PAD_LEFT) . '.' . $ext;
            $filePath = Storage::disk('s3')->putFileAs('products', $file, $fileName);
            if (!$filePath) {
                return null;
            }
            $fileUrl = $this->getS3BaseUrl() . $filePath;

            if (VideoThumbnailService::isVideoFile($file)) {
                $thumbnailService = app(VideoThumbnailService::class);
                $posterUrl = null;
                $posterPath = $thumbnailService->generatePoster($file, 1);
                if ($posterPath) {
                    $posterFileName = pathinfo($fileName, PATHINFO_FILENAME) . '-poster.jpg';
                    $contents = Storage::disk('local')->get($posterPath);
                    if ($contents) {
                        Storage::disk('s3')->put('products/posters/' . $posterFileName, $contents);
                        $posterUrl = $this->getS3BaseUrl() . 'products/posters/' . $posterFileName;
                    }
                    $thumbnailService->deleteTempPoster($posterPath);
                }
                // Nếu không tạo được poster thì để null để frontend fallback về ảnh khác,
                // tránh dùng URL video (.mp4) làm src của <img>
                return ['type' => 'video', 'url' => $fileUrl, 'poster' => $posterUrl];
            }

            return $fileUrl;
        } catch (Exception $e) {
            Log::error('Error uploading product media', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Admin xem tất cả products; Seller xem products do mình tạo HOẶC thuộc shop của mình (kể cả khi admin tạo rồi gán shop)
        $productsQuery = Product::with([
            'template.category',
            'template.user',
            'user',
            'shop',
            'variants',
            'collections:id,name,slug',
        ]);

        if (!$user->hasRole('admin')) {
            $productsQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('shop', function ($shopQ) use ($user) {
                        $shopQ->where('user_id', $user->id);
                    });
            });
        }

        // Apply filters
        if ($request->filled('category_id')) {
            $productsQuery->whereHas('template', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->filled('template_id')) {
            $productsQuery->where('template_id', $request->template_id);
        }

        if ($request->filled('shop_id')) {
            $productsQuery->where('shop_id', $request->shop_id);
        }

        if ($request->filled('collection_id')) {
            $productsQuery->whereHas('collections', function ($q) use ($request) {
                $q->where('collections.id', $request->collection_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('template', function ($templateQuery) use ($search) {
                        $templateQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Get filter options for dropdowns
        $categories = Category::orderBy('name', 'asc')->get();

        $templatesQuery = ProductTemplate::orderBy('name', 'asc');
        if (!$user->hasRole('admin')) {
            $templatesQuery->where('user_id', $user->id);
        }
        $templates = $templatesQuery->get();

        $shops = null;
        if ($user->hasRole('admin')) {
            $shops = Shop::orderBy('shop_name', 'asc')->get();
        }

        // Giống CollectionController@index: admin xem tất cả; seller xem collection của shop
        // + collection do admin tạo và đã duyệt (để filter và bulk "Thêm vào collection").
        $collectionsQuery = Collection::orderBy('name', 'asc');
        if (!$user->hasRole('admin')) {
            $collectionsQuery->where(function ($q) use ($user) {
                if ($user->hasShop()) {
                    $q->where('shop_id', $user->shop->id);
                }

                $q->orWhere(function ($adminCollections) {
                    $adminCollections
                        ->whereHas('user.roles', function ($roleQuery) {
                            $roleQuery->where('name', 'admin');
                        })
                        ->where('admin_approved', true);
                });
            });
        }
        $collections = $collectionsQuery->get();

        // Apply default sorting
        $productsQuery->orderBy('created_at', 'desc');

        // Per-page selection
        $perPage = (int) $request->input('per_page', 12);
        $allowedPerPage = [12, 25, 50, 100, 200, 500];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 12;
        }

        // Paginate
        $products = $productsQuery->paginate($perPage)->withQueryString();

        // GMC/domain config không còn dùng theo domain
        $availableGmcConfigs = collect();

        return view('admin.products.index', compact('products', 'categories', 'templates', 'shops', 'collections', 'availableGmcConfigs', 'perPage'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();

        // Check if seller has shop (required for sellers)
        if ($user->hasRole('seller') && !$user->hasShop()) {
            return redirect()->route('seller.shop.create')
                ->with('warning', 'You need to create a shop first before adding products!');
        }

        // Get templates based on role
        if ($user->hasRole('admin')) {
            $templates = ProductTemplate::with(['category', 'attributes', 'variants'])
                ->orderBy('name', 'asc')
                ->get();
        } else {
            // Seller chỉ thấy templates của mình
            $templates = ProductTemplate::with(['category', 'attributes', 'variants'])
                ->where('user_id', $user->id)
                ->orderBy('name', 'asc')
                ->get();
        }

        // Get all shops for admin to assign products
        $shops = null;
        if ($user->hasRole('admin')) {
            $shops = Shop::with('user')
                ->orderBy('shop_name', 'asc')
                ->get();
        }

        return view('admin.products.create', compact('templates', 'shops'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            $request->validate([
                'template_id' => 'required|exists:product_templates,id',
                'name' => 'required|string|max:255',
                'price_type' => 'required|in:template,override,add',
                'price' => 'nullable|numeric',
                'list_price' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
                'quantity' => 'required|integer|min:0',
                'status' => 'required|in:active,inactive,draft',
                'shop_id' => $user->hasRole('admin') ? 'nullable|exists:shops,id' : 'nullable',
                'media.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,avif,mp4,mov,avi,webm,ogg|mimetypes:image/jpeg,image/pjpeg,image/png,image/gif,image/webp,image/avif,video/mp4,video/quicktime,video/x-msvideo,video/webm,video/ogg|max:10240',
                'variants' => 'nullable|array',
                'variants.*.variant_name' => 'nullable|string',
                'variants.*.variant_key' => 'nullable|string',
                'variants.*.attributes' => 'nullable|string',
                'variants.*.price' => 'nullable|numeric|min:0',
                'variants.*.list_price' => 'nullable|numeric|min:0',
                'variants.*.quantity' => 'nullable|integer|min:0',
            ]);

            // Check if seller has shop (required for sellers)
            if ($user->hasRole('seller') && !$user->hasShop()) {
                return redirect()->route('seller.shop.create')
                    ->with('warning', 'You need to create a shop first before adding products!');
            }

            $data = $request->all();
            $data['slug'] = $this->generateUniqueSlug($request->name);
            $data['sku'] = $this->generateUniqueSKU();
            $data['user_id'] = auth()->id(); // Set product owner

            // Set shop_id based on user role
            if ($user->hasRole('admin')) {
                // Admin can assign to any shop via form
                $data['shop_id'] = $request->shop_id;
            } elseif ($user->hasShop()) {
                // Seller uses their own shop
                $data['shop_id'] = $user->shop->id;
            }

            // Calculate final price based on price_type
            $template = ProductTemplate::find($request->template_id);

            if ($request->price_type === 'template') {
                // Use template price - save the actual template price to database
                $data['price'] = $template->base_price;
            } elseif ($request->price_type === 'override') {
                // Override with custom price
                $data['price'] = $request->price;
            } elseif ($request->price_type === 'add') {
                // Add to template price
                $addAmount = floatval($request->price ?? 0);
                $data['price'] = $template->base_price + $addAmount;
            }

            $data['list_price'] = $request->filled('list_price') ? $request->list_price : ($template->list_price ?? null);

            // Handle description logic
            $customDescription = trim($request->description ?? '');
            if (!empty($customDescription)) {
                // Seller provided custom description - use it
                $data['description'] = $customDescription;
            } else {
                // No custom description - use template description
                $data['description'] = $template->description;
            }

            Log::info('Price calculation', [
                'price_type' => $request->price_type,
                'template_price' => $template->base_price,
                'input_price' => $request->price,
                'final_price' => $data['price']
            ]);

            Log::info('Description logic', [
                'custom_description' => $request->description,
                'template_description' => $template->description,
                'final_description' => $data['description']
            ]);

            // Handle media upload to S3 (ảnh + video; video có poster từ FFmpeg)
            if ($request->hasFile('media')) {
                $mediaFiles = $request->file('media');
                $mediaOrder = $request->input('media_order');
                $orderedIndices = $mediaOrder ? explode(',', $mediaOrder) : null;

                if ($orderedIndices && count($orderedIndices) === count($mediaFiles)) {
                    $orderedFiles = [];
                    foreach ($orderedIndices as $index) {
                        $orderedFiles[] = $mediaFiles[(int)$index];
                    }
                    $mediaFiles = $orderedFiles;
                }

                $mediaUrls = [];
                $uploadTs = time();
                $i = 1;
                foreach ($mediaFiles as $file) {
                    $item = $this->uploadOneProductMediaFile($file, (string) $data['slug'], $i, $uploadTs);
                    if ($item !== null) {
                        $mediaUrls[] = $item;
                    }
                    $i++;
                }
                if (!empty($mediaUrls)) {
                    $data['media'] = $mediaUrls;
                }
            }

            // Auto-generate meta_keywords via AI (best-effort; never block product creation)
            try {
                if (empty($data['meta_keywords']) && !empty($data['name'])) {
                    /** @var OpenAIService $ai */
                    $ai = app(OpenAIService::class);
                    $keywords = $ai->extractKeywords((string) $data['name']);
                    if (!empty($keywords)) {
                        $data['meta_keywords'] = implode(', ', $keywords);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('AI keyword generation failed during product create (continuing).', [
                    'product_name' => $data['name'] ?? null,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            }

            $product = Product::create($data);

            // Note: Variants and shop products count are automatically handled by Product model's created event

            // Create product variants from template variants
            if ($request->has('variants')) {
                foreach ($request->variants as $variantData) {
                    $variantName = $variantData['variant_name'] ?? '';

                    // Get attributes from form or parse from variant_name
                    $attributes = [];

                    // Get attributes from template variant first (preferred method)
                    $templateVariant = \App\Models\TemplateVariant::where('template_id', $request->template_id)
                        ->where('variant_name', $variantName)
                        ->first();

                    if ($templateVariant && !empty($templateVariant->attributes)) {
                        $attributes = $templateVariant->attributes;
                    } else {
                        // Try to get attributes from form
                        if (isset($variantData['attributes']) && !empty($variantData['attributes'])) {
                            $attributes = is_string($variantData['attributes'])
                                ? json_decode($variantData['attributes'], true)
                                : $variantData['attributes'];
                        }

                        // If still no attributes, try to parse from variant_name (fallback)
                        if (empty($attributes)) {
                            // Common size patterns
                            $sizePatterns = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Small', 'Medium', 'Large', '11oz', '12oz', '15oz'];
                            $colorPatterns = ['Black', 'White', 'Red', 'Blue', 'Green', 'Yellow', 'Purple', 'Pink', 'Gray', 'Grey', 'Brown', 'Orange', 'Navy', 'Maroon', 'Teal'];

                            // Handle format like "Black/S" or "Black S" or "Black-S"
                            $variantName = str_replace(['/', '-'], ' ', $variantName);
                            $nameParts = array_filter(explode(' ', trim($variantName)));

                            foreach ($nameParts as $part) {
                                $part = trim($part);
                                if (in_array($part, $sizePatterns)) {
                                    $attributes['Size'] = $part;
                                } elseif (in_array($part, $colorPatterns)) {
                                    $attributes['Color'] = $part;
                                } else {
                                    // If it's not a common size/color, treat as additional attribute
                                    if (!isset($attributes['Material'])) {
                                        $attributes['Material'] = $part;
                                    } elseif (!isset($attributes['Style'])) {
                                        $attributes['Style'] = $part;
                                    }
                                }
                            }
                        }

                        // If still no attributes, create a generic one
                        if (empty($attributes)) {
                            $attributes['Variant'] = $variantName;
                        }
                    }

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'template_id' => $request->template_id,
                        'variant_name' => $variantName,
                        'attributes' => $attributes,
                        'price' => $variantData['price'] ?? null,
                        'list_price' => $variantData['list_price'] ?? $templateVariant?->list_price ?? null,
                        'sku' => 'SKU-' . strtoupper(Str::random(8)),
                        'quantity' => $variantData['quantity'] ?? 0,
                        'media' => null,
                    ]);
                }
            }

            return redirect()->route('admin.products.index')
                ->with('success', 'Product created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            // Database errors (like duplicate entry, foreign key constraints, etc.)
            $errorMessage = 'Database error occurred while creating the product.';

            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errorMessage = 'A product with similar information already exists. Please check the product name or try again.';
            } elseif (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $errorMessage = 'Invalid template or shop selected. Please check your selections.';
            } elseif (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                $errorMessage = 'Data integrity error. Please check all required fields are filled correctly.';
            }

            return back()->with('error', $errorMessage)->withInput();
        } catch (\Exception $e) {
            // Any other unexpected errors
            \Log::error('Product creation error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['media']),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'An unexpected error occurred while creating the product. Please try again or contact support if the problem persists.')->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load(['template', 'variants']);

        return view('admin.products.show', [
            'product' => $product,
            'sizeChartTable' => ReferenceNailSizeChart::table(),
        ]);
    }

    /**
     * Duplicate a product
     */
    public function duplicate(Product $product)
    {
        try {
            $user = auth()->user();

            // Check authorization (admin, product owner, or shop owner)
            if (!$product->canEdit($user)) {
                abort(403, 'Unauthorized action.');
            }

            // Load product with relationships
            $product->load(['variants']);

            // Create new product data
            $newProductData = [
                'template_id' => $product->template_id,
                'user_id' => $user->id,
                'shop_id' => $product->shop_id,
                'name' => $product->name . ' (Copy)',
                'slug' => $this->generateUniqueSlug($product->name . ' (Copy)'),
                'sku' => $this->generateUniqueSKU(),
                'price' => $product->price,
                'description' => $product->description,
                'meta_keywords' => $product->meta_keywords,
                'media' => $product->media, // Copy media array
                'quantity' => $product->quantity,
                'status' => 'draft', // Set to draft by default
            ];

            // Create the duplicated product
            $newProduct = Product::create($newProductData);

            // Duplicate variants if they exist
            if ($product->variants && $product->variants->count() > 0) {
                foreach ($product->variants as $variant) {
                    \App\Models\ProductVariant::create([
                        'template_id' => $product->template_id,
                        'product_id' => $newProduct->id,
                        'variant_name' => $variant->variant_name,
                        'attributes' => $variant->attributes,
                        'price' => $variant->price,
                        'quantity' => $variant->quantity,
                        'sku' => 'SKU-' . strtoupper(Str::random(8)), // Generate new unique SKU
                        'media' => $variant->media,
                    ]);
                }
            }

            // Note: Shop products count is automatically incremented by Product model's created event

            Log::info('Product duplicated', [
                'original_product_id' => $product->id,
                'new_product_id' => $newProduct->id,
                'user_id' => $user->id
            ]);

            return redirect()->route('admin.products.edit', $newProduct)
                ->with('success', 'Product duplicated successfully! You can now edit the duplicated product.');
        } catch (\Exception $e) {
            Log::error('Product duplication error: ' . $e->getMessage(), [
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.products.index')
                ->with('error', 'Failed to duplicate product: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $user = auth()->user();

        // Check authorization (admin, product owner, or shop owner)
        if (!$product->canEdit($user)) {
            abort(403, 'Unauthorized action.');
        }

        // Get all shops for admin to assign products
        $shops = null;
        if ($user->hasRole('admin')) {
            $shops = Shop::with('user')
                ->orderBy('shop_name', 'asc')
                ->get();
        }

        $product->load(['template.variants', 'variants']);
        return view('admin.products.edit', compact('product', 'shops'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        try {
            $user = auth()->user();

            // Check authorization (admin, product owner, or shop owner)
            if (!$product->canEdit($user)) {
                abort(403, 'Unauthorized action.');
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'nullable|numeric|min:0',
                'list_price' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
                'quantity' => 'required|integer|min:0',
                'status' => 'required|in:active,inactive,draft',
                'shop_id' => $user->hasRole('admin') ? 'nullable|exists:shops,id' : 'nullable',
                'media.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,avif,mp4,mov,avi,webm,ogg|mimetypes:image/jpeg,image/pjpeg,image/png,image/gif,image/webp,image/avif,video/mp4,video/quicktime,video/x-msvideo,video/webm,video/ogg|max:10240',
                'current_media_order' => 'nullable|array',
                'variants' => 'nullable|array',
                'variants.*.id' => 'nullable|exists:product_variants,id',
                'variants.*.variant_name' => 'nullable|string',
                'variants.*.price' => 'nullable|numeric|min:0',
                'variants.*.list_price' => 'nullable|numeric|min:0',
                'variants.*.quantity' => 'nullable|integer|min:0',
            ]);

            $data = $request->only([
                'name',
                'price',
                'list_price',
                'description',
                'quantity',
                'status',
                'shop_id',
            ]);

            // Chỉ tạo slug mới nếu tên sản phẩm thay đổi
            if ($request->name !== $product->name) {
                $data['slug'] = $this->generateUniqueSlug($request->name, $product->id);
            }
            // Nếu tên không đổi, giữ nguyên slug cũ (không thêm vào $data)

            // Preserve and reorder current media based on submitted order
            $existingMediaOrder = $request->input('current_media_order', []);
            $orderedExistingMedia = [];

            if (is_array($existingMediaOrder) && !empty($existingMediaOrder)) {
                foreach ($existingMediaOrder as $mediaUrl) {
                    $mediaUrl = trim($mediaUrl);
                    if (!empty($mediaUrl)) {
                        $orderedExistingMedia[] = $mediaUrl;
                    }
                }
            } else {
                // Fallback to current product media if no order provided
                if (is_array($product->media)) {
                    $orderedExistingMedia = $product->media;
                } elseif (!empty($product->media)) {
                    $orderedExistingMedia = is_string($product->media)
                        ? json_decode($product->media, true) ?? []
                        : (array) $product->media;
                }
            }

            // Handle uploaded media (ảnh + video; video có poster từ FFmpeg)
            if ($request->hasFile('media')) {
                $uploadTs = time();
                $startIndex = count($orderedExistingMedia) + 1;
                $i = 0;
                foreach ($request->file('media') as $file) {
                    $item = $this->uploadOneProductMediaFile($file, (string) ($data['slug'] ?? $product->slug ?? 'product'), $startIndex + $i, $uploadTs);
                    if ($item !== null) {
                        $orderedExistingMedia[] = $item;
                    }
                    $i++;
                }
            }

            if (!empty($orderedExistingMedia)) {
                // Re-index array to ensure clean JSON encoding
                $data['media'] = array_values($orderedExistingMedia);
            } else {
                $data['media'] = [];
            }

            $product->update($data);

            // Update product variants
            if ($request->has('variants')) {
                foreach ($request->variants as $variantData) {
                    if (isset($variantData['id']) && $variantData['id']) {
                        // Update existing variant
                        $variant = \App\Models\ProductVariant::where('id', $variantData['id'])
                            ->where('product_id', $product->id)
                            ->first();

                        if ($variant) {
                            $variant->update([
                                'price' => $variantData['price'] ?? $variant->price,
                                'list_price' => array_key_exists('list_price', $variantData) ? $variantData['list_price'] : $variant->list_price,
                                'quantity' => $variantData['quantity'] ?? $variant->quantity,
                            ]);
                        }
                    }
                }
            }

            return redirect()->route('admin.products.index')
                ->with('success', 'Product updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            // Database errors
            $errorMessage = 'Database error occurred while updating the product.';

            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errorMessage = 'A product with similar information already exists. Please check the product name or try again.';
            } elseif (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $errorMessage = 'Invalid data selected. Please check your selections.';
            } elseif (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                $errorMessage = 'Data integrity error. Please check all required fields are filled correctly.';
            }

            return back()->with('error', $errorMessage)->withInput();
        } catch (\Exception $e) {
            // Any other unexpected errors
            \Log::error('Product update error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'product_id' => $product->id,
                'request_data' => $request->except(['media']),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'An unexpected error occurred while updating the product. Please try again or contact support if the problem persists.')->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $user = auth()->user();

        // Check authorization (admin, product owner, or shop owner)
        if (!$product->canEdit($user)) {
            abort(403, 'Unauthorized action.');
        }

        // Decrement shop product count if product has shop
        if ($product->shop) {
            $product->shop->decrement('total_products');
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully!');
    }

    /**
     * Bulk delete products
     */
    public function bulkDelete(Request $request)
    {
        try {
            Log::info('Bulk delete started', [
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            $user = auth()->user();

            // Validate request data
            try {
                $request->validate([
                    'product_ids' => 'required|array',
                    'product_ids.*' => 'exists:products,id',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Bulk delete validation failed', [
                    'errors' => $e->errors()
                ]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid product IDs provided.',
                        'errors' => $e->errors()
                    ], 422);
                }

                throw $e;
            }

            $productIds = $request->product_ids;
            Log::info('Attempting to delete products', [
                'product_ids' => $productIds,
                'count' => count($productIds)
            ]);

            $products = Product::with('shop')->whereIn('id', $productIds)->get();

            if ($products->isEmpty()) {
                Log::warning('No products found for IDs', ['product_ids' => $productIds]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No products found with the provided IDs.'
                    ], 404);
                }

                return back()->with('error', 'No products found with the provided IDs.');
            }

            // Check authorization for each product
            $deletedCount = 0;
            $shopProductCounts = [];
            $errors = [];

            foreach ($products as $product) {
                try {
                    // Admin, product owner, or shop owner can delete
                    if ($product->canEdit($user)) {
                        // Track shop product counts
                        if ($product->shop_id) {
                            if (!isset($shopProductCounts[$product->shop_id])) {
                                $shopProductCounts[$product->shop_id] = 0;
                            }
                            $shopProductCounts[$product->shop_id]++;
                        }

                        $product->delete();
                        $deletedCount++;

                        Log::info('Product deleted successfully', [
                            'product_id' => $product->id,
                            'product_name' => $product->name
                        ]);
                    } else {
                        $errors[] = "No permission to delete product: {$product->name}";
                        Log::warning('User lacks permission to delete product', [
                            'product_id' => $product->id,
                            'user_id' => $user->id,
                            'product_user_id' => $product->user_id
                        ]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to delete product: {$product->name} - " . $e->getMessage();
                    Log::error('Failed to delete product', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Update shop product counts
            foreach ($shopProductCounts as $shopId => $count) {
                try {
                    $shop = \App\Models\Shop::find($shopId);
                    if ($shop) {
                        $shop->total_products = $shop->products()->count();
                        $shop->save();
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to update shop product count', [
                        'shop_id' => $shopId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $message = $deletedCount === 0
                ? 'No products were deleted. You may not have permission to delete the selected products.'
                : "{$deletedCount} product(s) deleted successfully! 🗑️";

            // Add error details to message if there were errors
            if (!empty($errors)) {
                $message .= "\nErrors: " . implode('; ', $errors);
            }

            $success = $deletedCount > 0;

            Log::info('Bulk delete completed', [
                'deleted_count' => $deletedCount,
                'total_requested' => count($productIds),
                'errors' => $errors
            ]);

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => $success,
                    'message' => $message,
                    'deleted_count' => $deletedCount,
                    'errors' => $errors
                ]);
            }

            // Return redirect for form submissions
            if ($deletedCount === 0) {
                return back()->with('error', $message);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Bulk delete failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            $message = 'An unexpected error occurred while deleting products. Please try again.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->with('error', $message);
        }
    }

    /**
     * Gắn hàng loạt sản phẩm đã chọn vào một collection (manual).
     */
    public function bulkAddToCollection(Request $request)
    {
        $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ]);

        $user = auth()->user();
        $collection = Collection::findOrFail($request->collection_id);

        if (! $collection->canEdit($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền chỉnh sửa collection này.',
            ], 403);
        }

        if ($collection->type !== 'manual') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể thêm sản phẩm vào collection kiểu thủ công (Manual).',
            ], 422);
        }

        $products = Product::whereIn('id', $request->product_ids)->get();
        $allowedIds = [];
        foreach ($products as $product) {
            if ($product->canEdit($user)) {
                $allowedIds[] = $product->id;
            }
        }

        $allowedIds = array_values(array_unique($allowedIds));

        if ($allowedIds === []) {
            return response()->json([
                'success' => false,
                'message' => 'Không có sản phẩm hợp lệ hoặc bạn không có quyền thêm các sản phẩm đã chọn.',
            ], 422);
        }

        $collection->products()->syncWithoutDetaching($allowedIds);

        $skipped = count($request->product_ids) - count($allowedIds);
        $msg = 'Đã thêm '.count($allowedIds).' sản phẩm vào collection «'.$collection->name.'».';
        if ($skipped > 0) {
            $msg .= ' ('.$skipped.' sản phẩm bỏ qua do không có quyền.)';
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'attached' => count($allowedIds),
            'skipped' => $skipped,
        ]);
    }

    /**
     * Generate a unique slug for the product
     * 
     * @param string $name Product name
     * @param int|null $excludeProductId Product ID to exclude from uniqueness check (for updates)
     * @return string Unique slug
     */
    private function generateUniqueSlug($name, $excludeProductId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        // Check if slug already exists (excluding current product if updating)
        $query = Product::where('slug', $slug);
        if ($excludeProductId) {
            $query->where('id', '!=', $excludeProductId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;

            // Rebuild query for next check
            $query = Product::where('slug', $slug);
            if ($excludeProductId) {
                $query->where('id', '!=', $excludeProductId);
            }
        }

        return $slug;
    }

    /**
     * Generate a unique SKU for a product
     * Format: PRD-{random 8 characters}
     * 
     * @return string Unique SKU
     */
    private function generateUniqueSKU()
    {
        do {
            $sku = 'PRD-' . strtoupper(Str::random(8));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Preview product data that will be sent to GMC (for debugging)
     */
    public function previewGMCData(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
            ]);

            $product = Product::with(['template.category', 'shop', 'variants'])
                ->findOrFail($request->product_id);

            // Check authorization (admin, product owner, or shop owner)
            $user = auth()->user();
            if (!$product->canEdit($user)) {
                abort(403, 'Unauthorized');
            }

            // Get current domain from request
            $currentDomain = $request->getHost();
            $currentDomain = preg_replace('/^www\./', '', $currentDomain);

            // Get target country from request or use default
            $targetCountry = strtoupper($request->input('target_country', 'GB'));

            // Get GMC config for current domain and target country
            $gmcConfig = GmcConfig::getConfigForDomainAndCountry($currentDomain, $targetCountry);

            if (!$gmcConfig) {
                return response()->json([
                    'success' => false,
                    'message' => "Không tìm thấy cấu hình GMC cho domain '{$currentDomain}' và thị trường '{$targetCountry}'. Vui lòng cấu hình GMC trước."
                ], 400);
            }

            // Prepare product data
            $productData = $this->prepareProductForGMC($product, $gmcConfig, $currentDomain);

            if (!$productData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product does not have required data (name, price, image)'
                ], 400);
            }

            // Use service to prepare final API format
            try {
                $gmcService = GoogleMerchantCenterService::fromConfig($gmcConfig);
                $apiData = $gmcService->prepareProductData($productData);
                $apiEndpoint = $gmcService->getApiEndpoint();

                return response()->json([
                    'success' => true,
                    'api_endpoint' => $apiEndpoint,
                    'product_data' => $apiData,
                    'raw_product_data' => $productData,
                    'formatted_json' => json_encode($apiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                ]);
            } catch (\Exception $e) {
                // If service not configured, still return prepared data
                return response()->json([
                    'success' => true,
                    'message' => 'GMC service not configured, showing prepared data only',
                    'product_data' => $productData,
                    'formatted_json' => json_encode($productData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'error' => $e->getMessage()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error preparing product data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Feed selected products to Google Merchant Center
     * Uploads directly via API or generates XML feed
     */
    public function feedToGMC(Request $request)
    {
        try {
            $user = auth()->user();

            // Validate request
            $request->validate([
                'product_ids' => 'required|array',
                'product_ids.*' => 'exists:products,id',
                'method' => 'nullable|in:api,xml', // api or xml
                'target_country' => 'required|string|size:2', // US, GB, VN, etc.
            ]);

            $productIds = $request->product_ids;
            $method = $request->input('method', 'api'); // Default to API
            $targetCountry = strtoupper($request->target_country);

            // Get products with relationships first to determine domain
            $productsQuery = Product::with(['template.category', 'shop', 'variants'])
                ->whereIn('id', $productIds);

            if (!$user->hasRole('admin')) {
                $productsQuery->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereHas('shop', function ($shopQ) use ($user) {
                            $shopQ->where('user_id', $user->id);
                        });
                });
            }

            $products = $productsQuery->get();

            if ($products->isEmpty()) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No products found or you do not have permission to access these products.'
                    ], 404);
                }
                return back()->with('error', 'No products found or you do not have permission to access these products.');
            }

            // Get domain from current request host (where admin is accessing from)
            // This ensures we use the correct domain for the GMC config
            $currentDomain = $request->getHost();
            // Remove www. if present
            $currentDomain = preg_replace('/^www\./', '', $currentDomain);

            // Get GMC config for domain and target country
            $gmcConfig = GmcConfig::getConfigForDomainAndCountry($currentDomain, $targetCountry);

            if (!$gmcConfig) {
                $errorMessage = "Không tìm thấy cấu hình GMC cho domain '{$currentDomain}' và thị trường '{$targetCountry}'. Vui lòng cấu hình GMC trước.";

                Log::warning('GMC Config not found', [
                    'domain' => $currentDomain,
                    'target_country' => $targetCountry,
                    'request_host' => $request->getHost()
                ]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 400);
                }
                return back()->with('error', $errorMessage);
            }

            // Log domain being used
            Log::info('GMC Feed - Domain determined', [
                'domain_used' => $currentDomain,
                'target_country' => $targetCountry,
                'request_host' => $request->getHost(),
                'gmc_config_id' => $gmcConfig->id,
                'gmc_config_name' => $gmcConfig->name,
                'product_count' => $products->count()
            ]);

            // Use API method if requested and configured
            if ($method === 'api') {
                try {
                    // Create GMC service with config from database
                    $gmcService = GoogleMerchantCenterService::fromConfig($gmcConfig);

                    // Prepare products data for API - use current domain
                    $productsData = [];
                    foreach ($products as $product) {
                        $productData = $this->prepareProductForGMC($product, $gmcConfig, $currentDomain);
                        if ($productData) {
                            $productsData[] = $productData;
                        }
                    }

                    if (empty($productsData)) {
                        throw new \Exception('No valid products to upload. Products must have name, price, and image.');
                    }

                    // Batch upload products
                    $results = $gmcService->batchInsertProducts($productsData);

                    // Log successful batch upload
                    Log::info('GMC Batch upload completed from admin panel', [
                        'user_id' => auth()->id(),
                        'user_email' => auth()->user()->email ?? 'N/A',
                        'total_products' => $results['total'],
                        'success_count' => $results['success_count'],
                        'failed_count' => $results['failed_count'],
                        'product_ids' => $request->input('product_ids', []),
                        'results' => $results
                    ]);

                    // Return JSON response for AJAX requests
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => $results['success_count'] > 0,
                            'message' => "Uploaded {$results['success_count']} of {$results['total']} products to Google Merchant Center",
                            'results' => $results
                        ]);
                    }

                    // Return redirect with results
                    $message = "Successfully uploaded {$results['success_count']} of {$results['total']} products to Google Merchant Center";
                    if ($results['failed_count'] > 0) {
                        $message .= ". {$results['failed_count']} products failed to upload.";
                    }

                    return back()->with('success', $message)->with('gmc_results', $results);
                } catch (\Exception $e) {
                    Log::error('GMC API upload failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Fallback to XML if API fails
                    if (str_contains($e->getMessage(), 'not configured') || str_contains($e->getMessage(), 'credentials')) {
                        $errorMessage = 'Google Merchant Center API is not configured. Please configure GMC_MERCHANT_ID and GMC_CREDENTIALS_PATH in .env file. Falling back to XML download.';
                        Log::warning($errorMessage);

                        // Fall through to XML generation
                        $method = 'xml';
                    } else {
                        throw $e;
                    }
                }
            }

            // Generate XML feed (fallback or if explicitly requested)
            if ($method === 'xml') {
                $xml = $this->generateGMCXML($products, $gmcConfig, $currentDomain);

                // Return XML response
                return response($xml, 200)
                    ->header('Content-Type', 'application/xml; charset=utf-8')
                    ->header('Content-Disposition', 'attachment; filename="gmc_feed_' . date('Y-m-d_His') . '.xml"');
            }
        } catch (\Exception $e) {
            Log::error('GMC Feed failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            $message = 'An error occurred while processing GMC feed. Please try again.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->with('error', $message);
        }
    }

    /**
     * Prepare product data for Google Merchant Center API
     */
    private function prepareProductForGMC(Product $product, GmcConfig $gmcConfig, string $currentDomain): ?array
    {
        // Skip products without required data
        if (!$product->name || !$product->price) {
            return null;
        }

        // Use current domain to build base URL
        $scheme = request()->getScheme(); // http or https
        $baseUrl = $scheme . '://' . $currentDomain;

        // Ensure baseUrl doesn't end with slash
        $baseUrl = rtrim($baseUrl, '/');

        $media = $product->getEffectiveMedia();
        $primaryImage = !empty($media) ? $media[0] : null;

        // Convert media URL to string if it's an array
        if (is_array($primaryImage)) {
            $primaryImage = $primaryImage['url'] ?? $primaryImage['path'] ?? reset($primaryImage) ?? null;
        }

        if (!$primaryImage) {
            return null; // GMC requires image
        }

        // Ensure image URL is absolute (starts with http:// or https://)
        if ($primaryImage && !preg_match('/^https?:\/\//', $primaryImage)) {
            // If relative URL, make it absolute using baseUrl
            $primaryImage = $baseUrl . '/' . ltrim($primaryImage, '/');
        }

        // Get product URL - use shop domain
        $productUrl = $baseUrl . '/products/' . ($product->slug ?? $product->id);

        // Get description
        $description = $product->getEffectiveDescription();
        $description = strip_tags($description);
        $description = Str::limit($description, 5000); // GMC limit

        // Get category
        $category = $product->template->category->name ?? 'Other';
        $googleCategory = $this->mapToGoogleCategory($category);

        // Availability
        $availability = ($product->quantity > 0 || $product->variants->where('quantity', '>', 0)->count() > 0)
            ? 'in stock'
            : 'out of stock';

        // Get SKU (use as offer_id)
        $offerId = $product->sku ?? 'PRD-' . $product->id;

        // Brand is always Bluprinter
        $brand = 'Bluprinter';

        // Additional images - ensure all are absolute URLs
        $additionalImages = [];
        if (count($media) > 1) {
            $additionalImages = array_slice($media, 1, 10); // GMC allows up to 10 additional images
            $additionalImages = array_map(function ($image) use ($baseUrl) {
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['path'] ?? reset($image)) : $image;

                // Ensure absolute URL
                if ($imageUrl && !preg_match('/^https?:\/\//', $imageUrl)) {
                    $imageUrl = $baseUrl . '/' . ltrim($imageUrl, '/');
                }

                return $imageUrl;
            }, $additionalImages);
            $additionalImages = array_filter($additionalImages);
        }

        // Get country and language from GMC config
        $targetCountry = $gmcConfig->target_country;
        $currency = GmcConfig::getCurrencyForCountry($targetCountry);
        $contentLanguage = $gmcConfig->content_language;

        // Convert product price from USD to target currency
        $productPriceUSD = (float)($product->price ?? 0);
        $productPrice = $this->convertProductPrice($productPriceUSD, $currency, $gmcConfig);

        // Ensure price is valid and not empty
        if (empty($productPrice) || $productPrice <= 0) {
            Log::warning('GMC Feed: Product has invalid price', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'original_price' => $product->price,
                'converted_price' => $productPrice
            ]);
            return null; // Skip products without valid price
        }

        // Shipping: only send country, no price
        $shipping = [
            [
                'country' => $targetCountry
            ]
        ];

        // Base product data
        $productData = [
            'offer_id' => $offerId,
            'title' => Str::limit($product->name, 150),
            'description' => $description,
            'link' => $productUrl,
            'image_link' => $primaryImage,
            'price' => number_format($productPrice, 2, '.', ''),
            'currency' => $currency,
            'availability' => $availability,
            'condition' => 'new',
            'brand' => $brand,
            'google_product_category' => $googleCategory,
            'product_type' => $category,
            'mpn' => $offerId,
            'content_language' => $contentLanguage,
            'target_country' => $targetCountry,
            'additional_image_links' => array_values($additionalImages),
            'shipping' => $shipping,
        ];

        // Only add gender, color, and age_group for Clothing category
        $isClothing = stripos($category, 'clothing') !== false ||
            stripos($category, 'apparel') !== false ||
            stripos($category, 'Clothing') !== false ||
            stripos($category, 'Apparel') !== false;

        if ($isClothing) {
            // Get age_group, color, gender from product attributes or use defaults
            // These are required for apparel/clothing products
            $ageGroup = $product->age_group ?? $product->template->age_group ?? 'adult';
            $gender = $product->gender ?? $product->template->gender ?? 'unisex';

            // For clothing products, always use "Black" as default color
            $color = 'Black';

            // Validate age_group values (newborn, infant, toddler, kids, adult)
            $validAgeGroups = ['newborn', 'infant', 'toddler', 'kids', 'adult'];
            if (!in_array(strtolower($ageGroup), $validAgeGroups)) {
                $ageGroup = 'adult'; // Default to adult if invalid
            }

            // Validate gender values (male, female, unisex)
            $validGenders = ['male', 'female', 'unisex'];
            if (!in_array(strtolower($gender), $validGenders)) {
                $gender = 'unisex'; // Default to unisex if invalid
            }

            $productData['age_group'] = strtolower($ageGroup);
            $productData['color'] = $color;
            $productData['gender'] = strtolower($gender);
            $productData['size_system'] = 'us';
            $productData['size_type'] = 'regular';
        }

        return $productData;
    }

    /**
     * Generate XML feed in Google Merchant Center format
     */
    private function generateGMCXML($products, GmcConfig $gmcConfig, string $currentDomain)
    {
        // Use current domain to build base URL
        $scheme = request()->getScheme(); // http or https
        $baseUrl = $scheme . '://' . $currentDomain;

        $currency = GmcConfig::getCurrencyForCountry($gmcConfig->target_country);
        $targetCountry = $gmcConfig->target_country;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
        $xml .= '  <channel>' . "\n";
        $xml .= '    <title>Bluprinter Products Feed</title>' . "\n";
        $xml .= '    <link>' . $baseUrl . '</link>' . "\n";
        $xml .= '    <description>Product feed for Google Merchant Center</description>' . "\n";

        foreach ($products as $product) {
            // Skip products without required data
            if (!$product->name || !$product->price) {
                continue;
            }

            $media = $product->getEffectiveMedia();
            $primaryImage = !empty($media) ? $media[0] : null;

            // Convert media URL to string if it's an array
            if (is_array($primaryImage)) {
                $primaryImage = $primaryImage['url'] ?? $primaryImage['path'] ?? reset($primaryImage) ?? null;
            }

            // Get product URL
            $productUrl = $baseUrl . '/products/' . ($product->slug ?? $product->id);

            // Get description
            $description = $product->getEffectiveDescription();
            $description = strip_tags($description);
            $description = htmlspecialchars($description, ENT_XML1, 'UTF-8');
            $description = Str::limit($description, 5000); // GMC limit

            // Get category
            $category = $product->template->category->name ?? 'Other';
            $googleCategory = $this->mapToGoogleCategory($category);

            // Availability
            $availability = ($product->quantity > 0 || $product->variants->where('quantity', '>', 0)->count() > 0)
                ? 'in stock'
                : 'out of stock';

            // Get SKU
            $sku = $product->sku ?? 'PRD-' . $product->id;

            // Brand is always Bluprinter
            $brand = 'Bluprinter';

            $xml .= '    <item>' . "\n";
            $xml .= '      <g:id>' . htmlspecialchars($sku, ENT_XML1, 'UTF-8') . '</g:id>' . "\n";
            $xml .= '      <title>' . htmlspecialchars(Str::limit($product->name, 150), ENT_XML1, 'UTF-8') . '</title>' . "\n";
            $xml .= '      <description><![CDATA[' . $description . ']]></description>' . "\n";
            $xml .= '      <link>' . htmlspecialchars($productUrl, ENT_XML1, 'UTF-8') . '</link>' . "\n";

            if ($primaryImage) {
                $xml .= '      <g:image_link>' . htmlspecialchars($primaryImage, ENT_XML1, 'UTF-8') . '</g:image_link>' . "\n";
            }

            // Convert product price from USD to target currency for XML feed
            $productPriceUSD = (float)$product->price;
            $productPrice = $this->convertProductPrice($productPriceUSD, $currency, $gmcConfig);

            $xml .= '      <g:price>' . number_format($productPrice, 2, '.', '') . ' ' . $currency . '</g:price>' . "\n";
            $xml .= '      <g:availability>' . $availability . '</g:availability>' . "\n";
            $xml .= '      <g:condition>new</g:condition>' . "\n";
            $xml .= '      <g:brand>' . htmlspecialchars($brand, ENT_XML1, 'UTF-8') . '</g:brand>' . "\n";
            $xml .= '      <g:google_product_category>' . htmlspecialchars($googleCategory, ENT_XML1, 'UTF-8') . '</g:google_product_category>' . "\n";
            $xml .= '      <g:product_type>' . htmlspecialchars($category, ENT_XML1, 'UTF-8') . '</g:product_type>' . "\n";
            $xml .= '      <g:mpn>' . htmlspecialchars($sku, ENT_XML1, 'UTF-8') . '</g:mpn>' . "\n";

            // Add additional images if available
            if (count($media) > 1) {
                $additionalImages = array_slice($media, 1, 10); // GMC allows up to 10 additional images
                foreach ($additionalImages as $image) {
                    $imageUrl = is_array($image) ? ($image['url'] ?? $image['path'] ?? reset($image)) : $image;
                    if ($imageUrl && $imageUrl !== $primaryImage) {
                        $xml .= '      <g:additional_image_link>' . htmlspecialchars($imageUrl, ENT_XML1, 'UTF-8') . '</g:additional_image_link>' . "\n";
                    }
                }
            }

            // Add size_system and size_type for Clothing category
            $isClothing = stripos($category, 'clothing') !== false ||
                stripos($category, 'apparel') !== false ||
                stripos($category, 'Clothing') !== false ||
                stripos($category, 'Apparel') !== false;

            if ($isClothing) {
                $xml .= '      <g:size_system>us</g:size_system>' . "\n";
                $xml .= '      <g:size_type>regular</g:size_type>' . "\n";
            }

            $xml .= '    </item>' . "\n";
        }

        $xml .= '  </channel>' . "\n";
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Show form to delete product from GMC
     */
    public function showDeleteFromGMCForm(Request $request)
    {
        // Get all unique domains from GMC configs
        $domains = GmcConfig::where('is_active', true)
            ->distinct()
            ->orderBy('domain')
            ->pluck('domain')
            ->toArray();

        // Get all GMC configs grouped by domain
        $gmcConfigsByDomain = GmcConfig::where('is_active', true)
            ->orderBy('domain')
            ->orderBy('target_country')
            ->get()
            ->groupBy('domain');

        // Country labels
        $countryLabels = [
            'US' => 'United States (USD)',
            'GB' => 'United Kingdom (GBP)',
            'VN' => 'Vietnam (VND)',
            'CA' => 'Canada (CAD)',
            'AU' => 'Australia (AUD)',
            'DE' => 'Germany (EUR)',
            'FR' => 'France (EUR)',
            'IT' => 'Italy (EUR)',
            'ES' => 'Spain (EUR)',
        ];

        return view('admin.products.delete-from-gmc', compact('domains', 'gmcConfigsByDomain', 'countryLabels'));
    }

    /**
     * Delete a single product from Google Merchant Center by offer_id
     * Simple API endpoint for Postman testing
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteProductFromGMC(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'offer_id' => 'required|string',
                'domain' => 'nullable|string', // Optional: domain to determine GMC config
                'target_country' => 'nullable|string|size:2', // Optional: target country (US, GB, VN, etc.)
            ]);

            $offerId = $request->offer_id;
            $domain = $request->input('domain');
            $targetCountry = strtoupper($request->input('target_country', 'US'));

            // Get domain from request if not provided
            if (!$domain) {
                $domain = $request->getHost();
                // Remove port if present
                $domain = preg_replace('/:\d+$/', '', $domain);
                // Remove www. if present
                $domain = preg_replace('/^www\./', '', $domain);
            }

            // Get GMC config for domain and target country
            $gmcConfig = GmcConfig::getConfigForDomainAndCountry($domain, $targetCountry);

            if (!$gmcConfig) {
                Log::warning('GMC Config not found for delete', [
                    'domain' => $domain,
                    'target_country' => $targetCountry,
                    'offer_id' => $offerId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Không tìm thấy cấu hình GMC cho domain '{$domain}' và thị trường '{$targetCountry}'. Vui lòng cấu hình GMC trước.",
                    'domain' => $domain,
                    'target_country' => $targetCountry,
                    'offer_id' => $offerId
                ], 400);
            }

            // Create GMC service with config from database
            $gmcService = GoogleMerchantCenterService::fromConfig($gmcConfig);

            // Delete product from GMC
            $deleteResult = $gmcService->deleteProduct($offerId);

            // Log the operation
            Log::info('GMC Delete Product via API', [
                'offer_id' => $offerId,
                'domain' => $domain,
                'target_country' => $targetCountry,
                'success' => $deleteResult['success'],
                'message' => $deleteResult['message'] ?? null,
                'error' => $deleteResult['error'] ?? null
            ]);

            if ($deleteResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sản phẩm đã được xóa thành công khỏi Google Merchant Center',
                    'offer_id' => $offerId,
                    'domain' => $domain,
                    'target_country' => $targetCountry,
                    'result' => $deleteResult
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa sản phẩm khỏi Google Merchant Center',
                    'offer_id' => $offerId,
                    'domain' => $domain,
                    'target_country' => $targetCountry,
                    'error' => $deleteResult['error'] ?? 'Unknown error',
                    'result' => $deleteResult
                ], 400);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('GMC Delete Product API Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa sản phẩm khỏi Google Merchant Center: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete product(s) from Google Merchant Center
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function deleteFromGMC(Request $request)
    {
        try {
            $user = auth()->user();

            // Validate request
            $request->validate([
                'product_ids' => 'required|array',
                'product_ids.*' => 'exists:products,id',
                'target_country' => 'required|string|size:2', // US, GB, VN, etc.
            ]);

            $productIds = $request->product_ids;
            $targetCountry = strtoupper($request->target_country);

            // Get current domain
            $currentDomain = $request->getHost();
            // Remove port if present
            $currentDomain = preg_replace('/:\d+$/', '', $currentDomain);

            // Get GMC config for current domain and target country
            $gmcConfig = GmcConfig::getConfigForDomainAndCountry($currentDomain, $targetCountry);

            if (!$gmcConfig) {
                $errorMessage = "Không tìm thấy cấu hình GMC cho domain '{$currentDomain}' và thị trường '{$targetCountry}'. Vui lòng cấu hình GMC trước.";

                Log::warning('GMC Config not found for delete', [
                    'domain' => $currentDomain,
                    'target_country' => $targetCountry,
                    'request_host' => $request->getHost()
                ]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 400);
                }
                return back()->with('error', $errorMessage);
            }

            // Get products
            $products = Product::whereIn('id', $productIds)->get();

            if ($products->isEmpty()) {
                $errorMessage = 'Không tìm thấy sản phẩm nào để xóa.';

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 404);
                }
                return back()->with('error', $errorMessage);
            }

            // Create GMC service with config from database
            $gmcService = GoogleMerchantCenterService::fromConfig($gmcConfig);

            $results = [
                'success' => [],
                'failed' => [],
                'total' => $products->count(),
                'success_count' => 0,
                'failed_count' => 0
            ];

            // Delete each product from GMC
            foreach ($products as $product) {
                // Get offer_id (SKU or PRD-{id})
                $offerId = $product->sku ?? 'PRD-' . $product->id;

                try {
                    $deleteResult = $gmcService->deleteProduct($offerId);

                    if ($deleteResult['success']) {
                        $results['success'][] = [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'offer_id' => $offerId,
                            'message' => $deleteResult['message']
                        ];
                        $results['success_count']++;
                    } else {
                        $results['failed'][] = [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'offer_id' => $offerId,
                            'error' => $deleteResult['error'] ?? 'Unknown error',
                            'message' => $deleteResult['message']
                        ];
                        $results['failed_count']++;
                    }
                } catch (\Exception $e) {
                    Log::error('GMC Delete Product Error', [
                        'product_id' => $product->id,
                        'offer_id' => $offerId,
                        'error' => $e->getMessage()
                    ]);

                    $results['failed'][] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'offer_id' => $offerId,
                        'error' => $e->getMessage(),
                        'message' => 'Failed to delete product from Google Merchant Center'
                    ];
                    $results['failed_count']++;
                }

                // Add small delay to avoid rate limiting
                usleep(100000); // 0.1 second delay
            }

            // Log batch delete summary
            Log::info('GMC Batch delete completed from admin panel', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email ?? 'N/A',
                'total_products' => $results['total'],
                'success_count' => $results['success_count'],
                'failed_count' => $results['failed_count'],
                'product_ids' => $productIds,
                'results' => $results
            ]);

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => $results['success_count'] > 0,
                    'message' => "Đã xóa {$results['success_count']} / {$results['total']} sản phẩm khỏi Google Merchant Center",
                    'results' => $results
                ]);
            }

            // Return redirect with results
            $message = "Đã xóa thành công {$results['success_count']} / {$results['total']} sản phẩm khỏi Google Merchant Center";
            if ($results['failed_count'] > 0) {
                $message .= ". {$results['failed_count']} sản phẩm không thể xóa.";
            }

            return back()->with('success', $message)->with('gmc_delete_results', $results);
        } catch (\Exception $e) {
            Log::error('GMC Delete failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Lỗi khi xóa sản phẩm khỏi Google Merchant Center: ' . $e->getMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Convert product price from USD to target currency
     * Products are stored in USD, converted using default rates per currency.
     */
    private function convertProductPrice(float $usdPrice, string $targetCurrency, ?GmcConfig $gmcConfig = null): float
    {
        if ($targetCurrency === 'USD') {
            return $usdPrice;
        }

        $conversionRate = $this->getDefaultCurrencyConversionRate($targetCurrency);

        if ($conversionRate) {
            return $usdPrice * $conversionRate;
        }

        // Fallback: return USD price if conversion rate not found
        Log::warning('Product price currency conversion rate not found', [
            'target_currency' => $targetCurrency,
            'usd_price' => $usdPrice,
            'gmc_config_id' => $gmcConfig?->id
        ]);

        return $usdPrice;
    }

    /**
     * Get default currency conversion rate from USD
     */
    private function getDefaultCurrencyConversionRate(string $targetCurrency): ?float
    {
        // Default currency conversion rates (fallback)
        $conversionRates = [
            'GBP' => 0.79,  // 1 USD = 0.79 GBP
            'VND' => 25000, // 1 USD = 25000 VND
            'EUR' => 0.92,  // 1 USD = 0.92 EUR
            'CAD' => 1.35,  // 1 USD = 1.35 CAD
            'AUD' => 1.52,  // 1 USD = 1.52 AUD
        ];

        return $conversionRates[$targetCurrency] ?? null;
    }

    /**
     * Convert shipping cost from USD to target currency
     */
    private function convertShippingCurrency(float $usdAmount, string $targetCurrency, string $targetCountry, ?GmcConfig $gmcConfig = null): float
    {
        if ($targetCurrency === 'USD') {
            return $usdAmount;
        }

        $conversionRate = $this->getDefaultCurrencyConversionRate($targetCurrency);

        if ($conversionRate) {
            return $usdAmount * $conversionRate;
        }

        // Fallback: return USD amount if conversion rate not found
        Log::warning('Shipping currency conversion rate not found', [
            'target_currency' => $targetCurrency,
            'usd_amount' => $usdAmount,
            'gmc_config_id' => $gmcConfig?->id
        ]);

        return $usdAmount;
    }

    /**
     * Map category to Google Product Category
     * Returns a basic category ID - you should customize this based on your actual categories
     */
    private function mapToGoogleCategory($categoryName)
    {
        // Basic mapping - you should expand this based on your actual categories
        $mapping = [
            'Clothing' => '212',
            'Apparel' => '1604',
            'Accessories' => '166',
            'Electronics' => '172',
            'Home & Garden' => '533',
            'Sports & Outdoors' => '888',
            'Toys & Games' => '220',
            'Books' => '266',
            'Health & Beauty' => '376',
        ];

        // Try to find exact match
        foreach ($mapping as $key => $value) {
            if (stripos($categoryName, $key) !== false) {
                return $value;
            }
        }

        // Default category: Other
        return '783';
    }

    /**
     * Export products to Meta Commerce Catalog format (CSV/Excel)
     */
    public function exportToMeta(Request $request)
    {
        $user = auth()->user();

        // Nếu có product_ids từ request (selected products), chỉ export những sản phẩm đó
        if ($request->filled('product_ids')) {
            $productIds = is_array($request->product_ids)
                ? $request->product_ids
                : explode(',', $request->product_ids);

            $productsQuery = Product::with(['template.category', 'template.user', 'user', 'shop', 'variants', 'collections'])
                ->whereIn('id', $productIds);

            if (!$user->hasRole('admin')) {
                $productsQuery->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereHas('shop', function ($shopQ) use ($user) {
                            $shopQ->where('user_id', $user->id);
                        });
                });
            }

            $products = $productsQuery->get();
        } else {
            // Nếu không có product_ids, export tất cả (giữ nguyên logic cũ để tương thích)
            $productsQuery = Product::with(['template.category', 'template.user', 'user', 'shop', 'variants', 'collections']);

            if (!$user->hasRole('admin')) {
                $productsQuery->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereHas('shop', function ($shopQ) use ($user) {
                            $shopQ->where('user_id', $user->id);
                        });
                });
            }

            // Apply filters from request
            if ($request->filled('category_id')) {
                $productsQuery->whereHas('template', function ($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }

            if ($request->filled('template_id')) {
                $productsQuery->where('template_id', $request->template_id);
            }

            if ($request->filled('shop_id')) {
                $productsQuery->where('shop_id', $request->shop_id);
            }

            if ($request->filled('collection_id')) {
                $productsQuery->whereHas('collections', function ($q) use ($request) {
                    $q->where('collections.id', $request->collection_id);
                });
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $productsQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('template', function ($templateQuery) use ($search) {
                            $templateQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Only export active products
            $productsQuery->where('status', 'active');

            $products = $productsQuery->get();
        }

        // Get base URL for product links
        $baseUrl = config('app.url');

        // Prepare CSV data
        $csvData = [];

        // Header - PHẢI CHÍNH XÁC 100% về chữ hoa/thường, dấu gạch dưới, dấu ngoặc vuông
        $header = [
            'id',
            'title',
            'description',
            'availability',
            'condition',
            'price',
            'link',
            'image_link',
            'brand',
            'google_product_category',
            'fb_product_category',
            'quantity_to_sell_on_facebook',
            'sale_price',
            'sale_price_effective_date',
            'item_group_id',
            'gender',
            'color',
            'size',
            'age_group',
            'material',
            'pattern',
            'shipping',
            'shipping_weight',
            'video[0].url',
            'video[0].tag[0]',
            'gtin',
            'product_tags[0]',
            'product_tags[1]',
            'style[0]'
        ];

        $csvData[] = $header;

        // Helper function to validate and format fields
        $formatField = function ($value, $maxLength = null) {
            if (empty($value)) return '';
            $value = trim((string) $value);
            if ($maxLength) {
                $value = mb_substr($value, 0, $maxLength);
            }
            return $value;
        };

        // Helper function to validate gender
        $validateGender = function ($gender) {
            $gender = strtolower(trim($gender));
            $validGenders = ['female', 'male', 'unisex'];
            return in_array($gender, $validGenders) ? $gender : '';
        };

        // Helper function to validate age_group
        $validateAgeGroup = function ($ageGroup) {
            $ageGroup = strtolower(trim($ageGroup));
            $validAgeGroups = ['newborn', 'infant', 'toddler', 'kids', 'teen', 'adult', 'all ages'];
            return in_array($ageGroup, $validAgeGroups) ? $ageGroup : '';
        };

        // Process each product
        foreach ($products as $product) {
            // Get product media
            $media = $product->getEffectiveMedia();
            $imageLink = !empty($media) ? (is_string($media[0]) ? $media[0] : ($media[0]['url'] ?? $media[0]['path'] ?? '')) : '';

            // Convert relative URL to absolute URL
            if ($imageLink && !filter_var($imageLink, FILTER_VALIDATE_URL)) {
                if (strpos($imageLink, '/storage/') === 0 || strpos($imageLink, '/') === 0) {
                    $imageLink = $baseUrl . $imageLink;
                } else {
                    $imageLink = $baseUrl . '/storage/' . $imageLink;
                }
            }

            // Validate image_link is required
            if (empty($imageLink)) {
                continue; // Skip products without images
            }

            // Get product link
            $productLink = $baseUrl . '/products/' . ($product->slug ?? $product->id);

            // Get price (in USD format for Meta) - Bắt buộc
            $basePrice = $product->price ?? $product->template->base_price ?? 0;
            if ($basePrice <= 0) {
                continue; // Skip products without price
            }
            $price = number_format($basePrice, 2, '.', '') . ' USD';

            // Get description (strip HTML tags) - Bắt buộc, giới hạn 9999 ký tự, chỉ chữ thường
            $description = strip_tags($product->description ?? $product->template->description ?? '');
            if (empty($description)) {
                $description = $product->name; // Fallback to name if no description
            }
            $description = str_replace(["\r\n", "\r", "\n"], ' ', $description);
            $description = mb_strtolower($description, 'UTF-8'); // Chỉ dùng chữ thường
            $description = $formatField($description, 9999);

            // Get title - Bắt buộc, giới hạn 200 ký tự
            $title = $formatField($product->name, 200);

            // Get brand - Bắt buộc, giới hạn 100 ký tự
            $brand = $formatField('Bluprinter', 100);

            // Get category IDs - mặc định theo yêu cầu nail box
            $googleCategory = $product->google_product_category
                ?? 'health & beauty > beauty > nail care > artificial nails & accessories > manicure tool sets';
            $fbCategory = $product->fb_product_category
                ?? 'health & beauty > beauty > nail care > artificial nails & accessories > manicure tool sets';

            // Get quantity - ưu tiên từ product, sau đó từ quantity
            $quantity = max(1, (int)($product->quantity_to_sell_on_facebook ?? $product->quantity ?? 100));

            // Get video if exists - chỉ lấy video file, không lấy video player URL
            $videoUrl = '';
            $videoTag = '';

            // Danh sách các định dạng video được hỗ trợ bởi Meta
            $supportedVideoFormats = [
                '.3g2',
                '.3gp',
                '.3gpp',
                '.asf',
                '.avi',
                '.dat',
                '.divx',
                '.dv',
                '.f4v',
                '.flv',
                '.gif',
                '.m2ts',
                '.m4v',
                '.mkv',
                '.mod',
                '.mov',
                '.mp4',
                '.mpe',
                '.mpeg',
                '.mpeg4',
                '.mpg',
                '.mts',
                '.nsv',
                '.ogm',
                '.ogv',
                '.qt',
                '.tod',
                '.ts',
                '.vob',
                '.wmv'
            ];

            // Danh sách các domain video player cần loại bỏ (YouTube, Vimeo, etc.)
            $videoPlayerDomains = [
                'youtube.com',
                'youtu.be',
                'vimeo.com',
                'dailymotion.com',
                'facebook.com',
                'instagram.com',
                'tiktok.com',
                'twitch.tv'
            ];

            if (!empty($media)) {
                foreach ($media as $mediaItem) {
                    $mediaUrl = is_string($mediaItem) ? $mediaItem : ($mediaItem['url'] ?? $mediaItem['path'] ?? '');

                    if (empty($mediaUrl)) {
                        continue;
                    }

                    // Kiểm tra xem URL có phải là video player không
                    $isVideoPlayer = false;
                    foreach ($videoPlayerDomains as $domain) {
                        if (str_contains(strtolower($mediaUrl), $domain)) {
                            $isVideoPlayer = true;
                            break;
                        }
                    }

                    // Bỏ qua nếu là video player URL
                    if ($isVideoPlayer) {
                        continue;
                    }

                    // Kiểm tra định dạng video được hỗ trợ
                    $hasSupportedFormat = false;
                    $lowerUrl = strtolower($mediaUrl);
                    foreach ($supportedVideoFormats as $format) {
                        if (str_ends_with($lowerUrl, $format)) {
                            $hasSupportedFormat = true;
                            break;
                        }
                    }

                    // Nếu có định dạng được hỗ trợ, xử lý URL
                    if ($hasSupportedFormat) {
                        if (!filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
                            // Relative URL - cần thêm base URL
                            if (strpos($mediaUrl, '/storage/') === 0 || strpos($mediaUrl, '/') === 0) {
                                $videoUrl = $baseUrl . $mediaUrl;
                            } else {
                                $videoUrl = $baseUrl . '/storage/' . $mediaUrl;
                            }
                        } else {
                            // Absolute URL - sử dụng trực tiếp
                            $videoUrl = $mediaUrl;
                        }
                        break; // Chỉ lấy video đầu tiên tìm thấy
                    }
                }
            }

            // Get product tags/collections - giới hạn 110 ký tự mỗi tag
            $productTags = $product->collections->pluck('name')->take(2)->toArray();
            $productTag0 = $formatField($productTags[0] ?? '', 110);
            $productTag1 = $formatField($productTags[1] ?? '', 110);

            // Mỗi sản phẩm chỉ tạo 1 dòng, không quan tâm đến variants
            // item_group_id để trống theo yêu cầu

            // Product ID - Bắt buộc, giới hạn 100 ký tự, nên dùng SKU
            $productId = '';
            if ($product->sku) {
                $productId = $formatField($product->sku, 100);
            } else {
                $productId = $formatField('PROD_' . $product->id, 100);
            }

            // Set các giá trị mặc định
            $gender = $product->gender ? $validateGender($product->gender) : 'female';
            $color = ''; // Để trống theo yêu cầu
            $size = 'S (15/11/12/11/9mm)'; // Mặc định theo yêu cầu
            $ageGroup = $product->age_group ? $validateAgeGroup($product->age_group) : 'adult';
            $material = $product->material ?? 'stainless steel';
            $pattern = $product->pattern ?? 'graphic';

            // Create single row for product
            $row = [
                $productId, // id (Bắt buộc, max 100)
                $title, // title (Bắt buộc, max 200)
                $description, // description (Bắt buộc, max 9999, chữ thường)
                $quantity > 0 ? 'in stock' : 'out of stock', // availability (Bắt buộc)
                'new', // condition (Bắt buộc)
                $price, // price (Bắt buộc)
                $productLink, // link (Bắt buộc)
                $imageLink, // image_link (Bắt buộc)
                $brand, // brand (Bắt buộc, max 100)
                $googleCategory, // google_product_category
                $fbCategory, // fb_product_category
                max(1, (int)($product->quantity_to_sell_on_facebook ?? $quantity)), // quantity_to_sell_on_facebook (ưu tiên từ product, mặc định 100)
                '', // sale_price
                '', // sale_price_effective_date
                '', // item_group_id (để trống theo yêu cầu)
                $gender, // gender (mặc định female)
                $color, // color (để trống theo yêu cầu)
                $size, // size (mặc định theo yêu cầu)
                $ageGroup, // age_group (mặc định adult)
                $material, // material (mặc định stainless steel)
                $pattern, // pattern (mặc định graphic)
                $formatField($product->shipping ?? 'US::USPS:5.99 USD', 200), // shipping (mặc định US::USPS:5.99 USD)
                $formatField($product->shipping_weight ?? '200g', 50), // shipping_weight (mặc định 200g)
                $videoUrl, // video[0].url
                '', // video[0].tag[0] (bỏ trống theo yêu cầu)
                '', // gtin (bỏ trống theo yêu cầu)
                '', // product_tags[0] (bỏ trống theo yêu cầu)
                '', // product_tags[1] (bỏ trống theo yêu cầu)
                '' // style[0] (bỏ trống theo yêu cầu)
            ];

            $csvData[] = $row;
        }

        // Generate filename
        $filename = 'meta_products_export_' . date('Y-m-d_His') . '.csv';

        // Build CSV content
        $csvContent = '';

        // Add BOM for UTF-8 (Excel compatibility)
        $csvContent .= "\xEF\xBB\xBF";

        // Write CSV data
        foreach ($csvData as $row) {
            // Escape fields that contain commas, quotes, or newlines
            $escapedRow = array_map(function ($field) {
                // Convert to string and handle null/empty
                $field = (string) $field;

                if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false || strpos($field, "\r") !== false) {
                    return '"' . str_replace('"', '""', $field) . '"';
                }
                return $field;
            }, $row);

            $csvContent .= implode(',', $escapedRow) . "\n";
        }

        // Return CSV file download
        return Response::make($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }
}
