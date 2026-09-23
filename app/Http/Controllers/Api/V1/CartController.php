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
        return $this->wrapMutationThenCart($request, $controller, fn () => $controller->setDiscountMode($request));
    }

    public function applyPromo(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapMutationThenCart($request, $controller, fn () => $controller->applyPromo($request));
    }

    public function removePromo(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapMutationThenCart($request, $controller, fn () => $controller->removePromo());
    }

    public function applyGiftCard(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapMutationThenCart($request, $controller, fn () => $controller->applyGiftCard($request));
    }

    public function removeGiftCard(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapMutationThenCart($request, $controller, fn () => $controller->removeGiftCard());
    }

    public function sync(Request $request, StorefrontCartController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->sync($request));
    }

    /**
     * Run a cart mutation, then return the refreshed CartData (with volume/promo summary).
     *
     * @param  callable(): JsonResponse  $mutation
     */
    private function wrapMutationThenCart(
        Request $request,
        StorefrontCartController $controller,
        callable $mutation
    ): JsonResponse {
        $result = $mutation();
        $status = $result->getStatusCode();
        $body = $result->getData(true);

        if (! is_array($body) || (($body['success'] ?? false) !== true) || $status >= 400) {
            return $this->wrapStorefront($result);
        }

        $cart = $this->wrapStorefront($controller->get($request));
        $message = isset($body['message']) && is_string($body['message']) && $body['message'] !== ''
            ? $body['message']
            : null;

        if ($message === null) {
            return $cart;
        }

        $payload = $cart->getData(true);
        if (! is_array($payload)) {
            return $cart;
        }

        $payload['meta'] = array_merge(
            is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            ['message' => $message]
        );

        $out = response()->json($payload, $cart->getStatusCode());
        foreach (['X-Guest-Cart-Token', 'Idempotency-Replayed', 'Idempotency-Key', 'X-Request-ID'] as $header) {
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
