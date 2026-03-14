<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

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

        // Bảng tham chiếu chi tiết (Thumb, Index, Middle, Ring, Pinky)
        $sizeChartTable = [
            ['preset' => 'XS', 'thumb' => ['mm' => 14, 'num' => 3], 'index' => ['mm' => 11, 'num' => 6], 'middle' => ['mm' => 12, 'num' => 5], 'ring' => ['mm' => 10, 'num' => 7], 'pinky' => ['mm' => 8, 'num' => 9]],
            ['preset' => 'S',  'thumb' => ['mm' => 15, 'num' => 2], 'index' => ['mm' => 12, 'num' => 5], 'middle' => ['mm' => 13, 'num' => 4], 'ring' => ['mm' => 11, 'num' => 6], 'pinky' => ['mm' => 9, 'num' => 8]],
            ['preset' => 'M',  'thumb' => ['mm' => 16, 'num' => 1], 'index' => ['mm' => 12, 'num' => 5], 'middle' => ['mm' => 13, 'num' => 4], 'ring' => ['mm' => 11, 'num' => 6], 'pinky' => ['mm' => 10, 'num' => 7]],
            ['preset' => 'L',  'thumb' => ['mm' => 18, 'num' => 0], 'index' => ['mm' => 14, 'num' => 3], 'middle' => ['mm' => 15, 'num' => 2], 'ring' => ['mm' => 13, 'num' => 4], 'pinky' => ['mm' => 11, 'num' => 6]],
        ];

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
