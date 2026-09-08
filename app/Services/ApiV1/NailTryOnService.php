<?php

namespace App\Services\ApiV1;

use App\Jobs\ProcessNailTryOnJob;
use App\Models\ApiUploadAsset;
use App\Models\NailTryOn;
use App\Models\Product;
use App\Services\VirtualNailTrialService;
use App\Support\ApiV1\ApiResponse;
use App\Support\VirtualNailSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NailTryOnService
{
    public function __construct(
        private ApiUploadService $uploads,
        private VirtualNailTrialService $virtualNail,
    ) {}

    public function ownerFrom(Request $request): array
    {
        return [
            'user_id' => $request->user()?->id,
            'guest_token' => $request->header(config('api_v1.guest_cart_header', 'X-Guest-Cart-Token')),
        ];
    }

    public function findOwned(string $publicId, Request $request): ?NailTryOn
    {
        $job = NailTryOn::query()
            ->with(['handAsset', 'outputAsset', 'product'])
            ->where('public_id', $publicId)
            ->first();

        if (! $job) {
            return null;
        }

        $owner = $this->ownerFrom($request);
        if (! $job->isOwnedBy($owner['user_id'], $owner['guest_token'])) {
            return null;
        }

        return $job;
    }

    public function create(Request $request): JsonResponse
    {
        $key = $this->idempotencyKey($request);
        if ($key === null) {
            return ApiResponse::error(
                'IDEMPOTENCY_KEY_REQUIRED',
                'Send Idempotency-Key so retries do not create billed AI jobs.',
                400
            );
        }

        $validated = $request->validate([
            'handImageAssetId' => ['required', 'string', 'max:64'],
            'productId' => ['required', 'integer', 'min:1'],
            'variantId' => ['nullable', 'integer', 'min:1'],
            'tryOnAssetVersion' => ['nullable', 'string', 'max:120'],
            'handSide' => ['nullable', 'string', 'in:auto,left,right'],
            'shape' => ['nullable', 'string', 'max:80'],
            'length' => ['nullable', 'string', 'max:80'],
        ]);

        $owner = $this->ownerFrom($request);
        if (! $owner['user_id'] && ! $owner['guest_token']) {
            return ApiResponse::error('UNAUTHENTICATED', 'Auth or guest token required.', 401);
        }

        $hash = hash('sha256', json_encode([
            'hand' => $validated['handImageAssetId'],
            'product' => (int) $validated['productId'],
            'variant' => $validated['variantId'] ?? null,
            'version' => $validated['tryOnAssetVersion'] ?? null,
            'side' => $validated['handSide'] ?? 'auto',
        ]));

        $existing = NailTryOn::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            if ($existing->request_hash && ! hash_equals($existing->request_hash, $hash)) {
                return ApiResponse::error(
                    'IDEMPOTENCY_KEY_REUSED',
                    'Idempotency-Key was already used with a different payload.',
                    409
                );
            }

            return ApiResponse::success($this->payload($existing->load(['handAsset', 'outputAsset', 'product'])), [
                'replayed' => true,
            ]);
        }

        if ($deny = $this->rateLimitDeny($owner)) {
            return $deny;
        }

        $hand = ApiUploadAsset::query()->where('public_id', $validated['handImageAssetId'])->first();
        if (! $hand || ! $hand->isOwnedBy($owner['user_id'], $owner['guest_token'])) {
            return ApiResponse::error('ASSET_NOT_OWNED', 'Hand image asset not found or not owned.', 403);
        }
        if ($hand->isExpired() || $hand->trashed()) {
            return ApiResponse::error('ASSET_EXPIRED', 'Hand image asset expired.', 410);
        }
        if (! $hand->isReady() || $hand->purpose !== ApiUploadAsset::PURPOSE_VIRTUAL_TRY_ON_HAND) {
            return ApiResponse::error('UNSUPPORTED_IMAGE', 'Hand image asset is not ready for try-on.', 422);
        }

        $config = $this->virtualNail->tryOnConfigForProduct((int) $validated['productId']);
        if (! ($config['tryOnEnabled'] ?? false)) {
            $code = (string) ($config['unsupportedReason'] ?? 'TRY_ON_NOT_AVAILABLE_FOR_PRODUCT');
            if ($code === 'FEATURE_DISABLED' || $code === 'PROVIDER_NOT_CONFIGURED' || $code === 'PRODUCT_NOT_FOUND' || $code === 'MISSING_DESIGN_ASSET') {
                $code = 'TRY_ON_NOT_AVAILABLE_FOR_PRODUCT';
            }

            return ApiResponse::error(
                $code,
                (string) ($config['unsupportedMessage'] ?? 'Try-on is not available for this product.'),
                422
            );
        }

        $shape = $validated['shape'] ?? $config['defaults']['shape'] ?? VirtualNailSettings::defaultShape();
        $length = $validated['length'] ?? $config['defaults']['length'] ?? VirtualNailSettings::defaultLength();
        $version = $validated['tryOnAssetVersion'] ?? $config['tryOnAssetVersion'];

        $ttlHours = (int) config('api_v1.try_on_ttl_hours', 72);

        try {
            $job = DB::transaction(function () use ($owner, $hand, $validated, $shape, $length, $version, $key, $hash, $ttlHours) {
                return NailTryOn::query()->create([
                    'user_id' => $owner['user_id'],
                    'guest_token' => $owner['guest_token'],
                    'hand_asset_id' => $hand->id,
                    'product_id' => (int) $validated['productId'],
                    'variant_id' => $validated['variantId'] ?? null,
                    'try_on_asset_version' => $version,
                    'hand_side' => $validated['handSide'] ?? 'auto',
                    'nail_shape' => $shape,
                    'nail_length' => $length,
                    'status' => NailTryOn::STATUS_QUEUED,
                    'idempotency_key' => $key,
                    'request_hash' => $hash,
                    'expires_at' => now()->addHours($ttlHours),
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $replay = NailTryOn::query()->where('idempotency_key', $key)->first();
            if ($replay) {
                return ApiResponse::success($this->payload($replay->load(['handAsset', 'outputAsset', 'product'])), [
                    'replayed' => true,
                ]);
            }
            throw $e;
        }

        ProcessNailTryOnJob::dispatch($job->id);

        return ApiResponse::success($this->payload($job->load(['handAsset', 'outputAsset', 'product'])), [
            'replayed' => false,
        ], 202);
    }

    public function show(NailTryOn $job): JsonResponse
    {
        $this->expireIfNeeded($job);

        return ApiResponse::success($this->payload($job->fresh(['handAsset', 'outputAsset', 'product'])));
    }

    public function index(Request $request): JsonResponse
    {
        $owner = $this->ownerFrom($request);
        $query = NailTryOn::query()
            ->with(['handAsset', 'outputAsset', 'product'])
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        if ($owner['user_id']) {
            $query->where('user_id', $owner['user_id']);
        } elseif ($owner['guest_token']) {
            $query->where('guest_token', $owner['guest_token']);
        } else {
            return ApiResponse::error('UNAUTHENTICATED', 'Auth or guest token required.', 401);
        }

        $perPage = min(50, max(1, (int) $request->query('perPage', 20)));
        $page = max(1, (int) $request->query('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn (NailTryOn $job) => $this->payload($job))
            ->values()
            ->all();

        return ApiResponse::success([
            'items' => $items,
        ], [
            'page' => $paginator->currentPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'hasNextPage' => $paginator->hasMorePages(),
        ]);
    }

    public function variants(Request $request, NailTryOn $parent): JsonResponse
    {
        $key = $this->idempotencyKey($request) ?: ('variant-'.$parent->public_id.'-'.Str::uuid());

        $validated = $request->validate([
            'productId' => ['required', 'integer', 'min:1'],
            'variantId' => ['nullable', 'integer', 'min:1'],
            'tryOnAssetVersion' => ['nullable', 'string', 'max:120'],
            'shape' => ['nullable', 'string', 'max:80'],
            'length' => ['nullable', 'string', 'max:80'],
        ]);

        if ($parent->status === NailTryOn::STATUS_DELETED || $parent->trashed()) {
            return ApiResponse::error('NOT_FOUND', 'Parent try-on was deleted.', 404);
        }

        $hand = $parent->handAsset;
        if (! $hand || ! $hand->isReady()) {
            return ApiResponse::error('ASSET_EXPIRED', 'Parent hand image is no longer available.', 410);
        }

        $request->merge([
            'handImageAssetId' => $hand->public_id,
            'productId' => $validated['productId'],
            'variantId' => $validated['variantId'] ?? null,
            'tryOnAssetVersion' => $validated['tryOnAssetVersion'] ?? null,
            'handSide' => $parent->hand_side,
            'shape' => $validated['shape'] ?? $parent->nail_shape,
            'length' => $validated['length'] ?? $parent->nail_length,
        ]);
        $request->headers->set('Idempotency-Key', $key);

        $response = $this->create($request);
        $data = $response->getData(true);
        if (($data['success'] ?? false) && isset($data['data']['tryOnId'])) {
            NailTryOn::query()
                ->where('public_id', $data['data']['tryOnId'])
                ->whereNull('parent_try_on_id')
                ->update(['parent_try_on_id' => $parent->id]);
            $child = NailTryOn::query()->where('public_id', $data['data']['tryOnId'])->first();
            if ($child) {
                return ApiResponse::success($this->payload($child->load(['handAsset', 'outputAsset', 'product'])), [
                    'replayed' => (bool) data_get($data, 'meta.replayed', false),
                    'parentTryOnId' => $parent->public_id,
                ], $response->getStatusCode());
            }
        }

        return $response;
    }

    public function retry(Request $request, NailTryOn $job): JsonResponse
    {
        $key = $this->idempotencyKey($request);
        if ($key === null) {
            return ApiResponse::error('IDEMPOTENCY_KEY_REQUIRED', 'Send Idempotency-Key for safe retry.', 400);
        }

        $existing = NailTryOn::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            return ApiResponse::success($this->payload($existing->load(['handAsset', 'outputAsset', 'product'])), [
                'replayed' => true,
            ]);
        }

        if (! in_array($job->status, [NailTryOn::STATUS_FAILED, NailTryOn::STATUS_EXPIRED], true)) {
            return ApiResponse::error(
                'CONFLICT',
                'Only failed or expired try-ons can be retried.',
                409
            );
        }

        $hand = $job->handAsset;
        if (! $hand || ! $hand->isReady()) {
            return ApiResponse::error('ASSET_EXPIRED', 'Hand image asset expired.', 410);
        }

        $request->merge([
            'handImageAssetId' => $hand->public_id,
            'productId' => $job->product_id,
            'variantId' => $job->variant_id,
            'tryOnAssetVersion' => $job->try_on_asset_version,
            'handSide' => $job->hand_side,
            'shape' => $job->nail_shape,
            'length' => $job->nail_length,
        ]);
        $request->headers->set('Idempotency-Key', $key);

        $response = $this->create($request);
        $data = $response->getData(true);
        if (($data['success'] ?? false) && isset($data['data']['tryOnId'])) {
            NailTryOn::query()
                ->where('public_id', $data['data']['tryOnId'])
                ->update(['parent_try_on_id' => $job->parent_try_on_id ?: $job->id]);
        }

        return $response;
    }

    public function destroy(NailTryOn $job): JsonResponse
    {
        DB::transaction(function () use ($job) {
            $job->update([
                'status' => NailTryOn::STATUS_DELETED,
                'error_code' => null,
                'error_message' => null,
            ]);

            if ($job->outputAsset) {
                $this->uploads->purge($job->outputAsset);
                $job->output_asset_id = null;
                $job->save();
            }

            $job->delete();
        });

        return ApiResponse::success([
            'deleted' => true,
            'tryOnId' => $job->public_id,
        ]);
    }

    public function payload(NailTryOn $job): array
    {
        $this->expireIfNeeded($job);

        $outputUrl = null;
        $outputAssetId = null;
        if ($job->status === NailTryOn::STATUS_SUCCEEDED && $job->outputAsset && $job->outputAsset->isReady()) {
            $outputAssetId = $job->outputAsset->public_id;
            $outputUrl = $this->uploads->temporaryUrl(
                $job->outputAsset,
                (int) config('api_v1.signed_url_ttl_minutes', 15)
            );
        }

        return [
            'tryOnId' => $job->public_id,
            'status' => $job->status,
            'handImageAssetId' => $job->handAsset?->public_id,
            'productId' => $job->product_id,
            'variantId' => $job->variant_id,
            'tryOnAssetVersion' => $job->try_on_asset_version,
            'handSide' => $job->hand_side,
            'shape' => $job->nail_shape,
            'length' => $job->nail_length,
            'parentTryOnId' => $job->parent?->public_id,
            'outputAssetId' => $outputAssetId,
            'resultImageUrl' => $outputUrl,
            'resultUrlExpiresInSeconds' => $outputUrl ? ((int) config('api_v1.signed_url_ttl_minutes', 15) * 60) : null,
            'errorCode' => $job->error_code,
            'errorMessage' => $job->error_message,
            'provider' => $job->provider,
            'createdAt' => ApiResponse::iso($job->created_at),
            'completedAt' => ApiResponse::iso($job->completed_at),
            'expiresAt' => ApiResponse::iso($job->expires_at),
        ];
    }

    private function expireIfNeeded(NailTryOn $job): void
    {
        if ($job->trashed() || $job->status === NailTryOn::STATUS_DELETED) {
            return;
        }

        if ($job->expires_at && $job->expires_at->isPast() && ! $job->isTerminal()) {
            $job->update([
                'status' => NailTryOn::STATUS_EXPIRED,
                'error_code' => 'ASSET_EXPIRED',
                'error_message' => 'Try-on job expired before completion.',
                'completed_at' => now(),
            ]);
        } elseif ($job->expires_at && $job->expires_at->isPast() && $job->status === NailTryOn::STATUS_SUCCEEDED) {
            // Result URLs stop resolving after purge window; mark expired for client clarity.
            if ($job->outputAsset && $job->outputAsset->isReady()) {
                // keep succeeded but note expiry via expiresAt; optional soft expire
            }
        }
    }

    private function idempotencyKey(Request $request): ?string
    {
        $key = $request->header('Idempotency-Key')
            ?: $request->header('X-Idempotency-Key')
            ?: $request->input('idempotencyKey');

        if (! is_string($key)) {
            return null;
        }

        $key = trim($key);

        return preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $key) ? $key : null;
    }

    private function rateLimitDeny(array $owner): ?JsonResponse
    {
        $daily = (int) config('api_v1.try_on_daily_limit', 20);
        $query = NailTryOn::query()->where('created_at', '>=', now()->subDay());
        if ($owner['user_id']) {
            $query->where('user_id', $owner['user_id']);
        } else {
            $query->where('guest_token', $owner['guest_token']);
        }

        if ($query->count() >= $daily) {
            return ApiResponse::error(
                'RATE_LIMITED',
                'Daily virtual try-on limit reached.',
                429
            );
        }

        return null;
    }
}
