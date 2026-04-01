<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\ReferenceNailSizeChart;

class SizingKitController extends Controller
{
    /**
     * Slug category dùng cho Sizing Kit (admin tạo category này và gán sản phẩm vào).
     */
    public const SIZING_KIT_CATEGORY_SLUG = 'sizing-kit';

    /**
     * Hiển thị trang Sizing Kit: hero, why buy, choose shape (từ DB), how to use, size chart, FAQ.
     */
    public function index()
    {
        $sizeChart = [
            'XS' => ['mm' => '14-10-11-10-8mm', 'label' => 'Extra Small'],
            'S'  => ['mm' => '15-11-12-11-9mm', 'label' => 'Small'],
            'M'  => ['mm' => '16-12-13-12-10mm', 'label' => 'Medium'],
            'L'  => ['mm' => '18-13-14-13-12mm', 'label' => 'Large'],
        ];

        $sizeChartTable = ReferenceNailSizeChart::table();

        // Sản phẩm thuộc category "Sizing Kit" (slug = sizing-kit)
        $sizingKitCategory = Category::where('slug', self::SIZING_KIT_CATEGORY_SLUG)->first();
        $shapeKits = collect();
        if ($sizingKitCategory) {
            $shapeKits = Product::with('template')
                ->whereHas('template', function ($q) use ($sizingKitCategory) {
                    $q->where('category_id', $sizingKitCategory->id);
                })
                ->availableForDisplay()
                ->orderBy('name')
                ->get();
        }

        return view('sizing-kit.index', [
            'sizeChart' => $sizeChart,
            'sizeChartTable' => $sizeChartTable,
            'shapeKits' => $shapeKits,
            'sizingKitCategory' => $sizingKitCategory,
        ]);
    }
}
