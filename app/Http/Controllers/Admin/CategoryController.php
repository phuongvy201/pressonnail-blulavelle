<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::with(['parent', 'templates'])
            ->withCount('templates')
            ->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parentCategories = Category::whereNull('parent_id')->get();
        return view('admin.categories.create', compact('parentCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif|max:2048',
            'featured' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        $data = $request->all();

        // Handle image upload to S3
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

            // Upload to S3
            $imagePath = Storage::disk('s3')->putFileAs('categories', $image, $imageName);
            $data['image'] = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/categories/' . $imageName;
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return view('admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->get();
        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif|max:2048'
        ]);

        $data = $request->all();

        // Handle image upload to S3
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

            // Upload to S3
            $imagePath = Storage::disk('s3')->putFileAs('categories', $image, $imageName);
            $data['image'] = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/categories/' . $imageName;
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Show featured categories management page
     */
    public function featured()
    {
        $categories = Category::whereNull('parent_id')
            ->withCount('templates')
            ->orderBy('name')
            ->get();

        return view('admin.categories.featured', compact('categories'));
    }

    /**
     * Update featured categories
     */
    public function updateFeatured(Request $request)
    {
        $request->validate([
            'featured_categories' => 'array|max:6',
            'featured_categories.*' => 'exists:categories,id',
            'sort_order' => 'array',
            'sort_order.*' => 'integer|min:0'
        ]);

        // Reset all categories to not featured
        Category::whereNull('parent_id')->update(['featured' => false, 'sort_order' => 0]);

        // Update selected categories
        if ($request->has('featured_categories')) {
            foreach ($request->featured_categories as $index => $categoryId) {
                Category::where('id', $categoryId)->update([
                    'featured' => true,
                    'sort_order' => $request->sort_order[$categoryId] ?? $index + 1
                ]);
            }
        }

        return redirect()->route('admin.categories.featured')
            ->with('success', 'Featured categories updated successfully.');
    }
}
