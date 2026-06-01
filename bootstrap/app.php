<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__ . '/../routes/console.php',
        using: function (): void {
            Route::get('/up', function () {
                $exception = null;

                try {
                    Event::dispatch(new DiagnosingHealth);
                } catch (\Throwable $e) {
                    if (app()->hasDebugModeEnabled()) {
                        throw $e;
                    }

                    report($e);

                    $exception = $e->getMessage();
                }

                return response(View::file(
                    base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/health-up.blade.php'),
                    ['exception' => $exception]
                ), status: $exception ? 500 : 200);
            });

            PreventRequestsDuringMaintenance::except(['/up']);

            $creatorDomain = config('creator.domain');
            if (is_string($creatorDomain) && $creatorDomain !== '') {
                Route::middleware('web')
                    ->domain($creatorDomain)
                    ->group(base_path('routes/creator.php'));
            }

            Route::middleware('web')->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Spatie Permission middleware aliases
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'has.shop' => \App\Http\Middleware\HasShop::class,
            'affiliate.creator' => \App\Http\Middleware\EnsureApprovedAffiliate::class,
        ]);

        // Exclude API routes from CSRF protection
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'payment/stripe/webhook', // Stripe webhook POST without CSRF
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\TrackAffiliateReferral::class,
            \App\Http\Middleware\TrackAffiliateUtm::class,
            \App\Http\Middleware\ExpireLegacySessionCookies::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
