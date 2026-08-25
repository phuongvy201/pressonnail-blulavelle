<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\CatalogController;
use App\Http\Controllers\Api\Mobile\CartController;
use App\Http\Controllers\Api\Mobile\CheckoutController;
use App\Http\Controllers\Api\Mobile\OrderController;
use App\Http\Controllers\Api\Mobile\PaymentController;
use App\Http\Controllers\Api\Mobile\ProfileController;
use App\Http\Controllers\Api\Mobile\VirtualNailController;
use App\Http\Controllers\Api\Mobile\WishlistController;
use App\Http\Middleware\MobileCartSessionBridge;
use App\Http\Middleware\OptionalSanctumAuth;
use App\Http\Middleware\PrepareMobileGuestSession;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Route;
use Illuminate\Session\Middleware\StartSession;

/*
|--------------------------------------------------------------------------
| Mobile API (Flutter) — Bearer token auth via Laravel Sanctum
|--------------------------------------------------------------------------
| Base URL: /api/mobile/v1
| Auth header: Authorization: Bearer {token}
| Guest cart: X-Guest-Cart-Token (returned in response header when absent)
*/

$mobileSession = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    OptionalSanctumAuth::class,
    PrepareMobileGuestSession::class,
    StartSession::class,
];

Route::prefix('mobile/v1')->name('api.mobile.v1.')->group(function () use ($mobileSession) {

    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'version' => config('mobile_api.version', '1.0.0'),
            'service' => 'PressOnNail Mobile API',
        ]);
    })->name('health');

    // Auth — no prior token required
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:register')
            ->name('register');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login');
    });

    // Public catalog + virtual nail browse (guest session for consistency)
    Route::middleware($mobileSession)->group(function () {
        Route::prefix('catalog')->name('catalog.')->group(function () {
            Route::get('/products', [CatalogController::class, 'products'])->name('products.index');
            Route::get('/products/slug/{slug}', [CatalogController::class, 'productBySlug'])->name('products.show-by-slug');
            Route::get('/products/{id}', [CatalogController::class, 'productById'])
                ->whereNumber('id')
                ->name('products.show');
            Route::get('/collections', [CatalogController::class, 'collections'])->name('collections.index');
            Route::get('/shops', [CatalogController::class, 'shops'])->name('shops.index');
            Route::get('/templates', [CatalogController::class, 'templates'])->name('templates.index');
            Route::get('/templates/{id}', [CatalogController::class, 'template'])
                ->whereNumber('id')
                ->name('templates.show');
            Route::get('/search/suggestions', [CatalogController::class, 'searchSuggestions'])->name('search.suggestions');
        });

        Route::prefix('virtual-nail')->name('virtual-nail.')->group(function () {
            Route::middleware('throttle:virtual-nail-browse')->group(function () {
                Route::get('/status', [VirtualNailController::class, 'status'])->name('status');
                Route::get('/products', [VirtualNailController::class, 'products'])->name('products');
                Route::get('/products/suggestions', [VirtualNailController::class, 'productSuggestions'])->name('products.suggestions');
                Route::get('/picker-meta', [VirtualNailController::class, 'pickerMeta'])->name('picker-meta');
                Route::get('/products/{productId}/options', [VirtualNailController::class, 'productOptions'])
                    ->whereNumber('productId')
                    ->name('products.options');
            });

            Route::post('/try', [VirtualNailController::class, 'tryOn'])
                ->middleware('throttle:virtual-nail')
                ->name('try');

            Route::middleware('throttle:virtual-nail-browse')->group(function () {
                Route::get('/history', [VirtualNailController::class, 'history'])->name('history');
                Route::get('/pending', [VirtualNailController::class, 'pending'])->name('pending');
                Route::get('/trials/{uuid}/status', [VirtualNailController::class, 'trialStatus'])->name('trials.status');
                Route::get('/trials/{uuid}/result', [VirtualNailController::class, 'trialResult'])->name('trials.result');
                Route::get('/trials/{uuid}/hand', [VirtualNailController::class, 'trialHand'])->name('trials.hand');
            });
        });

        // Track order — public (email + order number)
        Route::post('/orders/track', [OrderController::class, 'track'])->name('orders.track');
    });

    // Guest or authenticated — cart, wishlist, checkout snapshot
    Route::middleware(array_merge($mobileSession, [MobileCartSessionBridge::class]))->group(function () {
        Route::prefix('cart')->name('cart.')->group(function () {
            Route::get('/', [CartController::class, 'index'])->name('index');
            Route::get('/checkout', [CartController::class, 'checkoutSnapshot'])->name('checkout');
            Route::post('/', [CartController::class, 'store'])->name('store');
            Route::put('/{itemId}', [CartController::class, 'update'])
                ->whereNumber('itemId')
                ->name('update');
            Route::delete('/{itemId}', [CartController::class, 'destroy'])
                ->whereNumber('itemId')
                ->name('destroy');
            Route::delete('/', [CartController::class, 'clear'])->name('clear');
            Route::post('/discount-mode', [CartController::class, 'setDiscountMode'])->name('discount-mode');
            Route::post('/promo', [CartController::class, 'applyPromo'])->name('promo.apply');
            Route::delete('/promo', [CartController::class, 'removePromo'])->name('promo.remove');
            Route::post('/gift-card', [CartController::class, 'applyGiftCard'])->name('gift-card.apply');
            Route::delete('/gift-card', [CartController::class, 'removeGiftCard'])->name('gift-card.remove');
            Route::post('/sync', [CartController::class, 'sync'])->name('sync');
        });

        Route::prefix('wishlist')->name('wishlist.')->group(function () {
            Route::get('/', [WishlistController::class, 'index'])->name('index');
            Route::post('/', [WishlistController::class, 'add'])->name('store');
            Route::delete('/', [WishlistController::class, 'remove'])->name('destroy');
            Route::post('/toggle', [WishlistController::class, 'toggle'])->name('toggle');
            Route::get('/count', [WishlistController::class, 'count'])->name('count');
            Route::post('/check', [WishlistController::class, 'check'])->name('check');
            Route::delete('/clear', [WishlistController::class, 'clear'])->name('clear');
        });

        Route::prefix('checkout')->name('checkout.')->group(function () {
            Route::post('/process', [CheckoutController::class, 'process'])->name('process');
            Route::post('/shipping/calculate', [CheckoutController::class, 'calculateShipping'])->name('shipping.calculate');
            Route::post('/shipping/rates', [CheckoutController::class, 'shippingRates'])->name('shipping.rates');
        });

        Route::post('/payments/stripe/intent', [PaymentController::class, 'createStripeIntent'])
            ->name('payments.stripe.intent');
    });

    // Authenticated routes
    Route::middleware(array_merge($mobileSession, ['auth:sanctum', MobileCartSessionBridge::class]))->group(function () {
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::get('/user', [AuthController::class, 'user'])->name('user');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        });

        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('show');
            Route::put('/', [ProfileController::class, 'update'])->name('update');
            Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
        });

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/{orderNumber}', [OrderController::class, 'show'])->name('show');
            Route::post('/{orderNumber}/cancel', [OrderController::class, 'cancel'])->name('cancel');
            Route::post('/{orderNumber}/return-request', [OrderController::class, 'returnRequest'])->name('return-request');
        });
    });
});
