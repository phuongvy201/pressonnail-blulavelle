<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use App\Services\VideoThumbnailService;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\AfterChunk;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use Illuminate\Http\File;

class ProductsImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnError,
    SkipsOnFailure,
    SkipsEmptyRows,
    WithChunkReading,
    WithEvents
{
    protected $errors = [];
    protected $successCount = 0;
    protected $user;
    protected $importedProducts = []; // Store products that need variants created
    protected $variantsCreated = false; // Flag to ensure variants are only created once
    protected $progressKey = null; // Cache key for progress tracking
    protected $totalRows = 0; // Total rows to process
    protected $processedRows = 0; // Rows processed so far

    /**
     * Constructor
     */
    public function __construct($user = null, $progressKey = null)
    {
        $this->user = $user ?? auth()->user();
        $this->progressKey = $progressKey ?? 'import_progress_' . uniqid();
    }

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Update total rows dynamically if not set yet (fallback for when countRows fails)
            // This ensures progress bar works even if initial count failed
            if ($this->totalRows == 0 && $this->processedRows > 0) {
                // Estimate will be done in updateProgress() method
                // Just trigger update here
                $this->updateProgress();
            }

            // Skip empty rows - check if required fields are empty
            $templateId = isset($row['template_id']) ? trim((string)$row['template_id']) : '';
            $productName = isset($row['product_name']) ? trim((string)$row['product_name']) : '';

            // If both template_id and product_name are empty, skip this row (empty row)
            if (empty($templateId) && empty($productName)) {
                $this->processedRows++;
                $this->updateProgress();
                return null;
            }

            // Validate required fields - if one is empty but the other is not, it's an error
            if (empty($templateId)) {
                $this->errors[] = "Row " . ($productName ?: 'Unknown') . ": template_id is required";
                $this->processedRows++;
                $this->updateProgress();
                return null;
            }

            if (empty($productName)) {
                $this->errors[] = "Row with template_id {$templateId}: product_name is required";
                $this->processedRows++;
                $this->updateProgress();
                return null;
            }

            // Check if template exists and load with variants
            $template = ProductTemplate::with('variants')->find($templateId);
            if (!$template) {
                $this->errors[] = "Row " . ($productName ?: 'Unknown') . ": Template ID {$templateId} not found";
                $this->processedRows++;
                $this->updateProgress();
                return null;
            }

            // Check ownership: Only admin can use any template, seller can only use their own
            if (!$this->user->hasRole('admin') && $template->user_id !== $this->user->id) {
                $this->errors[] = "Row " . ($productName ?: 'Unknown') . ": You don't have permission to use Template ID {$templateId}";
                $this->processedRows++;
                $this->updateProgress();
                return null;
            }

            // Calculate final price: base_price + additional price (if provided)
            $finalPrice = $template->base_price;
            if (!empty($row['price'])) {
                $finalPrice = $template->base_price + floatval($row['price']);
            }

            // Use custom description if provided, otherwise use template description
            $description = !empty($row['description']) ? $row['description'] : $template->description;

            // Collect and upload media to S3 (up to 8 images + 1 video)
            $mediaUrls = [];

            // Process images (image_1 to image_8) - one at a time with delay to avoid rate limiting
            for ($i = 1; $i <= 8; $i++) {
                $imageKey = 'image_' . $i;
                if (!empty($row[$imageKey])) {
                    $imageUrl = trim($row[$imageKey]);
                    // Validate URL format
                    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $this->errors[] = "Row " . ($productName ?: 'Unknown') . ": Invalid URL for image_{$i}: {$imageUrl}";
                        continue;
                    }

                    // Add delay between image downloads to avoid rate limiting (except for first image)
                    // Use random delay (1-2 seconds) to avoid pattern detection by CDN
                    if ($i > 1) {
                        $delay = rand(1000000, 2000000); // Random 1-2 seconds in microseconds
                        usleep($delay);
                    }

                    // Retry image download up to 5 times with exponential backoff
                    $s3Url = null;
                    $maxImageRetries = 5;
                    for ($retry = 1; $retry <= $maxImageRetries; $retry++) {
                        $s3Url = $this->downloadAndUploadToS3($imageUrl, 'products', $retry, $maxImageRetries);
                        if ($s3Url) {
                            break; // Success, exit retry loop
                        }

                        // If not last retry, wait before retrying with exponential backoff + random jitter
                        if ($retry < $maxImageRetries) {
                            $baseDelay = pow(2, $retry) * 1000000; // Exponential backoff: 2s, 4s, 8s, 16s
                            $jitter = rand(500000, 1500000); // Random 0.5-1.5s jitter to avoid pattern
                            $delay = $baseDelay + $jitter;
                            Log::info("Retrying image download ({$retry}/{$maxImageRetries}) for: {$productName}, image_{$i}, waiting " . round($delay / 1000000, 2) . "s");
                            usleep($delay);
                        }
                    }

                    if ($s3Url) {
                        $mediaUrls[] = $s3Url;
                        Log::info("Successfully uploaded image_{$i} to S3 for product: {$productName}");
                    } else {
                        // Log warning but don't fail the entire import - will use template media as fallback
                        Log::warning("Failed to upload image_{$i} to S3 after {$maxImageRetries} attempts for product: {$productName}, URL: " . Str::limit($imageUrl, 100));
                        $this->errors[] = "Row " . ($productName ?: 'Unknown') . ": Failed to upload image_{$i} to S3 after {$maxImageRetries} attempts (URL: " . Str::limit($imageUrl, 50) . ")";
                    }
                }
            }

            // Process video - with retry mechanism
            if (!empty($row['video_url'])) {
                $videoUrl = trim($row['video_url']);
                // Validate URL format
                if (!filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                    $this->errors[] = "Row " . ($productName ?: 'Unknown') . ": Invalid URL for video: {$videoUrl}";
                } else {
                    // Add delay before video download with random jitter
                    $delay = rand(1000000, 2000000); // Random 1-2 seconds
                    usleep($delay);

                    // Retry video download up to 5 times with exponential backoff
                    $videoMediaItem = null;
                    $maxVideoRetries = 5;
                    for ($retry = 1; $retry <= $maxVideoRetries; $retry++) {
                        $videoMediaItem = $this->downloadUploadVideoAndPosterToS3($videoUrl, $retry, $maxVideoRetries);
                        if ($videoMediaItem) {
                            break; // Success, exit retry loop
                        }

                        // If not last retry, wait before retrying with exponential backoff + random jitter
                        if ($retry < $maxVideoRetries) {
                            $baseDelay = pow(2, $retry) * 1000000; // Exponential backoff: 2s, 4s, 8s, 16s
                            $jitter = rand(500000, 1500000); // Random 0.5-1.5s jitter
                            $delay = $baseDelay + $jitter;
                            Log::info("Retrying video download ({$retry}/{$maxVideoRetries}) for: {$productName}, waiting " . round($delay / 1000000, 2) . "s");
                            usleep($delay);
                        }
                    }

                    if ($videoMediaItem) {
                        $mediaUrls[] = $videoMediaItem;
                        Log::info("Successfully uploaded video + poster to S3 for product: {$productName}");
                    } else {
                        // Log warning but don't fail the entire import
                        Log::warning("Failed to upload video to S3 after {$maxVideoRetries} attempts for product: {$productName}, URL: " . Str::limit($videoUrl, 100));
                        $this->errors[] = "Row " . ($productName ?: 'Unknown') . ": Failed to upload video to S3 after {$maxVideoRetries} attempts (URL: " . Str::limit($videoUrl, 50) . ")";
                    }
                }
            }

            // If no media uploaded successfully, use template media
            if (empty($mediaUrls) && !empty($template->media)) {
                $mediaUrls = $template->media;
            }

            // Check if product with same name and template already exists (prevent duplicates)
            $existingProduct = Product::where('name', $productName)
                ->where('template_id', $templateId)
                ->where('user_id', $this->user->id)
                ->first();

            if ($existingProduct) {
                $this->errors[] = "Row {$productName}: Product with this name and template already exists (ID: {$existingProduct->id})";
                Log::warning("Skipping duplicate product: {$productName}, Template ID: {$templateId}, Existing ID: {$existingProduct->id}");
                $this->processedRows++;
                $this->updateProgress();
                return null;
            }

            // Generate unique slug to avoid duplicates
            $baseSlug = Str::slug($productName);
            $slug = $baseSlug;
            $counter = 1;

            // Check if slug already exists and make it unique
            while (Product::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            // Create product with error handling for duplicates
            // IMPORTANT: Never set 'id' - let database auto-increment
            try {
                // Prepare data array - explicitly exclude 'id' to ensure auto-increment
                $productData = [
                    'template_id' => $templateId,
                    'user_id' => $this->user->id, // Product owner
                    'shop_id' => $this->user->hasShop() ? $this->user->shop->id : null, // Shop ID
                    'name' => $productName,
                    'slug' => $slug,
                    'price' => $finalPrice,
                    'description' => $description,
                    'quantity' => $row['quantity'] ?? 0,
                    'status' => $row['status'] ?? 'active',
                    'media' => $mediaUrls ?: null, // Laravel auto-cast to JSON via $casts
                ];

                // Ensure 'id' is not in the data (even if somehow included)
                unset($productData['id']);

                // Create product - variants will be created after import completes
                $product = new Product($productData);

                // Store template info for creating variants after import (batch insert doesn't trigger created event)
                if ($template->variants && $template->variants->count() > 0) {
                    // Only store essential data from variants, not the whole collection
                    // This prevents serialization issues and ensures we only get attributes
                    // Store only variant names - we'll query attributes from database when creating variants (same as ProductController)
                    // This ensures we always get fresh, clean attributes from database
                    $variantsData = [];
                    foreach ($template->variants as $tv) {
                        $variantsData[] = [
                            'variant_name' => $tv->variant_name,
                            // Don't store attributes here - query fresh from database when creating variants
                        ];
                    }

                    $this->importedProducts[] = [
                        'slug' => $slug,
                        'template_id' => $templateId,
                        'template_variants' => $variantsData, // Store only essential data
                    ];
                }

                $this->successCount++;
                $this->processedRows++;
                $this->updateProgress();
                return $product;
            } catch (QueryException $e) {
                // Handle duplicate entry or other database errors
                if ($e->getCode() == 23000) { // Integrity constraint violation
                    $errorMessage = $e->getMessage();
                    if (strpos($errorMessage, 'Duplicate entry') !== false) {
                        $this->errors[] = "Row {$productName}: Product already exists in database (duplicate entry). Please check if you're importing the same file twice.";
                        Log::error("Duplicate product entry: {$productName}, Error: " . $errorMessage);
                    } else {
                        $this->errors[] = "Row {$productName}: Database error - " . $errorMessage;
                        Log::error("Database error for product: {$productName}, Error: " . $errorMessage);
                    }
                    $this->processedRows++;
                    $this->updateProgress();
                    return null;
                }
                throw $e; // Re-throw if it's a different error
            }
        } catch (\Exception $e) {
            $productName = isset($row['product_name']) ? trim((string)$row['product_name']) : 'Unknown';
            $this->errors[] = "Row " . ($productName ?: 'Unknown') . ": {$e->getMessage()}";
            Log::error("Import error: " . $e->getMessage());
            $this->processedRows++;
            $this->updateProgress();
            return null;
        }
    }

    /**
     * Validation rules
     * Note: template_id and product_name are nullable here because we check for empty rows in model() method
     * If both are empty, the row will be skipped. If only one is empty, validation will catch it.
     */
    public function rules(): array
    {
        return [
            'template_id' => 'nullable|exists:product_templates,id',
            'product_name' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'quantity' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive,draft',
            'image_1' => 'nullable|url',
            'image_2' => 'nullable|url',
            'image_3' => 'nullable|url',
            'image_4' => 'nullable|url',
            'image_5' => 'nullable|url',
            'image_6' => 'nullable|url',
            'image_7' => 'nullable|url',
            'image_8' => 'nullable|url',
            'video_url' => 'nullable|url',
        ];
    }

    /**
     * Handle errors
     */
    public function onError(\Throwable $e)
    {
        $this->errors[] = $e->getMessage();
    }

    /**
     * Handle validation failures
     */
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = "Row {$failure->row()}: {$failure->errors()[0]}";
        }
    }

    /**
     * Chunk size for reading
     * Note: Process one product at a time (no batch) to ensure images are downloaded sequentially
     * This prevents rate limiting and ensures all images are processed properly
     */
    public function chunkSize(): int
    {
        return 1; // Process one product at a time to avoid rate limiting and ensure image downloads succeed
    }

    /**
     * Get import errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get success count
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * Get progress key
     */
    public function getProgressKey(): string
    {
        return $this->progressKey;
    }

    /**
     * Set total rows (called before import starts)
     */
    public function setTotalRows(int $total): void
    {
        $this->totalRows = $total;
        $this->updateProgress();
    }

    /**
     * Update progress in cache
     * Updates more frequently to ensure progress bar is accurate
     */
    protected function updateProgress(): void
    {
        // If total rows is 0, estimate based on processed rows
        $estimatedTotal = $this->totalRows;
        if ($estimatedTotal == 0 && $this->processedRows > 0) {
            // Estimate: assume we're at least 5% done, so total is at least 20x processed
            // This will be updated as we process more rows
            $estimatedTotal = max($this->processedRows * 20, 50);
        } elseif ($estimatedTotal == 0) {
            // If no rows processed yet, set a default estimate
            $estimatedTotal = 100;
        }

        $progress = [
            'processed' => $this->processedRows,
            'total' => $estimatedTotal,
            'success' => $this->successCount,
            'errors' => count($this->errors),
            'percentage' => $estimatedTotal > 0 ? min(100, round(($this->processedRows / $estimatedTotal) * 100, 2)) : 0,
            'status' => 'processing',
            'updated_at' => now()->toIso8601String(), // Add timestamp for debugging
        ];

        // Always update cache (no throttling) to ensure progress bar is accurate
        Cache::put($this->progressKey, $progress, 3600); // Store for 1 hour

        // Log progress update for debugging (every row for small imports, every 5 rows for large imports)
        $logInterval = $estimatedTotal > 50 ? 5 : 1; // Log every row if total <= 50, every 5 rows if > 50
        if ($this->processedRows % $logInterval == 0 || $this->processedRows == 1) {
            Log::debug("Progress updated", [
                'progress_key' => $this->progressKey,
                'processed' => $this->processedRows,
                'total' => $estimatedTotal,
                'success' => $this->successCount,
                'errors' => count($this->errors),
                'percentage' => $progress['percentage']
            ]);
        }
    }

    /**
     * Mark import as completed
     */
    public function markCompleted(): void
    {
        $progress = [
            'processed' => $this->processedRows,
            'total' => $this->totalRows,
            'success' => $this->successCount,
            'errors' => count($this->errors),
            'percentage' => 100,
            'status' => 'completed',
        ];

        Cache::put($this->progressKey, $progress, 3600);
    }

    /**
     * Mark import as failed
     */
    public function markFailed(string $error): void
    {
        $progress = [
            'processed' => $this->processedRows,
            'total' => $this->totalRows,
            'success' => $this->successCount,
            'errors' => count($this->errors),
            'percentage' => $this->totalRows > 0 ? round(($this->processedRows / $this->totalRows) * 100, 2) : 0,
            'status' => 'failed',
            'error' => $error,
        ];

        Cache::put($this->progressKey, $progress, 3600);
    }

    /**
     * Register events
     */
    public function registerEvents(): array
    {
        return [
            AfterChunk::class => function (AfterChunk $event) {
                // Force progress update after each chunk
                $this->updateProgress();

                // Memory cleanup after each chunk to reduce RAM usage
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            },
            AfterSheet::class => function (AfterSheet $event) {
                // After all products are imported, create variants for them
                // This is needed because batch insert doesn't trigger created event
                // Only create variants once (AfterSheet can be called multiple times for multiple sheets)
                if (!$this->variantsCreated) {
                    // Update total rows to actual processed count if it was estimated
                    if ($this->totalRows == 0 || $this->totalRows < $this->processedRows) {
                        $this->totalRows = $this->processedRows;
                    }

                    $this->createVariantsForImportedProducts();
                    $this->variantsCreated = true;
                    // Mark import as completed after variants are created
                    $this->markCompleted();
                }
            },
        ];
    }

    /**
     * Create variants for imported products
     */
    protected function createVariantsForImportedProducts(): void
    {
        if (empty($this->importedProducts)) {
            Log::info("No products need variants created");
            return;
        }

        Log::info("Starting to create variants for imported products", [
            'products_count' => count($this->importedProducts)
        ]);

        foreach ($this->importedProducts as $productInfo) {
            try {
                $product = Product::where('slug', $productInfo['slug'])->first();
                if (!$product) {
                    Log::warning("Product not found for slug: {$productInfo['slug']}, cannot create variants");
                    continue;
                }

                // Check if product already has variants
                if ($product->variants()->count() > 0) {
                    Log::info("Product already has variants, skipping", [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'existing_variants_count' => $product->variants()->count()
                    ]);
                    continue;
                }

                $templateVariants = $productInfo['template_variants'];
                Log::info("Creating variants for product", [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'template_id' => $productInfo['template_id'],
                    'template_variants_count' => is_array($templateVariants) ? count($templateVariants) : (is_object($templateVariants) ? $templateVariants->count() : 0)
                ]);

                foreach ($templateVariants as $variantData) {
                    $variantName = $variantData['variant_name'] ?? '';

                    // Get attributes from template variant (same as ProductController - query fresh from database)
                    $attributes = [];

                    // Query template variant from database to get fresh attributes
                    Log::info("DEBUG: Querying TemplateVariant", [
                        'template_id' => $productInfo['template_id'],
                        'variant_name' => $variantName,
                    ]);

                    $templateVariant = \App\Models\TemplateVariant::where('template_id', $productInfo['template_id'])
                        ->where('variant_name', $variantName)
                        ->first();

                    if ($templateVariant) {
                        Log::info("DEBUG: TemplateVariant found", [
                            'template_variant_id' => $templateVariant->id,
                            'template_variant_exists' => true,
                        ]);

                        // Log all ways to get attributes
                        Log::info("DEBUG: Getting attributes - Method 1: \$templateVariant->attributes", [
                            'value' => $templateVariant->attributes,
                            'type' => gettype($templateVariant->attributes),
                        ]);

                        Log::info("DEBUG: Getting attributes - Method 2: getAttribute('attributes')", [
                            'value' => $templateVariant->getAttribute('attributes'),
                            'type' => gettype($templateVariant->getAttribute('attributes')),
                        ]);

                        Log::info("DEBUG: Getting attributes - Method 3: getRawOriginal('attributes')", [
                            'value' => $templateVariant->getRawOriginal('attributes'),
                            'type' => gettype($templateVariant->getRawOriginal('attributes')),
                        ]);

                        Log::info("DEBUG: Getting attributes - Method 4: getOriginal('attributes')", [
                            'value' => $templateVariant->getOriginal('attributes'),
                            'type' => gettype($templateVariant->getOriginal('attributes')),
                        ]);

                        Log::info("DEBUG: TemplateVariant full model", [
                            'id' => $templateVariant->id,
                            'template_id' => $templateVariant->template_id,
                            'variant_name' => $templateVariant->variant_name,
                            'attributes_column' => $templateVariant->attributes,
                            'attributes_column_type' => gettype($templateVariant->attributes),
                            'price' => $templateVariant->price,
                            'quantity' => $templateVariant->quantity,
                            'media' => $templateVariant->media,
                        ]);

                        if (!empty($templateVariant->attributes)) {
                            // Get attributes directly from template variant (same as ProductController line 304)
                            $attributes = $templateVariant->attributes;

                            Log::info("DEBUG: Attributes from templateVariant->attributes", [
                                'attributes' => $attributes,
                                'attributes_type' => gettype($attributes),
                                'is_array' => is_array($attributes),
                                'is_string' => is_string($attributes),
                            ]);

                            // Ensure it's an array
                            if (!is_array($attributes)) {
                                if (is_string($attributes)) {
                                    Log::info("DEBUG: Attributes is string, decoding JSON", [
                                        'string_value' => $attributes,
                                    ]);
                                    $decoded = json_decode($attributes, true);
                                    $attributes = is_array($decoded) ? $decoded : [];
                                    Log::info("DEBUG: After JSON decode", [
                                        'decoded' => $decoded,
                                        'final_attributes' => $attributes,
                                    ]);
                                } else {
                                    Log::warning("DEBUG: Attributes is neither array nor string", [
                                        'type' => gettype($attributes),
                                        'value' => $attributes,
                                    ]);
                                    $attributes = [];
                                }
                            }
                        } else {
                            Log::warning("DEBUG: TemplateVariant attributes is empty", [
                                'template_variant_id' => $templateVariant->id,
                            ]);
                        }
                    } else {
                        Log::warning("DEBUG: TemplateVariant not found", [
                            'template_id' => $productInfo['template_id'],
                            'variant_name' => $variantName,
                        ]);
                    }

                    // If no attributes from template, try to parse from variant_name (same as ProductController)
                    if (empty($attributes)) {
                        Log::info("DEBUG: No attributes from template, parsing from variant_name", [
                            'variant_name' => $variantName,
                        ]);
                        $attributes = $this->parseAttributesFromVariantName($variantName);
                        Log::info("DEBUG: Parsed attributes from variant_name", [
                            'attributes' => $attributes,
                        ]);
                    }

                    // If still no attributes, create a generic one (same as ProductController)
                    if (empty($attributes)) {
                        Log::info("DEBUG: Still no attributes, creating generic one", [
                            'variant_name' => $variantName,
                        ]);
                        $attributes = ['Variant' => $variantName];
                    }

                    // Final log before saving
                    Log::info("DEBUG: Final attributes before saving to ProductVariant", [
                        'variant_name' => $variantName,
                        'attributes' => $attributes,
                        'attributes_type' => gettype($attributes),
                        'attributes_is_array' => is_array($attributes),
                        'attributes_count' => is_array($attributes) ? count($attributes) : 0,
                        'attributes_keys' => is_array($attributes) ? array_keys($attributes) : [],
                    ]);

                    try {
                        // Generate unique SKU (required field)
                        $sku = 'SKU-' . strtoupper(Str::random(8));
                        // Ensure SKU is unique
                        while (ProductVariant::where('sku', $sku)->exists()) {
                            $sku = 'SKU-' . strtoupper(Str::random(8));
                        }

                        // Log data before creating
                        $dataToSave = [
                            'product_id' => $product->id,
                            'template_id' => $productInfo['template_id'],
                            'variant_name' => $variantName,
                            'attributes' => $attributes,
                            'sku' => $sku,
                        ];

                        Log::info("DEBUG: Data to save to ProductVariant", [
                            'data' => $dataToSave,
                            'attributes_value' => $attributes,
                            'attributes_type' => gettype($attributes),
                            'attributes_json' => json_encode($attributes),
                        ]);

                        // Only save essential fields: product_id, template_id, variant_name, attributes, and sku (required)
                        $variant = ProductVariant::create($dataToSave);

                        // Log what was actually saved
                        Log::info("DEBUG: ProductVariant created - checking what was saved", [
                            'variant_id' => $variant->id,
                            'saved_attributes' => $variant->attributes,
                            'saved_attributes_type' => gettype($variant->attributes),
                            'saved_attributes_raw' => $variant->getRawOriginal('attributes'),
                            'saved_attributes_original' => $variant->getOriginal('attributes'),
                        ]);

                        Log::info("Variant created successfully", [
                            'product_id' => $product->id,
                            'variant_id' => $variant->id,
                            'variant_name' => $variantName,
                            'attributes' => $attributes, // Should only be {"Size": "Pack 1", "Color": "Black"}
                            'attributes_type' => gettype($attributes),
                            'saved_attributes' => $variant->attributes,
                            'saved_attributes_type' => gettype($variant->attributes),
                            'note' => 'Only attributes saved, other fields use defaults'
                        ]);
                    } catch (\Exception $e) {
                        Log::error("Failed to create variant", [
                            'product_id' => $product->id,
                            'variant_name' => $variantName,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $this->errors[] = "Failed to create variant '{$variantName}' for product '{$product->name}': " . $e->getMessage();
                    }
                }

                Log::info("Completed creating variants for product", [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'variants_created' => $product->variants()->count()
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create variants for product with slug: {$productInfo['slug']}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->errors[] = "Failed to create variants for product with slug: {$productInfo['slug']}, Error: " . $e->getMessage();
            }
        }

        Log::info("Finished creating variants for imported products", [
            'total_products_processed' => count($this->importedProducts)
        ]);
    }

    /**
     * Parse attributes from variant name (fallback method)
     * 
     * @param string $variantName
     * @return array
     */
    protected function parseAttributesFromVariantName(string $variantName): array
    {
        $attributes = [];

        // Common size patterns
        $sizePatterns = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Small', 'Medium', 'Large', '11oz', '12oz', '15oz'];
        $colorPatterns = ['Black', 'White', 'Red', 'Blue', 'Green', 'Yellow', 'Purple', 'Pink', 'Gray', 'Grey', 'Brown', 'Orange', 'Navy', 'Maroon', 'Teal'];

        // Handle format like "Black/S" or "Black S" or "Black-S" or "S/Black"
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

        return $attributes;
    }

    /**
     * Download file from URL and upload to S3
     * 
     * @param string $url
     * @param string $folder
     * @param int $retryAttempt Current retry attempt (for logging)
     * @param int $maxRetries Maximum retries (for logging)
     * @return string|null S3 URL or null on failure
     */
    protected function downloadAndUploadToS3(string $url, string $folder = 'products', int $retryAttempt = 1, int $maxRetries = 3): ?string
    {
        try {
            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                Log::warning("Invalid URL format: {$url}");
                return null;
            }

            // Download file from URL with timeout, retry, and proper headers
            // Some sites like Etsy block requests without User-Agent
            // Use manual retry with exponential backoff for connection reset errors
            $internalMaxRetries = 3; // Internal retries for HTTP requests
            $response = null;
            $lastError = null;

            Log::info("Downloading media (attempt {$retryAttempt}/{$maxRetries}): {$url}");

            for ($attempt = 1; $attempt <= $internalMaxRetries; $attempt++) {
                try {
                    $response = Http::timeout(90) // Increased timeout to 90 seconds for large files
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                            'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
                            'Accept-Language' => 'en-US,en;q=0.9',
                            'Referer' => parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST),
                            'Connection' => 'keep-alive',
                            'Accept-Encoding' => 'gzip, deflate, br',
                            'Cache-Control' => 'no-cache',
                        ])
                        ->withOptions([
                            'allow_redirects' => true,
                            'max_redirects' => 5,
                            'verify' => false, // Disable SSL verification if needed (for some CDNs)
                            'curl' => [
                                CURLOPT_TCP_KEEPALIVE => 1,
                                CURLOPT_TCP_KEEPIDLE => 30,
                                CURLOPT_TCP_KEEPINTVL => 10,
                                CURLOPT_FRESH_CONNECT => true, // Use fresh connection for each request
                                CURLOPT_FORBID_REUSE => true, // Don't reuse connections (helps with connection reset)
                                CURLOPT_CONNECTTIMEOUT => 30, // Connection timeout 30 seconds
                                CURLOPT_TIMEOUT => 90, // Total timeout 90 seconds
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // Use HTTP/1.1
                                CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification
                                CURLOPT_SSL_VERIFYHOST => false, // Disable SSL host verification
                            ],
                        ])
                        ->get($url);

                    if ($response->successful()) {
                        break; // Success, exit retry loop
                    } else {
                        $lastError = "HTTP {$response->status()}";
                        if ($attempt < $internalMaxRetries) {
                            $baseDelay = pow(2, $attempt) * 1000000; // Exponential backoff: 2s, 4s, 8s
                            $jitter = rand(500000, 1000000); // Random 0.5-1s jitter
                            $delay = $baseDelay + $jitter;
                            Log::warning("Download attempt {$attempt} failed for: {$url}, Status: {$response->status()}, Retrying in " . round($delay / 1000000, 2) . "s");
                            usleep($delay);
                        }
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $lastError = $e->getMessage();
                    if ($attempt < $internalMaxRetries) {
                        $baseDelay = pow(2, $attempt) * 1000000; // Exponential backoff: 2s, 4s, 8s
                        $jitter = rand(500000, 1000000); // Random 0.5-1s jitter
                        $delay = $baseDelay + $jitter;
                        Log::warning("Connection error on attempt {$attempt} for: {$url}, Error: {$lastError}, Retrying in " . round($delay / 1000000, 2) . "s");
                        usleep($delay);
                    } else {
                        Log::error("Connection error after {$internalMaxRetries} attempts: {$url}, Error: {$lastError}");
                    }
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    Log::error("Unexpected error downloading: {$url}, Error: {$lastError}");
                    // Don't return null immediately - let outer retry loop handle it
                    if ($attempt >= $internalMaxRetries) {
                        return null;
                    }
                }
            }

            if (!$response || !$response->successful()) {
                Log::warning("Failed to download file from URL after {$internalMaxRetries} internal attempts: {$url}, Last error: {$lastError}");
                return null;
            }

            // Check if response has content
            $fileContent = $response->body();
            if (empty($fileContent)) {
                Log::warning("Empty file content from URL: {$url}");
                return null;
            }

            // Check content type to ensure it's an image or video
            $contentType = $response->header('Content-Type');
            $isValidMedia = false;
            if ($contentType) {
                $validTypes = [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'video/mp4',
                    'video/mpeg',
                    'video/quicktime',
                    'video/x-msvideo'
                ];
                $isValidMedia = in_array(strtolower(explode(';', $contentType)[0]), $validTypes);
            }

            // If content type is not valid but we have content, still try to process (some servers don't send proper headers)
            if (!$isValidMedia && $contentType && !str_starts_with($contentType, 'image/') && !str_starts_with($contentType, 'video/')) {
                Log::warning("Invalid content type from URL: {$url}, Content-Type: {$contentType}");
                // Don't return null here - some servers don't send proper headers but still serve valid images
            }

            // Check file size (max 10MB for images, 50MB for videos)
            $fileSize = strlen($fileContent);
            $isVideo = $contentType && str_starts_with($contentType, 'video/');
            $maxSize = $isVideo ? (50 * 1024 * 1024) : (10 * 1024 * 1024); // 50MB for videos, 10MB for images
            if ($fileSize > $maxSize) {
                Log::warning("File too large from URL: {$url}, Size: " . round($fileSize / 1024 / 1024, 2) . "MB, Max: " . round($maxSize / 1024 / 1024, 2) . "MB");
                return null;
            }

            // Minimum file size check (very small files are likely errors)
            if ($fileSize < 100) {
                Log::warning("File too small from URL: {$url}, Size: {$fileSize} bytes");
                return null;
            }

            // Get file extension from URL or content type
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension) && $contentType) {
                $extension = $this->getExtensionFromContentType($contentType);
            }

            // If still no extension, try to detect from file content (magic bytes)
            if (empty($extension)) {
                $extension = $this->detectExtensionFromContent($fileContent);
            }

            // Final fallback
            if (empty($extension)) {
                $extension = 'jpg'; // Default extension
            }

            // Clean extension (remove query parameters if any)
            $extension = explode('?', $extension)[0];
            $extension = strtolower($extension);

            // Generate unique filename - same format as ProductController
            $fileName = time() . '_' . Str::random(10) . '.' . $extension;

            // Upload to S3 - use same method as ProductController
            $disk = Storage::disk('s3');
            if (!$disk) {
                Log::error("S3 disk not configured");
                return null;
            }

            // Validate S3 configuration
            $s3Config = config('filesystems.disks.s3');
            if (empty($s3Config['key']) || empty($s3Config['secret']) || empty($s3Config['bucket'])) {
                Log::error("S3 credentials not configured properly", [
                    'has_key' => !empty($s3Config['key']),
                    'has_secret' => !empty($s3Config['secret']),
                    'has_bucket' => !empty($s3Config['bucket']),
                    'bucket' => $s3Config['bucket'] ?? 'not set'
                ]);
                return null;
            }

            // Create temporary file from content to use putFileAs like ProductController
            $tempFile = tempnam(sys_get_temp_dir(), 'import_media_');
            if ($tempFile === false) {
                Log::error("Failed to create temporary file for: {$url}");
                return null;
            }

            try {
                // Write content to temporary file
                $bytesWritten = file_put_contents($tempFile, $fileContent);
                if ($bytesWritten === false || $bytesWritten === 0) {
                    Log::error("Failed to write content to temporary file for: {$url}");
                    return null;
                }

                // Create File object from temporary file (like ProductController uses UploadedFile)
                $fileObject = new File($tempFile);

                // Use putFileAs like ProductController does
                try {
                    // Check if file exists and is readable
                    if (!file_exists($tempFile) || !is_readable($tempFile)) {
                        Log::error("Temporary file not accessible: {$tempFile}, URL: {$url}");
                        return null;
                    }

                    $filePath = $disk->putFileAs('products', $fileObject, $fileName);

                    if ($filePath && !empty($filePath)) {
                        // Create the correct S3 URL format - same as ProductController
                        $s3Url = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $filePath;
                        Log::info("Successfully uploaded to S3", [
                            'file' => $fileName,
                            'path' => $filePath,
                            'url' => $s3Url,
                            'size' => $fileSize,
                            'source_url' => $url
                        ]);
                        return $s3Url;
                    } else {
                        // putFileAs returned false or empty - check S3 connection
                        Log::error("putFileAs returned false/empty for: {$fileName}", [
                            'url' => $url,
                            'temp_file' => $tempFile,
                            'temp_file_exists' => file_exists($tempFile),
                            'temp_file_size' => file_exists($tempFile) ? filesize($tempFile) : 0,
                            's3_bucket' => config('filesystems.disks.s3.bucket'),
                            's3_region' => config('filesystems.disks.s3.region'),
                            'has_s3_key' => !empty(config('filesystems.disks.s3.key'))
                        ]);
                        return null;
                    }
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    Log::error("AWS S3 Exception during upload: {$url}", [
                        'file' => $fileName,
                        'error_code' => $e->getAwsErrorCode(),
                        'error_message' => $e->getAwsErrorMessage(),
                        'request_id' => $e->getAwsRequestId(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return null;
                } catch (\Exception $e) {
                    Log::error("General exception during putFileAs: {$url}", [
                        'file' => $fileName,
                        'error' => $e->getMessage(),
                        'error_class' => get_class($e),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return null;
                }
            } catch (\Exception $e) {
                Log::error("Exception during S3 upload: {$url}, Error: " . $e->getMessage(), [
                    'file' => $fileName,
                    'trace' => $e->getTraceAsString()
                ]);
                return null;
            } finally {
                // Always clean up temporary file
                if (isset($tempFile) && file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        } catch (\Exception $e) {
            // Catch any unexpected errors that weren't handled above
            Log::error("Unexpected error in downloadAndUploadToS3: {$url}, Error: " . $e->getMessage(), [
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Download video, upload lên S3 và tạo poster (thumbnail) bằng FFmpeg rồi upload poster lên S3.
     * Trả về item dạng ['type'=>'video','url'=>...,'poster'=>...]
     */
    protected function downloadUploadVideoAndPosterToS3(string $url, int $retryAttempt = 1, int $maxRetries = 3): ?array
    {
        $tempFile = null;
        $posterRelative = null;

        try {
            // Download (reuse headers + retry style giống downloadAndUploadToS3)
            $internalMaxRetries = 3;
            $response = null;
            $lastError = null;

            Log::info("Downloading video (attempt {$retryAttempt}/{$maxRetries}): {$url}");

            for ($attempt = 1; $attempt <= $internalMaxRetries; $attempt++) {
                try {
                    $response = Http::timeout(120)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                            'Accept' => 'video/*,*/*;q=0.8',
                            'Accept-Language' => 'en-US,en;q=0.9',
                            'Referer' => parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST),
                            'Connection' => 'keep-alive',
                            'Accept-Encoding' => 'gzip, deflate, br',
                            'Cache-Control' => 'no-cache',
                        ])
                        ->withOptions([
                            'allow_redirects' => true,
                            'max_redirects' => 5,
                            'verify' => false,
                            'curl' => [
                                CURLOPT_TCP_KEEPALIVE => 1,
                                CURLOPT_TCP_KEEPIDLE => 30,
                                CURLOPT_TCP_KEEPINTVL => 10,
                                CURLOPT_FRESH_CONNECT => true,
                                CURLOPT_FORBID_REUSE => true,
                                CURLOPT_CONNECTTIMEOUT => 30,
                                CURLOPT_TIMEOUT => 120,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_SSL_VERIFYPEER => false,
                                CURLOPT_SSL_VERIFYHOST => false,
                            ],
                        ])
                        ->get($url);

                    if ($response->successful()) {
                        break;
                    }

                    $lastError = "HTTP {$response->status()}";
                    if ($attempt < $internalMaxRetries) {
                        $baseDelay = pow(2, $attempt) * 1000000;
                        $jitter = rand(500000, 1000000);
                        usleep($baseDelay + $jitter);
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $lastError = $e->getMessage();
                    if ($attempt < $internalMaxRetries) {
                        $baseDelay = pow(2, $attempt) * 1000000;
                        $jitter = rand(500000, 1000000);
                        usleep($baseDelay + $jitter);
                    }
                }
            }

            if (!$response || !$response->successful()) {
                Log::warning("Failed to download video after {$internalMaxRetries} internal attempts: {$url}, Last error: {$lastError}");
                return null;
            }

            $fileContent = $response->body();
            if (empty($fileContent)) {
                Log::warning("Empty video content from URL: {$url}");
                return null;
            }

            $contentType = $response->header('Content-Type');
            $fileSize = strlen($fileContent);
            if ($fileSize > (50 * 1024 * 1024)) {
                Log::warning("Video too large from URL: {$url}, Size: " . round($fileSize / 1024 / 1024, 2) . "MB");
                return null;
            }
            if ($fileSize < 1024) {
                Log::warning("Video too small (likely invalid) from URL: {$url}, Size: {$fileSize} bytes");
                return null;
            }

            // Determine extension
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            $extension = $extension ? strtolower(explode('?', $extension)[0]) : '';
            if (!$extension && $contentType) {
                $extension = $this->getExtensionFromContentType(strtolower(explode(';', $contentType)[0]));
            }
            if (!$extension) {
                $extension = 'mp4';
            }

            // Write to temp file
            $tempFile = tempnam(sys_get_temp_dir(), 'import_video_');
            if ($tempFile === false) {
                Log::error("Failed to create temp video file for: {$url}");
                return null;
            }

            // Ensure extension matches file name for some ffmpeg builds
            $tempFileWithExt = $tempFile . '.' . $extension;
            if (@rename($tempFile, $tempFileWithExt)) {
                $tempFile = $tempFileWithExt;
            }

            $bytesWritten = file_put_contents($tempFile, $fileContent);
            if ($bytesWritten === false || $bytesWritten === 0) {
                Log::error("Failed to write video to temp file for: {$url}");
                return null;
            }

            // Upload video to S3
            $fileName = time() . '_' . Str::random(10) . '.' . $extension;
            $disk = Storage::disk('s3');
            $fileObject = new File($tempFile);
            $filePath = $disk->putFileAs('products', $fileObject, $fileName);
            if (!$filePath) {
                Log::error("Failed to upload video to S3 for: {$url}");
                return null;
            }
            $videoS3Url = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $filePath;

            // Generate poster locally using FFmpeg
            $thumbnailService = app(VideoThumbnailService::class);
            $posterRelative = $thumbnailService->generatePoster($tempFile, 5);
            $posterS3Url = null;

            if ($posterRelative) {
                $posterAbs = Storage::disk('local')->path($posterRelative);
                if (is_file($posterAbs)) {
                    $posterFileName = pathinfo($fileName, PATHINFO_FILENAME) . '_poster.jpg';
                    $posterKey = 'products/posters/' . $posterFileName;
                    $contents = @file_get_contents($posterAbs);
                    if ($contents !== false && $contents !== '') {
                        $disk->put($posterKey, $contents);
                        $posterS3Url = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $posterKey;
                    }
                }
            }

            // Always return video item; poster can be null (frontend fallback)
            return [
                'type' => 'video',
                'url' => $videoS3Url,
                'poster' => $posterS3Url,
            ];
        } catch (\Throwable $e) {
            Log::error("downloadUploadVideoAndPosterToS3 failed: {$url}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        } finally {
            // Cleanup temp video
            if ($tempFile && file_exists($tempFile)) {
                @unlink($tempFile);
            }
            // Cleanup temp poster (disk local)
            if ($posterRelative) {
                try {
                    app(VideoThumbnailService::class)->deleteTempPoster($posterRelative);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }
    }

    /**
     * Get file extension from content type
     * 
     * @param string|null $contentType
     * @return string
     */
    protected function getExtensionFromContentType(?string $contentType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpeg',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
        ];

        return $map[$contentType] ?? 'jpg';
    }

    /**
     * Detect file extension from file content (magic bytes)
     * 
     * @param string $content
     * @return string
     */
    protected function detectExtensionFromContent(string $content): string
    {
        if (empty($content)) {
            return 'jpg';
        }

        // Get first few bytes (magic bytes)
        $header = substr($content, 0, 12);

        // Check for image types
        if (substr($header, 0, 2) === "\xFF\xD8") {
            return 'jpg'; // JPEG
        }
        if (substr($header, 0, 8) === "\x89PNG\r\n\x1A\n") {
            return 'png'; // PNG
        }
        if (substr($header, 0, 6) === "GIF87a" || substr($header, 0, 6) === "GIF89a") {
            return 'gif'; // GIF
        }
        if (substr($header, 0, 4) === "RIFF" && substr($header, 8, 4) === "WEBP") {
            return 'webp'; // WebP
        }

        // Check for video types
        if (substr($header, 4, 4) === "ftyp") {
            // MP4 or MOV
            if (strpos($header, "mp4") !== false || strpos($header, "isom") !== false) {
                return 'mp4';
            }
            if (strpos($header, "qt") !== false) {
                return 'mov';
            }
        }

        // Default to jpg if cannot detect
        return 'jpg';
    }
}
