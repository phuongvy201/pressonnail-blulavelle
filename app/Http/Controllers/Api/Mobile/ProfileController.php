<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ProfileController as StorefrontProfileController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request, StorefrontProfileController $controller): JsonResponse
    {
        return $controller->show($request);
    }

    public function update(Request $request, StorefrontProfileController $controller): JsonResponse
    {
        return $controller->update($request);
    }

    public function updateAddress(Request $request, StorefrontProfileController $controller): JsonResponse
    {
        return $controller->updateAddress($request);
    }

    public function updatePassword(Request $request, StorefrontProfileController $controller): JsonResponse
    {
        return $controller->updatePassword($request);
    }
}
