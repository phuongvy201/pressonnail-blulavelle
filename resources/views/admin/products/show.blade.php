@extends('layouts.admin')

@section('title', 'Product Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $product->name }}</h1>
                <span class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                    </svg>
                    ID: {{ $product->id }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                    {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : ($product->status === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800') }}">
                    {{ ucfirst($product->status) }}
                </span>
            </div>
            <p class="mt-2 text-sm text-gray-600">Product details and information</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('admin.products.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back
            </a>
            @if(auth()->user()->hasRole('admin') || $product->template->user_id === auth()->id())
            <a href="{{ route('admin.products.edit', $product) }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit
            </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Product Name</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $product->name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Price</label>
                            @php
                                $displayPrice = (float) ($product->price ?? $product->template->base_price);
                                $displayListPrice = (float) ($product->list_price ?? $product->template->list_price ?? 0);
                                $showListPrice = $displayListPrice > 0 && $displayListPrice > $displayPrice;
                            @endphp
                            <p class="mt-1 text-2xl font-bold text-green-600">${{ number_format($displayPrice, 2) }}</p>
                            @if($showListPrice)
                                <p class="mt-0.5 text-sm text-gray-500 line-through">${{ number_format($displayListPrice, 2) }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Quantity</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $product->quantity }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Status</label>
                            <p class="mt-1">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                    {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : ($product->status === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($product->status) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    @if($product->description || $product->template->description)
                    <div class="pt-4 border-t border-gray-200">
                        <label class="text-sm font-medium text-gray-600">Description</label>
                        <div class="mt-2 text-sm text-gray-900 prose max-w-none">
                            {!! $product->description ?? $product->template->description !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Variants -->
            @if($product->variants && $product->variants->count() > 0)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Product Variants</h3>
                    <p class="text-sm text-gray-600">{{ $product->variants->count() }} variants available</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Variant</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">SKU</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Quantity</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($product->variants as $variant)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $variant->variant_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $variant->variant_value }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $variant->sku }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    ${{ number_format($variant->getFinalPrice(), 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $variant->quantity }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Template Info -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Template Info</h3>
                </div>
                <div class="p-6 space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Template</label>
                        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $product->template->name }}</p>
                        <a href="{{ route('admin.product-templates.show', $product->template) }}" class="text-xs text-blue-600 hover:text-blue-700">
                            View template →
                        </a>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Category</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $product->template->category->name }}</p>
                    </div>
                    @if($product->template->user)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Created by</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $product->template->user->name }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Metadata -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Metadata</h3>
                </div>
                <div class="p-6 space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Created</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $product->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Last Updated</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $product->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Slug</label>
                        <p class="mt-1 text-sm text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded">{{ $product->slug }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection













