<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\VirtualNailTrialController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VirtualNailController extends Controller
{
    public function status(VirtualNailTrialController $controller): JsonResponse
    {
        return $controller->status();
    }

    public function products(Request $request, VirtualNailTrialController $controller): JsonResponse
    {
        return $controller->products($request);
    }

    public function productSuggestions(Request $request, VirtualNailTrialController $controller): JsonResponse
    {
        return $controller->productSuggestions($request);
    }

    public function pickerMeta(VirtualNailTrialController $controller): JsonResponse
    {
        return $controller->pickerMeta();
    }

    public function productOptions(int $productId, VirtualNailTrialController $controller): JsonResponse
    {
        return $controller->productOptions($productId);
    }

    public function tryOn(Request $request, VirtualNailTrialController $controller): JsonResponse
    {
        return $controller->tryOn($request);
    }

    public function history(Request $request, VirtualNailTrialController $controller): JsonResponse
    {
        return $controller->history($request);
    }

    public function pending(VirtualNailTrialController $controller): JsonResponse
    {
        return $controller->pending();
    }

    public function trialStatus(string $uuid, VirtualNailTrialController $controller): JsonResponse
    {
        return $controller->trialStatus($uuid);
    }

    public function trialResult(string $uuid, VirtualNailTrialController $controller): JsonResponse
    {
        return $controller->trialResult($uuid);
    }

    public function trialHand(string $uuid, VirtualNailTrialController $controller)
    {
        return $controller->trialHand($uuid);
    }
}
