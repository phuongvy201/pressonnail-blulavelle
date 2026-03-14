<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        // Admin xem tất cả collections, Seller chỉ xem collections của shop mình
        $collectionsQuery = Collection::with(['user', 'shop', 'products']);

        if ($user->hasRole('admin')) {
            $collections = $collectionsQuery->orderBy('admin_approved', 'asc')
                ->orderBy('sort_order')
                ->orderBy('created_at', 'desc')
                ->paginate(12);
        } else {
            // Seller chỉ thấy collections của shop mình
            if ($user->hasShop()) {
                $collections = $collectionsQuery->where('shop_id', $user->shop->id)
                    ->orderBy('sort_order')
                    ->orderBy('created_at', 'desc')
                    ->paginate(12);
            } else {
                $collections = new LengthAwarePaginator(
                    [],
                    0,
                    12,
                    1,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
            }
        }

        return view('admin.collections.index', compact('collections'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();

        // Get products for selection
        if ($user->hasRole('admin')) {
            $products = Product::with(['template.category', 'shop'])->orderBy('name')->get();
        } else {
            $products = Product::with(['template.category', 'shop'])->where('user_id', $user->id)->orderBy('name')->get();
        }

        return view('admin.collections.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Check if seller has shop (required for sellers)
        if (auth()->user()->hasRole('seller') && !auth()->user()->hasShop()) {
            return redirect()->route('seller.shop.create')
                ->with('warning', 'You need to create a shop first before creating collections!');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif|max:5120',
            'type' => 'required|in:manual,automatic',
            'status' => 'required|in:active,inactive,draft',
            'featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
            'auto_rules' => 'nullable|array',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->id();
        $data['slug'] = Collection::generateSlug($request->name);

        // Set shop_id if user has shop
        if (auth()->user()->hasShop()) {
            $data['shop_id'] = auth()->user()->shop->id;
        }

        // Set admin_approved based on user role
        $data['admin_approved'] = auth()->user()->hasRole('admin') ? true : false;

        // Handle image upload to S3
        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            $imageName = 'collection_' . time() . '_' . Str::random(10) . '.' . $imageFile->getClientOriginalExtension();
            $imagePath = 'collections/' . $imageName;
            Storage::disk('s3')->put($imagePath, file_get_contents($imageFile));
            $data['image'] = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $imagePath;
        }

        $collection = Collection::create($data);

        // Attach products if provided
        if ($request->has('products')) {
            $collection->products()->attach($request->products);
        }

        return redirect()->route('admin.collections.index')
            ->with('success', 'Collection created successfully! 🎉');
    }

    /**
     * Display the specified resource.
     */
    public function show(Collection $collection)
    {
        // Check permissions
        if (!$collection->canEdit()) {
            abort(403, 'You do not have permission to view this collection.');
        }

        $collection->load(['products.template', 'user']);

        return view('admin.collections.show', compact('collection'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Collection $collection)
    {
        // Check permissions
        if (!$collection->canEdit()) {
            abort(403, 'You do not have permission to edit this collection.');
        }

        $user = auth()->user();

        // Get products for selection
        if ($user->hasRole('admin')) {
            $products = Product::with(['template.category', 'shop'])->orderBy('name')->get();
        } else {
            $products = Product::with(['template.category', 'shop'])->where('user_id', $user->id)->orderBy('name')->get();
        }

        $collection->load('products');

        return view('admin.collections.edit', compact('collection', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Collection $collection)
    {
        // Check permissions
        if (!$collection->canEdit()) {
            abort(403, 'You do not have permission to edit this collection.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'type' => 'required|in:manual,automatic',
            'status' => 'required|in:active,inactive,draft',
            'featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
            'auto_rules' => 'nullable|array',
        ]);

        $data = $request->except(['image']);

        // Update slug if name changed
        if ($request->name !== $collection->name) {
            $data['slug'] = Collection::generateSlug($request->name, $collection->id);
        }

        // Handle new image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($collection->image) {
                // Parse URL to get path and delete from S3
                // Storage::disk('s3')->delete($oldPath);
            }

            $imageFile = $request->file('image');
            $imageName = 'collection_' . time() . '_' . Str::random(10) . '.' . $imageFile->getClientOriginalExtension();
            $imagePath = 'collections/' . $imageName;
            Storage::disk('s3')->put($imagePath, file_get_contents($imageFile));
            $data['image'] = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $imagePath;
        }

        $collection->update($data);

        // Update products
        if ($request->has('products')) {
            $collection->products()->sync($request->products);
        } else {
            $collection->products()->detach();
        }

        return redirect()->route('admin.collections.index')
            ->with('success', 'Collection updated successfully! 🎉');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Collection $collection)
    {
        // Check permissions
        if (!$collection->canEdit()) {
            abort(403, 'You do not have permission to delete this collection.');
        }

        // Delete image from S3 if exists
        if ($collection->image) {
            // Parse URL to get path and delete from S3
            // Storage::disk('s3')->delete($imagePath);
        }

        $collection->delete();

        return redirect()->route('admin.collections.index')
            ->with('success', 'Collection deleted successfully! 🗑️');
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Collection $collection)
    {
        // Check permissions
        if (!$collection->canEdit()) {
            abort(403, 'You do not have permission to modify this collection.');
        }

        $collection->update(['featured' => !$collection->featured]);

        $status = $collection->featured ? 'featured' : 'unfeatured';

        return back()->with('success', "Collection {$status} successfully! ⭐");
    }

    /**
     * Update sort order
     */
    public function updateSortOrder(Request $request)
    {
        $request->validate([
            'collections' => 'required|array',
            'collections.*.id' => 'required|exists:collections,id',
            'collections.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->collections as $item) {
            Collection::where('id', $item['id'])
                ->where('user_id', auth()->id())
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Approve collection (Admin only)
     */
    public function approve(Collection $collection)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Only admin can approve collections.');
        }

        $collection->update(['admin_approved' => true]);

        return back()->with('success', "Collection '{$collection->name}' approved successfully! ✅");
    }

    /**
     * Reject collection (Admin only)
     */
    public function reject(Request $request, Collection $collection)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Only admin can reject collections.');
        }

        $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        $collection->update([
            'admin_approved' => false,
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', "Collection '{$collection->name}' rejected with notes. ❌");
    }

    /**
     * Bulk approve collections (Admin only)
     */
    public function bulkApprove(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Only admin can bulk approve collections.');
        }

        $request->validate([
            'collection_ids' => 'required|array',
            'collection_ids.*' => 'exists:collections,id',
        ]);

        Collection::whereIn('id', $request->collection_ids)
            ->update(['admin_approved' => true]);

        return back()->with('success', count($request->collection_ids) . ' collections approved successfully! ✅');
    }
}
