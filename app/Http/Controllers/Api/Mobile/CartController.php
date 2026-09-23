<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\CartController as StorefrontCartController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Delegates to storefront cart API (session + user_id). Mobile middleware supplies session context.
 * Promo / gift-card / discount-mode mutations return a refreshed cart payload for Cart & Checkout screens.
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
        return $this->mutationThenCart($request, $controller, fn () => $controller->setDiscountMode($request));
    }

    public function applyPromo(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->mutationThenCart($request, $controller, fn () => $controller->applyPromo($request));
    }

    public function removePromo(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->mutationThenCart($request, $controller, fn () => $controller->removePromo());
    }

    public function applyGiftCard(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->mutationThenCart($request, $controller, fn () => $controller->applyGiftCard($request));
    }

    public function removeGiftCard(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->mutationThenCart($request, $controller, fn () => $controller->removeGiftCard());
    }

    public function sync(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $controller->sync($request);
    }

    /**
     * @param  callable(): JsonResponse  $mutation
     */
    private function mutationThenCart(
        Request $request,
        StorefrontCartController $controller,
        callable $mutation
    ): JsonResponse {
        $result = $mutation();
        $status = $result->getStatusCode();
        $body = $result->getData(true);

        if (! is_array($body) || (($body['success'] ?? false) !== true) || $status >= 400) {
            return $result;
        }

        $cart = $controller->get($request);
        $cartBody = $cart->getData(true);
        if (! is_array($cartBody)) {
            return $result;
        }

        $cartBody['success'] = true;
        if (isset($body['message']) && is_string($body['message'])) {
            $cartBody['message'] = $body['message'];
        }
        if (isset($body['applied_promo_code'])) {
            $cartBody['applied_promo_code'] = $body['applied_promo_code'];
        }
        if (isset($body['applied_gift_card_code'])) {
            $cartBody['applied_gift_card_code'] = $body['applied_gift_card_code'];
        }
        if (isset($body['mode'])) {
            $cartBody['mode'] = $body['mode'];
        }

        $out = response()->json($cartBody, $cart->getStatusCode());
        foreach (['X-Guest-Cart-Token'] as $header) {
            if ($cart->headers->has($header)) {
                $out->headers->set($header, $cart->headers->get($header));
            }
            if ($result->headers->has($header) && ! $out->headers->has($header)) {
                $out->headers->set($header, $result->headers->get($header));
            }
        }

        return $out;
    }
}
