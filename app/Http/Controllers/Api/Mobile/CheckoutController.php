<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CheckoutController as StoreCheckoutController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function process(Request $request, StoreCheckoutController $checkout): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return $checkout->process($request);
    }

    public function calculateShipping(Request $request, StoreCheckoutController $checkout): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return $checkout->calculateShipping($request);
    }

    public function shippingRates(Request $request, StoreCheckoutController $checkout): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return $checkout->getShippingRates($request);
    }
}
