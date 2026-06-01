<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateSampleRequest;
use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\AffiliateTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Validation\ValidationException;

class AffiliateSampleRequestService
{
    /**
     * @return array{period_days: int, max_requests: int, used: int, remaining: int, tier: string}
     */
    public function quotaSummary(Affiliate $affiliate): array
    {
        $tier = AffiliateTier::normalize($affiliate->tier ?: AffiliateTier::BASIC);
        $config = config("affiliate.sample_quotas.{$tier}", config('affiliate.sample_quotas.basic'));
        $periodDays = (int) ($config['period_days'] ?? 90);
        $max = (int) ($config['max_requests'] ?? 1);
        $used = $this->countQuotaUsage($affiliate, $periodDays);

        return [
            'tier' => $tier,
            'period_days' => $periodDays,
            'max_requests' => $max,
            'used' => $used,
            'remaining' => max(0, $max - $used),
        ];
    }

    public function countQuotaUsage(Affiliate $affiliate, int $periodDays): int
    {
        $since = $this->quotaPeriodStart($periodDays);

        return AffiliateSampleRequest::query()
            ->where('affiliate_id', $affiliate->id)
            ->whereIn('status', $this->quotaCountedStatuses())
            ->where('created_at', '>=', $since)
            ->count();
    }

    public function countProductQuotaUsage(Affiliate $affiliate, Product $product, int $periodDays): int
    {
        $since = $this->quotaPeriodStart($periodDays);

        return AffiliateSampleRequest::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('product_id', $product->id)
            ->whereIn('status', $this->quotaCountedStatuses())
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * @return array{limit: int|null, used: int, remaining: int|null, period_days: int}
     */
    public function productQuotaSummary(Affiliate $affiliate, Product $product): array
    {
        $quota = $this->quotaSummary($affiliate);
        $limit = $product->sample_quota_per_affiliate;

        if ($limit === null || (int) $limit <= 0) {
            return [
                'limit' => null,
                'used' => 0,
                'remaining' => null,
                'period_days' => $quota['period_days'],
            ];
        }

        $used = $this->countProductQuotaUsage($affiliate, $product, $quota['period_days']);

        return [
            'limit' => (int) $limit,
            'used' => $used,
            'remaining' => max(0, (int) $limit - $used),
            'period_days' => $quota['period_days'],
        ];
    }

    public function sampleEligibleProductsQuery(): Builder
    {
        return Product::query()
            ->sampleRequestEnabled()
            ->availableForDisplay()
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    public function affiliateHasSampleProductsAvailable(Affiliate $affiliate): bool
    {
        $tier = AffiliateTier::normalize($affiliate->tier ?: AffiliateTier::BASIC);

        $query = $this->sampleEligibleProductsQuery();
        $this->applyCreatorSampleTierFilter($query, $tier);

        return $query->exists();
    }

    /**
     * Collections that contain at least one sample-eligible product for this affiliate tier.
     *
     * @return SupportCollection<int, Collection>
     */
    public function sampleFilterCollectionsForAffiliate(Affiliate $affiliate): SupportCollection
    {
        $tier = AffiliateTier::normalize($affiliate->tier ?: AffiliateTier::BASIC);

        return Collection::query()
            ->active()
            ->whereHas('products', function (Builder $q) use ($tier) {
                $q->sampleRequestEnabled()
                    ->availableForDisplay()
                    ->whereNotNull('products.slug')
                    ->where('products.slug', '!=', '');
                $this->applyCreatorSampleTierFilter($q, $tier);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    /**
     * @return list<array{id: int, name: string, sku: string|null, thumbnail: string|null, min_tier: string|null, category: string|null, category_id: int|null}>
     */
    public function searchSampleProductsForAffiliate(
        Affiliate $affiliate,
        string $query,
        ?int $collectionId = null,
        int $limit = 20,
    ): array {
        $tier = AffiliateTier::normalize($affiliate->tier ?: AffiliateTier::BASIC);
        $limit = max(1, min(30, $limit));
        $query = trim($query);

        $builder = $this->sampleEligibleProductsQuery()
            ->select(['products.id', 'products.name', 'products.sku', 'products.media', 'products.sample_min_tier', 'products.template_id'])
            ->with([
                'template:id,media',
                'collections' => fn ($q) => $q->select('collections.id', 'collections.name')->orderByPivot('sort_order'),
            ]);
        $this->applyCreatorSampleTierFilter($builder, $tier);

        if ($collectionId) {
            $builder->whereHas('collections', fn (Builder $q) => $q->where('collections.id', $collectionId));
        }

        if ($query !== '') {
            $like = '%'.$query.'%';
            $builder->where(function (Builder $q) use ($like) {
                $q->where('products.name', 'like', $like)
                    ->orWhere('products.sku', 'like', $like)
                    ->orWhereHas('collections', fn (Builder $cq) => $cq->where('collections.name', 'like', $like));
            });
        }

        $candidates = $builder->orderBy('products.name')->limit($limit * 3)->get();

        $results = [];
        foreach ($candidates as $product) {
            if (! $product->hasStock()) {
                continue;
            }

            $productQuota = $this->productQuotaSummary($affiliate, $product);
            if ($productQuota['remaining'] !== null && $productQuota['remaining'] <= 0) {
                continue;
            }

            $category = $this->primaryCollectionForProduct($product, $collectionId);

            $results[] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'thumbnail' => $this->productThumbnailUrl($product),
                'min_tier' => $product->sample_min_tier ? AffiliateTier::label($product->sample_min_tier) : null,
                'category' => $category['name'] ?? null,
                'category_id' => $category['id'] ?? null,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sampleProductDetailsForAffiliate(Affiliate $affiliate, Product $product): ?array
    {
        $tier = AffiliateTier::normalize($affiliate->tier ?: AffiliateTier::BASIC);

        if (! $product->isSampleRequestEnabled() || ! $product->isAvailableForDisplay()) {
            return null;
        }

        if (! $product->isSampleEligibleForTier($tier)) {
            return null;
        }

        if (! $product->hasStock()) {
            return null;
        }

        $productQuota = $this->productQuotaSummary($affiliate, $product);
        if ($productQuota['remaining'] !== null && $productQuota['remaining'] <= 0) {
            return null;
        }

        $product->load([
            'variants' => fn ($q) => $q->where('quantity', '>', 0)->orderBy('variant_name'),
            'collections' => fn ($q) => $q->select('collections.id', 'collections.name')->orderByPivot('sort_order'),
        ]);

        $category = $this->primaryCollectionForProduct($product);

        $variants = $product->variants->map(function (ProductVariant $variant) {
            $attr = '';
            if (is_array($variant->attributes) && $variant->attributes !== []) {
                $attr = ' ('.collect($variant->attributes)->map(fn ($val, $key) => $key.': '.$val)->implode(', ').')';
            }

            return [
                'id' => $variant->id,
                'label' => $variant->variant_name.$attr,
                'qty' => (int) $variant->quantity,
            ];
        })->values()->all();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'thumbnail' => $this->productThumbnailUrl($product),
            'max_qty' => $product->maxSampleQuantityPerRequest(),
            'has_variants' => $product->variants->isNotEmpty(),
            'min_tier' => $product->sample_min_tier ? AffiliateTier::label($product->sample_min_tier) : null,
            'category' => $category['name'] ?? null,
            'category_id' => $category['id'] ?? null,
            'variants' => $variants,
        ];
    }

    /**
     * Products a creator may request, filtered by tier and stock.
     *
     * @return SupportCollection<int, Product>
     */
    public function sampleEligibleProductsForAffiliate(Affiliate $affiliate): SupportCollection
    {
        $tier = AffiliateTier::normalize($affiliate->tier ?: AffiliateTier::BASIC);

        $query = $this->sampleEligibleProductsQuery()
            ->with(['variants' => fn ($q) => $q->where('quantity', '>', 0)->orderBy('variant_name')]);
        $this->applyCreatorSampleTierFilter($query, $tier);

        return $query->orderBy('name')
            ->get()
            ->filter(function (Product $product) use ($affiliate) {
                if (! $product->hasStock()) {
                    return false;
                }

                $productQuota = $this->productQuotaSummary($affiliate, $product);
                if ($productQuota['remaining'] !== null && $productQuota['remaining'] <= 0) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(Affiliate $affiliate, User $user, array $data): AffiliateSampleRequest
    {
        $quota = $this->quotaSummary($affiliate);
        if ($quota['remaining'] <= 0) {
            throw ValidationException::withMessages([
                'quota' => "Sample quota reached ({$quota['used']}/{$quota['max_requests']} in the last {$quota['period_days']} days).",
            ]);
        }

        $tier = AffiliateTier::normalize($affiliate->tier ?: AffiliateTier::BASIC);

        $product = $this->sampleEligibleProductsQuery()
            ->with(['variants' => fn ($q) => $q->orderBy('variant_name')])
            ->findOrFail((int) $data['product_id']);

        if (! $product->isSampleEligibleForTier($tier)) {
            throw ValidationException::withMessages([
                'product_id' => 'This product requires '.AffiliateTier::label($product->sample_min_tier).' tier or higher.',
            ]);
        }

        $productQuota = $this->productQuotaSummary($affiliate, $product);
        if ($productQuota['remaining'] !== null && $productQuota['remaining'] <= 0) {
            throw ValidationException::withMessages([
                'product_id' => "You reached the sample limit for this product ({$productQuota['used']}/{$productQuota['limit']} in {$productQuota['period_days']} days).",
            ]);
        }

        $maxQty = $product->maxSampleQuantityPerRequest();
        $quantity = min(max(1, (int) ($data['quantity'] ?? 1)), $maxQty);

        $variant = null;
        $selectedVariant = null;
        $sizePreset = null;

        if ($product->variants->isNotEmpty()) {
            $variantId = (int) ($data['product_variant_id'] ?? 0);
            $variant = $product->variants()->where('id', $variantId)->first();
            if (! $variant || (int) $variant->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Selected variant is unavailable or out of stock.',
                ]);
            }
            $selectedVariant = [
                'id' => $variant->id,
                'variant_name' => $variant->variant_name,
                'attributes' => $variant->attributes,
                'sku' => $variant->sku,
            ];
        } else {
            if (! $product->hasStock() || (int) $product->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'product_id' => 'This product is out of stock.',
                ]);
            }
            $sizePreset = isset($data['size_preset']) ? strtoupper((string) $data['size_preset']) : null;
            if ($sizePreset && ! in_array($sizePreset, AffiliateSampleRequest::SIZE_PRESETS, true)) {
                throw ValidationException::withMessages([
                    'size_preset' => 'Invalid size preset.',
                ]);
            }
        }

        $sampleRequest = AffiliateSampleRequest::query()->create([
            'affiliate_id' => $affiliate->id,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
            'size_preset' => $sizePreset,
            'selected_variant' => $selectedVariant,
            'status' => AffiliateSampleRequest::STATUS_PENDING,
            'tier_at_request' => $tier,
            'shipping_name' => $data['shipping_name'],
            'shipping_phone' => $data['shipping_phone'] ?? null,
            'shipping_address' => $data['shipping_address'],
            'shipping_address_line2' => $data['shipping_address_line2'] ?? null,
            'shipping_city' => $data['shipping_city'],
            'shipping_state' => $data['shipping_state'] ?? null,
            'shipping_postal_code' => $data['shipping_postal_code'],
            'shipping_country' => strtoupper((string) ($data['shipping_country'] ?? 'US')),
            'creator_notes' => $data['creator_notes'] ?? null,
        ]);

        if (! $product->sample_requires_approval) {
            $admin = User::role('admin')->first() ?? $user;

            return $this->approve(
                $sampleRequest,
                $admin,
                'Auto-approved (product does not require manual approval).',
                true
            );
        }

        return $sampleRequest;
    }

    public function approve(AffiliateSampleRequest $request, User $admin, ?string $adminNotes = null, bool $createOrder = true): AffiliateSampleRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be approved.']);
        }

        return DB::transaction(function () use ($request, $admin, $adminNotes, $createOrder) {
            $request->load(['product', 'productVariant', 'affiliate.user']);

            $this->assertStillEligible($request);

            $orderId = null;
            if ($createOrder) {
                $orderId = $this->createInternalSampleOrder($request);
                $this->decrementStock($request);
            }

            $request->update([
                'status' => AffiliateSampleRequest::STATUS_APPROVED,
                'admin_notes' => $adminNotes,
                'order_id' => $orderId,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return $request->fresh(['product', 'productVariant', 'order', 'affiliate']);
        });
    }

    public function reject(AffiliateSampleRequest $request, User $admin, string $reason, ?string $adminNotes = null): AffiliateSampleRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be rejected.']);
        }

        $request->update([
            'status' => AffiliateSampleRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'admin_notes' => $adminNotes,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        return $request->fresh();
    }

    public function markShipped(AffiliateSampleRequest $request, ?string $trackingNumber = null): AffiliateSampleRequest
    {
        if (! in_array($request->status, [AffiliateSampleRequest::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages(['status' => 'Request must be approved before shipping.']);
        }

        $request->update([
            'status' => AffiliateSampleRequest::STATUS_SHIPPED,
            'tracking_number' => $trackingNumber,
            'shipped_at' => now(),
        ]);

        if ($request->order_id && $trackingNumber) {
            Order::query()->where('id', $request->order_id)->update([
                'tracking_number' => $trackingNumber,
            ]);
        }

        return $request->fresh();
    }

    public function markDelivered(AffiliateSampleRequest $request): AffiliateSampleRequest
    {
        if ($request->status !== AffiliateSampleRequest::STATUS_SHIPPED) {
            throw ValidationException::withMessages(['status' => 'Request must be shipped before marking delivered.']);
        }

        $request->update([
            'status' => AffiliateSampleRequest::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        return $request->fresh();
    }

    private function assertStillEligible(AffiliateSampleRequest $request): void
    {
        $product = $request->product ?? Product::query()->find($request->product_id);
        if (! $product || ! $product->isSampleRequestEnabled()) {
            throw ValidationException::withMessages(['product_id' => 'Product is no longer available for sample requests.']);
        }

        $tier = AffiliateTier::normalize($request->affiliate?->tier ?: $request->tier_at_request ?: AffiliateTier::BASIC);
        if (! $product->isSampleEligibleForTier($tier)) {
            throw ValidationException::withMessages(['product_id' => 'Creator tier no longer meets product sample requirements.']);
        }

        $qty = (int) $request->quantity;
        if ($request->product_variant_id) {
            $variant = ProductVariant::query()->find($request->product_variant_id);
            if (! $variant || (int) $variant->quantity < $qty) {
                throw ValidationException::withMessages(['stock' => 'Variant is out of stock.']);
            }
        } elseif (! $product->hasStock() || (int) $product->quantity < $qty) {
            throw ValidationException::withMessages(['stock' => 'Product is out of stock.']);
        }
    }

    private function decrementStock(AffiliateSampleRequest $request): void
    {
        $qty = (int) $request->quantity;
        if ($request->product_variant_id) {
            ProductVariant::query()
                ->where('id', $request->product_variant_id)
                ->where('quantity', '>=', $qty)
                ->decrement('quantity', $qty);

            return;
        }

        Product::query()
            ->where('id', $request->product_id)
            ->where('quantity', '>=', $qty)
            ->decrement('quantity', $qty);
    }

    private function createInternalSampleOrder(AffiliateSampleRequest $sample): int
    {
        $affiliate = $sample->affiliate;
        $user = $affiliate?->user;
        $product = $sample->product;
        $email = $user?->email ?? 'creator@sample.local';

        $shippingLine = trim($sample->shipping_address);
        if ($sample->shipping_address_line2) {
            $shippingLine .= ', '.$sample->shipping_address_line2;
        }

        $notes = 'Affiliate sample request #'.$sample->id;
        if ($sample->size_preset) {
            $notes .= ' · Size: '.$sample->size_preset;
        }
        if ($sample->creator_notes) {
            $notes .= ' · Creator: '.$sample->creator_notes;
        }

        $order = Order::query()->create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $user?->id,
            'affiliate_id' => null,
            'affiliate_attribution' => 'none',
            'utm_snapshot' => [],
            'customer_name' => $sample->shipping_name,
            'customer_email' => $email,
            'customer_phone' => $sample->shipping_phone,
            'shipping_address' => $shippingLine,
            'city' => $sample->shipping_city,
            'state' => $sample->shipping_state,
            'postal_code' => $sample->shipping_postal_code,
            'country' => $sample->shipping_country,
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'gift_card_amount' => 0,
            'shipping_cost' => 0,
            'tip_amount' => 0,
            'total_amount' => 0,
            'currency' => 'USD',
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => 'sample',
            'payment_id' => 'SAMPLE-'.$sample->id,
            'paid_at' => now(),
            'notes' => $notes,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_description' => $product->description,
            'unit_price' => 0,
            'quantity' => $sample->quantity,
            'total_price' => 0,
            'product_options' => [
                'selected_variant' => $sample->selected_variant,
                'size_preset' => $sample->size_preset,
                'affiliate_sample_request_id' => $sample->id,
            ],
            'shipping_cost' => 0,
            'is_first_item' => true,
            'shipping_notes' => 'Affiliate sample',
        ]);

        return (int) $order->id;
    }

    /**
     * @return array{
     *     quota: array{period_days: int, max_requests: int, used: int, remaining: int, tier: string},
     *     status_counts: array<string, int>,
     *     pending_count: int,
     *     total: int,
     *     recent: \Illuminate\Support\Collection<int, AffiliateSampleRequest>
     * }
     */
    public function dashboardSummary(Affiliate $affiliate, int $recentLimit = 5): array
    {
        $statusCounts = AffiliateSampleRequest::query()
            ->where('affiliate_id', $affiliate->id)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->map(fn ($c) => (int) $c)
            ->all();

        return [
            'quota' => $this->quotaSummary($affiliate),
            'status_counts' => $statusCounts,
            'pending_count' => (int) ($statusCounts[AffiliateSampleRequest::STATUS_PENDING] ?? 0),
            'total' => array_sum($statusCounts),
            'recent' => AffiliateSampleRequest::query()
                ->where('affiliate_id', $affiliate->id)
                ->with('product:id,name')
                ->orderByDesc('created_at')
                ->limit($recentLimit)
                ->get(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, AffiliateSampleRequest>
     */
    public function listForAffiliate(Affiliate $affiliate, int $limit = 50)
    {
        return AffiliateSampleRequest::query()
            ->where('affiliate_id', $affiliate->id)
            ->with(['product:id,name', 'order:id,order_number'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private function applyCreatorSampleTierFilter(Builder $query, string $affiliateTier): void
    {
        $affiliateRank = AffiliateTier::rank($affiliateTier);

        $query->whereRaw("(
            CASE COALESCE(products.sample_min_tier, 'basic')
                WHEN 'diamond' THEN 4
                WHEN 'gold' THEN 3
                WHEN 'silver' THEN 2
                ELSE 1
            END
        ) <= ?", [$affiliateRank]);
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function primaryCollectionForProduct(Product $product, ?int $preferredCollectionId = null): ?array
    {
        $collections = $product->relationLoaded('collections')
            ? $product->collections
            : $product->collections()->select('collections.id', 'collections.name')->orderByPivot('sort_order')->get();

        if ($collections->isEmpty()) {
            return null;
        }

        if ($preferredCollectionId) {
            $match = $collections->firstWhere('id', $preferredCollectionId);
            if ($match) {
                return ['id' => (int) $match->id, 'name' => $match->name];
            }
        }

        $first = $collections->first();

        return ['id' => (int) $first->id, 'name' => $first->name];
    }

    private function productThumbnailUrl(Product $product): ?string
    {
        $media = $product->getEffectiveMedia();
        if ($media === []) {
            return null;
        }

        $first = $media[0];
        if (is_string($first)) {
            return $first !== '' ? $first : null;
        }

        if (is_array($first)) {
            $url = $first['url'] ?? $first['path'] ?? null;

            return is_string($url) && $url !== '' ? $url : null;
        }

        return null;
    }

    private function quotaPeriodStart(int $periodDays): \Illuminate\Support\Carbon
    {
        return now()->subDays(max(1, $periodDays) - 1)->startOfDay();
    }

    /**
     * @return list<string>
     */
    private function quotaCountedStatuses(): array
    {
        return [
            AffiliateSampleRequest::STATUS_PENDING,
            AffiliateSampleRequest::STATUS_APPROVED,
            AffiliateSampleRequest::STATUS_SHIPPED,
            AffiliateSampleRequest::STATUS_DELIVERED,
        ];
    }
}
