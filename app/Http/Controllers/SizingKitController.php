<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Support\ReferenceNailSizeChart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SizingKitController extends Controller
{
    /**
     * Slug category dùng cho Sizing Kit (admin tạo category này và gán sản phẩm vào).
     */
    public const SIZING_KIT_CATEGORY_SLUG = 'sizing-kit';

    /**
     * Sản phẩm sizing kit mặc định khi bấm "Order a Kit" (không có ?product=slug).
     * Lấy biến thể đầu tiên (sort theo id) rồi redirect checkout.
     */
    public const DEFAULT_SIZING_KIT_PRODUCT_ID = 343;

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

        $defaultSizingKitProduct = Product::with('template')
            ->where('id', self::DEFAULT_SIZING_KIT_PRODUCT_ID)
            ->availableForDisplay()
            ->first();

        return view('sizing-kit.index', [
            'sizeChart' => $sizeChart,
            'sizeChartTable' => $sizeChartTable,
            'shapeKits' => $shapeKits,
            'sizingKitCategory' => $sizingKitCategory,
            'defaultSizingKitProduct' => $defaultSizingKitProduct,
        ]);
    }

    /**
     * Thêm sizing kit mặc định (hoặc theo ?product=slug) vào giỏ với biến thể đầu tiên, rồi chuyển tới checkout.
     * Logic gộp dòng giỏ giống Api\CartController::add.
     */
    public function orderCheckout(Request $request)
    {
        $slug = $request->query('product');

        if ($slug) {
            $sizingKitCategory = Category::where('slug', self::SIZING_KIT_CATEGORY_SLUG)->first();
            if (! $sizingKitCategory) {
                return redirect()
                    ->route('sizing-kit.index')
                    ->with('error', 'Chưa cấu hình category sizing kit.');
            }

            $product = Product::with(['variants', 'template'])
                ->whereHas('template', function ($q) use ($sizingKitCategory) {
                    $q->where('category_id', $sizingKitCategory->id);
                })
                ->where('slug', $slug)
                ->availableForDisplay()
                ->first();
        } else {
            $product = Product::with(['variants', 'template'])
                ->where('id', self::DEFAULT_SIZING_KIT_PRODUCT_ID)
                ->availableForDisplay()
                ->first();

            if (! $product) {
                return redirect()
                    ->route('sizing-kit.index')
                    ->with(
                        'error',
                        'Không tìm thấy sizing kit mặc định (sản phẩm ID '.self::DEFAULT_SIZING_KIT_PRODUCT_ID.'). Kiểm tra sản phẩm active, shop active, tồn kho và media.'
                    );
            }
        }

        if (! $product) {
            return redirect()
                ->route('sizing-kit.index')
                ->with('error', 'Không tìm thấy sizing kit để đặt.');
        }

        $variant = $product->variants->sortBy('id')->first();
        $basePrice = (float) ($product->price ?? $product->template->base_price ?? 0);
        $unitPrice = $variant && $variant->price !== null && (string) $variant->price !== ''
            ? (float) $variant->price
            : $basePrice;

        $selectedVariantInput = [];
        if ($variant) {
            $selectedVariantInput = [
                'id' => $variant->id,
                'attributes' => is_array($variant->attributes) ? $variant->attributes : [],
            ];
        }

        $normalizedVariant = $this->normalizeSelectedVariantForCart($selectedVariantInput);
        $quantity = max(1, min(99, (int) $request->query('quantity', 1)));

        $sessionId = session()->getId();
        $userId = Auth::id();

        $cartItems = Cart::where('product_id', $product->id)
            ->where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->get();

        $existingCart = null;
        foreach ($cartItems as $item) {
            if (
                $this->cartVariantsMatch($item->selected_variant, $normalizedVariant)
                && $this->cartCustomizationsMatch($item->customizations, [])
            ) {
                $existingCart = $item;
                break;
            }
        }

        if ($existingCart) {
            $existingCart->increment('quantity', $quantity);
            $existingCart->update(['price' => $unitPrice]);
        } else {
            Cart::create([
                'session_id' => $userId ? null : $sessionId,
                'user_id' => $userId,
                'product_id' => $product->id,
                'variant_id' => $normalizedVariant['id'] ?? null,
                'quantity' => $quantity,
                'price' => $unitPrice,
                'selected_variant' => $normalizedVariant !== [] ? $normalizedVariant : null,
                'customizations' => null,
            ]);
        }

        return redirect()->route('checkout.index');
    }

    private function normalizeSelectedVariantForCart(array $input): array
    {
        if ($input === []) {
            return [];
        }
        $id = isset($input['id']) ? (int) $input['id'] : null;
        $attributes = isset($input['attributes']) && is_array($input['attributes'])
            ? $input['attributes']
            : [];

        return [
            'id' => $id ?: null,
            'attributes' => $attributes,
        ];
    }

    private function cartVariantsMatch($variant1, $variant2): bool
    {
        $var1 = is_array($variant1) ? $variant1 : (is_object($variant1) ? json_decode(json_encode($variant1), true) : []);
        $var2 = is_array($variant2) ? $variant2 : (is_object($variant2) ? json_decode(json_encode($variant2), true) : []);
        $var1 = $var1 ?: [];
        $var2 = $var2 ?: [];

        if ($var1 === [] && $var2 === []) {
            return true;
        }
        if (isset($var1['attributes'], $var2['attributes'])) {
            $a1 = $var1['attributes'];
            $a2 = $var2['attributes'];
            ksort($a1);
            ksort($a2);

            return $a1 === $a2;
        }

        return $var1 === $var2;
    }

    private function cartCustomizationsMatch($custom1, $custom2): bool
    {
        $c1 = is_array($custom1) ? $custom1 : (is_object($custom1) ? json_decode(json_encode($custom1), true) : []);
        $c2 = is_array($custom2) ? $custom2 : (is_object($custom2) ? json_decode(json_encode($custom2), true) : []);
        $c1 = $c1 ?: [];
        $c2 = $c2 ?: [];

        return $c1 === $c2;
    }
}
