<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVirtualNailTrialJob;
use App\Models\Product;
use App\Models\VirtualNailTrial;
use App\Services\VirtualNailTrialService;
use App\Support\VirtualNailSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VirtualNailTrialController extends Controller
{
    public function __construct(
        private readonly VirtualNailTrialService $service
    ) {}

    public function index(Request $request): View
    {
        abort_unless(VirtualNailSettings::enabled(), 404);

        $product = null;
        $slug = trim((string) $request->query('product', ''));
        if ($slug !== '') {
            $product = Product::query()
                ->where('slug', $slug)
                ->availableForDisplay()
                ->first();
        }

        $styleOptions = $this->service->styleOptionsForProduct($product);

        return view('virtual-nail.index', [
            'enabled' => $this->service->isConfigured(),
            'selectedProduct' => $this->service->resolveProductPayload($product),
            'shapes' => $styleOptions['shapes'],
            'lengths' => $styleOptions['lengths'],
            'defaultShape' => $this->service->pickAllowedOption(
                VirtualNailSettings::defaultShape(),
                $styleOptions['shapes'],
                $styleOptions['shapes'][0] ?? 'Almond'
            ),
            'defaultLength' => $this->service->pickAllowedOption(
                VirtualNailSettings::defaultLength(),
                $styleOptions['lengths'],
                $styleOptions['lengths'][0] ?? 'Medium'
            ),
            'captureTips' => VirtualNailSettings::captureTips(),
            'noticeTitle' => VirtualNailSettings::noticeTitle(),
            'noticeText' => VirtualNailSettings::noticeText(),
            'cameraDemo' => config('app.debug') && $request->boolean('camera_demo'),
            'asyncEnabled' => (bool) config('virtual_nail.async', true),
        ]);
    }

    public function status(): JsonResponse
    {
        $browser = $this->service->isChatgpt2apiConfigured();
        $imageApi = $this->service->isImageApiConfigured();

        return response()->json([
            'enabled' => $this->service->isConfigured(),
            'async' => (bool) config('virtual_nail.async', true),
            'prefer_browser_pool' => VirtualNailSettings::preferBrowserPool(),
            'providers' => [
                'image_api' => [
                    'configured' => $imageApi,
                    'endpoint' => 'images/edits',
                ],
                'chatgpt2api' => [
                    'configured' => $browser,
                    'healthy' => $browser ? $this->service->isBrowserPoolHealthy() : false,
                ],
            ],
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        if (! $this->service->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Virtual nail try-on is not available.',
            ], 503);
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $items = $this->service->listProductsForPicker(
            $validated['search'] ?? null,
            (int) ($validated['limit'] ?? 24)
        );

        return response()->json([
            'success' => true,
            'products' => $items,
        ]);
    }

    public function productOptions(int $productId): JsonResponse
    {
        if (! $this->service->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Virtual nail try-on is not available.',
            ], 503);
        }

        $product = Product::query()
            ->availableForDisplay()
            ->with(['template.variants', 'variants'])
            ->find($productId);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Selected nail design is not available.',
            ], 404);
        }

        $styleOptions = $this->service->styleOptionsForProduct($product);

        return response()->json([
            'success' => true,
            'product' => $this->service->resolveProductPayload($product),
            'shapes' => $styleOptions['shapes'],
            'lengths' => $styleOptions['lengths'],
        ]);
    }

    public function tryOn(Request $request): JsonResponse
    {
        if (! $this->service->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Virtual nail try-on is not available.',
            ], 503);
        }

        $maxKb = (int) config('virtual_nail.max_upload_kb', 8192);

        $validated = $request->validate([
            'hand_image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.$maxKb],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'nail_shape' => ['required', 'string', 'max:80'],
            'nail_length' => ['required', 'string', 'max:80'],
        ]);

        $product = Product::query()
            ->availableForDisplay()
            ->with(['template.variants', 'variants'])
            ->find((int) $validated['product_id']);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Selected nail design is not available.',
            ], 422);
        }

        $nailShape = $this->service->normalizeShape($validated['nail_shape'], $product);
        $nailLength = $this->service->normalizeLength($validated['nail_length'], $product);

        if ((bool) config('virtual_nail.async', true)) {
            return $this->queueTryOn($request, $product, $nailShape, $nailLength);
        }

        $result = $this->service->tryOn(
            $request->file('hand_image'),
            $product,
            $nailShape,
            $nailLength
        );

        $status = ($result['success'] ?? false) ? 200 : 422;

        return response()->json($result, $status);
    }

    public function trialStatus(Request $request, string $uuid): JsonResponse
    {
        $trial = VirtualNailTrial::query()->with('product:id,name,slug')->where('uuid', $uuid)->first();
        if (! $trial || ! $this->ownsTrial($request, $trial)) {
            return response()->json(['success' => false, 'message' => 'Try-on not found.'], 404);
        }

        $payload = $this->service->serializeTrial($trial);
        $payload['success'] = $trial->status === VirtualNailTrial::STATUS_COMPLETED;

        if ($trial->status === VirtualNailTrial::STATUS_COMPLETED && $payload['result_url']) {
            $payload['image'] = $payload['result_url'];
        }

        return response()->json($payload);
    }

    public function history(Request $request): JsonResponse
    {
        if (! $this->service->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Not available.'], 503);
        }

        $limit = (int) config('virtual_nail.history_limit', 20);
        $items = $this->service->listHistoryForOwner(
            $request->user()?->id,
            $request->session()->getId(),
            $limit
        );

        return response()->json([
            'success' => true,
            'trials' => $items->values(),
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        if (! $this->service->isConfigured()) {
            return response()->json(['success' => false, 'trials' => []], 503);
        }

        $items = $this->service->listPendingForOwner(
            $request->user()?->id,
            $request->session()->getId()
        );

        return response()->json([
            'success' => true,
            'trials' => $items->values(),
        ]);
    }

    public function trialResult(Request $request, string $uuid): StreamedResponse|JsonResponse
    {
        $trial = VirtualNailTrial::query()->where('uuid', $uuid)->first();
        if (! $trial || ! $this->ownsTrial($request, $trial) || ! $trial->result_image_path) {
            abort(404);
        }

        return $this->streamTrialFile($trial->result_image_path, 'virtual-nail-result.png');
    }

    public function trialHand(Request $request, string $uuid): StreamedResponse|JsonResponse
    {
        $trial = VirtualNailTrial::query()->where('uuid', $uuid)->first();
        if (! $trial || ! $this->ownsTrial($request, $trial)) {
            abort(404);
        }

        return $this->streamTrialFile($trial->hand_image_path, 'virtual-nail-hand.jpg');
    }

    private function queueTryOn(Request $request, Product $product, string $nailShape, string $nailLength): JsonResponse
    {
        $trial = VirtualNailTrial::query()->create([
            'user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId(),
            'product_id' => $product->id,
            'nail_shape' => $nailShape,
            'nail_length' => $nailLength,
            'hand_image_path' => '',
            'status' => VirtualNailTrial::STATUS_PENDING,
        ]);

        $handPath = $this->service->storeHandImage($trial->uuid, $request->file('hand_image'));
        if ($handPath === null) {
            $trial->delete();

            return response()->json([
                'success' => false,
                'message' => 'Could not save the hand photo. Please try again.',
            ], 422);
        }

        $trial->update(['hand_image_path' => $handPath]);

        ProcessVirtualNailTrialJob::dispatch($trial->id);

        return response()->json([
            'success' => true,
            'async' => true,
            'trial_id' => $trial->uuid,
            'status' => $trial->status,
            'message' => 'Your preview is being generated. You can keep browsing — we will notify you when it is ready.',
            'status_url' => route('api.virtual-nail.trials.status', $trial->uuid),
        ], 202);
    }

    private function ownsTrial(Request $request, VirtualNailTrial $trial): bool
    {
        return $trial->isOwnedBy($request->user()?->id, $request->session()->getId());
    }

    private function streamTrialFile(string $path, string $downloadName): StreamedResponse
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            abort(404);
        }

        $mime = mime_content_type($disk->path($path)) ?: 'image/png';

        return response()->stream(function () use ($disk, $path) {
            echo $disk->get($path);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
