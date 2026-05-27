<?php



use App\Http\Controllers\Creator\AffiliateOnboardingController;

use App\Http\Controllers\Creator\CreatorAffiliateSetupController;

use App\Http\Controllers\Creator\CreatorAnalyticsController;
use App\Http\Controllers\Creator\CreatorDashboardController;

use App\Http\Controllers\Creator\CreatorHomeController;

use App\Http\Controllers\Creator\CreatorProductLinksController;
use App\Http\Controllers\Creator\CreatorPromoCodesController;
use App\Http\Controllers\Creator\CreatorSampleRequestsController;

use App\Http\Controllers\Creator\CreatorOnboardingAccountController;

use App\Http\Controllers\Creator\CreatorPortalAuthController;

use App\Http\Controllers\Creator\CreatorPolicyPageController;

use Illuminate\Support\Facades\Route;



Route::get('/', CreatorHomeController::class)->name('creator.home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [CreatorPortalAuthController::class, 'create'])->name('creator.login');
    Route::post('/login', [CreatorPortalAuthController::class, 'store'])->middleware('throttle:login');
});

Route::post('/logout', [CreatorPortalAuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('creator.logout');

// Multi-step creator / affiliate onboarding

Route::get('/affiliate/apply', [AffiliateOnboardingController::class, 'step1'])->name('creator.affiliate.apply');

Route::post('/affiliate/apply', [AffiliateOnboardingController::class, 'storeStep1'])

    ->middleware('throttle:creator-affiliate-register')

    ->name('creator.affiliate.apply.store');



Route::get('/affiliate/apply/account', [AffiliateOnboardingController::class, 'step2'])->name('creator.affiliate.apply.account');

Route::get('/affiliate/apply/verify-email', [AffiliateOnboardingController::class, 'step3VerifyEmail'])
    ->middleware('auth')
    ->name('creator.affiliate.apply.verify-email');

Route::post('/affiliate/apply/verify-email/resend', [CreatorOnboardingAccountController::class, 'resendVerificationEmail'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('creator.affiliate.apply.verify-email.resend');

Route::post('/affiliate/apply/submit', [AffiliateOnboardingController::class, 'submit'])

    ->middleware(['auth', 'throttle:creator-affiliate-register'])

    ->name('creator.affiliate.apply.submit');



Route::middleware('guest')->group(function () {

    Route::get('/affiliate/apply/login', [CreatorOnboardingAccountController::class, 'loginForm'])

        ->name('creator.affiliate.apply.login');

    Route::post('/affiliate/apply/login', [CreatorOnboardingAccountController::class, 'login'])

        ->middleware('throttle:login');

    Route::get('/affiliate/apply/register-account', [CreatorOnboardingAccountController::class, 'registerForm'])

        ->name('creator.affiliate.apply.register-account');

    Route::post('/affiliate/apply/register-account', [CreatorOnboardingAccountController::class, 'register'])

        ->middleware('throttle:register');

});



Route::post('/affiliate/apply/logout', [CreatorOnboardingAccountController::class, 'logout'])

    ->middleware('auth')

    ->name('creator.affiliate.apply.logout');



Route::get('/affiliate/status', [AffiliateOnboardingController::class, 'status'])

    ->middleware('auth')

    ->name('creator.affiliate.status');



Route::get('/affiliate/thanks', [AffiliateOnboardingController::class, 'thanks'])

    ->name('creator.affiliate.register.thanks');



Route::middleware(['auth', 'affiliate.creator'])->group(function () {

    Route::get('/dashboard', CreatorDashboardController::class)->name('creator.dashboard');

    Route::get('/dashboard/analytics/overview', [CreatorAnalyticsController::class, 'overview'])->name('creator.analytics.overview');
    Route::get('/dashboard/analytics/links', [CreatorAnalyticsController::class, 'links'])->name('creator.analytics.links');
    Route::get('/dashboard/analytics/traffic', [CreatorAnalyticsController::class, 'traffic'])->name('creator.analytics.traffic');
    Route::get('/dashboard/analytics/products', [CreatorAnalyticsController::class, 'products'])->name('creator.analytics.products');
    Route::get('/dashboard/analytics/commissions', [CreatorAnalyticsController::class, 'commissions'])->name('creator.analytics.commissions');

    Route::get('/dashboard/product-links', [CreatorProductLinksController::class, 'index'])->name('creator.product-links.index');

    Route::get('/dashboard/promo-codes', [CreatorPromoCodesController::class, 'index'])->name('creator.promo-codes.index');

    Route::get('/dashboard/sample-requests', [CreatorSampleRequestsController::class, 'index'])->name('creator.sample-requests.index');
    Route::get('/dashboard/sample-requests/create', [CreatorSampleRequestsController::class, 'create'])->name('creator.sample-requests.create');
    Route::post('/dashboard/sample-requests', [CreatorSampleRequestsController::class, 'store'])->name('creator.sample-requests.store');
    Route::get('/dashboard/sample-requests/{sampleRequest}', [CreatorSampleRequestsController::class, 'show'])->name('creator.sample-requests.show');

    Route::get('/dashboard/setup', [CreatorAffiliateSetupController::class, 'index'])->name('creator.setup.index');
    Route::put('/dashboard/setup/profile', [CreatorAffiliateSetupController::class, 'updateProfile'])->name('creator.setup.profile');
    Route::put('/dashboard/setup/social', [CreatorAffiliateSetupController::class, 'updateSocial'])->name('creator.setup.social');
    Route::put('/dashboard/setup/payout', [CreatorAffiliateSetupController::class, 'updatePayout'])->name('creator.setup.payout');

});



// Legacy URL → step 1

Route::get('/affiliate/register', fn () => redirect()->route('creator.affiliate.apply'))

    ->name('creator.affiliate.register');

Route::post('/affiliate/register', fn () => redirect()->route('creator.affiliate.apply'))

    ->name('creator.affiliate.register.store');



Route::get('/policies/{slug}', [CreatorPolicyPageController::class, 'show'])

    ->name('creator.policies.show');

