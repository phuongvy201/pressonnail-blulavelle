<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateBalanceAdjustment;
use App\Models\AffiliateClickEvent;
use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PromoCode;
use App\Support\AffiliateCommissionEligibility;
use App\Support\AffiliateReferralLink;
use App\Support\AffiliateSettings;
use Carbon\CarbonInterface;

class AffiliateDashboardAnalyticsService
{
    /** Max rows per list block on the main creator dashboard (full lists live on analytics pages). */
    public const DASHBOARD_PREVIEW_LIMIT = 5;

    /**
     * @return array<string, mixed>
     */
    public function build(Affiliate $affiliate, string $period = '30d'): array
    {
        $since = $this->periodStart($period);
        $affiliateId = $affiliate->id;
        $preview = self::DASHBOARD_PREVIEW_LIMIT;

        $clicks = $this->countClicks($affiliateId, $since);
        $orders = $this->countAttributedOrders($affiliateId, $since);
        $revenue = $this->sumAttributedRevenue($affiliateId, $since);
        $commissionEarned = $this->sumCommissions($affiliateId, $since, null);
        $availablePayout = $this->sumCommissions($affiliateId, null, AffiliateCommission::STATUS_PENDING);

        $conversionRate = $clicks > 0 ? round(($orders / $clicks) * 100, 2) : 0.0;

        return [
            'period' => $period,
            'period_label' => $this->periodLabel($period),
            'since' => $since,
            'kpis' => [
                'total_clicks' => $clicks,
                'total_orders' => $orders,
                'conversion_rate' => $conversionRate,
                'total_revenue' => round($revenue, 2),
                'total_commission' => round($commissionEarned, 2),
                'available_payout' => round($availablePayout, 2),
            ],
            'series' => $this->timeSeries($affiliateId, $period, $since),
            'preview_limit' => $preview,
            'top_products' => $this->topProducts($affiliateId, $since, $preview),
            'top_products_total' => $this->countTopProducts($affiliateId, $since),
            'link_performance' => $this->linkPerformance($affiliate, $since, $preview),
            'link_performance_total' => $this->countLinkLandingPaths($affiliateId, $since),
            'traffic_sources' => $this->trafficSources($affiliateId, $since, $preview),
            'traffic_sources_total' => $this->countTrafficSources($affiliateId, $since),
            'coupon_usage' => $this->couponUsage($affiliate, $since, $preview),
            'coupon_usage_total' => $this->countCouponUsageRows($affiliate, $since),
            'commissions' => $this->commissionBreakdown($affiliateId, $since),
            'recent_commissions' => $this->recentCommissions($affiliateId, $preview),
            'recent_commissions_total' => $this->countCommissions($affiliateId, $since),
            'payout_history' => $this->payoutHistory($affiliateId, $preview),
            'payout_history_total' => $this->countPayoutHistoryRows($affiliateId),
            'tier_progress' => $this->tierProgress($affiliate),
            'shop_home_url' => AffiliateReferralLink::homeUrl($affiliate),
            'coming_soon' => [
                'content_submissions' => true,
            ],
        ];
    }

    public function periodStart(string $period): ?CarbonInterface
    {
        return match ($period) {
            '7d' => now()->subDays(6)->startOfDay(),
            '30d' => now()->subDays(29)->startOfDay(),
            '90d' => now()->subDays(89)->startOfDay(),
            'all' => null,
            default => now()->subDays(29)->startOfDay(),
        };
    }

    public function periodLabel(string $period): string
    {
        return match ($period) {
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            '90d' => 'Last 90 days',
            'all' => 'All time',
            default => 'Last 30 days',
        };
    }

    public function validatePeriod(string $period): string
    {
        return in_array($period, ['7d', '30d', '90d', 'all'], true) ? $period : '30d';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function programAffiliateSummaries(string $period): array
    {
        $since = $this->periodStart($period);
        $rows = [];

        foreach (Affiliate::query()->with('user')->orderBy('code')->get() as $affiliate) {
            $clicks = $this->countClicks($affiliate->id, $since);
            $orders = $this->countAttributedOrders($affiliate->id, $since);

            $rows[] = [
                'affiliate' => $affiliate,
                'clicks' => $clicks,
                'orders' => $orders,
                'revenue' => round($this->sumAttributedRevenue($affiliate->id, $since), 2),
                'commission' => round($this->sumCommissions($affiliate->id, $since, null), 2),
                'conversion_rate' => $clicks > 0 ? round(($orders / $clicks) * 100, 2) : 0.0,
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['clicks'] <=> $a['clicks']);

        return $rows;
    }

    /**
     * @return array{clicks: int, orders: int, revenue: float, commission: float}
     */
    public function programTotals(string $period): array
    {
        $rows = $this->programAffiliateSummaries($period);

        return [
            'clicks' => (int) array_sum(array_column($rows, 'clicks')),
            'orders' => (int) array_sum(array_column($rows, 'orders')),
            'revenue' => round((float) array_sum(array_column($rows, 'revenue')), 2),
            'commission' => round((float) array_sum(array_column($rows, 'commission')), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function overviewDetail(Affiliate $affiliate, string $period): array
    {
        $since = $this->periodStart($period);

        return [
            'period' => $period,
            'period_label' => $this->periodLabel($period),
            'kpis' => $this->build($affiliate, $period)['kpis'],
            'series' => $this->timeSeries($affiliate->id, $period, $since),
            'tier_progress' => $this->tierProgress($affiliate),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function linkPerformanceList(Affiliate $affiliate, string $period, int $limit = 50): array
    {
        return $this->linkPerformance($affiliate, $this->periodStart($period), $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function topProductsList(Affiliate $affiliate, string $period, int $limit = 50): array
    {
        return $this->topProducts($affiliate->id, $this->periodStart($period), $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function trafficSourcesList(Affiliate $affiliate, string $period, int $limit = 30): array
    {
        return $this->trafficSources($affiliate->id, $this->periodStart($period), $limit);
    }

    /**
     * @return array<string, mixed>
     */
    public function commissionsDetail(Affiliate $affiliate, string $period): array
    {
        $since = $this->periodStart($period);

        return [
            'period_label' => $this->periodLabel($period),
            'breakdown' => $this->commissionBreakdown($affiliate->id, $since),
            'recent' => $this->recentCommissions($affiliate->id, 100),
            'attributed_orders' => $this->attributedOrdersForCreator($affiliate->id, $since, 100),
            'payout_history' => $this->payoutHistory($affiliate->id, 100),
        ];
    }

    /**
     * Attributed paid orders with commission eligibility (for creator transparency).
     *
     * @return list<array<string, mixed>>
     */
    public function attributedOrdersForCreator(int $affiliateId, ?CarbonInterface $since, int $limit = 100): array
    {
        $q = Order::query()
            ->with(['affiliateCommission:id,order_id,commission_amount,status'])
            ->where('affiliate_id', $affiliateId)
            ->where('payment_status', 'paid')
            ->orderByDesc(\Illuminate\Support\Facades\DB::raw('COALESCE(paid_at, created_at)'));

        if ($since) {
            $q->where(function ($sub) use ($since) {
                $sub->where('paid_at', '>=', $since)
                    ->orWhere(function ($s) use ($since) {
                        $s->whereNull('paid_at')->where('created_at', '>=', $since);
                    });
            });
        }

        $commissionService = app(AffiliateCommissionService::class);

        return $q->limit($limit)->get()->map(function (Order $order) use ($commissionService) {
            if ($order->affiliate_commission_eligibility === null) {
                $commissionService->createPendingCommissionIfEligible($order->fresh(['items.product']));
                $order->refresh();
            }

            $hasCommission = $order->affiliateCommission !== null;
            $eligibility = $order->affiliate_commission_eligibility
                ?? ($hasCommission ? AffiliateCommissionEligibility::ELIGIBLE : AffiliateCommissionEligibility::INELIGIBLE);

            return [
                'order_number' => $order->order_number,
                'date' => ($order->paid_at ?? $order->created_at)?->format('M j, Y'),
                'total' => round((float) $order->total_amount, 2),
                'eligibility' => $eligibility,
                'note_code' => $order->affiliate_commission_note,
                'note_label' => AffiliateCommissionEligibility::label($order->affiliate_commission_note),
                'commission_amount' => $hasCommission
                    ? round((float) $order->affiliateCommission->commission_amount, 2)
                    : null,
                'commission_status' => $order->affiliateCommission?->status,
            ];
        })->all();
    }

    private function countClicks(int $affiliateId, ?CarbonInterface $since): int
    {
        $q = AffiliateClickEvent::query()->where('affiliate_id', $affiliateId);
        if ($since) {
            $q->where('created_at', '>=', $since);
        }

        return (int) $q->count();
    }

    private function countAttributedOrders(int $affiliateId, ?CarbonInterface $since): int
    {
        $q = Order::query()
            ->where('affiliate_id', $affiliateId)
            ->where('payment_status', 'paid');

        if ($since) {
            $q->where(function ($sub) use ($since) {
                $sub->where('paid_at', '>=', $since)
                    ->orWhere(function ($s) use ($since) {
                        $s->whereNull('paid_at')->where('created_at', '>=', $since);
                    });
            });
        }

        return (int) $q->count();
    }

    private function sumAttributedRevenue(int $affiliateId, ?CarbonInterface $since): float
    {
        $q = Order::query()
            ->where('affiliate_id', $affiliateId)
            ->where('payment_status', 'paid');

        if ($since) {
            $q->where(function ($sub) use ($since) {
                $sub->where('paid_at', '>=', $since)
                    ->orWhere(function ($s) use ($since) {
                        $s->whereNull('paid_at')->where('created_at', '>=', $since);
                    });
            });
        }

        return (float) $q->selectRaw('SUM(GREATEST(0, subtotal - COALESCE(discount_amount, 0))) as agg')->value('agg');
    }

    private function sumCommissions(int $affiliateId, ?CarbonInterface $since, ?string $status): float
    {
        $q = AffiliateCommission::query()->where('affiliate_id', $affiliateId);
        if ($status) {
            $q->where('status', $status);
        }
        if ($since) {
            $q->where('created_at', '>=', $since);
        }

        return (float) $q->sum('commission_amount');
    }

    /**
     * @return array{labels: list<string>, clicks: list<int>, orders: list<int>, revenue: list<float>, commission: list<float>}
     */
    private function timeSeries(int $affiliateId, string $period, ?CarbonInterface $since): array
    {
        $days = match ($period) {
            '7d' => 7,
            '90d' => 90,
            'all' => 30,
            default => 30,
        };

        $start = $since ?? now()->subDays($days - 1)->startOfDay();
        $labels = [];
        $clicks = [];
        $orders = [];
        $revenue = [];
        $commission = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            if ($day->isFuture()) {
                break;
            }
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('M j');
            $clicks[$key] = 0;
            $orders[$key] = 0;
            $revenue[$key] = 0.0;
            $commission[$key] = 0.0;
        }

        $clickRows = AffiliateClickEvent::query()
            ->where('affiliate_id', $affiliateId)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        foreach ($clickRows as $d => $c) {
            if (isset($clicks[$d])) {
                $clicks[$d] = (int) $c;
            }
        }

        $orderRows = Order::query()
            ->where('affiliate_id', $affiliateId)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(COALESCE(paid_at, created_at)) as d, COUNT(*) as cnt, SUM(GREATEST(0, subtotal - COALESCE(discount_amount, 0))) as rev')
            ->groupBy('d')
            ->get();

        foreach ($orderRows as $row) {
            $d = $row->d;
            if (isset($orders[$d])) {
                $orders[$d] = (int) $row->cnt;
                $revenue[$d] = (float) $row->rev;
            }
        }

        $commRows = AffiliateCommission::query()
            ->where('affiliate_id', $affiliateId)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, SUM(commission_amount) as amt')
            ->groupBy('d')
            ->pluck('amt', 'd');

        foreach ($commRows as $d => $amt) {
            if (isset($commission[$d])) {
                $commission[$d] = (float) $amt;
            }
        }

        return [
            'labels' => $labels,
            'clicks' => array_values($clicks),
            'orders' => array_values($orders),
            'revenue' => array_values($revenue),
            'commission' => array_values($commission),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topProducts(int $affiliateId, ?CarbonInterface $since, int $limit = 8): array
    {
        $lineTotalsSub = OrderItem::query()
            ->selectRaw('order_id, SUM(total_price) as lines_total')
            ->groupBy('order_id');

        $netLineRevenueSql = <<<'SQL'
(CASE
    WHEN order_lines.lines_total > 0 THEN
        (GREATEST(0, orders.subtotal - COALESCE(orders.discount_amount, 0)))
        * (order_items.total_price / order_lines.lines_total)
    ELSE 0
END)
SQL;

        $q = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->joinSub($lineTotalsSub, 'order_lines', function ($join) {
                $join->on('order_lines.order_id', '=', 'order_items.order_id');
            })
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.affiliate_id', $affiliateId)
            ->where('orders.payment_status', 'paid')
            ->whereNotNull('order_items.product_id');

        if ($since) {
            $q->where('orders.created_at', '>=', $since);
        }

        $rows = $q
            ->selectRaw('order_items.product_id, MAX(COALESCE(products.name, order_items.product_name)) as name, MAX(products.slug) as slug, SUM(order_items.quantity) as qty, SUM('.$netLineRevenueSql.') as revenue')
            ->groupBy('order_items.product_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'product_id' => $r->product_id,
            'name' => $r->name,
            'slug' => $r->slug,
            'quantity' => (int) $r->qty,
            'revenue' => round((float) $r->revenue, 2),
        ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function linkPerformance(Affiliate $affiliate, ?CarbonInterface $since, int $limit = 10): array
    {
        $q = AffiliateClickEvent::query()
            ->where('affiliate_id', $affiliate->id)
            ->selectRaw('landing_path, COUNT(*) as clicks, MAX(product_id) as product_id')
            ->groupBy('landing_path')
            ->orderByDesc('clicks')
            ->limit($limit);

        if ($since) {
            $q->where('created_at', '>=', $since);
        }

        $rows = $q->get();

        $productIds = $rows->pluck('product_id')->filter()->unique()->values()->all();
        $productsById = $productIds !== []
            ? Product::query()->whereIn('id', $productIds)->get(['id', 'name', 'slug'])->keyBy('id')
            : collect();

        $slugsFromPaths = $rows
            ->pluck('landing_path')
            ->map(fn ($p) => $this->productSlugFromLandingPath(is_string($p) ? $p : null))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $missingSlugs = array_diff(
            $slugsFromPaths,
            $productsById->pluck('slug')->filter()->all()
        );
        $productsBySlug = $missingSlugs !== []
            ? Product::query()->whereIn('slug', $missingSlugs)->get(['id', 'name', 'slug'])->keyBy('slug')
            : collect();

        return $rows->map(function ($row) use ($affiliate, $productsById, $productsBySlug) {
            $path = $row->landing_path ?: '/';
            $clicks = (int) $row->clicks;
            $product = $row->product_id ? $productsById->get($row->product_id) : null;

            if (! $product) {
                $slug = $this->productSlugFromLandingPath($path);
                if ($slug) {
                    $product = $productsBySlug->get($slug);
                }
            }

            if ($product) {
                return [
                    'path' => $path,
                    'label' => $product->name,
                    'subtitle' => '/products/'.$product->slug,
                    'type' => 'product',
                    'product_id' => $product->id,
                    'product_slug' => $product->slug,
                    'shop_url' => route('products.show', $product->slug),
                    'referral_url' => AffiliateReferralLink::productUrl($product, $affiliate),
                    'clicks' => $clicks,
                ];
            }

            if ($path === '/' || $path === '') {
                return [
                    'path' => $path,
                    'label' => 'Storefront home',
                    'subtitle' => null,
                    'type' => 'home',
                    'product_id' => null,
                    'product_slug' => null,
                    'shop_url' => url('/'),
                    'referral_url' => AffiliateReferralLink::homeUrl($affiliate),
                    'clicks' => $clicks,
                ];
            }

            return [
                'path' => $path,
                'label' => $this->friendlyLandingPathLabel($path),
                'subtitle' => $path,
                'type' => 'other',
                'product_id' => null,
                'product_slug' => null,
                'shop_url' => null,
                'referral_url' => null,
                'clicks' => $clicks,
            ];
        })->all();
    }

    private function productSlugFromLandingPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if (! preg_match('~^/products/([^/?]+)~', $path, $m)) {
            return null;
        }

        $slug = urldecode($m[1]);

        return $slug !== '' ? $slug : null;
    }

    private function friendlyLandingPathLabel(string $path): string
    {
        if (preg_match('~^/products/([^/?]+)~', $path, $m)) {
            $slug = str_replace(['-', '_'], ' ', urldecode($m[1]));

            return 'Product: '.ucwords($slug);
        }

        return $path;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function trafficSources(int $affiliateId, ?CarbonInterface $since, int $limit = 8): array
    {
        $fromClicks = AffiliateClickEvent::query()
            ->where('affiliate_id', $affiliateId)
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->selectRaw("COALESCE(NULLIF(utm_source, ''), NULLIF(referrer_host, ''), 'direct') as source, COUNT(*) as cnt")
            ->groupBy('source')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get();

        $sources = [];
        foreach ($fromClicks as $row) {
            $sources[$row->source] = (int) $row->cnt;
        }

        $orders = Order::query()
            ->where('affiliate_id', $affiliateId)
            ->where('payment_status', 'paid')
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->get(['utm_snapshot']);

        foreach ($orders as $order) {
            $snap = $order->utm_snapshot;
            if (! is_array($snap)) {
                continue;
            }
            $src = trim((string) ($snap['utm_source'] ?? ''));
            if ($src === '') {
                continue;
            }
            $sources[$src] = ($sources[$src] ?? 0) + 1;
        }

        arsort($sources);

        return collect($sources)->take($limit)->map(fn ($cnt, $source) => [
            'source' => $source,
            'count' => $cnt,
        ])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function couponUsage(Affiliate $affiliate, ?CarbonInterface $since, int $limit = 8): array
    {
        $out = $this->couponUsageRows($affiliate, $since);

        return array_slice($out, 0, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function couponUsageRows(Affiliate $affiliate, ?CarbonInterface $since): array
    {
        $promos = PromoCode::query()
            ->where('affiliate_id', $affiliate->id)
            ->get(['id', 'code', 'used_count']);

        if ($promos->isEmpty()) {
            return [];
        }

        $out = [];
        foreach ($promos as $promo) {
            $code = strtoupper((string) $promo->code);
            $oq = Order::query()
                ->where('affiliate_id', $affiliate->id)
                ->where('payment_status', 'paid')
                ->whereRaw('UPPER(promo_code) = ?', [$code]);

            if ($since) {
                $oq->where('created_at', '>=', $since);
            }

            $uses = (int) $oq->count();
            if ($uses === 0 && (int) $promo->used_count === 0) {
                continue;
            }

            $revenue = (float) $oq->clone()
                ->selectRaw('SUM(GREATEST(0, subtotal - COALESCE(discount_amount, 0))) as agg')
                ->value('agg');

            $out[] = [
                'code' => $promo->code,
                'uses' => max($uses, (int) $promo->used_count),
                'revenue' => round($revenue, 2),
            ];
        }

        usort($out, fn ($a, $b) => $b['uses'] <=> $a['uses']);

        return $out;
    }

    private function countLinkLandingPaths(int $affiliateId, ?CarbonInterface $since): int
    {
        $q = AffiliateClickEvent::query()->where('affiliate_id', $affiliateId);
        if ($since) {
            $q->where('created_at', '>=', $since);
        }

        return (int) $q->selectRaw('COUNT(DISTINCT landing_path) as aggregate')->value('aggregate');
    }

    private function countTopProducts(int $affiliateId, ?CarbonInterface $since): int
    {
        $q = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.affiliate_id', $affiliateId)
            ->where('orders.payment_status', 'paid')
            ->whereNotNull('order_items.product_id');

        if ($since) {
            $q->where('orders.created_at', '>=', $since);
        }

        return (int) $q->selectRaw('COUNT(DISTINCT order_items.product_id) as aggregate')->value('aggregate');
    }

    private function countTrafficSources(int $affiliateId, ?CarbonInterface $since): int
    {
        return count($this->trafficSources($affiliateId, $since, 500));
    }

    private function countCouponUsageRows(Affiliate $affiliate, ?CarbonInterface $since): int
    {
        return count($this->couponUsageRows($affiliate, $since));
    }

    private function countCommissions(int $affiliateId, ?CarbonInterface $since): int
    {
        $q = AffiliateCommission::query()->where('affiliate_id', $affiliateId);
        if ($since) {
            $q->where('created_at', '>=', $since);
        }

        return (int) $q->count();
    }

    private function countPayoutHistoryRows(int $affiliateId): int
    {
        return count($this->payoutHistory($affiliateId, 500));
    }

    /**
     * @return array{pending: float, paid: float, void: float, counts: array<string, int>}
     */
    private function commissionBreakdown(int $affiliateId, ?CarbonInterface $since): array
    {
        $q = AffiliateCommission::query()->where('affiliate_id', $affiliateId);
        if ($since) {
            $q->where('created_at', '>=', $since);
        }

        $rows = $q
            ->selectRaw('status, COUNT(*) as cnt, SUM(commission_amount) as total')
            ->groupBy('status')
            ->get();

        $amounts = ['pending' => 0.0, 'paid' => 0.0, 'void' => 0.0];
        $counts = ['pending' => 0, 'paid' => 0, 'void' => 0];

        foreach ($rows as $row) {
            $status = $row->status;
            if (isset($amounts[$status])) {
                $amounts[$status] = round((float) $row->total, 2);
                $counts[$status] = (int) $row->cnt;
            }
        }

        return [
            'pending' => $amounts['pending'],
            'paid' => $amounts['paid'],
            'void' => $amounts['void'],
            'counts' => $counts,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentCommissions(int $affiliateId, int $limit): array
    {
        return AffiliateCommission::query()
            ->with('order:id,order_number')
            ->where('affiliate_id', $affiliateId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'order_number' => $c->order?->order_number,
                'amount' => round((float) $c->commission_amount, 2),
                'status' => $c->status,
                'base' => round((float) $c->commission_base, 2),
                'rate' => (float) $c->commission_rate,
                'created_at' => $c->created_at?->format('M j, Y'),
                'paid_at' => $c->paid_at?->format('M j, Y'),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function payoutHistory(int $affiliateId, int $limit): array
    {
        $paid = AffiliateCommission::query()
            ->where('affiliate_id', $affiliateId)
            ->where('status', AffiliateCommission::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->orderByDesc('paid_at')
            ->limit($limit)
            ->get()
            ->map(fn ($c) => [
                'date' => $c->paid_at?->format('M j, Y'),
                'amount' => round((float) $c->commission_amount, 2),
                'type' => 'commission_payout',
                'note' => 'Order '.($c->order_id ? '#'.$c->order_id : ''),
                'sort' => $c->paid_at?->timestamp ?? 0,
            ]);

        $adjustments = AffiliateBalanceAdjustment::query()
            ->where('affiliate_id', $affiliateId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($a) => [
                'date' => $a->created_at?->format('M j, Y'),
                'amount' => round((float) $a->amount, 2),
                'type' => $a->type,
                'note' => $a->note,
                'sort' => $a->created_at?->timestamp ?? 0,
            ]);

        return $paid->concat($adjustments)
            ->sortByDesc('sort')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function tierProgress(Affiliate $affiliate): array
    {
        $days = AffiliateSettings::tierEvaluationDays();
        $thresholds = AffiliateSettings::tierOrderThresholds();
        $inactivityDays = AffiliateSettings::tierInactivityDays();

        $orderCount = app(AffiliateTierService::class)->countAttributedPaidOrders($affiliate, $days);
        $daysSinceActivity = app(AffiliateTierService::class)->daysSinceLastAttributedOrder($affiliate);

        $tier = \App\Support\AffiliateTier::normalize($affiliate->tier);
        $nextTier = \App\Support\AffiliateTier::nextTier($tier);
        $nextThreshold = $nextTier
            ? \App\Support\AffiliateTier::orderThresholdForTier($tier, $thresholds)
            : null;

        $progress = 100.0;
        if ($nextThreshold && $nextThreshold > 0) {
            $progress = min(100, round(($orderCount / $nextThreshold) * 100, 1));
        }

        return [
            'current_tier' => $tier,
            'current_tier_label' => \App\Support\AffiliateTier::label($tier),
            'commission_percent' => $affiliate->effectiveCommissionPercent(),
            'tier_locked' => (bool) $affiliate->tier_locked,
            'rolling_orders' => $orderCount,
            'rolling_days' => $days,
            'next_tier' => $nextTier,
            'next_threshold_orders' => $nextThreshold,
            'progress_percent' => $progress,
            'threshold_silver' => $thresholds['silver'],
            'threshold_gold' => $thresholds['gold'],
            'threshold_diamond' => $thresholds['diamond'],
            'inactivity_days' => $inactivityDays,
            'days_since_activity' => $daysSinceActivity,
            'inactivity_warning' => $daysSinceActivity > $inactivityDays,
        ];
    }
}
