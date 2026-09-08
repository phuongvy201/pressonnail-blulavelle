<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Mobile\OrderController as MobileOrderController;
use App\Http\Controllers\Api\OrderController as StorefrontOrderController;
use App\Http\Controllers\Api\V1\Concerns\WrapsStorefrontJson;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use WrapsStorefrontJson;

    public function index(Request $request, StorefrontOrderController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->index($request));
    }

    public function show(Request $request, string $orderNumber, StorefrontOrderController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->show($request, $orderNumber));
    }

    public function track(Request $request, MobileOrderController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->track($request));
    }

    public function cancel(Request $request, string $orderNumber, MobileOrderController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->cancel($request, $orderNumber));
    }

    public function returnRequest(Request $request, string $orderNumber, MobileOrderController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->returnRequest($request, $orderNumber));
    }
}
