@extends('layouts.app')

@section('title', 'Sizing Kit - Find Your Perfect Fit')

@section('content')
<div class="min-h-screen bg-background-light font-display text-slate-900">
    {{-- Hero Section --}}
    <section class="px-4 sm:px-6 lg:px-10 py-12 md:py-20 bg-white">
        <div class="max-w-6xl mx-auto flex flex-col lg:flex-row gap-12 items-center">
            <div class="flex flex-col gap-8 flex-1">
                <div class="flex flex-col gap-4">
                    <span class="text-primary font-bold tracking-widest text-sm uppercase">Fit Guarantee</span>
                    <h1 class="text-slate-900 text-4xl md:text-6xl font-black leading-tight tracking-tight">
                        Find Your <span class="text-primary">Perfect</span> Fit
                    </h1>
                    <p class="text-slate-600 text-lg leading-relaxed max-w-xl">
                        Don't guess your size. Our sizing kits ensure your press-ons look natural, professional, and stay on longer with our 100% fit guarantee.
                    </p>
                </div>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('products.index') }}" class="inline-flex min-w-[160px] items-center justify-center rounded-xl h-12 px-6 bg-primary text-white text-base font-bold shadow-lg shadow-primary/25 hover:opacity-90 transition-all">
                        Order a Kit
                    </a>
                    <a href="#size-chart" class="inline-flex min-w-[160px] items-center justify-center rounded-xl h-12 px-6 bg-slate-100 text-slate-900 text-base font-bold hover:bg-slate-200 transition-all">
                        View Size Guide
                    </a>
                </div>
            </div>
            <div class="flex-1 w-full">
                <div class="relative rounded-2xl overflow-hidden aspect-[4/3] shadow-2xl bg-slate-100">
                    <img src="https://images.unsplash.com/photo-1604654894610-df63bc536371?w=800" alt="Sizing kit - find your perfect fit" class="absolute inset-0 w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Why Buy Section --}}
    <section class="px-4 sm:px-6 lg:px-10 py-16 bg-background-light">
        <div class="max-w-6xl mx-auto flex flex-col gap-12">
            <div class="text-center max-w-2xl mx-auto">
                <h2 class="text-slate-900 text-3xl md:text-4xl font-bold mb-4">Why Buy a Sizing Kit?</h2>
                <p class="text-slate-600">Skip the measuring tape. Get the accuracy of a professional salon fitting from the comfort of your home.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="flex flex-col gap-4 rounded-2xl bg-white p-8 shadow-sm border border-slate-100">
                    <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-3xl">verified</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">100% Accuracy</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Measurements vary by brand. Our kit matches our exact nail molds for a seamless fit every time.</p>
                </div>
                <div class="flex flex-col gap-4 rounded-2xl bg-white p-8 shadow-sm border border-slate-100">
                    <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-3xl">touch_app</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Easy To Use</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Simply press the clear sample tips onto your natural nails to find your perfect match in seconds.</p>
                </div>
                <div class="flex flex-col gap-4 rounded-2xl bg-white p-8 shadow-sm border border-slate-100">
                    <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-3xl">recycling</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Reusable Samples</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Keep your kit as a reference for future shapes. Sizes may differ between Almond and Square.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Choose Your Shape (product cards) --}}
    <section class="px-4 sm:px-6 lg:px-10 py-16 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-end gap-6 mb-10">
                <div class="flex flex-col gap-2">
                    <h2 class="text-slate-900 text-3xl md:text-4xl font-bold">Choose Your Shape</h2>
                    <p class="text-slate-600 text-base">Select the shape you're planning to wear for the most accurate fit.</p>
                </div>
                <a href="{{ $sizingKitCategory ? route('products.index', ['category' => $sizingKitCategory->id]) : route('products.index') }}" class="text-primary font-bold flex items-center gap-2 hover:underline">
                    View All Shapes <span class="material-symbols-outlined text-xl">arrow_forward</span>
                </a>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($shapeKits as $index => $product)
                @php
                    $media = $product->getEffectiveMedia();
                    $imageUrl = null;
                    if ($media && count($media) > 0) {
                        if (is_string($media[0])) {
                            $imageUrl = str_starts_with($media[0], 'http') ? $media[0] : asset('storage/' . $media[0]);
                        } elseif (is_array($media[0])) {
                            $u = $media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? null;
                            $imageUrl = $u ? (str_starts_with((string)$u, 'http') ? $u : asset('storage/' . $u)) : null;
                        }
                    }
                    $price = (float) ($product->price ?? $product->template->base_price ?? 0);
                @endphp
                <div class="group flex flex-col gap-4">
                    <a href="{{ route('products.show', $product->slug) }}" class="relative aspect-square overflow-hidden rounded-xl bg-slate-100 block">
                        @if($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        @else
                        <div class="absolute inset-0 flex items-center justify-center bg-slate-200">
                            <span class="material-symbols-outlined text-4xl text-slate-400">image</span>
                        </div>
                        @endif
                        @if($index === 0)
                        <div class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider text-slate-900">Most Popular</div>
                        @endif
                    </a>
                    <div class="flex flex-col gap-1">
                        <h3 class="text-slate-900 font-bold">
                            <a href="{{ route('products.show', $product->slug) }}" class="hover:text-primary transition-colors">{{ $product->name }}</a>
                        </h3>
                        <div class="flex justify-between items-center">
                            @if($product->description)
                            <span class="text-slate-500 text-sm line-clamp-1">{{ Str::limit(strip_tags($product->description), 30) }}</span>
                            @endif
                            <span class="text-primary font-bold">{{ currency_symbol() }}{{ number_format($price, 2) }}</span>
                        </div>
                    </div>
                    <a href="{{ route('products.show', $product->slug) }}" class="w-full bg-slate-900 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-primary transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">add_shopping_cart</span> Add to Cart
                    </a>
                </div>
                @endforeach
            </div>
            @if($shapeKits->isEmpty())
            <p class="text-slate-500 text-center py-8">Chưa có sizing kit nào. Admin hãy tạo category slug <strong>sizing-kit</strong> và thêm sản phẩm vào category đó.</p>
            @endif
        </div>
    </section>

    {{-- How To Use --}}
    <section class="px-4 sm:px-6 lg:px-10 py-16 bg-primary/5">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-slate-900 text-3xl md:text-4xl font-bold mb-4">How to Use Your Sizing Kit</h2>
                <p class="text-slate-600">Finding your sizes is as easy as 1-2-3.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="flex flex-col items-center text-center gap-4">
                    <div class="size-16 rounded-full bg-primary text-white flex items-center justify-center font-bold text-2xl shadow-lg">1</div>
                    <h3 class="text-xl font-bold text-slate-900">Sample and Match</h3>
                    <p class="text-slate-600">Place the numbered clear nail tips over your natural nails. Press down firmly to simulate how it will sit.</p>
                </div>
                <div class="flex flex-col items-center text-center gap-4">
                    <div class="size-16 rounded-full bg-primary text-white flex items-center justify-center font-bold text-2xl shadow-lg">2</div>
                    <h3 class="text-xl font-bold text-slate-900">Note Your Numbers</h3>
                    <p class="text-slate-600">Write down the number that fits each finger from thumb to pinky for both your left and right hand.</p>
                </div>
                <div class="flex flex-col items-center text-center gap-4">
                    <div class="size-16 rounded-full bg-primary text-white flex items-center justify-center font-bold text-2xl shadow-lg">3</div>
                    <h3 class="text-xl font-bold text-slate-900">Check the Chart</h3>
                    <p class="text-slate-600">Compare your numbers to our preset sizes (XS, S, M, L) or provide your custom list when ordering.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Reference Size Chart Table --}}
    <section id="size-chart" class="px-4 sm:px-6 lg:px-10 py-16 bg-white scroll-mt-24">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-slate-900 mb-8 text-center">Reference Size Chart</h2>
            <div class="overflow-hidden rounded-2xl border border-slate-200">
                <table class="w-full text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 font-bold text-slate-900">Preset Size</th>
                            <th class="px-6 py-4 font-bold text-slate-900">Thumb</th>
                            <th class="px-6 py-4 font-bold text-slate-900">Index</th>
                            <th class="px-6 py-4 font-bold text-slate-900">Middle</th>
                            <th class="px-6 py-4 font-bold text-slate-900">Ring</th>
                            <th class="px-6 py-4 font-bold text-slate-900">Pinky</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($sizeChartTable as $row)
                        <tr class="hover:bg-primary/5 transition-colors">
                            <td class="px-6 py-4 font-bold text-primary">{{ $row['preset'] }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $row['thumb']['mm'] }}mm ({{ $row['thumb']['num'] }})</td>
                            <td class="px-6 py-4 text-slate-600">{{ $row['index']['mm'] }}mm ({{ $row['index']['num'] }})</td>
                            <td class="px-6 py-4 text-slate-600">{{ $row['middle']['mm'] }}mm ({{ $row['middle']['num'] }})</td>
                            <td class="px-6 py-4 text-slate-600">{{ $row['ring']['mm'] }}mm ({{ $row['ring']['num'] }})</td>
                            <td class="px-6 py-4 text-slate-600">{{ $row['pinky']['mm'] }}mm ({{ $row['pinky']['num'] }})</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-slate-400 mt-4 text-center italic">*Measurements are in millimeters (mm). Sample numbers in parenthesis ( ).</p>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section class="px-4 sm:px-6 lg:px-10 py-16 bg-background-light">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold text-slate-900 mb-10 text-center">Frequently Asked Questions</h2>
            <div class="flex flex-col gap-4">
                <details class="group bg-white rounded-xl border border-slate-100 p-6">
                    <summary class="list-none flex justify-between items-center font-bold text-slate-900 cursor-pointer">
                        Do I need a kit for every shape?
                        <span class="material-symbols-outlined transition-transform group-open:rotate-180">expand_more</span>
                    </summary>
                    <div class="mt-4 text-slate-600 text-sm leading-relaxed">
                        Ideally, yes. Different shapes (like Almond vs Square) are manufactured using different molds. While your size might be the same, the curvature and fit can vary slightly between shapes.
                    </div>
                </details>
                <details class="group bg-white rounded-xl border border-slate-100 p-6">
                    <summary class="list-none flex justify-between items-center font-bold text-slate-900 cursor-pointer">
                        What if my nails are between sizes?
                        <span class="material-symbols-outlined transition-transform group-open:rotate-180">expand_more</span>
                    </summary>
                    <div class="mt-4 text-slate-600 text-sm leading-relaxed">
                        If you're between sizes, we always recommend going with the larger size. You can easily file the sides of the press-on nail for a customized, perfect fit.
                    </div>
                </details>
                <details class="group bg-white rounded-xl border border-slate-100 p-6">
                    <summary class="list-none flex justify-between items-center font-bold text-slate-900 cursor-pointer">
                        How long does shipping take for the kit?
                        <span class="material-symbols-outlined transition-transform group-open:rotate-180">expand_more</span>
                    </summary>
                    <div class="mt-4 text-slate-600 text-sm leading-relaxed">
                        Sizing kits are processed within 24 hours and shipped via standard mail, which typically takes 3-5 business days. We offer free shipping on all sizing kits!
                    </div>
                </details>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="px-4 sm:px-6 lg:px-10 py-12 bg-white border-t border-slate-100">
        <div class="max-w-2xl mx-auto text-center">
            <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center gap-2 bg-primary-dark text-white font-bold py-4 px-8 rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all">
                <span class="material-symbols-outlined">shopping_bag</span>
                Shop All Nails
            </a>
        </div>
    </section>
</div>
@endsection
