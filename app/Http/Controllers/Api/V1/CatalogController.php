<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductTemplateController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\V1\Concerns\WrapsStorefrontJson;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SearchController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    use WrapsStorefrontJson;

    public function products(Request $request, ProductController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->index($request));
    }

    public function productBySlug(string $slug, ProductController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->showBySlug($slug));
    }

    public function productById(int $id, ProductController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->show($id));
    }

    public function collections(Request $request, CollectionController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->index($request));
    }

    public function shops(Request $request, ShopController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->index($request));
    }

    public function templates(Request $request, ProductTemplateController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->index($request));
    }

    public function template(int $id, ProductTemplateController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->show($id));
    }

    public function searchSuggestions(Request $request, SearchController $controller): JsonResponse
    {
        return $this->wrapStorefront($controller->suggestions($request));
    }
}
