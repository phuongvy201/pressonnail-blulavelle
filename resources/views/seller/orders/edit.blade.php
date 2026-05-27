@extends('layouts.app')

@section('title', 'Edit Order - Seller')

@section('content')
@include('customer.profile.partials.account-wrap-open')
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Edit Order</h1>
                    <p class="text-gray-600 mt-2">Update order status and details</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('seller.orders.index') }}" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                        Back to Orders
                    </a>
                </div>
            </div>
        </div>

        <!-- Order Details -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order Number</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $order->order_number }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $order->customer_name }}</p>
                    <p class="text-sm text-gray-600">{{ $order->customer_email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Total Amount</label>
                    <p class="text-lg font-semibold text-gray-900">${{ number_format($order->total_amount, 2) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order Date</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $order->created_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST" action="{{ route('seller.orders.update', $order) }}">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Order Status</label>
                        <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                            <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                        <p class="text-lg font-semibold text-gray-900">{{ ucfirst($order->payment_status) }}</p>
                        <p class="text-sm text-gray-600">Payment status can only be changed by admin</p>
                    </div>
                </div>

                <div class="mt-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" id="notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Add any notes about this order...">{{ $order->notes }}</textarea>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('seller.orders.index') }}" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                        Update Order
                    </button>
                </div>
            </form>
        </div>
@include('customer.profile.partials.account-wrap-close')
@endsection
