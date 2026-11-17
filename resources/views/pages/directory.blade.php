@extends('layouts.app')

@section('title', 'Sitemap')
@section('meta_description', 'Browse BluLavelle pages, collections, categories, and nail artists.')

@section('content')
<div class="bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-4xl font-bold text-slate-900 mb-8">Sitemap</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-4">Shop</h2>
                <ul class="space-y-2 text-sm">
                    <li><a class="text-[#0195FE] hover:underline" href="{{ route('home') }}">Home</a></li>
                    <li><a class="text-[#0195FE] hover:underline" href="{{ route('products.index') }}">All products</a></li>
                    <li><a class="text-[#0195FE] hover:underline" href="{{ route('collections.index') }}">Collections</a></li>
                    <li><a class="text-[#0195FE] hover:underline" href="{{ route('shops.index') }}">Nail artists</a></li>
                    <li><a class="text-[#0195FE] hover:underline" href="{{ route('blog.index') }}">Blog</a></li>
                    <li><a class="text-[#0195FE] hover:underline" href="{{ route('reviews.public') }}">Reviews</a></li>
                    <li><a class="text-[#0195FE] hover:underline" href="{{ route('sizing-kit.index') }}">Sizing kit</a></li>
                </ul>
                @if($directory['collections']->isNotEmpty())
                    <h3 class="mt-6 font-semibold text-slate-900">Collections</h3>
                    <ul class="mt-2 space-y-2 text-sm">
                        @foreach($directory['collections'] as $collection)
                            <li><a class="text-[#0195FE] hover:underline" href="{{ route('collections.show', $collection->slug) }}">{{ $collection->name }}</a></li>
                        @endforeach
                    </ul>
                @endif
                @if($directory['categories']->isNotEmpty())
                    <h3 class="mt-6 font-semibold text-slate-900">Categories</h3>
                    <ul class="mt-2 space-y-2 text-sm">
                        @foreach($directory['categories'] as $category)
                            <li><a class="text-[#0195FE] hover:underline" href="{{ route('category.show', $category->slug) }}">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                @endif
                @if($directory['shops']->isNotEmpty())
                    <h3 class="mt-6 font-semibold text-slate-900">Artists</h3>
                    <ul class="mt-2 space-y-2 text-sm">
                        @foreach($directory['shops'] as $shop)
                            <li><a class="text-[#0195FE] hover:underline" href="{{ route('shops.show', $shop->shop_slug) }}">{{ $shop->shop_name }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-4">Help and policies</h2>
                <ul class="space-y-2 text-sm">
                    @foreach($directory['pages'] as $entry)
                        @continue(in_array($entry->slug, ['sitemap', 'contact-us-2', 'order-tracking', 'free-return'], true))
                        <li><a class="text-[#0195FE] hover:underline" href="{{ route('pages.show', $entry->slug) }}">{{ $entry->title }}</a></li>
                    @endforeach
                    <li><a class="text-[#0195FE] hover:underline" href="{{ route('contact') }}">Contact</a></li>
                    <li><a class="text-[#0195FE] hover:underline" href="{{ route('shipping-delivery.index') }}">Shipping and delivery</a></li>
                    <li><a class="text-[#0195FE] hover:underline" href="{{ route('orders.track') }}">Order tracking</a></li>
                </ul>
            </section>
        </div>
    </div>
</div>
@endsection
