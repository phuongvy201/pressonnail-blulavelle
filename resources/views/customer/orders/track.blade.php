@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Track Order Form -->
        @if(!isset($order))
            <div class="bg-white rounded-xl shadow-lg p-8 md:p-12">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-[#005366] rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Track Your Order</h1>
                    <p class="text-gray-600">Enter your order details to track your shipment</p>
                </div>

                @if(session('error'))
                    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                <form method="GET" action="{{ route('orders.track') }}" class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">Order Number</label>
                        <input type="text" 
                               name="order_number" 
                               value="{{ request('order_number') }}"
                               placeholder="e.g. BLU20241015-001"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent transition"
                               required>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">Email Address</label>
                        <input type="email" 
                               name="email" 
                               value="{{ request('email') }}"
                               placeholder="your@email.com"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366] focus:border-transparent transition"
                               required>
                    </div>

                    <button type="submit" 
                            class="w-full px-6 py-4 bg-[#0195FE] text-white font-bold rounded-lg hover:bg-[#017fda] transition shadow-lg hover:shadow-xl">
                        Track Order
                    </button>
                </form>

                <div class="mt-8 text-center text-sm text-gray-600">
                    <p>Order number can be found in your confirmation email</p>
                </div>
            </div>
        @else
            <!-- Order Found - Display Status -->
            <div class="space-y-6">
                <!-- Order Header -->
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Order #{{ $order->order_number }}</h1>
                            <p class="text-gray-600">Placed on {{ $order->created_at->format('M d, Y') }}</p>
                        </div>
                        <span class="px-4 py-2 text-sm font-semibold rounded-full
                            @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                            @elseif($order->status == 'processing') bg-blue-100 text-blue-800
                            @elseif($order->status == 'completed') bg-green-100 text-green-800
                            @elseif($order->status == 'cancelled') bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>

                    <!-- Order Timeline -->
                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        <div class="space-y-6">
                            <!-- Order Placed -->
                            <div class="relative flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-500 flex items-center justify-center z-10">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="font-semibold text-gray-900">Order Placed</p>
                                    <p class="text-sm text-gray-600">{{ $order->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>

                            <!-- Payment Confirmed -->
                            <div class="relative flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full {{ $order->payment_status == 'paid' ? 'bg-green-500' : 'bg-gray-300' }} flex items-center justify-center z-10">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="font-semibold text-gray-900">Payment {{ ucfirst($order->payment_status) }}</p>
                                    @if($order->paid_at)
                                        <p class="text-sm text-gray-600">{{ $order->paid_at->format('M d, Y h:i A') }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Processing -->
                            <div class="relative flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full {{ in_array($order->status, ['processing', 'completed']) ? 'bg-green-500' : 'bg-gray-300' }} flex items-center justify-center z-10">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="font-semibold text-gray-900">Processing</p>
                                    <p class="text-sm text-gray-600">Your order is being prepared</p>
                                </div>
                            </div>

                            <!-- Shipped -->
                            <div class="relative flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full {{ $order->tracking_number ? 'bg-green-500' : 'bg-gray-300' }} flex items-center justify-center z-10">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="font-semibold text-gray-900">Shipped</p>
                                    @if($order->tracking_number)
                                        <p class="text-sm text-gray-600">Tracking: {{ $order->tracking_number }}</p>
                                    @else
                                        <p class="text-sm text-gray-600">Waiting for shipment</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Delivered -->
                            <div class="relative flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full {{ $order->status == 'completed' ? 'bg-green-500' : 'bg-gray-300' }} flex items-center justify-center z-10">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="font-semibold text-gray-900">Delivered</p>
                                    @if($order->status == 'completed')
                                        <p class="text-sm text-gray-600">Order completed</p>
                                    @else
                                        <p class="text-sm text-gray-600">Pending delivery</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Order Items</h2>
                    <div class="space-y-4">
                        @foreach($order->items as $item)
                            <div class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg">
                                @php
                                    $productMedia = $item->product ? $item->product->getEffectiveMedia() : [];
                                    $productImageUrl = null;
                                    if (!empty($productMedia)) {
                                        if (is_string($productMedia[0])) {
                                            $productImageUrl = $productMedia[0];
                                        } elseif (is_array($productMedia[0])) {
                                            $productImageUrl = $productMedia[0]['url'] ?? $productMedia[0]['path'] ?? reset($productMedia[0]) ?? null;
                                        }
                                    }
                                @endphp
                                @if($productImageUrl)
                                    <img src="{{ $productImageUrl }}" 
                                         alt="{{ $item->product_name }}"
                                         class="w-16 h-16 object-cover rounded-lg">
                                @else
                                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <p class="font-semibold">{{ $item->product_name }}</p>
                                    <p class="text-sm text-gray-600">Quantity: {{ $item->quantity }}</p>
                                </div>
                                <p class="font-bold text-[#005366]">${{ number_format($item->price * $item->quantity, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Track Another Order -->
                <div class="text-center">
                    <a href="{{ route('orders.track') }}" 
                       class="inline-flex items-center text-[#0195FE] hover:text-[#017fda] transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Track Another Order
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

