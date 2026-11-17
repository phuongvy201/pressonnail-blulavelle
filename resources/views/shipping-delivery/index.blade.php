@extends('layouts.app')

@section('title', 'Shipping & Delivery')
@section('meta_description', 'Learn about BluLavelle shipping and delivery. Processing times, delivery times by product, shipping costs, tracking, and customs information.')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
        <!-- Hero Header -->
        <div class="relative bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 text-white px-8 py-16">
            <div class="absolute inset-0 bg-black opacity-10"></div>
            <div class="relative z-10 text-center">
                <div class="flex justify-center mb-6">
                    <div class="w-32 h-32 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm shadow-2xl">
                        <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                    </div>
                </div>
                <h1 class="text-6xl font-bold mb-4">Shipping & Delivery</h1>
                <p class="text-2xl text-blue-100">Fast, Reliable Worldwide Shipping</p>
                <p class="text-lg text-cyan-100 mt-4">Updated: {{ now()->format('M d, Y') }}</p>
            </div>
        </div>

        <div class="px-8 py-12">
            <!-- Processing Notice -->
            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 border-l-4 border-blue-500 rounded-r-lg p-8 mb-10">
                <p class="text-xl text-gray-800 leading-relaxed text-center">
                    Your product will enter the <strong class="text-blue-600">processing stage</strong> as soon as your order is placed.
                </p>
            </div>

            <!-- Timeline Factors -->
            <div class="mb-12">
                <h2 class="text-4xl font-bold text-gray-800 text-center mb-8">Delivery Timeline</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-8 shadow-xl border-2 border-purple-300">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center mr-4 shadow-lg">
                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-purple-900 mb-3">Processing Time</h3>
                                <p class="text-gray-800 leading-relaxed">
                                    After your payment is confirmed, your order will enter the processing stage and usually takes <strong class="text-purple-600">1 - 7 days</strong> depending on the product you purchase.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-8 shadow-xl border-2 border-green-300">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full flex items-center justify-center mr-4 shadow-lg">
                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-green-900 mb-3">Shipping Time</h3>
                                <p class="text-gray-800 leading-relaxed">
                                    Once the processing is complete, your order will be shipped and will take a few more days to reach your address.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $regionColors = [
                    'US' => ['from-blue-600', 'to-cyan-600', 'border-blue-300', 'bg-blue-100', 'text-blue-700'],
                    'UK' => ['from-purple-600', 'to-indigo-600', 'border-purple-300', 'bg-purple-100', 'text-purple-700'],
                    'CA' => ['from-red-600', 'to-rose-600', 'border-red-300', 'bg-red-100', 'text-red-700'],
                    'MX' => ['from-orange-600', 'to-amber-600', 'border-orange-300', 'bg-orange-100', 'text-orange-700'],
                    'EU' => ['from-indigo-600', 'to-violet-600', 'border-indigo-300', 'bg-indigo-100', 'text-indigo-700'],
                ];
            @endphp


            <!-- All Regions Shipping Costs -->
            @if(isset($allFormattedCosts) && $allFormattedCosts->isNotEmpty())
            <div class="mb-12">
                <div class="bg-gradient-to-r from-indigo-600 via-blue-600 to-teal-600 text-white px-6 py-5 rounded-t-lg">
                    <h2 class="text-3xl font-bold text-center">All Regions & Categories Shipping Fees</h2>
                    <p class="text-center text-blue-100 mt-2">Includes rates configured for this domain and shared general rates.</p>
                </div>
                <div class="bg-blue-50 border-2 border-blue-200 rounded-b-lg p-6 mb-6">
                    <p class="text-gray-800 font-semibold mb-2">Important notes:</p>
                    <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li>Prices are converted to your currency using the current rate.</li>  
                        <li>A 7% handling fee applies to the order value (before shipping).</li>
                        <li><strong>First Item</strong> is for the first product; <strong>Additional Items</strong> applies to each extra product.</li>
                        <li>Taxes/duties (if any) are paid by the receiver per local regulations.</li>
                        <li>Domain-specific category rates are prioritized; otherwise general rates are shown.</li>
                    </ul>
                </div>

                <div class="space-y-8">
                    @foreach($allFormattedCosts as $regionKey => $costs)
                    @php
                        $colors = $regionColors[$regionKey] ?? $regionColors['US'];
                        $regionLabel = $regionNames[$regionKey] ?? $regionKey;
                    @endphp
                    <div class="border-2 {{ $colors[2] }} rounded-lg overflow-hidden shadow-sm">
                        <div class="bg-gradient-to-r {{ $colors[0] }} {{ $colors[1] }} text-white px-6 py-3 flex items-center justify-between">
                            <h3 class="text-2xl font-bold">{{ $regionLabel }}</h3>
                            
                        </div>
                        <div class="bg-white overflow-x-auto">
                            <table class="w-full">
                                <thead class="{{ $colors[3] }}">
                                    <tr>
                                        <th class="px-4 py-4 text-left font-bold text-gray-800 border-b-2 {{ $colors[2] }}">Product Type</th>
                                        <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 {{ $colors[2] }}">First Item</th>
                                        <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 {{ $colors[2] }}">Additional Items</th>
                                        <th class="px-4 py-4 text-center font-bold text-gray-800 border-b-2 {{ $colors[2] }}">Delivery</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($costs as $cost)
                                    <tr class="hover:bg-gray-50 transition {{ $loop->even ? 'bg-gray-50' : '' }}">
                                        <td class="px-4 py-3 font-semibold text-gray-800">
                                            {{ ucfirst(str_replace('_', ' ', $cost['product_type'])) }}
                                        </td>
                                        <td class="px-4 py-3 text-center {{ $colors[4] }} font-bold">{{ $cost['first_item'] }}</td>
                                        <td class="px-4 py-3 text-center text-gray-700">{{ $cost['additional_item'] }}</td>
                                        <td class="px-4 py-3 text-center text-gray-700">{{ $cost['delivery_text'] ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Order Tracking -->
            <div class="mb-12">
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-purple-300 rounded-lg p-8">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center mr-4 shadow-lg">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-3xl font-bold text-purple-900 mb-3">Order Tracking</h3>
                            <p class="text-gray-800 leading-relaxed text-lg">
                                Once your order has been shipped, you will receive a <strong>tracking number via email</strong>. You can use this number to monitor your shipment's progress through our tracking portal or the courier's website.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                <!-- Customs -->
                <div class="bg-gradient-to-br from-orange-50 to-red-50 border-2 border-orange-300 rounded-lg p-6">
                    <div class="flex items-start mb-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-orange-900 mb-2">Customs, Duties & Taxes</h3>
                            <p class="text-gray-800 leading-relaxed">
                                Orders shipped outside USA may be subject to customs duties, taxes, and fees. <strong class="text-orange-600">These charges are the customer's responsibility</strong> and vary by country.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Failed Deliveries -->
                <div class="bg-gradient-to-br from-red-50 to-rose-50 border-2 border-red-300 rounded-lg p-6">
                    <div class="flex items-start mb-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-red-500 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-red-900 mb-2">Failed Deliveries</h3>
                            <p class="text-gray-800 leading-relaxed">
                                BluLavelle is <strong class="text-red-600">not responsible</strong> for packages delayed, lost, or returned due to incorrect addresses. Additional fees may apply to resend.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 rounded-lg p-10 text-center text-white">
                <div class="flex justify-center mb-6">
                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-3xl font-bold mb-4">Questions About Shipping?</h3>
                <p class="text-xl text-cyan-100 mb-6 max-w-3xl mx-auto">
                    If your order hasn't arrived or you have concerns about your shipment, we're here to help!
                </p>
                <a href="mailto:support@blulavelle.com" class="inline-flex items-center px-10 py-4 bg-white text-teal-600 font-bold rounded-lg shadow-xl hover:shadow-2xl transition duration-200 text-xl">
                    <svg class="w-7 h-7 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Contact Customer Service
                </a>
                <p class="mt-6 text-blue-100 text-lg">
                    At BluLavelle, customer satisfaction is our priority. Thank you for choosing us!
                </p>
            </div>
        </div>
    </div>
</div>
@endsection




















