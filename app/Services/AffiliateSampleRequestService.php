<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateSampleRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\AffiliateTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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

    /**
     * Products a creator may request, filtered by tier and stock.
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    public function sampleEligibleProductsForAffiliate(Affiliate $affiliate)
    {
        $tier = AffiliateTier::normalize($affiliate->tier ?: AffiliateTier::BASIC);

        return $this->sampleEligibleProductsQuery()
            ->with(['variants' => fn ($q) => $q->where('quantity', '>', 0)->orderBy('variant_name')])
            ->orderBy('name')
            ->get()
            ->filter(function (Product $product) use ($affiliate, $tier) {
                if (! $product->isSampleEligibleForTier($tier)) {
                    return false;
                }

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
