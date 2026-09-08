<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\WrapsStorefrontJson;
use App\Http\Controllers\Api\WishlistController as StorefrontWishlistController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    use WrapsStorefrontJson;

    public function index(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->index($request));
    }

    public function add(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->add($request));
    }

    public function remove(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->remove($request));
    }

    public function toggle(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->toggle($request));
    }

    public function count(StorefrontWishlistController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->count());
    }

    public function check(Request $request, StorefrontWishlistController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->check($request));
    }

    public function clear(StorefrontWishlistController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->clear());
    }
}
