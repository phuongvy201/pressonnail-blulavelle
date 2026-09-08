<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\VirtualNailTrialService;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;

class NailTryOnController extends Controller
{
    public function __construct(private readonly VirtualNailTrialService $virtualNail)
    {
    }

    /**
     * GET /api/v1/nail/products/{productId}/try-on-config
     */
    public function tryOnConfig(int $productId): JsonResponse
    {
        $config = $this->virtualNail->tryOnConfigForProduct($productId);

        $status = match ($config['unsupportedReason']) {
            'PRODUCT_NOT_FOUND' => 404,
            'FEATURE_DISABLED', 'PROVIDER_NOT_CONFIGURED' => 503,
            default => 200,
        };

        // Still return 200 with tryOnEnabled=false for missing design asset so the app can show reason in-product.
        if ($config['unsupportedReason'] === 'MISSING_DESIGN_ASSET') {
            $status = 200;
        }

        if (in_array($status, [404, 503], true) && ! $config['tryOnEnabled']) {
            return ApiResponse::error(
                (string) $config['unsupportedReason'],
                (string) ($config['unsupportedMessage'] ?? 'Virtual nail try-on is not available.'),
                $status
            );
        }

        return ApiResponse::success($config, [
            'api' => 'nail.try-on-config',
            'version' => config('api_v1.version', '1.0.0'),
        ]);
    }
}
