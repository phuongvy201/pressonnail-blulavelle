@extends('layouts.admin')

@section('title', 'Category Cross-Sell Rules')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Category Cross-Sell Rules</h1>
            <p class="text-sm text-gray-600">Configure category-level cross-sell mapping for scalable recommendations.</p>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Add Mapping</h2>
        <form action="{{ route('admin.category-cross-sells.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            @csrf
            <select name="source_category_id" class="rounded-lg border-gray-300" required>
                <option value="">Source category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="target_category_id" class="rounded-lg border-gray-300" required>
                <option value="">Target category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <input type="number" name="priority" min="1" max="100" placeholder="Auto STT" class="rounded-lg border-gray-300">
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Add Rule
            </button>
        </form>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-6">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <select name="source_category_id" class="rounded-lg border-gray-300">
                <option value="">All source categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($sourceCategoryId === $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <button class="px-4 py-2 rounded-lg bg-gray-800 text-white hover:bg-gray-900">Filter</button>
            <a href="{{ route('admin.category-cross-sells.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Reset</a>
        </form>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">STT</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Source</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Target</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Priority</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($mappings as $mapping)
                        <tr>
                            <td class="px-4 py-3 text-sm font-semibold text-slate-700">#{{ $mapping->priority }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $mapping->sourceCategory->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $mapping->targetCategory->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <form action="{{ route('admin.category-cross-sells.update', $mapping) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="source_category_id" value="{{ $mapping->source_category_id }}">
                                    <input type="hidden" name="target_category_id" value="{{ $mapping->target_category_id }}">
                                    <input type="number" name="priority" min="1" max="100" value="{{ $mapping->priority }}" class="w-20 rounded-lg border-gray-300 text-sm">
                                    <button class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-semibold">Save</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('admin.category-cross-sells.destroy', $mapping) }}" method="POST" onsubmit="return confirm('Delete this mapping?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1.5 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 text-xs font-semibold">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-500">No cross-sell rules found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $mappings->links() }}
        </div>
    </div>

</div>
@endsection
