<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\WrapsStorefrontJson;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Payment\StripePaymentController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use WrapsStorefrontJson;

    public function createStripeIntent(Request $request, StripePaymentController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->createPaymentIntent($request));
    }
}
