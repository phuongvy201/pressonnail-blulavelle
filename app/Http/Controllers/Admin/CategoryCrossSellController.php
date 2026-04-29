<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryCrossSell;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryCrossSellController extends Controller
{
    public function index(Request $request): View
    {
        $sourceCategoryId = $request->integer('source_category_id');

        $mappings = CategoryCrossSell::query()
            ->with(['sourceCategory:id,name,slug', 'targetCategory:id,name,slug'])
            ->when($sourceCategoryId, fn($q) => $q->where('source_category_id', $sourceCategoryId))
            ->orderBy('source_category_id')
            ->orderBy('priority')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('admin.category-cross-sells.index', compact('mappings', 'categories', 'sourceCategoryId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_category_id' => ['required', 'integer', 'exists:categories,id'],
            'target_category_id' => [
                'required',
                'integer',
                'exists:categories,id',
                'different:source_category_id',
                Rule::unique('category_cross_sells')->where(function ($query) use ($request) {
                    return $query->where('source_category_id', $request->input('source_category_id'));
                }),
            ],
            'priority' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
                Rule::unique('category_cross_sells')->where(function ($query) use ($request) {
                    return $query->where('source_category_id', $request->input('source_category_id'));
                }),
            ],
        ]);

        if (empty($validated['priority'])) {
            $validated['priority'] = (int) CategoryCrossSell::query()
                ->where('source_category_id', $validated['source_category_id'])
                ->max('priority') + 1;
        }

        CategoryCrossSell::create($validated);

        return back()->with('success', 'Category cross-sell mapping created successfully.');
    }

    public function update(Request $request, CategoryCrossSell $categoryCrossSell): RedirectResponse
    {
        $validated = $request->validate([
            'source_category_id' => ['required', 'integer', 'exists:categories,id'],
            'target_category_id' => [
                'required',
                'integer',
                'exists:categories,id',
                'different:source_category_id',
                Rule::unique('category_cross_sells')
                    ->ignore($categoryCrossSell->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('source_category_id', $request->input('source_category_id'));
                    }),
            ],
            'priority' => [
                'required',
                'integer',
                'min:1',
                'max:100',
                Rule::unique('category_cross_sells')
                    ->ignore($categoryCrossSell->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('source_category_id', $request->input('source_category_id'));
                    }),
            ],
        ]);

        $categoryCrossSell->update($validated);

        return back()->with('success', 'Category cross-sell mapping updated successfully.');
    }

    public function destroy(CategoryCrossSell $categoryCrossSell): RedirectResponse
    {
        $categoryCrossSell->delete();

        return back()->with('success', 'Category cross-sell mapping deleted successfully.');
    }
}
