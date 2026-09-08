<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\WrapsStorefrontJson;
use App\Http\Controllers\CheckoutController as StoreCheckoutController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shipping helpers for iOS. Order creation uses POST /api/v1/checkout/attempts.
 */
class CheckoutController extends Controller
{
    use WrapsStorefrontJson;

    public function calculateShipping(Request $request, StoreCheckoutController $checkout): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return $this->wrapStorefront($checkout->calculateShipping($request));
    }

    public function shippingRates(Request $request, StoreCheckoutController $checkout): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return $this->wrapStorefront($checkout->getShippingRates($request));
    }
}
