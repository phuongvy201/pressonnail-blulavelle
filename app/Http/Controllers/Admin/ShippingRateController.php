<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Category;
use Illuminate\Http\Request;

class ShippingRateController extends Controller
{
    /**
     * Display a listing of shipping rates.
     */
    public function index(Request $request)
    {
        $query = ShippingRate::with(['shippingZone', 'category']);

        // Filter by zone
        if ($request->filled('zone_id')) {
            $query->where('shipping_zone_id', $request->zone_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            if ($request->category_id === 'null') {
                $query->whereNull('category_id');
            } else {
                $query->where('category_id', $request->category_id);
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $rates = $query->ordered()->paginate(20);
        $zones = ShippingZone::ordered()->get();
        $categories = $this->getCategoriesHierarchy();
        $domains = $this->getDomainsList();

        return view('admin.shipping-rates.index', compact('rates', 'zones', 'categories', 'domains'));
    }

    /**
     * Show the form for creating a new shipping rate.
     */
    public function create()
    {
        $zones = ShippingZone::active()->ordered()->get();
        $categories = $this->getCategoriesHierarchy();
        $domains = $this->getDomainsList();

        return view('admin.shipping-rates.create', compact('zones', 'categories', 'domains'));
    }

    /**
     * Get categories in hierarchical format for dropdown
     */
    protected function getCategoriesHierarchy()
    {
        $categories = Category::with('children')->whereNull('parent_id')->orderBy('name')->get();
        $flatCategories = [];

        foreach ($categories as $category) {
            $flatCategories[] = [
                'id' => $category->id,
                'name' => $category->name,
                'level' => 0
            ];

            // Add children
            if ($category->children->count() > 0) {
                foreach ($category->children->sortBy('name') as $child) {
                    $flatCategories[] = [
                        'id' => $child->id,
                        'name' => $child->name,
                        'level' => 1,
                        'parent' => $category->name
                    ];
                }
            }
        }

        return collect($flatCategories);
    }

    /**
     * Get list of domains (kept for view compatibility; no longer used)
     */
    protected function getDomainsList(): array
    {
        return [];
    }

    /**
     * Store a newly created shipping rate in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipping_zone_id' => 'required|exists:shipping_zones,id',
            'domain' => 'nullable|string|max:255', // legacy single domain (optional)
            'domains' => 'nullable|array',
            'domains.*' => 'string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'delivery_min_days' => 'nullable|integer|min:0',
            'delivery_max_days' => 'nullable|integer|min:0',
            'delivery_note' => 'nullable|string|max:255',
            'first_item_cost' => 'required|numeric|min:0',
            'additional_item_cost' => 'required|numeric|min:0',
            'min_items' => 'nullable|integer|min:1',
            'max_items' => 'nullable|integer|min:1',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_order_value' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_default'] = $request->has('is_default');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $rate = ShippingRate::create($validated);
        
        // If set as default, unset other defaults for same zone/category
        if ($rate->is_default) {
            $rate->setAsDefault();
        }

        return redirect()->route('admin.shipping-rates.index')
            ->with('success', 'Shipping rate created successfully!');
    }

    /**
     * Display the specified shipping rate.
     */
    public function show(ShippingRate $shippingRate)
    {
        $shippingRate->load(['shippingZone', 'category']);

        return view('admin.shipping-rates.show', compact('shippingRate'));
    }

    /**
     * Show the form for editing the specified shipping rate.
     */
    public function edit(ShippingRate $shippingRate)
    {
        $zones = ShippingZone::active()->ordered()->get();
        $categories = $this->getCategoriesHierarchy();
        $domains = $this->getDomainsList();

        return view('admin.shipping-rates.edit', compact('shippingRate', 'zones', 'categories', 'domains'));
    }

    /**
     * Update the specified shipping rate in storage.
     */
    public function update(Request $request, ShippingRate $shippingRate)
    {
        $validated = $request->validate([
            'shipping_zone_id' => 'required|exists:shipping_zones,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'delivery_min_days' => 'nullable|integer|min:0',
            'delivery_max_days' => 'nullable|integer|min:0',
            'delivery_note' => 'nullable|string|max:255',
            'first_item_cost' => 'required|numeric|min:0',
            'additional_item_cost' => 'required|numeric|min:0',
            'min_items' => 'nullable|integer|min:1',
            'max_items' => 'nullable|integer|min:1',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_order_value' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $wasDefault = $shippingRate->is_default;
        $validated['is_default'] = $request->has('is_default');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $shippingRate->update($validated);
        
        // If set as default, unset other defaults for same zone/category
        if ($shippingRate->is_default && !$wasDefault) {
            $shippingRate->setAsDefault();
        }

        return redirect()->route('admin.shipping-rates.index')
            ->with('success', 'Shipping rate updated successfully!');
    }

    /**
     * Remove the specified shipping rate from storage.
     */
    public function destroy(ShippingRate $shippingRate)
    {
        $shippingRate->delete();

        return redirect()->route('admin.shipping-rates.index')
            ->with('success', 'Shipping rate deleted successfully!');
    }

    /**
     * Set shipping rate as default for its zone/category
     */
    public function setDefault(ShippingRate $shippingRate)
    {
        $shippingRate->setAsDefault();

        return redirect()->route('admin.shipping-rates.index')
            ->with('success', 'Shipping rate set as default successfully!');
    }

    /**
     * Unset shipping rate as default
     */
    public function unsetDefault(ShippingRate $shippingRate)
    {
        $shippingRate->unsetAsDefault();

        return redirect()->route('admin.shipping-rates.index')
            ->with('success', 'Shipping rate unset as default successfully!');
    }
}
