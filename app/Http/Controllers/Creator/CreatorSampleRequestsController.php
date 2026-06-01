<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\AffiliateSampleRequest;
use App\Models\Product;
use App\Services\AffiliateSampleRequestService;
use App\Support\AffiliateSetupStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatorSampleRequestsController extends Controller
{
    public function __construct(
        private readonly AffiliateSampleRequestService $samples,
    ) {}

    public function index(Request $request): View
    {
        $affiliate = auth()->user()->affiliate;

        $requests = AffiliateSampleRequest::query()
            ->where('affiliate_id', $affiliate->id)
            ->with(['product:id,name,slug'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('creator.sample-requests.index', [
            'affiliate' => $affiliate,
            'setup' => AffiliateSetupStatus::for($affiliate),
            'requests' => $requests,
            'quota' => $this->samples->quotaSummary($affiliate),
        ]);
    }

    public function create(): View
    {
        $affiliate = auth()->user()->affiliate;
        $quota = $this->samples->quotaSummary($affiliate);

        $initialProduct = null;
        if ($oldProductId = (int) old('product_id')) {
            $product = Product::query()->find($oldProductId);
            if ($product) {
                $initialProduct = $this->samples->sampleProductDetailsForAffiliate($affiliate, $product);
            }
        }

        return view('creator.sample-requests.create', [
            'affiliate' => $affiliate,
            'setup' => AffiliateSetupStatus::for($affiliate),
            'quota' => $quota,
            'hasSampleProducts' => $this->samples->affiliateHasSampleProductsAvailable($affiliate),
            'filterCollections' => $this->samples->sampleFilterCollectionsForAffiliate($affiliate),
            'initialProduct' => $initialProduct,
            'sizePresets' => AffiliateSampleRequest::SIZE_PRESETS,
            'affiliateTier' => $affiliate->tier ?: 'basic',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $affiliate = auth()->user()->affiliate;

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'size_preset' => 'nullable|string|in:'.implode(',', AffiliateSampleRequest::SIZE_PRESETS),
            'quantity' => 'nullable|integer|min:1|max:10',
            'shipping_name' => 'required|string|max:255',
            'shipping_phone' => 'nullable|string|max:64',
            'shipping_address' => 'required|string|max:500',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_city' => 'required|string|max:120',
            'shipping_state' => 'nullable|string|max:64',
            'shipping_postal_code' => 'required|string|max:32',
            'shipping_country' => 'required|string|size:2',
            'creator_notes' => 'nullable|string|max:2000',
        ]);

        $sampleRequest = $this->samples->submit($affiliate, $request->user(), $validated);

        $message = $sampleRequest->isPending()
            ? 'Sample request submitted. Our team will review it shortly.'
            : 'Sample request approved. Your sample order is being processed.';

        return redirect()
            ->route('creator.sample-requests.show', $sampleRequest)
            ->with('success', $message);
    }

    public function show(AffiliateSampleRequest $sampleRequest): View
    {
        $affiliate = auth()->user()->affiliate;
        if ((int) $sampleRequest->affiliate_id !== (int) $affiliate->id) {
            abort(404);
        }

        $sampleRequest->load(['product', 'productVariant', 'order:id,order_number,tracking_number,status']);

        return view('creator.sample-requests.show', [
            'affiliate' => $affiliate,
            'setup' => AffiliateSetupStatus::for($affiliate),
            'sampleRequest' => $sampleRequest,
            'quota' => $this->samples->quotaSummary($affiliate),
        ]);
    }
}
