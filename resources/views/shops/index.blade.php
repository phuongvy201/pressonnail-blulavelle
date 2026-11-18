@extends('layouts.app')

@section('title', 'Independent Nail Artists')
@section('meta_description', \App\Support\Seo::catalogDescription('shops'))

@section('content')
<div class="bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-4xl font-bold text-slate-900 mb-3">Nail Artists</h1>
        <p class="text-slate-600 mb-10 max-w-2xl">Browse the independent artists who design handmade press-on nails for BluLavelle.</p>

        @if($shops->isEmpty())
            <p class="text-slate-500">Artist shops will appear here as they open.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($shops as $shop)
                    <a href="{{ route('shops.show', $shop->shop_slug) }}" class="block bg-white border border-slate-200 rounded-2xl p-6 hover:border-[#0195FE] transition-colors">
                        <h2 class="text-xl font-bold text-slate-900">{{ $shop->shop_name }}</h2>
                        <p class="mt-2 text-sm text-slate-600 line-clamp-3">{{ trim(strip_tags((string) $shop->shop_description)) !== '' ? trim(strip_tags((string) $shop->shop_description)) : $shop->shop_name.' designs handmade press-on nails on BluLavelle.' }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
