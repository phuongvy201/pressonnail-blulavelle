@extends('layouts.app')

@section('title', 'Your Favorites')

@section('content')
@php
    $currentCurrency = currency();
    $currencySymbol = currency_symbol();
@endphp
<style>
    .toast {
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        min-width: 300px; max-width: 400px; padding: 14px 18px;
        border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        transform: translateX(100%); transition: transform 0.3s ease-in-out;
        display: flex; align-items: center; gap: 12px;
    }
    .toast.show { transform: translateX(0); }
    .toast.success { background-color: #10b981; color: white; }
    .toast.error { background-color: #ef4444; color: white; }
    .toast.warning { background-color: #f59e0b; color: white; }
    .toast-icon { flex-shrink: 0; width: 20px; height: 20px; }
</style>

<div class="bg-background-light min-h-screen font-display text-slate-900 py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-20">
        <!-- Page Title -->
        <div class="mb-12 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900 mb-2">Your Favorites</h1>
                <p class="text-slate-500 text-lg">Items you love, saved for later.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-lg font-semibold hover:brightness-110 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">storefront</span>
                    Browse products
                </a>
                @if($wishlistItems->count() > 0)
                <button type="button" id="clear-wishlist-btn" class="inline-flex items-center gap-2 px-5 py-2.5 border-2 border-primary/30 text-primary rounded-lg font-semibold hover:bg-primary/10 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">delete_sweep</span>
                    Clear all
                </button>
                @endif
            </div>
        </div>

        <!-- Clear Wishlist Modal -->
        <div id="clear-wishlist-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-md w-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-primary text-2xl">warning</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Clear All Wishlist</h3>
                        <p class="text-sm text-slate-500">This action cannot be undone.</p>
                    </div>
                </div>
                <p class="text-slate-600 mb-6">Are you sure you want to remove all products from your wishlist?</p>
                <div class="flex gap-3">
                    <button type="button" id="confirm-clear-wishlist" class="flex-1 bg-primary text-white py-2.5 px-4 rounded-xl font-semibold hover:brightness-110 transition-colors">
                        Clear All
                    </button>
                    <button type="button" id="cancel-clear-wishlist" class="flex-1 border-2 border-slate-200 text-slate-700 py-2.5 px-4 rounded-xl font-semibold hover:border-slate-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Remove Item Modal -->
        <div id="remove-item-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-md w-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-primary text-2xl">delete</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Remove from wishlist</h3>
                        <p class="text-sm text-slate-500">Product will be removed from your favorites.</p>
                    </div>
                </div>
                <p class="text-slate-600 mb-6">Are you sure you want to remove this product from your wishlist?</p>
                <div class="flex gap-3">
                    <button type="button" id="confirm-remove-item" class="flex-1 bg-primary text-white py-2.5 px-4 rounded-xl font-semibold hover:brightness-110 transition-colors">
                        Remove
                    </button>
                    <button type="button" id="cancel-remove-item" class="flex-1 border-2 border-slate-200 text-slate-700 py-2.5 px-4 rounded-xl font-semibold hover:border-slate-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        @if($wishlistItems->count() > 0)
            <!-- Product Grid: dùng component product-card (giống trang products) -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 gap-y-8 sm:gap-6 lg:gap-8">
                @foreach($wishlistItems as $wishlistItem)
                    @if($wishlistItem->product)
                        <x-product-card :product="$wishlistItem->product" :show-quick-view="true" />
                    @else
                        <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col items-center justify-center text-center min-h-[280px]">
                            <p class="text-slate-500 text-sm mb-3">Product no longer available.</p>
                            <button type="button" onclick="removeFromWishlist({{ $wishlistItem->product_id }})" class="text-primary font-semibold text-sm hover:underline">Remove from wishlist</button>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Pagination -->
            @if($wishlistItems->hasPages())
            <div class="mt-10 flex justify-center">
                {{ $wishlistItems->links() }}
            </div>
            @endif

            <!-- Explore More -->
            <div class="mt-16 flex justify-center">
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-8 py-3 border-2 border-primary text-primary font-bold rounded-full hover:bg-primary hover:text-white transition-all duration-300">
                    Explore More
                </a>
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-2xl border border-primary/10 shadow-sm p-12 text-center max-w-lg mx-auto">
                <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-5xl text-primary">favorite</span>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Your wishlist is empty</h2>
                <p class="text-slate-600 mb-8">Start adding products by clicking the heart icon on any product.</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-semibold hover:brightness-110 transition-colors">
                        <span class="material-symbols-outlined">storefront</span>
                        Browse Products
                    </a>
                    <a href="{{ route('collections.index') }}" class="inline-flex items-center gap-2 border-2 border-primary/30 text-primary px-6 py-3 rounded-xl font-semibold hover:bg-primary/10 transition-colors">
                        <span class="material-symbols-outlined">collections</span>
                        View Collections
                    </a>
                </div>
            </div>
        @endif

        <!-- Recently Viewed -->
        <section class="mt-12 pt-10">
            <x-recently-viewed :products="$recentlyViewedProducts ?? null" :limit="5" wrapperClass="" />
        </section>
    </div>
</div>

<script>
function showToast(message, type) {
    var existing = document.querySelectorAll('.toast');
    existing.forEach(function(t) { t.remove(); });
    var toast = document.createElement('div');
    toast.className = 'toast ' + (type || 'success');
    var icon = type === 'error' ? '<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' : '<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
    toast.innerHTML = icon + '<span>' + message + '</span>';
    document.body.appendChild(toast);
    setTimeout(function() { toast.classList.add('show'); }, 100);
    setTimeout(function() { toast.classList.remove('show'); setTimeout(function() { toast.remove(); }, 300); }, 4000);
}

var currentProductId = null;
var removeItemModal = document.getElementById('remove-item-modal');
var confirmRemoveBtn = document.getElementById('confirm-remove-item');
var cancelRemoveBtn = document.getElementById('cancel-remove-item');

function removeFromWishlist(productId) {
    currentProductId = productId;
    removeItemModal.classList.remove('hidden');
    removeItemModal.classList.add('flex');
}

if (cancelRemoveBtn) {
    cancelRemoveBtn.addEventListener('click', function() {
        removeItemModal.classList.add('hidden');
        removeItemModal.classList.remove('flex');
        currentProductId = null;
    });
}
removeItemModal.addEventListener('click', function(e) {
    if (e.target === removeItemModal) {
        removeItemModal.classList.add('hidden');
        removeItemModal.classList.remove('flex');
        currentProductId = null;
    }
});

if (confirmRemoveBtn) {
    confirmRemoveBtn.addEventListener('click', function() {
        if (!currentProductId) return;
        confirmRemoveBtn.disabled = true;
        confirmRemoveBtn.textContent = 'Removing...';
        fetch('{{ route("wishlist.remove") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ product_id: currentProductId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                removeItemModal.classList.add('hidden');
                removeItemModal.classList.remove('flex');
                location.reload();
            } else {
                confirmRemoveBtn.disabled = false;
                confirmRemoveBtn.textContent = 'Remove';
                showToast(data.message || 'Failed to remove.', 'error');
            }
        })
        .catch(function() {
            confirmRemoveBtn.disabled = false;
            confirmRemoveBtn.textContent = 'Remove';
            showToast('An error occurred.', 'error');
        });
    });
}

var clearWishlistBtn = document.getElementById('clear-wishlist-btn');
var clearWishlistModal = document.getElementById('clear-wishlist-modal');
var confirmClearBtn = document.getElementById('confirm-clear-wishlist');
var cancelClearBtn = document.getElementById('cancel-clear-wishlist');

if (clearWishlistBtn) {
    clearWishlistBtn.addEventListener('click', function() {
        clearWishlistModal.classList.remove('hidden');
        clearWishlistModal.classList.add('flex');
    });
}
if (cancelClearBtn) {
    cancelClearBtn.addEventListener('click', function() {
        clearWishlistModal.classList.add('hidden');
        clearWishlistModal.classList.remove('flex');
    });
}
clearWishlistModal.addEventListener('click', function(e) {
    if (e.target === clearWishlistModal) {
        clearWishlistModal.classList.add('hidden');
        clearWishlistModal.classList.remove('flex');
    }
});

if (confirmClearBtn) {
    confirmClearBtn.addEventListener('click', function() {
        confirmClearBtn.disabled = true;
        confirmClearBtn.textContent = 'Clearing...';
        fetch('{{ route("wishlist.clear") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                clearWishlistModal.classList.add('hidden');
                clearWishlistModal.classList.remove('flex');
                location.reload();
            } else {
                confirmClearBtn.disabled = false;
                confirmClearBtn.textContent = 'Clear All';
                showToast(data.message || 'Failed to clear.', 'error');
            }
        })
        .catch(function() {
            confirmClearBtn.disabled = false;
            confirmClearBtn.textContent = 'Clear All';
            showToast('An error occurred.', 'error');
        });
    });
}
</script>
@endsection
