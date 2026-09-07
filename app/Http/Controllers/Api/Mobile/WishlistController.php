<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\WishlistController as StorefrontWishlistController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->index($request);
    }

    public function add(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->add($request);
    }

    public function remove(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->remove($request);
    }

    public function toggle(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->toggle($request);
    }

    public function count(StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->count();
    }

    public function check(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->check($request);
    }

    public function clear(StorefrontWishlistController $controller): JsonResponse
    {
        return $controller->clear();
    }
}
