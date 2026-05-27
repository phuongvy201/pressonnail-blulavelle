<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateApplication;
use App\Services\AffiliateApplicationReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AffiliateApplicationAdminController extends Controller
{
    public function __construct(
        private readonly AffiliateApplicationReviewService $reviewService,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $q = AffiliateApplication::query()->with('user')->latest();

        if (in_array($status, [
            AffiliateApplication::STATUS_PENDING,
            AffiliateApplication::STATUS_APPROVED,
            AffiliateApplication::STATUS_REJECTED,
        ], true)) {
            $q->where('status', $status);
        }

        $applications = $q->paginate(25)->withQueryString();

        return view('admin.affiliate-applications.index', [
            'applications' => $applications,
            'statusFilter' => $status,
        ]);
    }

    public function show(AffiliateApplication $affiliateApplication): View
    {
        $affiliateApplication->load('user');

        return view('admin.affiliate-applications.show', [
            'application' => $affiliateApplication,
        ]);
    }

    public function approve(AffiliateApplication $affiliateApplication): RedirectResponse
    {
        try {
            $affiliate = $this->reviewService->approve($affiliateApplication, Auth::user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', "Application approved. Affiliate profile «{$affiliate->code}» is active. Approval email sent to the creator.");
    }

    public function reject(Request $request, AffiliateApplication $affiliateApplication): RedirectResponse
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:2000',
        ]);

        try {
            $this->reviewService->reject(
                $affiliateApplication,
                Auth::user(),
                $request->input('admin_note')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Application rejected.');
    }
}
