<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\CartController as StorefrontCartController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Delegates to storefront cart API (session + user_id). Mobile middleware supplies session context.
 */
class CartController extends Controller
{
    public function index(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $controller->get($request);
    }

    public function checkoutSnapshot(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $controller->checkout($request);
    }

    public function store(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $controller->add($request);
    }

    public function update(Request $request, int $itemId, StorefrontCartController $controller): JsonResponse
    {
        return $controller->update($request, $itemId);
    }

    public function destroy(Request $request, int $itemId, StorefrontCartController $controller): JsonResponse
    {
        return $controller->remove($request, $itemId);
    }

    public function clear(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $controller->clear($request);
    }

    public function setDiscountMode(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $controller->setDiscountMode($request);
    }

    public function applyPromo(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $controller->applyPromo($request);
    }

    public function removePromo(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $controller->removePromo($request);
    }

    public function applyGiftCard(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $controller->applyGiftCard($request);
    }

    public function removeGiftCard(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $controller->removeGiftCard($request);
    }

    public function sync(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $controller->sync($request);
    }
}
