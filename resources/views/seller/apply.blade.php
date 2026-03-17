@extends('layouts.app')

@section('content')
@php
    $recaptchaSiteKey = config('services.recaptcha.site_key');
@endphp
<main class="bg-gray-50">
    <!-- Hero -->
    <section class="bg-gradient-to-br from-sky-600 via-sky-500 to-blue-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-20 grid lg:grid-cols-2 gap-10 items-center">
            <div class="space-y-5">
                <p class="text-sm uppercase tracking-widest text-white/80">Sell on BluLavelle</p>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight">
                    Turn your nail designs into a real business.
                </h1>
                <p class="text-lg text-white/90">
                    Create, list, and sell press-on nails to customers worldwide—fast setup, secure payments, and reliable fulfillment support.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="#apply-form" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-sky-700 font-semibold shadow-lg hover:shadow-xl transition">
                        Start selling
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#join" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border border-white/60 text-white font-semibold hover:bg-white/10 transition">
                        See benefits
                    </a>
                </div>
                <p class="text-sm text-white/80">No monthly fees. Secure payments. Shipping & support made simple.</p>
            </div>
            <div class="relative">
                <div class="absolute inset-0 bg-white/10 blur-3xl rounded-full"></div>
                <img src="https://meear.com/modules/seller/images/your-art.png?v=20251219085848" alt="Sell on BluLavelle" class="relative w-full max-w-xl mx-auto rounded-3xl shadow-2xl ring-1 ring-white/20">
            </div>
        </div>
    </section>

    <!-- Apply form -->
    <section id="apply-form" class="bg-white py-14">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-lg bg-green-50 text-green-800 border border-green-200">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-800 border border-red-200">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-800 border border-red-200">
                    <ul class="list-disc ml-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-gray-50 rounded-2xl shadow-sm border border-gray-100 p-8 space-y-6">
                <div class="space-y-2">
                    <p class="text-sm font-semibold text-sky-600">Apply</p>
                    <h3 class="text-2xl font-bold text-gray-900">Tell us about your nail brand</h3>
                    <p class="text-gray-600">Share a few details and we’ll review to activate your BluLavelle seller account.</p>
                </div>

                <form action="{{ route('seller.apply.submit') }}" method="POST" class="space-y-6" id="seller-apply-form">
                    @csrf
                    <input type="text" name="hp_email" id="hp_email" value="" style="display:none;" tabindex="-1" autocomplete="off">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full name *</label>
                            <input type="text" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Jane Doe">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="you@example.com">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone ?? auth()->user()->phone_number ?? '') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="(+84) 0912 345 678">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Store / brand</label>
                            <input type="text" name="store_name" value="{{ old('store_name') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Your shop name">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Main product categories *</label>
                        <input type="text" name="product_categories" value="{{ old('product_categories') }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="E.g. Apparel, Decor, Accessories">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Additional notes</label>
                        <textarea name="message" rows="5" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Scale, support needs, special requests...">{{ old('message') }}</textarea>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-gray-500">By submitting, you agree BluLavelle may contact you via email/phone.</p>
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-semibold shadow-sm transition">
                            Submit application
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        @if($recaptchaSiteKey)
                            <div class="w-full mt-2">
                                <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </section>
    @if($recaptchaSiteKey)
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif

    <!-- How it works -->
    <section class="bg-white py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="text-center space-y-3">
                <p class="text-sm font-semibold text-sky-600">How it works</p>
                <h2 class="text-3xl font-bold text-gray-900">How BluLavelle works</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">From listing your press-on nails to delivery—every step is simple.</p>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @php
                    $steps = [
                        ['title' => 'List your nail sets', 'desc' => 'Create your shop and publish your press-on nail designs.', 'img' => 'https://meear.com/modules/seller/images/upload.svg'],
                        ['title' => 'Customers order', 'desc' => 'Shoppers discover nail sets they love and place orders.', 'img' => 'https://meear.com/modules/seller/images/customers.svg'],
                        ['title' => 'We help you ship', 'desc' => 'We provide guidance and tools to get orders delivered smoothly.', 'img' => 'https://meear.com/modules/seller/images/products.svg'],
                        ['title' => 'Get paid', 'desc' => 'Secure payouts flow automatically to your account.', 'img' => 'https://meear.com/modules/seller/images/paid.svg'],
                    ];
                @endphp
                @foreach($steps as $step)
                    <div class="bg-gray-50 rounded-2xl p-6 text-center shadow-sm hover:shadow-md transition">
                        <img src="{{ $step['img'] }}" alt="{{ $step['title'] }}" class="w-28 h-28 mx-auto mb-4" loading="lazy">
                        <h3 class="font-semibold text-lg text-gray-900">{{ $step['title'] }}</h3>
                        <p class="text-sm text-gray-600 mt-2">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- What to sell -->
    <section class="bg-gradient-to-b from-white to-sky-50 py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-10 items-center">
            <div class="order-2 lg:order-1 space-y-4">
                <p class="text-sm font-semibold text-sky-600">Product</p>
                <h2 class="text-3xl font-bold text-gray-900">Sell press-on nails customers love</h2>
                <p class="text-gray-600">From minimal everyday sets to bold statement designs—BluLavelle helps you reach customers and grow.</p>
                <ul class="space-y-2 text-gray-700">        
                    <li class="flex items-start gap-2"><span class="mt-1 text-sky-600">•</span> No monthly fees, simple onboarding.</li>
                    <li class="flex items-start gap-2"><span class="mt-1 text-sky-600">•</span> Tools to manage products, orders, and customers.</li>
                    <li class="flex items-start gap-2"><span class="mt-1 text-sky-600">•</span> Promotions, insights, and seller support.</li>
                </ul>
            </div>
            <div class="order-1 lg:order-2">
                <img src="https://meear.com/modules/seller/images/your-art.png?v=20251219085848" alt="What to sell" class="w-full rounded-2xl shadow-xl" loading="lazy">
            </div>
        </div>
    </section>

    <!-- Join sellers -->
    <section id="join" class="bg-white py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-10 items-center">
            <div class="space-y-4">
                <p class="text-sm font-semibold text-sky-600">Community</p>
                <h2 class="text-3xl font-bold text-gray-900">Join nail creators worldwide</h2>
                <p class="text-gray-600">BluLavelle is home to nail artists, brands, and passionate creators. Build your audience and earn from your designs.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="/shops" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-sky-600 text-white font-semibold hover:bg-sky-700 transition">
                        View shops
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#apply-form" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-sky-600 text-sky-700 font-semibold hover:bg-sky-50 transition">
                        Sign up now
                    </a>
                </div>
            </div>
            <div>
                <img src="https://meear.com/modules/seller/images/artists.png" alt="Sellers" class="w-full rounded-2xl shadow-lg" loading="lazy">
            </div>
        </div>
    </section>

    <!-- Simple & secure -->
    <section class="bg-slate-900 text-white py-14">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="text-center space-y-3">
                <p class="text-sm font-semibold text-sky-300">Transparent</p>
                <h2 class="text-3xl font-bold">Simple, transparent, secure</h2>
                <p class="text-white/80">No monthly fees. Secure transactions. Automated payouts.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    @php
                        $points = [
                            'No monthly maintenance fees',
                            'Secure payments and fraud protection',
                            'Automatic scheduled payouts',
                            'Seller and brand protection',
                        ];
                    @endphp
                    @foreach($points as $p)
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-sky-500/20 text-sky-300 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-white/90">{{ $p }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="bg-white/5 rounded-2xl p-4 md:p-6 border border-white/10">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-sm font-semibold text-sky-300">Sample fee table</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-white/90">
                            <thead class="text-white/70 uppercase text-xs">
                                <tr>
                                    <th class="py-3 pr-4">Product</th>
                                    <th class="py-3 pr-4">Regular</th>
                                    <th class="py-3 pr-4">Sale</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <tr>
                                    <td class="py-3 pr-4 font-semibold">Press-on nail sets</td>
                                    <td class="py-3 pr-4">—</td>
                                    <td class="py-3 pr-4">—</td>
                                </tr>
                                <tr>
                                    <td class="py-3 pr-4 font-semibold">Nail prep & accessories</td>
                                    <td class="py-3 pr-4">—</td>
                                    <td class="py-3 pr-4">—</td>
                                </tr>
                                <tr>
                                    <td class="py-3 pr-4 font-semibold">Bundles</td>
                                    <td class="py-3 pr-4">—</td>
                                    <td class="py-3 pr-4">—</td>
                                </tr>
                                <tr>
                                    <td class="py-3 pr-4 font-semibold">Seasonal collections</td>
                                    <td class="py-3 pr-4">—</td>
                                    <td class="py-3 pr-4">—</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="py-3">
                                        <span class="text-white/70 text-xs">Fees vary by category and promotion. We’ll share details after your application is approved.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- You're the boss -->
    <section class="bg-white py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="text-center space-y-3">
                <p class="text-sm font-semibold text-sky-600">Control</p>
                <h2 class="text-3xl font-bold text-gray-900">You’re the boss</h2>
                <p class="text-gray-600">Pricing, products, brand protection, and operations—all in your control.</p>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        ['title' => 'Own your pricing', 'desc' => 'Customize prices and promotions for every product.', 'img' => 'https://meear.com/modules/seller/images/complete-control.svg'],
                        ['title' => 'Brand protection', 'desc' => 'Report counterfeit listings and protect your designs.', 'img' => 'https://meear.com/modules/seller/images/anti-piracy.svg'],
                        ['title' => 'Sell globally', 'desc' => 'Reach customers worldwide with reliable shipping support.', 'img' => 'https://meear.com/modules/seller/images/world-class.svg'],
                    ];
                @endphp
                @foreach($features as $f)
                    <div class="bg-gray-50 rounded-2xl p-6 shadow-sm hover:shadow-md transition">
                        <img src="{{ $f['img'] }}" alt="{{ $f['title'] }}" class="w-24 h-24 mx-auto mb-4" loading="lazy">
                        <h3 class="text-lg font-semibold text-gray-900 text-center">{{ $f['title'] }}</h3>
                        <p class="text-sm text-gray-600 mt-2 text-center">{{ $f['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="bg-sky-600 text-white py-14">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4">
            <h3 class="text-3xl font-bold">Ready to start selling?</h3>
            <p class="text-white/90">Apply now to open your BluLavelle nail shop and reach global customers.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="#apply-form" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-sky-700 font-semibold hover:shadow-lg transition">
                    Start now
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border border-white/60 text-white font-semibold hover:bg-white/10 transition">
                    Need help? Contact us
                </a>
            </div>
        </div>
    </section>
</main>
@endsection

 