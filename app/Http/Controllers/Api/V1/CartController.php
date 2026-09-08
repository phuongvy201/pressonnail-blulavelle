<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\CartController as StorefrontCartController;
use App\Http\Controllers\Api\V1\Concerns\WrapsStorefrontJson;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use WrapsStorefrontJson;

    public function index(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->get($request));
    }

    public function checkoutSnapshot(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->checkout($request));
    }

    public function store(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->add($request));
    }

    public function update(Request $request, int $itemId, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->update($request, $itemId));
    }

    public function destroy(Request $request, int $itemId, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->remove($request, $itemId));
    }

    public function clear(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->clear($request));
    }

    public function setDiscountMode(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->setDiscountMode($request));
    }

    public function applyPromo(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->applyPromo($request));
    }

    public function removePromo(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->removePromo($request));
    }

    public function applyGiftCard(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->applyGiftCard($request));
    }

    public function removeGiftCard(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->removeGiftCard($request));
    }

    public function sync(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->sync($request));
    }
}
