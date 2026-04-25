<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductTemplateController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Admin\ProductImportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\ReviewImportController;
use App\Http\Controllers\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Admin\AnalyticsSettingsController;
use App\Http\Controllers\Admin\BulkDiscountSettingsController;
use App\Http\Controllers\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Seller\SellerDashboardController;
use App\Http\Controllers\Seller\ShopController as SellerShopController;
use App\Http\Controllers\Seller\PostController as SellerPostController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Api\CartController as ApiCartController;
use App\Http\Controllers\Api\CustomFileController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\Admin\ShippingZoneController;
use App\Http\Controllers\Admin\ShippingRateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use App\Http\Controllers\Customer\ReturnRequestController;
use App\Http\Controllers\ReviewListingController;
use App\Http\Controllers\PublicMediaResizeController;
use App\Http\Controllers\TelegramWebhookController;

RateLimiter::for('register', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

use App\Http\Controllers\SupportController;
use App\Http\Controllers\BulkOrderController;
use App\Http\Controllers\PromoCodeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\SellerApplicationController;
use App\Http\Controllers\Admin\SellerApplicationAdminController;
use App\Http\Controllers\Admin\ReturnRequestController as AdminReturnRequestController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\SitemapController;

// Public routes
Route::get('/_media/resize', [PublicMediaResizeController::class, 'show'])->name('media.resize');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    $base = rtrim(config('app.url'), '/');
    $body = "User-agent: *\nDisallow:\n\nSitemap: {$base}/sitemap.xml\n";

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/recently-viewed', [ProductController::class, 'recentlyViewedFragment'])->name('products.recently-viewed');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{slug}/reviews', [ProductController::class, 'storeReview'])->middleware('auth')->name('products.reviews.store');
Route::post('/products/calculate-shipping', [ProductController::class, 'calculateShippingCost'])->name('products.calculate-shipping');
Route::get('/shops/{shop}', [App\Http\Controllers\ShopController::class, 'show'])->name('shops.show');
Route::get('/shops/{shop}/reviews', [App\Http\Controllers\ShopController::class, 'reviews'])->name('shops.reviews');
Route::get('/reviews', [ReviewListingController::class, 'index'])->name('reviews.public');

// GMC API - Public (no authentication required, no CSRF)
Route::post('/api/gmc/delete-product', [AdminProductController::class, 'deleteProductFromGMC'])->name('api.gmc.delete-product');

// Collections routes
Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
Route::get('/collections/{slug}', [CollectionController::class, 'show'])->name('collections.show');

// Search routes
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/api/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

// Category routes
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');

// Checkout routes
Route::get('/checkout', [App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/process', [App\Http\Controllers\CheckoutController::class, 'process'])->name('checkout.process');
Route::post('/checkout/calculate-shipping', [App\Http\Controllers\CheckoutController::class, 'calculateShipping'])->name('checkout.calculate-shipping');
Route::post('/checkout/get-shipping-rates', [App\Http\Controllers\CheckoutController::class, 'getShippingRates'])->name('checkout.get-shipping-rates');
Route::get('/checkout/success/{orderNumber}', [App\Http\Controllers\CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/receipt/{orderNumber}', [App\Http\Controllers\CheckoutController::class, 'downloadReceipt'])->name('checkout.receipt');
Route::get('/checkout/receipt/{orderNumber}/view', [App\Http\Controllers\CheckoutController::class, 'showReceipt'])->name('checkout.receipt.view');

// LianLian Pay callback routes
Route::get('/checkout/lianlian/success', [App\Http\Controllers\CheckoutController::class, 'lianlianSuccess'])->name('checkout.lianlian.success');
Route::get('/checkout/lianlian/cancel', [App\Http\Controllers\CheckoutController::class, 'lianlianCancel'])->name('checkout.lianlian.cancel');

// LianLian Pay routes
Route::prefix('payment/lianlian')->name('payment.lianlian.')->group(function () {
    Route::post('/create/{order}', [App\Http\Controllers\Payment\LianLianPayController::class, 'createPayment'])->name('create');
    Route::get('/return', [App\Http\Controllers\Payment\LianLianPayController::class, 'handleReturn'])->name('return');
    Route::get('/cancel', [App\Http\Controllers\Payment\LianLianPayController::class, 'handleCancel'])->name('cancel');
    Route::post('/webhook', [App\Http\Controllers\Payment\LianLianPayController::class, 'handleWebhook'])->name('webhook');
    Route::post('/webhook-v2', [App\Http\Controllers\Payment\LianLianPayController::class, 'handleWebhookV2'])->name('webhook-v2');
    Route::get('/query/{order}', [App\Http\Controllers\Payment\LianLianPayController::class, 'queryPayment'])->name('query');
    Route::post('/refund/{order}', [App\Http\Controllers\Payment\LianLianPayController::class, 'processRefund'])->name('refund');
    Route::get('/token', [App\Http\Controllers\Payment\LianLianPayController::class, 'getToken'])->name('token');
    Route::post('/3ds-result', [App\Http\Controllers\Payment\LianLianPayController::class, 'handle3DSResult'])->name('3ds-result');

    // New routes for separate payment page
    Route::get('/payment', [App\Http\Controllers\Payment\LianLianPayController::class, 'showPaymentPage'])->name('payment');
    Route::post('/process', [App\Http\Controllers\Payment\LianLianPayController::class, 'processPayment'])->name('process');

    // New route for processing payment with card token
    Route::post('/process-payment', [App\Http\Controllers\Payment\LianLianPayController::class, 'processPayment'])->name('process-payment');
});

// Stripe Payment routes
Route::prefix('payment/stripe')->name('payment.stripe.')->group(function () {
    Route::post('/create-payment-intent', [App\Http\Controllers\Payment\StripePaymentController::class, 'createPaymentIntent'])->name('create-intent');
    Route::post('/process', [App\Http\Controllers\Payment\StripePaymentController::class, 'processPayment'])->name('process');
    Route::post('/webhook', [App\Http\Controllers\Payment\StripePaymentController::class, 'webhook'])->name('webhook');
});

// Wishlist routes
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist/add', [WishlistController::class, 'add'])->name('wishlist.add');
Route::post('/wishlist/remove', [WishlistController::class, 'remove'])->name('wishlist.remove');
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
Route::get('/wishlist/count', [WishlistController::class, 'count'])->name('wishlist.count');
Route::post('/wishlist/check', [WishlistController::class, 'check'])->name('wishlist.check');
Route::post('/wishlist/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');
Route::post('/wishlist/transfer', [WishlistController::class, 'transferSessionToUser'])->name('wishlist.transfer');

// Customer Orders routes (for logged in customers)
Route::middleware(['auth'])->prefix('my')->name('customer.')->group(function () {
    Route::get('/orders', [App\Http\Controllers\Customer\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderNumber}', [App\Http\Controllers\Customer\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{orderNumber}/cancel', [App\Http\Controllers\Customer\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{orderNumber}/return-request', [ReturnRequestController::class, 'store'])->name('orders.return-request');
});

// Order tracking (public - no login required)
Route::get('/track-order', [App\Http\Controllers\Customer\OrderController::class, 'track'])->name('orders.track');
Route::get('/checkout/paypal/success', [App\Http\Controllers\CheckoutController::class, 'paypalSuccess'])->name('checkout.paypal.success');
Route::get('/checkout/paypal/cancel', [App\Http\Controllers\CheckoutController::class, 'paypalCancel'])->name('checkout.paypal.cancel');

// Blog routes (Posts)
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');

// Shipping & Delivery route (before pages to avoid conflicts)
Route::get('/shipping-delivery', [App\Http\Controllers\ShippingDeliveryController::class, 'index'])->name('shipping-delivery.index');

// Sizing Kit (how to measure nail size, size chart)
Route::get('/sizing-kit', [App\Http\Controllers\SizingKitController::class, 'index'])->name('sizing-kit.index');
Route::get('/sizing-kit/order-checkout', [App\Http\Controllers\SizingKitController::class, 'orderCheckout'])->name('sizing-kit.order-checkout');

// Pages routes (must be last to avoid conflicts)
Route::get('/page/{slug}', [PageController::class, 'show'])->name('page.show');

// Support Ticket routes
Route::get('/support/ticket', [SupportController::class, 'create'])->name('support.ticket.create');
Route::post('/support/ticket', [SupportController::class, 'store'])->name('support.ticket.store');

// Support Request routes
Route::get('/support/request', [SupportController::class, 'requestCreate'])->name('support.request.create');
Route::post('/support/request', [SupportController::class, 'requestStore'])->name('support.request.store');

// Bulk Order routes
Route::get('/bulk-order', [BulkOrderController::class, 'create'])->name('bulk.order.create');
Route::post('/bulk-order', [BulkOrderController::class, 'store'])->name('bulk.order.store');

// Promo Code routes
Route::get('/promo-code', [PromoCodeController::class, 'create'])->name('promo.code.create');
Route::post('/promo-code', [PromoCodeController::class, 'store'])->name('promo.code.store');

// Promo popup (sau Add to Cart / Wishlist): offer text + claim by email
Route::get('/promo-offer', [App\Http\Controllers\PromoPopupController::class, 'offer'])->name('promo.offer');
Route::post('/promo-claim', [App\Http\Controllers\PromoPopupController::class, 'claim'])->name('promo.claim');

// Live Chat (khách hàng): kiểm tra resume, bắt đầu, lấy tin nhắn, gửi tin
Route::get('/live-chat/resume-status', [App\Http\Controllers\LiveChatController::class, 'resumeStatus'])->name('live-chat.resume-status');
Route::post('/live-chat/start', [App\Http\Controllers\LiveChatController::class, 'startOrGet'])->name('live-chat.start');
Route::get('/live-chat/conversations/{conversationId}/messages', [App\Http\Controllers\LiveChatController::class, 'messages'])->name('live-chat.messages');
Route::post('/live-chat/send', [App\Http\Controllers\LiveChatController::class, 'send'])->name('live-chat.send');
Route::post('/api/telegram/webhook/{token}', TelegramWebhookController::class)->name('telegram.webhook');

// Seller Application routes
Route::get('/become-a-seller', [SellerApplicationController::class, 'create'])->name('seller.apply');
Route::post('/become-a-seller', [SellerApplicationController::class, 'store'])->name('seller.apply.submit');

// Customer Profile routes (requires authentication)
Route::middleware('auth')->prefix('customer')->name('customer.')->group(function () {
    Route::get('/profile', [App\Http\Controllers\Customer\ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [App\Http\Controllers\Customer\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\Customer\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [App\Http\Controllers\Customer\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [App\Http\Controllers\Customer\ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Test route
Route::get('/test-shop', function () {
    $shop = App\Models\Shop::first();
    if ($shop) {
        return response()->json([
            'shop_slug' => $shop->shop_slug,
            'shop_name' => $shop->shop_name,
            'exists' => true
        ]);
    }
    return response()->json(['exists' => false]);
});

// Test shop show route
Route::get('/test-shop-show/{slug}', function ($slug) {
    try {
        $shop = App\Models\Shop::where('shop_slug', $slug)->first();
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        return response()->json([
            'shop' => $shop->toArray(),
            'success' => true
        ]);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Test ShopController step by step
Route::get('/test-shop-debug/{slug}', function ($slug) {
    try {
        $shop = App\Models\Shop::where('shop_slug', $slug)->first();
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        // Test 1: Basic shop data
        $result = [
            'shop_id' => $shop->id,
            'shop_name' => $shop->shop_name,
            'shop_slug' => $shop->shop_slug
        ];

        // Test 2: User relationship
        try {
            $shop->load('user');
            $result['user_loaded'] = true;
            $result['user_name'] = $shop->user ? $shop->user->name : 'No user';
        } catch (Exception $e) {
            $result['user_error'] = $e->getMessage();
        }

        // Test 3: Products count
        try {
            $productsCount = $shop->products()->count();
            $result['products_count'] = $productsCount;
        } catch (Exception $e) {
            $result['products_error'] = $e->getMessage();
        }

        // Test 4: Followers count
        try {
            $followersCount = $shop->followers()->count();
            $result['followers_count'] = $followersCount;
        } catch (Exception $e) {
            $result['followers_error'] = $e->getMessage();
        }

        // Test 5: Favorites count
        try {
            $favoritesCount = $shop->favorites()->count();
            $result['favorites_count'] = $favoritesCount;
        } catch (Exception $e) {
            $result['favorites_error'] = $e->getMessage();
        }

        // Test 6: Load products with relationships
        try {
            $shop->load(['products' => function ($query) {
                $query->where('status', 'active')
                    ->with(['template', 'variants'])
                    ->orderBy('created_at', 'desc');
            }]);
            $result['products_with_relationships'] = true;
            $result['products_loaded_count'] = $shop->products->count();
        } catch (Exception $e) {
            $result['products_relationships_error'] = $e->getMessage();
        }

        return response()->json($result);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Test ShopController complete
Route::get('/test-shop-controller-complete/{slug}', function ($slug) {
    try {
        $shop = App\Models\Shop::where('shop_slug', $slug)->first();
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $controller = new App\Http\Controllers\ShopController();

        // Test the show method step by step
        $result = [];

        // Test 1: Load relationships
        try {
            $shop->load(['user', 'products' => function ($query) {
                $query->where('status', 'active')
                    ->with(['template', 'variants'])
                    ->orderBy('created_at', 'desc');
            }]);
            $result['step1_relationships'] = 'OK';
        } catch (Exception $e) {
            $result['step1_error'] = $e->getMessage();
        }

        // Test 2: Get stats
        try {
            $stats = [
                'total_products' => $shop->products()->where('status', 'active')->count(),
                'followers' => $shop->followers()->count(),
                'favorited' => $shop->favorites()->count(),
            ];
            $result['step2_stats'] = 'OK';
            $result['stats'] = $stats;
        } catch (Exception $e) {
            $result['step2_error'] = $e->getMessage();
        }

        // Test 3: Get categories
        try {
            $categories = App\Models\Category::whereHas('templates.products', function ($query) use ($shop) {
                $query->where('shop_id', $shop->id)->where('status', 'active');
            })->with(['templates.products' => function ($query) use ($shop) {
                $query->where('shop_id', $shop->id)->where('status', 'active')->limit(1);
            }])->get();
            $result['step3_categories'] = 'OK';
            $result['categories_count'] = $categories->count();
        } catch (Exception $e) {
            $result['step3_error'] = $e->getMessage();
        }

        // Test 4: Get hot products
        try {
            $hotProducts = $shop->products()
                ->where('status', 'active')
                ->with(['template', 'variants'])
                ->orderBy('created_at', 'desc')
                ->limit(12)
                ->get();
            $result['step4_hot_products'] = 'OK';
            $result['hot_products_count'] = $hotProducts->count();
        } catch (Exception $e) {
            $result['step4_error'] = $e->getMessage();
        }

        // Test 5: Get all products
        try {
            $allProducts = $shop->products()
                ->where('status', 'active')
                ->with(['template', 'variants'])
                ->orderBy('created_at', 'desc')
                ->paginate(24);
            $result['step5_all_products'] = 'OK';
            $result['all_products_count'] = $allProducts->count();
        } catch (Exception $e) {
            $result['step5_error'] = $e->getMessage();
        }

        return response()->json($result);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Test view rendering
Route::get('/test-shop-view/{slug}', function ($slug) {
    try {
        $shop = App\Models\Shop::where('shop_slug', $slug)->first();
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        // Load relationships
        $shop->load(['user', 'products' => function ($query) {
            $query->where('status', 'active')
                ->with(['template', 'variants'])
                ->orderBy('created_at', 'desc');
        }]);

        // Get shop statistics
        $stats = [
            'total_products' => $shop->products()->where('status', 'active')->count(),
            'followers' => $shop->followers()->count(),
            'favorited' => $shop->favorites()->count(),
        ];

        // Get product categories for this shop
        $categories = App\Models\Category::whereHas('templates.products', function ($query) use ($shop) {
            $query->where('shop_id', $shop->id)->where('status', 'active');
        })->with(['templates.products' => function ($query) use ($shop) {
            $query->where('shop_id', $shop->id)->where('status', 'active')->limit(1);
        }])->get();

        // Get hot products (most viewed/favorited)
        $hotProducts = $shop->products()
            ->where('status', 'active')
            ->with(['template', 'variants'])
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();

        // Get all products for the shop
        $allProducts = $shop->products()
            ->where('status', 'active')
            ->with(['template', 'variants'])
            ->orderBy('created_at', 'desc')
            ->paginate(24);

        // Check if current user follows this shop
        $isFollowing = false;
        if (Auth::check()) {
            $isFollowing = $shop->followers()->where('user_id', Auth::id())->exists();
        }

        // Test view rendering
        try {
            $view = view('shops.show', compact(
                'shop',
                'stats',
                'categories',
                'hotProducts',
                'allProducts',
                'isFollowing'
            ));

            $html = $view->render();
            return response()->json([
                'success' => true,
                'html_length' => strlen($html)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'view_error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::post('/shops/{shop}/follow', [App\Http\Controllers\ShopController::class, 'follow'])->name('shops.follow');
Route::post('/shops/{shop}/contact', [App\Http\Controllers\ShopController::class, 'contact'])->name('shops.contact');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

// Cart API routes (with web middleware for session support)
Route::prefix('api/cart')->middleware('web')->group(function () {
    Route::post('/add', [ApiCartController::class, 'add'])->name('api.cart.add');
    Route::get('/get', [ApiCartController::class, 'get'])->name('api.cart.get');
    Route::put('/update/{id}', [ApiCartController::class, 'update'])->name('api.cart.update');
    Route::delete('/remove/{id}', [ApiCartController::class, 'remove'])->name('api.cart.remove');
    Route::delete('/clear', [ApiCartController::class, 'clear'])->name('api.cart.clear');
    Route::post('/sync', [ApiCartController::class, 'sync'])->name('api.cart.sync');
    Route::post('/discount-mode', [ApiCartController::class, 'setDiscountMode'])->name('api.cart.discount-mode');
    Route::post('/apply-promo', [ApiCartController::class, 'applyPromo'])->name('api.cart.apply-promo');
    Route::post('/remove-promo', [ApiCartController::class, 'removePromo'])->name('api.cart.remove-promo');
});

// Analytics API routes
Route::prefix('api/analytics')->middleware('web')->name('api.analytics.')->group(function () {
    Route::get('/realtime', [AnalyticsController::class, 'realtime'])->name('realtime');
    Route::get('/realtime/pages', [AnalyticsController::class, 'realtimePages'])->name('realtime.pages');
    Route::get('/realtime/active-users', [AnalyticsController::class, 'realtimeActiveUsers'])->name('realtime.active-users');
    Route::get('/realtime/locations', [AnalyticsController::class, 'realtimeLocations'])->name('realtime.locations');
    Route::get('/realtime/sources', [AnalyticsController::class, 'realtimeSources'])->name('realtime.sources');
    Route::get('/realtime/devices', [AnalyticsController::class, 'realtimeDevices'])->name('realtime.devices');
    Route::get('/behavior', [AnalyticsController::class, 'behaviorReport'])->name('behavior');
});

// Product API routes for AI integration (with CORS support)
Route::prefix('api/products')->middleware(['web'])->group(function () {
    // Add OPTIONS route for CORS preflight
    Route::options('/create', function () {
        return response('', 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'X-API-Token, Content-Type, Accept, Authorization');
    });

    Route::post('/create', [App\Http\Controllers\Api\ProductController::class, 'create'])
        ->name('api.products.create');
    Route::get('/{id}', [App\Http\Controllers\Api\ProductController::class, 'show'])
        ->name('api.products.show');
    Route::get('/', [App\Http\Controllers\Api\ProductController::class, 'index'])
        ->name('api.products.index');
});

// Demo routes
Route::get('/demo/color-libraries', function () {
    return view('demo.color-libraries');
})->name('demo.color-libraries');

Route::get('/test-color', function () {
    return view('test-color');
})->name('test.color');

Route::get('/test-colour', function () {
    return view('test-colour');
})->name('test.colour');

Route::get('/test-complete-colors', function () {
    return view('test-complete-colors');
})->name('test.complete-colors');

Route::get('/test-all-colors-display', function () {
    return view('test-all-colors-display');
})->name('test.all-colors-display');

Route::get('/dashboard', function () {
    $user = Auth::user();

    if (!$user) {
        return redirect()->route('login');
    }

    /** @var \App\Models\User $user */

    // Redirect based on role
    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->hasRole('ad-partner')) {
        return redirect()->route('admin.orders.index');
    } elseif ($user->hasRole('seller')) {
        return redirect()->route('admin.seller.dashboard');
    }

    // Default dashboard for customers
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin routes (Admin only)
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('categories', AdminCategoryController::class);
        Route::get('categories/featured/manage', [AdminCategoryController::class, 'featured'])->name('categories.featured');
        Route::put('categories/featured/update', [AdminCategoryController::class, 'updateFeatured'])->name('categories.update-featured');

        // Pages (Admin only)
        Route::post('pages/upload-image', [AdminPageController::class, 'uploadImage'])->name('pages.upload-image');
        Route::resource('pages', AdminPageController::class);

        // Shipping Management (Admin only)
        Route::resource('shipping-zones', ShippingZoneController::class);
        Route::resource('shipping-rates', ShippingRateController::class);
        Route::post('shipping-rates/{shippingRate}/set-default', [ShippingRateController::class, 'setDefault'])->name('shipping-rates.set-default');
        Route::post('shipping-rates/{shippingRate}/unset-default', [ShippingRateController::class, 'unsetDefault'])->name('shipping-rates.unset-default');

        Route::resource('promo-codes', App\Http\Controllers\Admin\PromoCodeController::class);

        // Analytics settings
        Route::get('settings/analytics', [AnalyticsSettingsController::class, 'edit'])->name('settings.analytics.edit');
        Route::put('settings/analytics', [AnalyticsSettingsController::class, 'update'])->name('settings.analytics.update');

        // Pricing settings: quantity/bulk discounts
        Route::get('settings/bulk-discounts', [BulkDiscountSettingsController::class, 'edit'])->name('settings.bulk-discounts.edit');
        Route::put('settings/bulk-discounts', [BulkDiscountSettingsController::class, 'update'])->name('settings.bulk-discounts.update');

        // Return Requests (Refund/Exchange)
        Route::get('returns', [AdminReturnRequestController::class, 'index'])->name('returns.index');
        Route::get('returns/{returnRequest}', [AdminReturnRequestController::class, 'show'])->name('returns.show');
        Route::put('returns/{returnRequest}', [AdminReturnRequestController::class, 'update'])->name('returns.update');

        // Analytics Dashboard
        Route::get('analytics', [App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');

        // API Token (Admin only)
        Route::get('/api-token', [App\Http\Controllers\ApiDocController::class, 'tokenDashboard'])->name('api-token');

        // Seller Applications
        Route::get('seller-applications', [SellerApplicationAdminController::class, 'index'])->name('seller-applications.index');
        Route::get('seller-applications/{sellerApplication}', [SellerApplicationAdminController::class, 'show'])->name('seller-applications.show');
        Route::post('seller-applications/{sellerApplication}/approve', [SellerApplicationAdminController::class, 'approve'])->name('seller-applications.approve');
        Route::post('seller-applications/{sellerApplication}/reject', [SellerApplicationAdminController::class, 'reject'])->name('seller-applications.reject');

        // Content blocks API (inline edit trang chủ / trang tĩnh)
        Route::get('api/content-blocks', [App\Http\Controllers\Admin\ContentBlockController::class, 'index'])->name('api.content-blocks.index');
        Route::put('api/content-blocks', [App\Http\Controllers\Admin\ContentBlockController::class, 'update'])->name('api.content-blocks.update');
        Route::post('api/content-blocks/upload-image', [App\Http\Controllers\Admin\ContentBlockController::class, 'uploadImage'])->name('api.content-blocks.upload-image');
        Route::post('api/content-blocks/upload-video', [App\Http\Controllers\Admin\ContentBlockController::class, 'uploadVideo'])->name('api.content-blocks.upload-video');

        // Preview & edit trang chủ (view home với chế độ chỉnh sửa)
        Route::get('site/home-preview', [HomeController::class, 'preview'])->name('site.home-preview');
    });

    // Live Chat (Admin/Seller) - xem danh sách hội thoại và trả lời
    Route::prefix('admin')->name('admin.')->middleware('role:admin|seller')->group(function () {
        Route::get('live-chat', [App\Http\Controllers\Admin\LiveChatController::class, 'index'])->name('live-chat.index');
        Route::get('live-chat/api/conversations', [App\Http\Controllers\Admin\LiveChatController::class, 'apiConversations'])->name('live-chat.api.conversations');
        Route::post('live-chat/{conversation}/mark-read', [App\Http\Controllers\Admin\LiveChatController::class, 'markRead'])->name('live-chat.mark-read');
        Route::get('live-chat/{conversation}', [App\Http\Controllers\Admin\LiveChatController::class, 'show'])->name('live-chat.show');
        Route::get('live-chat/{conversation}/messages', [App\Http\Controllers\Admin\LiveChatController::class, 'messages'])->name('live-chat.messages');
        Route::post('live-chat/{conversation}/reply', [App\Http\Controllers\Admin\LiveChatController::class, 'reply'])->name('live-chat.reply');
    });

    // Orders management (Admin + Ad-Partner) - using controller middleware
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('orders/export', [App\Http\Controllers\Admin\OrderController::class, 'export'])->name('orders.export');
        Route::resource('orders', App\Http\Controllers\Admin\OrderController::class)->only(['index', 'show']);
    });

    // Seller routes (Seller + Admin)
    Route::prefix('admin')->name('admin.')->middleware('role:seller|admin')->group(function () {
        Route::get('/seller/dashboard', [SellerDashboardController::class, 'index'])->name('seller.dashboard');

        // Product Templates with clone route
        Route::post('product-templates/{product_template}/clone', [ProductTemplateController::class, 'clone'])->name('product-templates.clone');
        Route::resource('product-templates', ProductTemplateController::class);

        // Products Import
        Route::get('products/import', [ProductImportController::class, 'showImportForm'])->name('products.import');
        Route::post('products/import', [ProductImportController::class, 'import'])->name('products.import.process');
        Route::get('products/import/template', [ProductImportController::class, 'downloadTemplate'])->name('products.import.template');
        Route::get('products/import/progress', [ProductImportController::class, 'getProgress'])->name('products.import.progress');

        // Reviews (Admin + Seller): list + import + delete + pin
        Route::get('reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::post('reviews/bulk-destroy', [AdminReviewController::class, 'bulkDestroy'])->name('reviews.bulk-destroy');
        Route::post('reviews/{review}/toggle-pin', [AdminReviewController::class, 'togglePin'])->name('reviews.toggle-pin');
        Route::post('reviews/bulk-pin', [AdminReviewController::class, 'bulkPin'])->name('reviews.bulk-pin');
        Route::post('reviews/bulk-unpin', [AdminReviewController::class, 'bulkUnpin'])->name('reviews.bulk-unpin');
        Route::get('reviews/import', [ReviewImportController::class, 'showImportForm'])->name('reviews.import');
        Route::post('reviews/import', [ReviewImportController::class, 'import'])->name('reviews.import.process');
        Route::get('reviews/import/template', [ReviewImportController::class, 'downloadTemplate'])->name('reviews.import.template');

        // Products - Custom routes must be defined BEFORE resource route to avoid conflicts
        Route::get('products/delete-from-gmc', [AdminProductController::class, 'showDeleteFromGMCForm'])->name('products.show-delete-from-gmc');
        Route::post('products/delete-from-gmc', [AdminProductController::class, 'deleteFromGMC'])->name('products.delete-from-gmc');
        Route::post('products/delete-product-from-gmc', [AdminProductController::class, 'deleteProductFromGMC'])->name('products.delete-product-from-gmc');
        Route::get('products/preview-gmc-data', [AdminProductController::class, 'previewGMCData'])->name('products.preview-gmc-data');
        Route::post('products/feed-to-gmc', [AdminProductController::class, 'feedToGMC'])->name('products.feed-to-gmc');
        Route::post('products/bulk-delete', [AdminProductController::class, 'bulkDelete'])->name('products.bulk-delete');
        Route::post('products/bulk-add-to-collection', [AdminProductController::class, 'bulkAddToCollection'])->name('products.bulk-add-to-collection');
        Route::post('products/{product}/duplicate', [AdminProductController::class, 'duplicate'])->name('products.duplicate');
        Route::post('products/export/meta', [AdminProductController::class, 'exportToMeta'])->name('products.export.meta');

        // Products Resource Route (must be last to avoid conflicts)
        Route::resource('products', AdminProductController::class);

        // Collections
        Route::resource('collections', AdminCollectionController::class);
        Route::post('collections/{collection}/toggle-featured', [AdminCollectionController::class, 'toggleFeatured'])->name('collections.toggle-featured');
        Route::post('collections/update-sort-order', [AdminCollectionController::class, 'updateSortOrder'])->name('collections.update-sort-order');

        // Admin approval routes
        Route::post('collections/{collection}/approve', [AdminCollectionController::class, 'approve'])->name('collections.approve');
        Route::post('collections/{collection}/reject', [AdminCollectionController::class, 'reject'])->name('collections.reject');
        Route::post('collections/bulk-approve', [AdminCollectionController::class, 'bulkApprove'])->name('collections.bulk-approve');

        // Posts (Seller can manage their own posts)
        Route::post('posts/upload-image', [SellerPostController::class, 'uploadImage'])->name('posts.upload-image');
        Route::resource('posts', SellerPostController::class);
        Route::post('posts/{post}/approve', [SellerPostController::class, 'approve'])->name('posts.approve');
        Route::post('posts/{post}/reject', [SellerPostController::class, 'reject'])->name('posts.reject');

        // Post Categories (Admin only)
        Route::resource('post-categories', App\Http\Controllers\Admin\PostCategoryController::class);

        // Post Tags (Admin only)
        Route::resource('post-tags', App\Http\Controllers\Admin\PostTagController::class);
    });

    // Demo routes với role middleware
    Route::get('/admin-only', function () {
        return response()->json(['message' => 'Only admin can see this']);
    })->middleware('role:admin');

    Route::get('/seller-only', function () {
        return response()->json(['message' => 'Only seller can see this']);
    })->middleware('role:seller');

    Route::get('/customer-only', function () {
        return response()->json(['message' => 'Only customer can see this']);
    })->middleware('role:customer');

    Route::get('/user-management', function () {
        return response()->json(['message' => 'User management area']);
    })->middleware('permission:view-users');

    // Seller Shop Routes
    Route::prefix('seller')->name('seller.')->middleware('role:seller|admin')->group(function () {
        Route::get('/shop/create', [SellerShopController::class, 'create'])->name('shop.create');
        Route::post('/shop', [SellerShopController::class, 'store'])->name('shop.store');

        Route::middleware(['has.shop'])->group(function () {
            Route::get('/shop/dashboard', [SellerShopController::class, 'dashboard'])->name('shop.dashboard');
            Route::get('/shop/edit', [SellerShopController::class, 'edit'])->name('shop.edit');
            Route::put('/shop', [SellerShopController::class, 'update'])->name('shop.update');

            // Seller Orders
            Route::resource('orders', App\Http\Controllers\Seller\OrderController::class);
            Route::get('orders/export', [App\Http\Controllers\Seller\OrderController::class, 'export'])->name('orders.export');
        });
    });

    // Admin Shop Management
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/shops', [AdminShopController::class, 'index'])->name('shops.index');
        Route::post('/shops/{shop}/verify', [AdminShopController::class, 'verify'])->name('shops.verify');
        Route::post('/shops/{shop}/suspend', [AdminShopController::class, 'suspend'])->name('shops.suspend');
        Route::post('/shops/{shop}/activate', [AdminShopController::class, 'activate'])->name('shops.activate');

        // Orders management - Admin only actions (update, destroy)
        Route::put('orders/{order}', [App\Http\Controllers\Admin\OrderController::class, 'update'])->name('orders.update');
        Route::delete('orders/{order}', [App\Http\Controllers\Admin\OrderController::class, 'destroy'])->name('orders.destroy');
    });
});

Route::get('/test-zoom-effect', function () {
    return view('test-zoom-effect');
});

Route::get('/test-cart', function () {
    return view('test-cart');
})->name('test.cart');

Route::get('/test-s3-upload', function () {
    return view('test-s3-upload');
})->name('test.s3-upload');

// Custom File Upload API Routes
Route::prefix('api/custom-files')->name('api.custom-files.')->group(function () {
    Route::post('/upload', [CustomFileController::class, 'upload'])->name('upload');
    Route::get('/files', [CustomFileController::class, 'getFiles'])->name('files');
    Route::delete('/{fileId}', [CustomFileController::class, 'delete'])->name('delete');
    Route::post('/{fileId}/extend', [CustomFileController::class, 'extendExpiration'])->name('extend');
    Route::get('/upload-info', [CustomFileController::class, 'getUploadInfo'])->name('info');
    Route::post('/cleanup', [CustomFileController::class, 'cleanupExpired'])->name('cleanup');
});

// Direct S3 Upload API Routes
Route::prefix('api/upload')->name('api.upload.')->group(function () {
    Route::post('/presigned-urls', [UploadController::class, 'generatePresignedUrls'])->name('presigned-urls');
    Route::post('/confirm', [UploadController::class, 'confirmUpload'])->name('confirm');
    // Multipart upload endpoints
    Route::post('/multipart/init', [UploadController::class, 'initMultipart'])->name('multipart.init');
    Route::post('/multipart/part-urls', [UploadController::class, 'getMultipartPartUrls'])->name('multipart.part-urls');
    Route::post('/multipart/complete', [UploadController::class, 'completeMultipart'])->name('multipart.complete');
});

// Test route for variant removal logic
Route::get('/test-remove-variant', [ProductTemplateController::class, 'testRemoveVariant'])
    ->name('test.remove.variant');

// Newsletter routes
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{email}', [NewsletterController::class, 'showUnsubscribe'])->name('newsletter.unsubscribe');
Route::post('/newsletter/unsubscribe/{email}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe.post');
Route::get('/newsletter/status', [NewsletterController::class, 'status'])->name('newsletter.status');

// Test page for variant removal
Route::get('/test-variant-removal-page', function () {
    return view('test-variant-removal');
})->name('test.variant.removal.page');
// Load auth routes first to avoid conflicts with catch-all route
require __DIR__ . '/auth.php';

Route::get('/test-keywords', [TestController::class, 'test']);

// Page routes - Must be at the end to avoid conflicts
Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show')->where('slug', '^(?!admin|api|dashboard|cart|checkout|wishlist|search|collections|products|category|shops|blog|login|register|password|email|verification|logout|seller|newsletter|verify-email).*$');
