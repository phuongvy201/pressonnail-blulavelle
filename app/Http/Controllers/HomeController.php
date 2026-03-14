<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Collection;
use App\Models\ProductTemplate;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Get featured collections
        $featuredCollections = Collection::with(['products.template'])
            ->where('featured', true)
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        // Get featured products (chỉ lấy sản phẩm đủ điều kiện hiển thị)
        $featuredProducts = Product::with(['template.category', 'shop'])
            ->availableForDisplay()
            ->inRandomOrder()
            ->limit(8)
            ->get();

        // Get popular templates
        $popularTemplates = ProductTemplate::with(['category', 'user'])
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $canEdit = auth()->check() && auth()->user()->hasRole('admin');
        $editMode = request()->boolean('edit');
        return view('home', compact('featuredCollections', 'featuredProducts', 'popularTemplates', 'canEdit', 'editMode'));
    }

    /**
     * Preview trang chủ với chế độ chỉnh sửa (chỉ admin). Gọi từ dashboard admin.
     */
    public function preview()
    {
        $featuredCollections = Collection::with(['products.template'])
            ->where('featured', true)
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $featuredProducts = Product::with(['template.category', 'shop'])
            ->availableForDisplay()
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $popularTemplates = ProductTemplate::with(['category', 'user'])
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $canEdit = true;
        $editMode = true;
        return view('home', compact('featuredCollections', 'featuredProducts', 'popularTemplates', 'canEdit', 'editMode'));
    }
}
