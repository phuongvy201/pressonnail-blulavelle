@extends('layouts.app')

@section('title', 'Promo Codes')
@section('robots', 'noindex, follow')

@section('content')
<div class="bg-gray-50 py-8">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Banner -->
    <div class="relative overflow-hidden rounded-2xl shadow bg-gradient-to-r from-orange-500 to-yellow-500">
      <div class="p-8 sm:p-12 text-white">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
          <div>
            <p class="uppercase tracking-widest text-white/90 text-xs">Limited Time</p>
            <h1 class="text-4xl sm:text-5xl font-extrabold leading-tight">Save up to <span class="drop-shadow-md">70% OFF</span></h1>
            <p class="mt-2 text-white/90">Special events and seasonal coupons updated regularly.</p>
          </div>
          <a href="#coupons" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white/95 text-orange-600 font-semibold hover:bg-white shadow">
            Get Offer Now
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </a>
        </div>
      </div>
      <!-- Decorative edges -->
      <div class="absolute inset-y-0 right-0 w-24 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.25)_2px,transparent_2px)] [background-size:12px_12px] opacity-40"></div>
    </div>

    <!-- Notice -->
    <div class="mt-8 p-4 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-800">
      Promo codes are temporarily unavailable. Please check back later.
    </div>

    <!-- Coupons -->
    <div id="coupons" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
      @foreach(($coupons ?? []) as $c)
        <div class="bg-white rounded-2xl shadow border border-gray-100 p-6 flex items-center gap-6">
          <div class="flex-shrink-0 text-center">
            <div class="text-3xl font-extrabold text-indigo-700">{{ $c['percent'] }}%</div>
            <div class="text-sm text-gray-500 uppercase tracking-wide">OFF</div>
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-900">{{ $c['title'] }}</h3>
            <p class="text-sm text-gray-500">on orders over ${{ number_format($c['min_order'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Expires {{ $c['expires_at'] }}</p>
            <span class="inline-flex items-center mt-2 px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-700">Sold out</span>
          </div>
          <div class="flex flex-col items-end gap-2">
            <div class="relative">
              <input readonly disabled value="{{ $c['code'] }}" class="w-36 text-center font-mono text-sm px-3 py-2 rounded border border-dashed border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed"/>
            </div>
            <button type="button" disabled class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-gray-300 text-gray-600 text-sm font-semibold cursor-not-allowed">
              Unavailable
            </button>
          </div>
        </div>
      @endforeach
    </div>

    <!-- Optional: Personal promo request form -->
    <div class="mt-12">
      <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-2">Need a custom promo?</h2>
        <p class="text-sm text-gray-500 mb-4">Tell us what you are looking for. We'll email you if a qualifying promo is available.</p>
        <form action="{{ route('promo.code.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          @csrf
          <input type="text" name="name" placeholder="Your Name" class="border border-gray-300 rounded-md px-3 py-2" required>
          <input type="email" name="email" placeholder="Your Email" class="border border-gray-300 rounded-md px-3 py-2" required>
          <input type="text" name="interest" placeholder="Product/category of interest (optional)" class="border border-gray-300 rounded-md px-3 py-2">
          <div class="md:col-span-3">
            <textarea name="message" rows="3" placeholder="Any notes (optional)" class="w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
          </div>
          <div class="md:col-span-3 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Request Promo</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  document.addEventListener('click', function(e){
    if(e.target.closest('.copy-btn')){
      const btn = e.target.closest('.copy-btn');
      const code = btn.getAttribute('data-code');
      navigator.clipboard.writeText(code).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = 'Copied!';
        setTimeout(()=> btn.innerHTML = original, 1200);
      });
    }
  });
</script>
@endpush
@endsection
