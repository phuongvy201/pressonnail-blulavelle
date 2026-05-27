@extends('layouts.creator')

@section('title', 'Product referral links')

@section('content')
    <div class="mx-auto max-w-5xl px-5 py-12 md:px-16">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('creator.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>
                <h1 class="creator-font-headline mt-2 text-3xl font-bold text-[#0b1c30]">Product referral links</h1>
                <p class="mt-2 max-w-2xl text-sm text-[#404753]">
                    Chỉ sản phẩm shop đã bật <strong>Affiliate</strong> và <strong>đủ điều kiện hiển thị</strong> (có thể mở trang SP trên shop).
                    Hoa hồng khi khách mua qua link có mã
                    <code class="rounded bg-[#e5eeff] px-1">{{ $affiliate->code }}</code>.
                </p>
            </div>
            <div class="shrink-0 rounded-xl border border-[#bfc7d5] bg-white px-4 py-3 text-sm shadow-sm">
                <span class="creator-font-label text-xs font-semibold uppercase tracking-wide text-[#707884]">Commission</span>
                <p class="mt-1 font-semibold text-[#0b1c30]">{{ ucfirst($affiliate->tier) }} · {{ $affiliate->effectiveCommissionPercent() }}%</p>
            </div>
        </div>

        @include('creator.partials.setup-note', ['setup' => $setup ?? null])

        <div class="mt-8 rounded-xl border border-[#bfc7d5] bg-white p-5 shadow-sm">
            <h2 class="creator-font-label text-sm font-semibold uppercase tracking-wide text-[#404753]">Storefront link</h2>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                <p id="shop-home-link" class="min-w-0 flex-1 break-all font-mono text-sm text-primary">{{ $shopHomeUrl }}</p>
                <button type="button"
                        onclick="copyReferralLink('shop-home-link', this)"
                        class="creator-btn-primary creator-font-label shrink-0 rounded-lg px-4 py-2 text-sm font-semibold">
                    Copy
                </button>
            </div>
        </div>

        <form method="get" action="{{ route('creator.product-links.index') }}" class="mt-6 rounded-xl border border-[#bfc7d5] bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2 lg:col-span-1">
                    <label for="search" class="creator-font-label mb-1 block text-xs font-semibold uppercase tracking-wide text-[#707884]">Search</label>
                    <input type="search"
                           id="search"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Name or SKU…"
                           class="w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label for="shop_id" class="creator-font-label mb-1 block text-xs font-semibold uppercase tracking-wide text-[#707884]">Shop</label>
                    <select id="shop_id" name="shop_id" class="w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="">All shops</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ (string) request('shop_id') === (string) $shop->id ? 'selected' : '' }}>
                                {{ $shop->shop_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="collection_id" class="creator-font-label mb-1 block text-xs font-semibold uppercase tracking-wide text-[#707884]">Collection</label>
                    <select id="collection_id" name="collection_id" class="w-full rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="">All collections</option>
                        @foreach($collections as $collection)
                            <option value="{{ $collection->id }}" {{ (string) request('collection_id') === (string) $collection->id ? 'selected' : '' }}>
                                {{ $collection->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-1">
                    <button type="submit" class="creator-btn-primary creator-font-label flex-1 rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                    @if(request()->anyFilled(['search', 'shop_id', 'collection_id']))
                        <a href="{{ route('creator.product-links.index') }}" class="shrink-0 rounded-lg border border-[#bfc7d5] px-3 py-2 text-sm font-semibold text-[#404753] hover:bg-[#f8f9ff]">Clear</a>
                    @endif
                </div>
            </div>
        </form>

        <p class="mt-4 text-sm text-[#707884]">
            {{ $products->total() }} affiliate product{{ $products->total() === 1 ? '' : 's' }}
            @if(request()->filled('search'))
                matching “{{ request('search') }}”
            @endif
            @if(request()->filled('shop_id') && $shops->firstWhere('id', request('shop_id')))
                · shop: {{ $shops->firstWhere('id', request('shop_id'))->shop_name }}
            @endif
            @if(request()->filled('collection_id') && $collections->firstWhere('id', request('collection_id')))
                · collection: {{ $collections->firstWhere('id', request('collection_id'))->name }}
            @endif
        </p>

        @if($products->isEmpty())
            <div class="mt-6 rounded-xl border border-dashed border-[#bfc7d5] bg-white/80 px-6 py-12 text-center">
                <p class="text-[#404753]">No affiliate products match your filters.</p>
                <p class="mt-2 text-sm text-[#707884]">Try clearing filters or contact the store to enable more products for affiliates.</p>
            </div>
        @else
            <ul class="mt-4 divide-y divide-[#e5eeff] rounded-xl border border-[#bfc7d5] bg-white shadow-sm">
                @foreach($products as $product)
                    @php
                        $link = $productLinks[$product->id] ?? null;
                        $media = $product->getEffectiveMedia();
                        $thumb = null;
                        if (!empty($media)) {
                            $first = $media[0];
                            $thumb = is_string($first) ? $first : ($first['url'] ?? $first['path'] ?? null);
                        }
                        $displayPrice = (float) ($product->price ?? 0);
                    @endphp
                    <li class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:gap-5">
                        <div class="flex min-w-0 flex-1 items-center gap-4">
                            @if($thumb && !str_contains(strtolower($thumb), '.mp4'))
                                <img src="{{ $thumb }}" alt="" class="h-14 w-14 shrink-0 rounded-lg border border-[#e5eeff] object-cover">
                            @else
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-[#e5eeff] text-primary">
                                    <span class="material-symbols-outlined text-2xl">inventory_2</span>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-[#0b1c30]" title="{{ $product->name }}">{{ $product->name }}</p>
                                @if($product->sku)
                                    <p class="text-xs text-[#707884]">SKU: {{ $product->sku }}</p>
                                @endif
                                @if($displayPrice > 0)
                                    <p class="mt-0.5 text-sm font-semibold text-primary">${{ number_format($displayPrice, 2) }}</p>
                                @endif
                            </div>
                        </div>
                        @if($link)
                            <div class="flex w-full min-w-0 flex-col gap-2 sm:max-w-md sm:flex-row sm:items-center">
                                <p id="product-link-{{ $product->id }}" class="min-w-0 flex-1 break-all font-mono text-xs text-[#404753] sm:text-sm">{{ $link }}</p>
                                <button type="button"
                                        onclick="copyReferralLink('product-link-{{ $product->id }}', this)"
                                        class="creator-font-label shrink-0 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5">
                                    Copy link
                                </button>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <script>
        function copyReferralLink(elementId, btn) {
            const el = document.getElementById(elementId);
            if (!el) return;
            const text = el.textContent.trim();
            const done = () => {
                const prev = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(() => { btn.textContent = prev; }, 2000);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
            } else {
                fallbackCopy(text, done);
            }
        }
        function fallbackCopy(text, done) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                done();
            } catch (e) {
                alert('Copy failed. Please select the link manually.');
            }
            document.body.removeChild(ta);
        }
    </script>
@endsection
