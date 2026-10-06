<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\WrapsStorefrontJson;
use App\Http\Controllers\Controller;
use App\Http\Controllers\VirtualNailTrialController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VirtualNailController extends Controller
{
    use WrapsStorefrontJson;

    public function status(VirtualNailTrialController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->status());
    }

    public function products(Request $request, VirtualNailTrialController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->products($request));
    }

    public function productSuggestions(Request $request, VirtualNailTrialController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->productSuggestions($request));
    }

    public function pickerMeta(VirtualNailTrialController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->pickerMeta());
    }

    public function productOptions(int $productId, VirtualNailTrialController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->productOptions($productId));
    }

    public function tryOn(Request $request, VirtualNailTrialController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->tryOn($request));
    }

    public function history(Request $request, VirtualNailTrialController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->history($request));
    }

    public function pending(Request $request, VirtualNailTrialController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->pending($request));
    }

    public function trialStatus(Request $request, string $uuid, VirtualNailTrialController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->trialStatus($request, $uuid));
    }

    public function trialResult(Request $request, string $uuid, VirtualNailTrialController $controller): Response
    {
        $result = $controller->trialResult($request, $uuid);

        return $result instanceof JsonResponse
            ? $this->wrapStorefront($result)
            : $result;
    }

    public function trialHand(Request $request, string $uuid, VirtualNailTrialController $controller): Response
    {
        $result = $controller->trialHand($request, $uuid);

        return $result instanceof JsonResponse
            ? $this->wrapStorefront($result)
            : $result;
    }
}
