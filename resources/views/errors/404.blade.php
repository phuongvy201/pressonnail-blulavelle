@extends('layouts.app')

@section('title', 'Page not found')
@section('robots', 'noindex, follow')

@section('content')
<div class="bg-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
        <p class="text-sm font-bold tracking-widest text-[#0195FE]">404</p>
        <h1 class="mt-3 text-4xl font-bold text-slate-900">Page not found</h1>
        <p class="mt-4 text-slate-600">That link does not match a BluLavelle page. Try a search, or go back to the shop.</p>
        <form action="{{ route('search') }}" method="GET" class="mt-8 flex gap-2 max-w-md mx-auto">
            <label class="sr-only" for="missing-page-search">Search</label>
            <input id="missing-page-search" type="search" name="q" placeholder="Search nails" class="flex-1 rounded-xl border border-slate-300 px-4 py-3">
            <button type="submit" class="rounded-xl bg-[#0195FE] px-5 py-3 font-semibold text-white">Search</button>
        </form>
        <div class="mt-8 flex flex-wrap justify-center gap-4 text-sm">
            <a class="text-[#0195FE] font-semibold hover:underline" href="{{ route('home') }}">Home</a>
            <a class="text-[#0195FE] font-semibold hover:underline" href="{{ route('products.index') }}">All products</a>
            <a class="text-[#0195FE] font-semibold hover:underline" href="{{ route('collections.index') }}">Collections</a>
            <a class="text-[#0195FE] font-semibold hover:underline" href="{{ route('shops.index') }}">Artists</a>
            <a class="text-[#0195FE] font-semibold hover:underline" href="{{ route('contact') }}">Contact</a>
        </div>
    </div>
</div>
@endsection
