<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AffiliateSampleRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreatorSampleProductController extends Controller
{
    public function __construct(
        private readonly AffiliateSampleRequestService $samples,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $affiliate = $this->affiliate();

        $validated = $request->validate([
            'q' => 'nullable|string|max:120',
            'collection_id' => 'nullable|integer|exists:collections,id',
            'limit' => 'nullable|integer|min:1|max:30',
        ]);

        $query = (string) ($validated['q'] ?? '');
        $collectionId = isset($validated['collection_id']) ? (int) $validated['collection_id'] : null;

        if (trim($query) !== '' && mb_strlen(trim($query)) < 2) {
            return response()->json(['data' => []]);
        }

        $data = $this->samples->searchSampleProductsForAffiliate(
            $affiliate,
            $query,
            $collectionId,
            (int) ($validated['limit'] ?? 30),
        );

        return response()->json(['data' => $data]);
    }

    public function show(Product $product): JsonResponse
    {
        $affiliate = $this->affiliate();
        $details = $this->samples->sampleProductDetailsForAffiliate($affiliate, $product);

        if ($details === null) {
            abort(404);
        }

        return response()->json($details);
    }

    private function affiliate()
    {
        $affiliate = auth()->user()?->affiliate;
        if (! $affiliate || ! $affiliate->is_active) {
            abort(403);
        }

        return $affiliate;
    }
}
