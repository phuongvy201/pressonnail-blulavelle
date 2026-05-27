<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateSampleRequest;
use App\Services\AffiliateSampleRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateSampleRequestAdminController extends Controller
{
    public function __construct(
        private readonly AffiliateSampleRequestService $samples,
    ) {}

    public function index(Request $request): View
    {
        $query = AffiliateSampleRequest::query()
            ->with(['affiliate:id,code,display_name,tier', 'product:id,name', 'user:id,name,email'])
            ->orderByDesc('created_at');

        if ($request->filled('status') && in_array($request->status, AffiliateSampleRequest::STATUSES, true)) {
            $query->where('status', $request->status);
        }

        return view('admin.sample-requests.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function show(AffiliateSampleRequest $sampleRequest): View
    {
        $sampleRequest->load(['affiliate.user', 'product', 'productVariant', 'order', 'reviewer']);

        return view('admin.sample-requests.show', [
            'sampleRequest' => $sampleRequest,
        ]);
    }

    public function approve(Request $request, AffiliateSampleRequest $sampleRequest): RedirectResponse
    {
        $data = $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
            'create_order' => 'nullable|boolean',
        ]);

        $this->samples->approve(
            $sampleRequest,
            $request->user(),
            $data['admin_notes'] ?? null,
            $request->boolean('create_order', true)
        );

        return redirect()
            ->route('admin.sample-requests.show', $sampleRequest)
            ->with('success', 'Sample request approved.');
    }

    public function reject(Request $request, AffiliateSampleRequest $sampleRequest): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $this->samples->reject(
            $sampleRequest,
            $request->user(),
            $data['rejection_reason'],
            $data['admin_notes'] ?? null
        );

        return redirect()
            ->route('admin.sample-requests.show', $sampleRequest)
            ->with('success', 'Sample request rejected.');
    }

    public function ship(Request $request, AffiliateSampleRequest $sampleRequest): RedirectResponse
    {
        $data = $request->validate([
            'tracking_number' => 'nullable|string|max:255',
        ]);

        $this->samples->markShipped($sampleRequest, $data['tracking_number'] ?? null);

        return redirect()
            ->route('admin.sample-requests.show', $sampleRequest)
            ->with('success', 'Marked as shipped.');
    }

    public function deliver(AffiliateSampleRequest $sampleRequest): RedirectResponse
    {
        $this->samples->markDelivered($sampleRequest);

        return redirect()
            ->route('admin.sample-requests.show', $sampleRequest)
            ->with('success', 'Marked as delivered.');
    }
}
