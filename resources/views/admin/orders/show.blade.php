@extends('layouts.admin')

@section('title', 'Order Details - Admin')

@section('content')
<style>
    .order-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .status-badge {
        @apply px-3 py-1 rounded-full text-xs font-semibold;
    }

    .status-pending {
        @apply bg-yellow-100 text-yellow-800;
    }

    .status-processing {
        @apply bg-blue-100 text-blue-800;
    }

    .status-shipped {
        @apply bg-purple-100 text-purple-800;
    }

    .status-delivered {
        @apply bg-green-100 text-green-800;
    }

    .status-cancelled {
        @apply bg-red-100 text-red-800;
    }

    .payment-paid {
        @apply bg-green-100 text-green-800;
    }

    .payment-pending {
        @apply bg-yellow-100 text-yellow-800;
    }

    .payment-failed {
        @apply bg-red-100 text-red-800;
    }

    .payment-refunded {
        @apply bg-gray-100 text-gray-800;
    }

    .product-item {
        transition: all 0.3s ease;
    }

    .product-item:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('admin.orders.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center mb-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Orders
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900">Order Details</h1>
                    <p class="text-gray-600 mt-2">Order #{{ $order->order_number }}</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Order Status -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Status</h3>
                    <div class="flex items-center justify-between">
                        <div class="flex space-x-4">
                            <div>
                                <span class="status-badge status-{{ $order->status }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                            <div>
                                <span class="status-badge payment-{{ $order->payment_status }}">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Order Date</p>
                            <p class="font-semibold">{{ $order->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Name</p>
                            <p class="font-semibold">{{ $order->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-semibold">{{ $order->customer_email }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Phone</p>
                            <p class="font-semibold">{{ $order->customer_phone ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">User Account</p>
                            <p class="font-semibold">
                                @if($order->user)
                                    {{ $order->user->name }} ({{ $order->user->email }})
                                @else
                                    Guest Customer
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Shipping Address</h3>
                    <div class="space-y-2">
                        <p class="font-semibold">{{ $order->customer_name }}</p>
                        <p>{{ $order->shipping_address }}</p>
                        <p>{{ $order->city }}, {{ $order->state }} {{ $order->postal_code }}</p>
                        <p>{{ $order->country }}</p>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h3>
                    <div class="space-y-4">
                        @foreach($order->items as $item)
                            <div class="product-item flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                                @php
                                    $media = $item->product ? $item->product->getEffectiveMedia() : [];
                                    $imageUrl = null;
                                    $adminOrderImgAlt = $item->product_name;
                                    if ($media && count($media) > 0 && $item->product) {
                                        $adminOrderImgAlt = $item->product->altForMediaItem($media[0], $item->product_name, 0);
                                        if (is_string($media[0])) {
                                            $imageUrl = $media[0];
                                        } elseif (is_array($media[0])) {
                                            $imageUrl = $media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? null;
                                        }
                                    }
                                @endphp
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" 
                                         alt="{{ $adminOrderImgAlt }}"
                                         class="w-16 h-16 object-cover rounded-lg">
                                @else
                                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                                
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900">{{ $item->product_name }}</h4>
                                    <p class="text-sm text-gray-600">Quantity: {{ $item->quantity }}</p>
                                    <p class="text-sm text-gray-600">Unit Price: ${{ number_format($item->unit_price, 2) }}</p>
                                    @if($item->product && $item->product->shop)
                                        <p class="text-xs text-gray-500">Shop: {{ $item->product->shop->name }}</p>
                                    @endif

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
                                
                                <div class="text-right">
                                    <p class="font-bold text-lg text-gray-900">${{ number_format($item->total_price, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Order Summary & Actions -->
            <div class="space-y-6">
                <!-- Order Summary -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>${{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Tax (8%)</span>
                            <span>${{ number_format($order->tax_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span>${{ number_format($order->shipping_cost, 2) }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <div class="flex justify-between text-lg font-bold text-gray-900">
                                <span>Total</span>
                                <span>${{ number_format($order->total_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->hasRole('admin'))
                <!-- Update Order Status -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Order</h3>
                    <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Order Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                    <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                                    <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                    <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                                <select name="payment_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="failed" {{ $order->payment_status == 'failed' ? 'selected' : '' }}>Failed</option>
                                    <option value="refunded" {{ $order->payment_status == 'refunded' ? 'selected' : '' }}>Refunded</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tracking Number</label>
                                <input type="text" name="tracking_number"
                                       value="{{ old('tracking_number', $order->tracking_number) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter tracking number">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                <textarea name="notes" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Add notes about this order...">{{ $order->notes }}</textarea>
                            </div>

                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition-colors">
                                Update Order
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Order Information -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Information</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Order Number:</span>
                            <span class="font-semibold">{{ $order->order_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Payment Method:</span>
                            <span class="font-semibold">{{ ucfirst($order->payment_method) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Currency:</span>
                            <span class="font-semibold">{{ $order->currency }}</span>
                        </div>
                        @if($order->payment_id)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment ID:</span>
                                <span class="font-semibold">{{ $order->payment_id }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                @if(auth()->user()->hasRole('admin'))
                <!-- Danger Zone -->
                <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-red-900 mb-4">Danger Zone</h3>
                    <p class="text-sm text-red-700 mb-4">Deleting an order will permanently remove it and all associated data.</p>
                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" 
                          onsubmit="return confirm('Are you sure you want to delete this order? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition-colors">
                            Delete Order
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
