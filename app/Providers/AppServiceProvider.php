<?php

namespace App\Providers;

use App\Support\AffiliateSetupStatus;
use App\Support\SharedCookieDomain;
use App\Services\CurrencyService;
use App\Models\AffiliateApplication;
use App\Models\AffiliateSampleRequest;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bot user-agent fragments that should not persist sessions.
     *
     * @var array<int, string>
     */
    private const SESSIONLESS_BOT_SIGNATURES = [
        'AdsBot-Google',
        'Googlebot',
        'bingbot',
        'Slurp',
        'DuckDuckBot',
        'Baiduspider',
        'YandexBot',
        'facebot',
        'ia_archiver',
        'crawler',
        'spider',
        'bot',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            $userAgent = (string) request()->userAgent();
            if ($this->isSessionlessBotUserAgent($userAgent)) {
                config(['session.driver' => 'array']);
            }
        }

        Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);

        // Force HTTPS in production to avoid Mixed Content issues
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email !== '' ? $email.'|'.$request->ip() : $request->ip());
        });

        RateLimiter::for('creator-affiliate-register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        if ($this->app->environment('local') && config('creator.domain') && ! SharedCookieDomain::resolve()) {
            logger()->info('CREATOR_DOMAIN is set and SESSION_DOMAIN is empty — main and creator will keep separate sessions unless SESSION_DOMAIN is explicitly configured.');
        }

        // Share currency information with all views
        View::composer('*', function ($view) {
            $currency = CurrencyService::getCurrentCurrency();
            $currencyRate = CurrencyService::getCurrentCurrencyRate();
            $currencySymbol = CurrencyService::getCurrencySymbol();

            // Sidebar badges for admin/ad-partner: new orders (pending) + return requests (pending)
            $sidebarPendingAffiliateApplications = 0;
            $sidebarPendingSampleRequests = 0;
            $user = Auth::user();
            if ($user && $user->hasRole('admin')) {
                $sidebarPendingAffiliateApplications = AffiliateApplication::query()
                    ->where('status', AffiliateApplication::STATUS_PENDING)
                    ->count();
                $sidebarPendingSampleRequests = AffiliateSampleRequest::query()
                    ->where('status', AffiliateSampleRequest::STATUS_PENDING)
                    ->count();
            }

            $sidebarPendingOrders = 0;
            $sidebarPendingReturns = 0;
            $returnsSeenAt = Session::get('returns_seen_at');

            if ($user && ($user->hasRole('admin') || $user->hasRole('ad-partner'))) {
                $sidebarPendingOrders = Order::where('status', 'pending')->count();
                $sidebarPendingReturns = ReturnRequest::where('status', 'pending')
                    ->when($returnsSeenAt, function ($q) use ($returnsSeenAt) {
                        $q->where('updated_at', '>', $returnsSeenAt);
                    })
                    ->count();
            }

            $liveChatUnreadCount = 0;
            if ($user && ($user->hasRole('admin') || $user->hasRole('seller'))) {
                $liveChatUnreadCount = ChatMessage::where('is_from_customer', true)
                    ->whereNull('read_at')
                    ->whereHas('conversation', fn ($q) => $q->where('seller_id', $user->id))
                    ->count();
            }
            
            if (! array_key_exists('canEdit', $view->getData())) {
                $view->with('canEdit', $user && $user->hasRole('admin'));
            }
            if (! array_key_exists('editMode', $view->getData())) {
                $view->with('editMode', request()->boolean('edit'));
            }

            $view->with([
                'currentCurrency' => $currency,
                'currentCurrencyRate' => $currencyRate,
                'currentCurrencySymbol' => $currencySymbol,
                'sidebarPendingOrders' => $sidebarPendingOrders,
                'sidebarPendingReturns' => $sidebarPendingReturns,
                'sidebarPendingAffiliateApplications' => $sidebarPendingAffiliateApplications,
                'sidebarPendingSampleRequests' => $sidebarPendingSampleRequests,
                'liveChatUnreadCount' => $liveChatUnreadCount,
            ]);
        });

        View::composer('layouts.creator', function ($view): void {
            $setup = null;
            $creatorSetupIncomplete = false;
            $user = Auth::user();

            if ($user && $user->canAccessCreatorAffiliateFeatures()) {
                $affiliate = $user->affiliate;
                if ($affiliate) {
                    $setup = AffiliateSetupStatus::for($affiliate);
                    $creatorSetupIncomplete = ! $setup->allComplete();
                }
            }

            $view->with([
                'creatorSetup' => $setup,
                'creatorSetupIncomplete' => $creatorSetupIncomplete,
                'creatorLayoutFooter' => content_block('creator.layout.footer', creator_layout_footer_block_defaults()),
            ]);
        });
    }

    private function isSessionlessBotUserAgent(string $userAgent): bool
    {
        if ($userAgent === '') {
            return false;
        }

        foreach (self::SESSIONLESS_BOT_SIGNATURES as $signature) {
            if (stripos($userAgent, $signature) !== false) {
                return true;
            }
        }

        return false;
    }
}
