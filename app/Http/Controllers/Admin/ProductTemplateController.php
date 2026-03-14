<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductTemplate;
use App\Models\Category;
use App\Models\TemplateAttribute;
use App\Models\TemplateVariant;
use App\Services\VideoThumbnailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductTemplateController extends Controller
{
    protected function getS3BaseUrl(): string
    {
        return 'https://s3.us-east-1.amazonaws.com/image.bluprinter/';
    }

    /**
     * Upload media files (images + videos) to S3. Với video thì tạo poster bằng FFmpeg và lưu dạng ['type'=>'video','url'=>...,'poster'=>...].
     */
    protected function uploadTemplateMedia(Request $request): array
    {
        $mediaItems = [];
        $thumbnailService = app(VideoThumbnailService::class);
        $baseUrl = $this->getS3BaseUrl();

        foreach ($request->file('media') as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            Storage::disk('s3')->putFileAs('templates', $file, $fileName);
            $fileUrl = $baseUrl . 'templates/' . $fileName;

            if (VideoThumbnailService::isVideoFile($file)) {
                $posterUrl = null;
                $posterPath = $thumbnailService->generatePoster($file, 1);
                if ($posterPath) {
                    $posterFileName = pathinfo($fileName, PATHINFO_FILENAME) . '_poster.jpg';
                    $contents = Storage::disk('local')->get($posterPath);
                    if ($contents) {
                        Storage::disk('s3')->put('templates/posters/' . $posterFileName, $contents);
                        $posterUrl = $baseUrl . 'templates/posters/' . $posterFileName;
                    }
                    $thumbnailService->deleteTempPoster($posterPath);
                }
                // Nếu không tạo được poster thì để null để frontend fallback về ảnh khác,
                // tránh dùng URL video (.mp4) làm src của <img>
                $mediaItems[] = ['type' => 'video', 'url' => $fileUrl, 'poster' => $posterUrl];
            } else {
                $mediaItems[] = $fileUrl;
            }
        }

        return $mediaItems;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        // Admin xem tất cả templates, Seller chỉ xem templates của mình
        $templatesQuery = ProductTemplate::with(['category', 'attributes', 'user'])
            ->withCount('products');

        if ($user->hasRole('admin')) {
            // Admin thấy tất cả
            $templates = $templatesQuery->orderBy('created_at', 'desc')->paginate(12);
        } else {
            // Seller chỉ thấy của mình
            $templates = $templatesQuery
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(12);
        }

        return view('admin.product-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get all categories with hierarchical structure
        $categories = Category::with('parent')
            ->orderBy('parent_id', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.product-templates.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'list_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'media.*' => 'nullable|mimes:jpeg,png,jpg,gif,webp,avif,mp4,mov,avi,webm,ogg|max:10240',
            'allow_customization' => 'nullable|boolean',
            'customizations' => 'nullable|array',
            'customizations.*.type' => 'required_with:customizations|string|in:text,number,textarea,select,checkbox,file',
            'customizations.*.price' => 'required_with:customizations|numeric|min:0',
            'customizations.*.label' => 'required_with:customizations|string|max:255',
            'customizations.*.placeholder' => 'nullable|string|max:255',
            'customizations.*.options' => 'nullable|string',
            'customizations.*.required' => 'nullable|boolean',
            'attributes' => 'nullable|array',
            'attributes.*.name' => 'required_with:attributes|string|max:255',
            'attributes.*.values' => 'required_with:attributes|string|max:500',
            'variants' => 'nullable|array',
            'variants.*.variant_name' => 'required_with:variants|string|max:255',
            'variants.*.variant_key' => 'nullable|string|max:255',
            'variants.*.attributes' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.list_price' => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'nullable|integer|min:0',
            'variants.*.media' => 'nullable|mimes:jpeg,png,jpg,gif,webp,avif,mp4,mov,avi,webm,ogg|max:10240',
        ]);

        $data = $request->all();

        // Set user_id to current authenticated user
        $data['user_id'] = auth()->id();

        // Handle customization data
        if ($request->has('allow_customization')) {
            $data['allow_customization'] = true;
            if ($request->has('customizations')) {
                $data['customizations'] = $request->customizations;
            }
        } else {
            $data['allow_customization'] = false;
            $data['customizations'] = null;
        }

        // Handle media upload to S3 (ảnh + video; video có poster từ FFmpeg)
        if ($request->hasFile('media')) {
            $data['media'] = $this->uploadTemplateMedia($request);
        }

        $template = ProductTemplate::create($data);

        // Handle template attributes
        if ($request->has('attributes')) {
            $attributes = $request->input('attributes', []);
            foreach ($attributes as $attribute) {
                if (!empty($attribute['name']) && !empty($attribute['values'])) {
                    $values = explode(',', $attribute['values']);
                    foreach ($values as $value) {
                        $value = trim($value);
                        if (!empty($value)) {
                            $template->attributes()->create([
                                'attribute_name' => $attribute['name'],
                                'attribute_value' => $value
                            ]);
                        }
                    }
                }
            }
        }

        // Handle template variants
        if ($request->has('variants')) {
            foreach ($request->variants as $index => $variantData) {
                if (!empty($variantData['variant_name'])) {
                    // Handle variant media upload
                    $variantMediaUrls = [];

                    // Debug logging
                    Log::info("Processing variant {$index}: {$variantData['variant_name']}");
                    Log::info("Checking for media file: variants.{$index}.media");
                    Log::info("Has file: " . ($request->hasFile("variants.{$index}.media") ? 'yes' : 'no'));

                    // Check if media file exists for this variant
                    if ($request->hasFile("variants.{$index}.media")) {
                        $file = $request->file("variants.{$index}.media");
                        Log::info("File found: " . $file->getClientOriginalName());

                        if ($file->isValid()) {
                            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                            $filePath = Storage::disk('s3')->putFileAs('template-variants', $file, $fileName);
                            $variantMediaUrls[] = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/template-variants/' . $fileName;
                            Log::info("File uploaded successfully: " . $fileName);
                        }
                    }

                    $variantData['template_id'] = $template->id;
                    $variantData['media'] = $variantMediaUrls;

                    // Parse attributes from JSON string
                    if (isset($variantData['attributes']) && !empty($variantData['attributes'])) {
                        $attributes = json_decode($variantData['attributes'], true);
                        $variantData['attributes'] = $attributes ?: [];
                    } else {
                        $variantData['attributes'] = [];
                    }

                    Log::info("Creating variant with media: " . json_encode($variantMediaUrls));
                    Log::info("Creating variant with attributes: " . json_encode($variantData['attributes']));

                    TemplateVariant::create($variantData);
                }
            }
        }

        return redirect()->route('admin.product-templates.edit', $template->id)
            ->with('success', "Template created successfully! Template ID: {$template->id}");
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductTemplate $productTemplate)
    {
        $productTemplate->load(['category', 'attributes', 'products']);
        return view('admin.product-templates.show', compact('productTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductTemplate $productTemplate)
    {
        $user = auth()->user();

        // Check authorization: Admin có thể edit tất cả, Seller chỉ edit của mình
        if (!$user->hasRole('admin') && $productTemplate->user_id !== $user->id) {
            abort(403, 'Unauthorized action. You can only edit your own templates.');
        }

        $categories = Category::with('parent')
            ->orderBy('parent_id', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        $productTemplate->load(['attributes', 'variants']);
        return view('admin.product-templates.edit', compact('productTemplate', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductTemplate $productTemplate)
    {
        $user = auth()->user();

        // Check authorization: Admin có thể update tất cả, Seller chỉ update của mình
        if (!$user->hasRole('admin') && $productTemplate->user_id !== $user->id) {
            abort(403, 'Unauthorized action. You can only update your own templates.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'list_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'media.*' => 'nullable|mimes:jpeg,png,jpg,gif,webp,avif,mp4,mov,avi,webm,ogg|max:10240',
            'allow_customization' => 'nullable|boolean',
            'customizations' => 'nullable|array',
            'customizations.*.type' => 'required_with:customizations|string|in:text,number,textarea,select,checkbox,file',
            'customizations.*.price' => 'required_with:customizations|numeric|min:0',
            'customizations.*.label' => 'required_with:customizations|string|max:255',
            'customizations.*.placeholder' => 'nullable|string|max:255',
            'customizations.*.options' => 'nullable|string',
            'customizations.*.required' => 'nullable|boolean',
            'attributes' => 'nullable|array',
            'attributes.*.name' => 'required_with:attributes|string|max:255',
            'attributes.*.values' => 'required_with:attributes|string|max:500',
            'variants' => 'nullable|array',
            'variants.*.variant_name' => 'required_with:variants|string|max:255',
            'variants.*.variant_key' => 'nullable|string|max:255',
            'variants.*.attributes' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.list_price' => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'nullable|integer|min:0',
            'variants.*.media' => 'nullable|mimes:jpeg,png,jpg,gif,webp,avif,mp4,mov,avi,webm,ogg|max:10240',
        ]);

        $data = $request->all();

        // Handle customization data
        if ($request->has('allow_customization')) {
            $data['allow_customization'] = true;
            if ($request->has('customizations')) {
                $data['customizations'] = $request->customizations;
            }
        } else {
            $data['allow_customization'] = false;
            $data['customizations'] = null;
        }

        // Handle media upload to S3 (ảnh + video; video có poster từ FFmpeg)
        if ($request->hasFile('media')) {
            $data['media'] = $this->uploadTemplateMedia($request);
        }

        $productTemplate->update($data);

        // Handle template attributes
        // IMPORTANT: Only update if attributes are explicitly provided
        // This prevents accidental deletion when form doesn't send attributes
        if ($request->has('attributes')) {
            $attributesData = $request->input('attributes', []);

            // Filter out empty attributes
            $validAttributes = array_filter($attributesData, function ($attr) {
                return !empty($attr['name']) && !empty($attr['values']);
            });

            Log::info('Processing attributes update', [
                'raw_attributes' => $attributesData,
                'valid_attributes_count' => count($validAttributes),
                'valid_attributes' => $validAttributes
            ]);

            // Always delete existing attributes and recreate from form data
            $productTemplate->attributes()->delete();

            if (count($validAttributes) > 0) {
                foreach ($validAttributes as $attribute) {
                    $attributeName = trim($attribute['name']);
                    $values = array_map('trim', explode(',', $attribute['values']));
                    $values = array_filter($values); // Remove empty values

                    Log::info("Processing attribute: {$attributeName}", [
                        'raw_values' => $attribute['values'],
                        'parsed_values' => $values
                    ]);

                    foreach ($values as $value) {
                        if (!empty($value)) {
                            $created = $productTemplate->attributes()->create([
                                'attribute_name' => $attributeName,
                                'attribute_value' => $value
                            ]);
                            Log::info("Created attribute", [
                                'id' => $created->id,
                                'name' => $attributeName,
                                'value' => $value
                            ]);
                        }
                    }
                }
            } else {
                Log::info('No valid attributes provided - all attributes deleted');
            }
        } else {
            Log::info('No attributes key in request - keeping existing attributes');
        }

        // Handle template variants
        if ($request->has('variants') && is_array($request->variants) && count($request->variants) > 0) {
            Log::info('Processing variants update', [
                'variant_count' => count($request->variants),
                'variant_data' => $request->variants
            ]);

            // Get existing variants for media preservation
            $existingVariants = $productTemplate->variants->keyBy('variant_name');

            // Delete existing variants
            $productTemplate->variants()->delete();

            foreach ($request->variants as $index => $variantData) {
                if (!empty($variantData['variant_name'])) {
                    $variantMediaUrls = [];

                    // Check if new media file is uploaded
                    if ($request->hasFile("variants.{$index}.media")) {
                        $file = $request->file("variants.{$index}.media");

                        if ($file->isValid()) {
                            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                            $filePath = Storage::disk('s3')->putFileAs('template-variants', $file, $fileName);
                            $variantMediaUrls[] = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/template-variants/' . $fileName;
                        }
                    } else {
                        // Keep existing media if no new file uploaded
                        $existingVariant = $existingVariants->get($variantData['variant_name']);
                        if ($existingVariant && $existingVariant->media) {
                            $variantMediaUrls = $existingVariant->media;
                        }
                    }

                    $variantData['template_id'] = $productTemplate->id;
                    $variantData['media'] = $variantMediaUrls;

                    // Parse attributes from JSON string
                    if (isset($variantData['attributes']) && !empty($variantData['attributes'])) {
                        $attributes = json_decode($variantData['attributes'], true);
                        $variantData['attributes'] = $attributes ?: [];
                    } else {
                        $variantData['attributes'] = [];
                    }

                    TemplateVariant::create($variantData);
                    Log::info("Created variant: {$variantData['variant_name']}", [
                        'media_count' => count($variantMediaUrls),
                        'attributes' => $variantData['attributes']
                    ]);
                }
            }
        } else {
            Log::info('No variants data in request - keeping existing variants');
        }

        return redirect()->route('admin.product-templates.index')
            ->with('success', 'Template updated successfully.');
    }

    /**
     * Clone an existing template
     */
    public function clone(ProductTemplate $productTemplate)
    {
        // Create new template with copied data
        $newTemplate = $productTemplate->replicate();
        $newTemplate->name = $productTemplate->name . ' (Copy)';
        $newTemplate->save();

        // Clone attributes
        foreach ($productTemplate->attributes as $attribute) {
            $newTemplate->attributes()->create([
                'attribute_name' => $attribute->attribute_name,
                'attribute_value' => $attribute->attribute_value,
            ]);
        }

        // Clone variants with attributes
        foreach ($productTemplate->variants as $variant) {
            $newVariant = $newTemplate->variants()->create([
                'variant_name' => $variant->variant_name,
                'attributes' => $variant->attributes, // Clone attributes
                'price' => $variant->price,
                'quantity' => $variant->quantity,
                'media' => $variant->media,
            ]);

            Log::info("Variant cloned: {$variant->variant_name} with attributes: " . json_encode($variant->attributes));
        }

        Log::info("Template cloned: {$productTemplate->id} → {$newTemplate->id}");

        return redirect()->route('admin.product-templates.edit', $newTemplate->id)
            ->with('success', 'Template cloned successfully. You can now edit the copy.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductTemplate $productTemplate)
    {
        $user = auth()->user();

        // Check authorization: Admin có thể delete tất cả, Seller chỉ delete của mình
        if (!$user->hasRole('admin') && $productTemplate->user_id !== $user->id) {
            abort(403, 'Unauthorized action. You can only delete your own templates.');
        }

        $productTemplate->delete();

        return redirect()->route('admin.product-templates.index')
            ->with('success', 'Template deleted successfully.');
    }
}
