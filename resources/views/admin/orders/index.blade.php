@extends('layouts.admin')

@section('title', 'Orders Management - Admin')

@section('content')
<style>
    .order-card {
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border-color: #3b82f6;
    }

    .status-badge {
        @apply px-4 py-2 rounded-full text-xs font-semibold inline-flex items-center;
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .status-pending {
        @apply bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800;
    }

    .status-processing {
        @apply bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800;
    }

    .status-shipped {
        @apply bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800;
    }

    .status-delivered {
        @apply bg-gradient-to-r from-green-100 to-green-200 text-green-800;
    }

    .status-cancelled {
        @apply bg-gradient-to-r from-red-100 to-red-200 text-red-800;
    }

    .payment-paid {
        @apply bg-gradient-to-r from-green-100 to-green-200 text-green-800;
    }

    .payment-pending {
        @apply bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800;
    }

    .payment-failed {
        @apply bg-gradient-to-r from-red-100 to-red-200 text-red-800;
    }

    .payment-refunded {
        @apply bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800;
    }

    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: all 0.3s ease;
        border-radius: 16px;
    }

    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }

    .order-info {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 8px;
        padding: 16px;
    }

    .order-header {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 20px;
    }

    .filter-section {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .order-list {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
</style>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="order-header mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Orders Management</h1>
                    <p class="text-blue-100 mt-2">Manage and track all customer orders across the platform</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.orders.export', request()->only(['search', 'status', 'payment_status', 'date_from', 'date_to'])) }}" 
                       id="export-csv-btn"
                       class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-6 py-3 rounded-xl transition-all duration-200 border border-white border-opacity-30 backdrop-blur-sm inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span id="export-text">Export CSV</span>
                        <svg id="export-loading" class="w-4 h-4 ml-2 animate-spin hidden" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
            <div class="stats-card text-white p-6 rounded-xl">
                <div class="flex items-center">
                    <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm opacity-90">Total Orders</p>
                        <p class="text-2xl font-bold">{{ $stats['total_orders'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Pending</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_orders'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Processing</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['processing_orders'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Completed</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['completed_orders'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Revenue</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($stats['total_revenue'], 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center">
                    <div class="p-3 bg-indigo-100 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Today</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['today_orders'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section p-6 mb-8">
            <div class="flex items-center mb-4">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900">Filter Orders</h3>
            </div>
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Orders</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Order number, customer name, email..."
                               class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <svg class="absolute left-3 top-3.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order Status</label>
                    <select name="status" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>🟡 Pending</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>🔵 Processing</option>
                        <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>🟣 Shipped</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>🟢 Delivered</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>🔴 Cancelled</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                    <select name="payment_status" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <option value="">All Payment Status</option>
                        <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>🟡 Payment Pending</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>🟢 Payment Paid</option>
                        <option value="failed" {{ request('payment_status') == 'failed' ? 'selected' : '' }}>🔴 Payment Failed</option>
                        <option value="refunded" {{ request('payment_status') == 'refunded' ? 'selected' : '' }}>⚪ Payment Refunded</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                </div>

                <div class="lg:col-span-5 flex justify-end space-x-3 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                        </svg>
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.orders.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-8 py-3 rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Orders List -->
        <div class="order-list">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">All Orders ({{ $orders->total() }})</h3>
                    <div class="flex items-center text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        Platform Orders
                    </div>
                </div>
            </div>

            @if($orders->count() > 0)
                <div class="divide-y divide-gray-100">
                    @foreach($orders as $order)
                        <div class="order-card p-6 hover:bg-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <!-- Order Header -->
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h4 class="text-xl font-bold text-gray-900">{{ $order->order_number }}</h4>
                                            <p class="text-sm text-gray-600 mt-1">{{ $order->customer_name }} • {{ $order->customer_email }}</p>
                                        </div>
                                        
                                        <!-- Status Badges -->
                                        <div class="flex flex-col space-y-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="text-xs font-medium text-gray-500">Order:</span>
                                                <span class="status-badge status-{{ $order->status }}">
                                                    @if($order->status == 'pending')
                                                        🟡 Pending
                                                    @elseif($order->status == 'processing')
                                                        🔵 Processing
                                                    @elseif($order->status == 'shipped')
                                                        🟣 Shipped
                                                    @elseif($order->status == 'delivered')
                                                        🟢 Delivered
                                                    @else
                                                        🔴 Cancelled
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-xs font-medium text-gray-500">Payment:</span>
                                                <span class="status-badge payment-{{ $order->payment_status }}">
                                                    @if($order->payment_status == 'pending')
                                                        🟡 Pending
                                                    @elseif($order->payment_status == 'paid')
                                                        🟢 Paid
                                                    @elseif($order->payment_status == 'failed')
                                                        🔴 Failed
                                                    @else
                                                        ⚪ Refunded
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Order Details -->
                                    <div class="order-info">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm text-gray-500">Total Amount</p>
                                                    <p class="text-lg font-bold text-gray-900">${{ number_format($order->total_amount, 2) }}</p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm text-gray-500">Order Date</p>
                                                    <p class="text-lg font-bold text-gray-900">{{ $order->created_at->format('M d, Y') }}</p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm text-gray-500">Items</p>
                                                    <p class="text-lg font-bold text-gray-900">{{ $order->items->count() }} products</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Order Items -->
                                        <div class="mt-4">
                                            <h5 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                                </svg>
                                                Order Items ({{ $order->items->count() }})
                                            </h5>
                                            <div class="space-y-2">
                                                @foreach($order->items as $item)
                                                    <div class="flex items-center justify-between bg-white rounded-lg p-3 border border-gray-100">
                                                        <div class="flex items-center space-x-3">
                                                            @php
                                                                $media = $item->product ? $item->product->getEffectiveMedia() : [];
                                                                $imageUrl = null;
                                                                $adminOrderListImgAlt = $item->product ? $item->product->name : ($item->product_name ?? 'Product');
                                                                if ($media && count($media) > 0 && $item->product) {
                                                                    $adminOrderListImgAlt = $item->product->altForMediaItem($media[0], $item->product_name ?? $item->product->name, 0);
                                                                    if (is_string($media[0])) {
                                                                        $imageUrl = $media[0];
                                                                    } elseif (is_array($media[0])) {
                                                                        $imageUrl = $media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? null;
                                                                    }
                                                                }
                                                            @endphp
                                                            @if($imageUrl)
                                                                <img src="{{ $imageUrl }}" 
                                                                     alt="{{ $adminOrderListImgAlt }}" 
                                                                     class="w-12 h-12 object-cover rounded-lg">
                                                            @else
                                                                <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                    </svg>
                                                                </div>
                                                            @endif
                                                            <div class="flex-1">
                                                                <p class="font-medium text-gray-900">{{ $item->product->name ?? 'Product not found' }}</p>
                                                                <p class="text-sm text-gray-500">Qty: {{ $item->quantity }} × ${{ number_format($item->price, 2) }}</p>
                                                                
                                                                <!-- Customizations -->
                                                                @if($item->product_options)
                                                                    @php
                                                                        $productOptions = is_string($item->product_options) 
                                                                            ? json_decode($item->product_options, true) 
                                                                            : $item->product_options;
                                                                        $customizations = $productOptions['customizations'] ?? null;
                                                                        $selectedVariant = $productOptions['selected_variant'] ?? null;
                                                                    @endphp
                                                                    
                                                                    @if($customizations && is_array($customizations))
                                                                        <div class="mt-2">
                                                                            <p class="text-xs font-medium text-blue-600 mb-1">Customizations:</p>
                                                                            <div class="space-y-1">
                                                                                @foreach($customizations as $key => $customization)
                                                                                    @if(is_array($customization) && isset($customization['value']))
                                                                                        <div class="text-xs bg-blue-50 text-blue-800 px-2 py-1 rounded inline-block mr-1 mb-1">
                                                                                            <span class="font-medium">{{ $key }}:</span> {{ $customization['value'] }}
                                                                                            @if(isset($customization['price']) && $customization['price'] > 0)
                                                                                                <span class="text-green-600 font-medium">(+${{ number_format($customization['price'], 2) }})</span>
                                                                                            @endif
                                                                                        </div>
                                                                                    @elseif(!is_array($customization))
                                                                                        <div class="text-xs bg-blue-50 text-blue-800 px-2 py-1 rounded inline-block mr-1 mb-1">
                                                                                            <span class="font-medium">{{ $key }}:</span> {{ $customization }}
                                                                                        </div>
                                                                                    @endif
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    @if($selectedVariant && is_array($selectedVariant))
                                                                        <div class="mt-2">
                                                                            <p class="text-xs font-medium text-green-600 mb-1">Selected Variant:</p>
                                                                            <div class="text-xs bg-green-50 text-green-800 px-2 py-1 rounded inline-block mr-1 mb-1">
                                                                                <span class="font-medium">Variant:</span> {{ $selectedVariant['variant_name'] ?? 'N/A' }}
                                                                                @if(isset($selectedVariant['attributes']))
                                                                                    @foreach($selectedVariant['attributes'] as $attrKey => $attrValue)
                                                                                        <span class="ml-2">{{ $attrKey }}: {{ $attrValue }}</span>
                                                                                    @endforeach
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                                
                                                                <!-- Custom Files -->
                                                                @if($item->product && $item->product->allow_customization)
                                                                    @php
                                                                        $customFiles = \App\Models\CustomFile::where('product_id', $item->product_id)
                                                                            ->where(function($query) use ($order) {
                                                                                if ($order->user_id) {
                                                                                    $query->where('user_id', $order->user_id);
                                                                                } else {
                                                                                    $query->where('session_id', $order->session_id);
                                                                                }
                                                                            })
                                                                            ->active()
                                                                            ->get();
                                                                    @endphp
                                                                    @if($customFiles->count() > 0)
                                                                        <div class="mt-2">
                                                                            <p class="text-xs font-medium text-purple-600 mb-1">Custom Files ({{ $customFiles->count() }}):</p>
                                                                            <div class="flex flex-wrap gap-1">
                                                                                @foreach($customFiles as $file)
                                                                                    <a href="{{ $file->file_url }}" 
                                                                                       target="_blank" 
                                                                                       class="text-xs bg-purple-50 text-purple-800 px-2 py-1 rounded inline-flex items-center hover:bg-purple-100 transition-colors">
                                                                                        @if($file->is_image)
                                                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                                            </svg>
                                                                                        @elseif($file->is_video)
                                                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                                                            </svg>
                                                                                        @else
                                                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                                            </svg>
                                                                                        @endif
                                                                                        {{ Str::limit($file->original_name, 15) }}
                                                                                    </a>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="font-semibold text-gray-900">${{ number_format($item->quantity * $item->price, 2) }}</p>
                                                            @if($item->product && $item->product->shop)
                                                                <p class="text-xs text-gray-500">by {{ $item->product->shop->name }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Button -->
                                <div class="ml-6">
                                    <a href="{{ route('admin.orders.show', $order) }}" 
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $orders->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">No orders found</h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">No orders match your current search criteria. Try adjusting your filters or check back later for new orders.</p>
                    <div class="flex justify-center space-x-4">
                        <a href="{{ route('admin.orders.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Clear Filters
                        </a>
                        <a href="{{ route('admin.dashboard') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                            </svg>
                            Dashboard
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const exportBtn = document.getElementById('export-csv-btn');
    const exportText = document.getElementById('export-text');
    const exportLoading = document.getElementById('export-loading');
    
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            // Show loading state
            exportText.textContent = 'Exporting...';
            exportLoading.classList.remove('hidden');
            exportBtn.classList.add('opacity-75', 'pointer-events-none');
            
            // Reset after 3 seconds in case the download doesn't start
            setTimeout(function() {
                exportText.textContent = 'Export CSV';
                exportLoading.classList.add('hidden');
                exportBtn.classList.remove('opacity-75', 'pointer-events-none');
            }, 3000);
        });
    }
});
</script>
@endsection
