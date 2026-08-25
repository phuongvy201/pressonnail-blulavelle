@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('customer.orders.index') }}" 
               class="inline-flex items-center text-[#0195FE] hover:text-[#017fda] transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Orders
            </a>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
                <div class="flex">
                    <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        @endif

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

        <!-- Order Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        Order #{{ $order->order_number }}
                    </h1>
                    <p class="text-gray-600">
                        Placed on {{ $order->created_at->format('M d, Y \a\t h:i A') }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="px-4 py-2 text-sm font-semibold rounded-full
                        @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                        @elseif($order->status == 'processing') bg-blue-100 text-blue-800
                        @elseif($order->status == 'completed' || $order->status == 'delivered') bg-green-100 text-green-800
                        @elseif($order->status == 'cancelled') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($order->status) }}
                    </span>
                    <span class="px-4 py-2 text-sm font-semibold rounded-full
                        @if($order->payment_status == 'paid') bg-green-100 text-green-800
                        @elseif($order->payment_status == 'pending') bg-yellow-100 text-yellow-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Order Items -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Order Items</h2>
                    <div class="space-y-4">
                        @foreach($order->items as $item)
                            <div class="flex items-start space-x-4 p-4 border border-gray-200 rounded-lg">
                                <!-- Product Image -->
                                <div class="flex-shrink-0">
                                    @php
                                        $productMedia = $item->product ? $item->product->getEffectiveMedia() : [];
                                        $productImageUrl = null;
                                        $productImageAlt = $item->product_name;
                                        if (!empty($productMedia) && $item->product) {
                                            $productImageAlt = $item->product->altForMediaItem($productMedia[0], $item->product_name, 0);
                                            if (is_string($productMedia[0])) {
                                                $productImageUrl = $productMedia[0];
                                            } elseif (is_array($productMedia[0])) {
                                                $productImageUrl = $productMedia[0]['url'] ?? $productMedia[0]['path'] ?? reset($productMedia[0]) ?? null;
                                            }
                                        }
                                    @endphp
                                    @if($productImageUrl)
                                        <img src="{{ $productImageUrl }}" 
                                             alt="{{ $productImageAlt }}"
                                             class="w-20 h-20 object-cover rounded-lg">
                                    @else
                                        <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <!-- Product Info -->
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">{{ $item->product_name }}</h3>
                                    @if($item->variant_name)
                                        <p class="text-sm text-gray-600 mt-1">
                                            Variant: {{ $item->variant_name }}
                                        </p>
                                    @endif
                                    @if($item->customizations)
                                        <div class="mt-2 text-sm text-gray-600">
                                            <p class="font-medium">Customizations:</p>
                                            <div class="ml-2">
                                                @foreach(json_decode($item->customizations, true) as $key => $value)
                                                    <p>{{ ucfirst($key) }}: {{ $value }}</p>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    <div class="mt-2 flex items-center space-x-4 text-sm">
                                        <span class="text-gray-600">Qty: {{ $item->quantity }}</span>
                                        <span class="text-gray-600">Price: ${{ number_format($item->price, 2) }}</span>
                                    </div>
                                </div>

                                <!-- Item Total -->
                                <div class="text-right">
                                    <p class="text-lg font-bold text-[#005366]">
                                        ${{ number_format($item->quantity * $item->price, 2) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="space-y-6">
                <!-- Order Summary Card -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Order Summary</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>${{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span>${{ number_format($order->shipping_cost, 2) }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span class="text-[#005366]">${{ number_format($order->total_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Shipping Address</h2>
                    <div class="text-gray-600 space-y-1">
                        <p class="font-semibold text-gray-900">{{ $order->customer_name }}</p>
                        <p>{{ $order->shipping_address }}</p>
                        <p>{{ $order->city }}, {{ $order->state }} {{ $order->postal_code }}</p>
                        <p>{{ $order->country }}</p>
                        @if($order->customer_phone)
                            <p class="mt-2">Phone: {{ $order->customer_phone }}</p>
                        @endif
                        <p>Email: {{ $order->customer_email }}</p>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Payment Information</h2>
                    <div class="text-gray-600 space-y-2">
                        <div class="flex justify-between">
                            <span>Method:</span>
                            <span class="font-semibold">{{ ucfirst($order->payment_method) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Status:</span>
                            <span class="font-semibold 
                                @if($order->payment_status == 'paid') text-green-600
                                @elseif($order->payment_status == 'pending') text-yellow-600
                                @else text-red-600
                                @endif">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </div>
                        @if($order->payment_transaction_id)
                            <div class="flex justify-between">
                                <span>Transaction ID:</span>
                                <span class="font-mono text-xs">{{ $order->payment_transaction_id }}</span>
                            </div>
                        @endif
                        @if($order->paid_at)
                            <div class="flex justify-between">
                                <span>Paid At:</span>
                                <span>{{ $order->paid_at->format('M d, Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Tracking Information (if available) -->
                @if($order->tracking_number)
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Tracking Information</h2>
                        <div class="text-gray-600">
                            <p class="font-semibold mb-2">Tracking Number:</p>
                            <p class="font-mono bg-gray-100 p-3 rounded-lg">
                                {{ $order->tracking_number }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @php
            $reviewableOrderItems = $order->items
                ->filter(function ($item) {
                    return $item->product_id
                        && $item->product
                        && !($item->product->is_gift_card ?? false);
                })
                ->unique('product_id');
            $pendingReviewCount = $canReviewOrder
                ? $reviewableOrderItems->filter(fn ($item) => !isset($userReviewsByProductId[$item->product_id]))->count()
                : 0;
        @endphp

        @if($reviewableOrderItems->isNotEmpty())
        <div id="order-reviews" class="mt-6 bg-white rounded-xl shadow-sm p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Product Reviews</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        @if($canReviewOrder)
                            Share your experience with the products you received.
                        @else
                            You can review products after your order is delivered.
                        @endif
                    </p>
                </div>
                @if($canReviewOrder && $pendingReviewCount > 0)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        {{ $pendingReviewCount }} pending {{ $pendingReviewCount === 1 ? 'review' : 'reviews' }}
                    </span>
                @endif
            </div>

            <div class="space-y-6">
                @foreach($reviewableOrderItems as $item)
                    @php
                        $existingReview = $userReviewsByProductId[$item->product_id] ?? null;
                        $canReviewItem = $canReviewOrder && !$existingReview;
                        $productMedia = $item->product->getEffectiveMedia();
                        $productImageUrl = null;
                        if (!empty($productMedia)) {
                            if (is_string($productMedia[0])) {
                                $productImageUrl = $productMedia[0];
                            } elseif (is_array($productMedia[0])) {
                                $productImageUrl = $productMedia[0]['url'] ?? $productMedia[0]['path'] ?? reset($productMedia[0]) ?? null;
                            }
                        }
                    @endphp
                    <div class="border border-gray-200 rounded-xl p-5">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="flex-shrink-0">
                                @if($productImageUrl)
                                    <img src="{{ $productImageUrl }}" alt="{{ $item->product_name }}" class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                                @else
                                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900">{{ $item->product_name }}</h3>
                                @if($item->product?->slug)
                                    <a href="{{ route('products.show', $item->product->slug) }}" class="text-sm text-[#0195FE] hover:underline">
                                        View product page
                                    </a>
                                @endif
                            </div>
                            @if($existingReview)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    Reviewed
                                </span>
                            @endif
                        </div>

                        @if($existingReview)
                            <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
                                <div class="flex items-center gap-1 text-amber-400 mb-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= (int) $existingReview->rating ? 'fill-current' : 'text-gray-300' }}" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                    <span class="ml-1 text-xs font-semibold text-gray-600">{{ (int) $existingReview->rating }}/5</span>
                                </div>
                                @if($existingReview->title)
                                    <p class="text-sm font-semibold text-gray-900">{{ $existingReview->title }}</p>
                                @endif
                                @if($existingReview->review_text)
                                    <p class="text-sm text-gray-700 mt-1">{{ $existingReview->review_text }}</p>
                                @endif
                                <p class="text-xs text-gray-500 mt-2">Submitted {{ $existingReview->created_at?->format('M d, Y') }}</p>
                            </div>
                        @elseif($canReviewItem)
                            <form action="{{ route('products.reviews.store', $item->product->slug) }}" method="POST" class="space-y-4">
                                @csrf
                                <input type="hidden" name="redirect_to" value="{{ route('customer.orders.show', $order->order_number) }}#order-reviews">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Rating</label>
                                    <div class="flex flex-wrap gap-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 hover:border-[#0195FE] cursor-pointer">
                                                <input type="radio" name="rating" value="{{ $i }}" class="text-[#0195FE] focus:ring-[#0195FE]" {{ (int) old('rating', 5) === $i ? 'checked' : '' }}>
                                                <span class="text-sm font-medium text-gray-700">{{ $i }}★</span>
                                            </label>
                                        @endfor
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Title (optional)</label>
                                    <input type="text" name="title" value="{{ old('title') }}" maxlength="120"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0195FE] focus:border-[#0195FE]"
                                           placeholder="Example: Beautiful set and long-lasting">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Your review</label>
                                    <textarea name="review_text" rows="3" required maxlength="2000"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0195FE] focus:border-[#0195FE]"
                                              placeholder="Share your experience with this product...">{{ old('review_text') }}</textarea>
                                </div>
                                <button type="submit"
                                        class="px-5 py-2.5 rounded-lg text-white font-semibold bg-[#0195FE] hover:bg-[#017fda] transition">
                                    Submit Review
                                </button>
                            </form>
                        @else
                            <p class="text-sm text-gray-600">
                                Review will be available once this order is marked as delivered.
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        @php
            $latestReturn = ($order->returnRequests ?? collect())->first();
        @endphp

        @if($latestReturn)
        <!-- Return / Refund Status -->
        <div class="mt-6 bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Return / Refund Status</h2>
                    <p class="text-sm text-gray-600 mt-1">Latest request status for this order.</p>
                </div>
                <span class="px-3 py-1 text-xs font-semibold rounded-full
                    @if($latestReturn->status === 'pending') bg-yellow-100 text-yellow-800
                    @elseif($latestReturn->status === 'processing') bg-blue-100 text-blue-800
                    @elseif($latestReturn->status === 'approved') bg-green-100 text-green-800
                    @elseif($latestReturn->status === 'completed') bg-emerald-100 text-emerald-800
                    @else bg-red-100 text-red-800 @endif">
                    {{ ucfirst($latestReturn->status) }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div>
                    <p class="font-semibold text-gray-900">Resolution</p>
                    <p class="mt-1">{{ ucfirst(str_replace('_',' ', $latestReturn->resolution ?? '')) }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Reason</p>
                    <p class="mt-1">{{ ucfirst(str_replace('_',' ', $latestReturn->reason ?? '')) }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Submitted at</p>
                    <p class="mt-1">{{ $latestReturn->created_at?->format('M d, Y H:i') }}</p>
                </div>
                @if($latestReturn->admin_note)
                <div>
                    <p class="font-semibold text-gray-900">Store note</p>
                    <p class="mt-1 whitespace-pre-line">{{ $latestReturn->admin_note }}</p>
                </div>
                @endif
            </div>

            @if($latestReturn->description)
                <div class="mt-4">
                    <p class="font-semibold text-gray-900">Your message</p>
                    <p class="mt-1 text-gray-700 whitespace-pre-line">{{ $latestReturn->description }}</p>
                </div>
            @endif

            @if($latestReturn->evidence_paths && count($latestReturn->evidence_paths) > 0)
                <div class="mt-4">
                    <p class="font-semibold text-gray-900 mb-2">Evidence</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach($latestReturn->evidence_paths as $path)
                            <a href="{{ Storage::url($path) }}" target="_blank" class="block">
                                <div class="aspect-video rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                                    <img src="{{ Storage::url($path) }}" alt="Evidence" class="w-full h-full object-cover">
                                </div>
                                <p class="text-xs text-gray-500 mt-1 truncate">{{ basename($path) }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        @endif

        <!-- Return / Exchange Request -->
        <div class="mt-10 bg-white rounded-xl shadow-sm p-6">
            @php
                $returnRoute = \Illuminate\Support\Facades\Route::has('customer.orders.return-request')
                    ? route('customer.orders.return-request', $order->order_number)
                    : null;
            @endphp
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Return / Exchange Request</h2>
                    <p class="text-sm text-gray-600 mt-1">Tell us why you want to return or exchange this order.</p>
                </div>
                @if(!$returnRoute)
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        Submission not configured yet
                    </span>
                @endif
            </div>

            @if(!$returnRoute)
                <div class="mb-4 text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    This form is for your reference. Please contact support to submit the request.
                </div>
            @endif

            <form class="space-y-5" method="POST" action="{{ $returnRoute ?? '#' }}" enctype="multipart/form-data">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Reason</label>
                    <select name="reason"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            {{ $returnRoute ? '' : 'disabled' }}>
                        <option value="">Select a reason</option>
                        <option value="product_defect">Product defect</option>
                        <option value="not_as_described">Not as described</option>
                        <option value="wrong_item">Wrong item received</option>
                        <option value="size_issue">Does not fit the size</option>
                        <option value="changed_mind">Changed my mind</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Preferred resolution</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @php
                            $resolutions = [
                                ['value' => 'refund', 'label' => 'Refund to original payment'],
                                ['value' => 'exchange', 'label' => 'Exchange for a new product'],
                                ['value' => 'store_credit', 'label' => 'Store credit'],
                            ];
                        @endphp
                        @foreach($resolutions as $res)
                            <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-blue-500">
                                <input type="radio"
                                       name="resolution"
                                       value="{{ $res['value'] }}"
                                       class="mt-1 text-blue-600 focus:ring-blue-500"
                                       {{ $returnRoute ? '' : 'disabled' }}>
                                <span class="text-sm text-gray-700">{{ $res['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tell us more (optional)</label>
                    <textarea name="description"
                              rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Describe the issue briefly..."
                              {{ $returnRoute ? '' : 'disabled' }}></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Upload evidence (images, optional)</label>
                    <input type="file"
                           name="evidence[]"
                           accept="image/*"
                           multiple
                           class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                           {{ $returnRoute ? '' : 'disabled' }}>
                    <p class="text-xs text-gray-500 mt-1">You can upload product photos, package photos, or any proof.</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox"
                           id="confirm"
                           name="confirm"
                           class="text-blue-600 focus:ring-blue-500 rounded"
                           {{ $returnRoute ? '' : 'disabled' }}>
                    <label for="confirm" class="text-sm text-gray-700">
                        I confirm the information above is accurate.
                    </label>
                </div>

                <div class="pt-2">
                    <button type="{{ $returnRoute ? 'submit' : 'button' }}"
                            class="px-5 py-3 rounded-lg text-white font-semibold shadow-sm transition
                            {{ $returnRoute ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed' }}">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

