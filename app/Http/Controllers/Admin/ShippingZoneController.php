<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use Illuminate\Http\Request;

class ShippingZoneController extends Controller
{
    /**
     * Display a listing of shipping zones.
     */
    public function index(Request $request)
    {
        $zones = ShippingZone::withCount('shippingRates')->ordered()->paginate(20);
        $domains = [];

        return view('admin.shipping-zones.index', compact('zones', 'domains'));
    }

    /**
     * Show the form for creating a new shipping zone.
     */
    public function create()
    {
        $domains = [];

        return view('admin.shipping-zones.create', compact('domains'));
    }

    /**
     * Store a newly created shipping zone in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'countries' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Convert countries from CSV string to array
        $countriesArray = array_map(
            fn($c) => strtoupper(trim($c)),
            array_filter(explode(',', $validated['countries']))
        );

        if (empty($countriesArray)) {
            return back()->withInput()->withErrors(['countries' => 'Please enter at least one country code']);
        }

        $validated['countries'] = $countriesArray;
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ShippingZone::create($validated);

        return redirect()->route('admin.shipping-zones.index')
            ->with('success', 'Shipping zone created successfully!');
    }

    /**
     * Display the specified shipping zone.
     */
    public function show(ShippingZone $shippingZone)
    {
        $shippingZone->load('shippingRates');

        return view('admin.shipping-zones.show', compact('shippingZone'));
    }

    /**
     * Show the form for editing the specified shipping zone.
     */
    public function edit(ShippingZone $shippingZone)
    {
        $domains = [];

        return view('admin.shipping-zones.edit', compact('shippingZone', 'domains'));
    }

    /**
     * Update the specified shipping zone in storage.
     */
    public function update(Request $request, ShippingZone $shippingZone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'countries' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Convert countries from CSV string to array
        $countriesArray = array_map(
            fn($c) => strtoupper(trim($c)),
            array_filter(explode(',', $validated['countries']))
        );

        if (empty($countriesArray)) {
            return back()->withInput()->withErrors(['countries' => 'Please enter at least one country code']);
        }

        $validated['countries'] = $countriesArray;
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $shippingZone->update($validated);

        return redirect()->route('admin.shipping-zones.index')
            ->with('success', 'Shipping zone updated successfully!');
    }

    /**
     * Remove the specified shipping zone from storage.
     */
    public function destroy(ShippingZone $shippingZone)
    {
        $ratesCount = $shippingZone->shippingRates()->count();

        if ($ratesCount > 0) {
            return redirect()->route('admin.shipping-zones.index')
                ->with('error', "Cannot delete zone with {$ratesCount} shipping rates. Delete rates first.");
        }

        $shippingZone->delete();

        return redirect()->route('admin.shipping-zones.index')
            ->with('success', 'Shipping zone deleted successfully!');
    }
}
