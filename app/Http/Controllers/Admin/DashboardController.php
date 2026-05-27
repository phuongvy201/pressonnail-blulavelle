<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateApplication;
use App\Models\AffiliateSampleRequest;
use App\Models\Order;
use App\Models\User;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalRoles = Role::count();
        $totalOrders = Order::count();

        $pendingAffiliateApplicationsCount = AffiliateApplication::query()
            ->where('status', AffiliateApplication::STATUS_PENDING)
            ->count();

        $recentAffiliateApplications = AffiliateApplication::query()
            ->where('status', AffiliateApplication::STATUS_PENDING)
            ->latest()
            ->take(8)
            ->get();

        $pendingSampleRequestsCount = AffiliateSampleRequest::query()
            ->where('status', AffiliateSampleRequest::STATUS_PENDING)
            ->count();

        $recentSampleRequests = AffiliateSampleRequest::query()
            ->where('status', AffiliateSampleRequest::STATUS_PENDING)
            ->with(['affiliate:id,code,display_name', 'product:id,name'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalRoles',
            'totalOrders',
            'pendingAffiliateApplicationsCount',
            'recentAffiliateApplications',
            'pendingSampleRequestsCount',
            'recentSampleRequests',
        ));
    }
}
