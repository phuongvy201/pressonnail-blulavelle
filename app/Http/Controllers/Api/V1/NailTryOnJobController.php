<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApiV1\NailTryOnService;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NailTryOnJobController extends Controller
{
    public function __construct(private NailTryOnService $tryOns)
    {
    }

    public function store(Request $request): JsonResponse
    {
        return $this->tryOns->create($request);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->tryOns->index($request);
    }

    public function show(Request $request, string $tryOnId): JsonResponse
    {
        $job = $this->tryOns->findOwned($tryOnId, $request);
        if (! $job) {
            return ApiResponse::error('ASSET_NOT_OWNED', 'Try-on not found or not owned by caller.', 403);
        }

        return $this->tryOns->show($job);
    }

    public function variants(Request $request, string $tryOnId): JsonResponse
    {
        $job = $this->tryOns->findOwned($tryOnId, $request);
        if (! $job) {
            return ApiResponse::error('ASSET_NOT_OWNED', 'Try-on not found or not owned by caller.', 403);
        }

        return $this->tryOns->variants($request, $job);
    }

    public function retry(Request $request, string $tryOnId): JsonResponse
    {
        $job = $this->tryOns->findOwned($tryOnId, $request);
        if (! $job) {
            return ApiResponse::error('ASSET_NOT_OWNED', 'Try-on not found or not owned by caller.', 403);
        }

        return $this->tryOns->retry($request, $job);
    }

    public function destroy(Request $request, string $tryOnId): JsonResponse
    {
        $job = $this->tryOns->findOwned($tryOnId, $request);
        if (! $job) {
            return ApiResponse::error('ASSET_NOT_OWNED', 'Try-on not found or not owned by caller.', 403);
        }

        return $this->tryOns->destroy($job);
    }
}
