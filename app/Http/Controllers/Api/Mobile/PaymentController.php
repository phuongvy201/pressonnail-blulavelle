<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Payment\StripePaymentController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function createStripeIntent(Request $request, StripePaymentController $controller): JsonResponse
    {
        return $controller->createPaymentIntent($request);
    }
}
