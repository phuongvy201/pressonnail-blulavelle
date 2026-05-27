<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Shop;
use App\Support\AffiliateReferralLink;
use App\Support\AffiliateSetupStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatorProductLinksController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'search' => 'nullable|string|max:200',
            'shop_id' => 'nullable|integer|exists:shops,id',
            'collection_id' => 'nullable|integer|exists:collections,id',
        ]);

        $affiliate = auth()->user()->affiliate;

        $productsQuery = $this->applyProductLinkFilters(
            $this->affiliateEligibleProductsQuery(),
            $request
        );

        $products = $productsQuery
            ->orderByDesc('updated_at')
            ->paginate(24)
            ->withQueryString();

        $productLinks = [];
        foreach ($products as $product) {
            $productLinks[$product->id] = AffiliateReferralLink::productUrl($product, $affiliate);
        }

        $eligibleScope = fn (): Builder => $this->affiliateEligibleProductsQuery();

        $shops = Shop::query()
            ->whereIn('id', (clone $eligibleScope())->whereNotNull('shop_id')->select('shop_id'))
            ->orderBy('shop_name')
            ->get(['id', 'shop_name']);

        $collections = Collection::query()
            ->whereHas('products', fn (Builder $q) => $this->applyAffiliateEligibleConstraints($q))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('creator.product-links.index', [
            'affiliate' => $affiliate,
            'setup' => AffiliateSetupStatus::for($affiliate),
            'products' => $products,
            'productLinks' => $productLinks,
            'shops' => $shops,
            'collections' => $collections,
            'shopHomeUrl' => AffiliateReferralLink::homeUrl($affiliate),
        ]);
    }

    protected function affiliateEligibleProductsQuery(): Builder
    {
        return Product::query()
            ->affiliateEligible()
            ->availableForDisplay()
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    protected function applyAffiliateEligibleConstraints(Builder $query): Builder
    {
        return $query
            ->affiliateEligible()
            ->availableForDisplay()
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    protected function applyProductLinkFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            if ($search !== '') {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            }
        }

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->integer('shop_id'));
        }

        if ($request->filled('collection_id')) {
            $collectionId = $request->integer('collection_id');
            $query->whereHas('collections', function (Builder $q) use ($collectionId) {
                $q->where('collections.id', $collectionId);
            });
        }

        return $query;
    }
}
