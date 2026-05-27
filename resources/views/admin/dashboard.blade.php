@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    @if(($pendingAffiliateApplicationsCount ?? 0) > 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 sm:px-6 sm:flex sm:items-center sm:justify-between gap-4">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800 font-bold">{{ $pendingAffiliateApplicationsCount }}</span>
                <div>
                    <p class="font-semibold text-amber-950">Có đơn đăng ký affiliate / KOC đang chờ xử lý</p>
                    <p class="text-sm text-amber-900/90 mt-0.5">Xem danh sách và chi tiết trong mục Affiliate requests.</p>
                </div>
            </div>
            <a href="{{ route('admin.affiliate-applications.index', ['status' => 'pending']) }}" class="mt-3 sm:mt-0 inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 shrink-0">
                Mở danh sách
            </a>
        </div>
    @endif

    @if(($pendingSampleRequestsCount ?? 0) > 0)
        <div class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-4 sm:px-6">
            <div class="sm:flex sm:items-start sm:justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-800 font-bold">{{ $pendingSampleRequestsCount }}</span>
                    <div>
                        <p class="font-semibold text-violet-950">Có yêu cầu sample creator đang chờ duyệt</p>
                        <p class="text-sm text-violet-900/90 mt-0.5">Duyệt và tạo đơn sample trong mục Sample requests.</p>
                    </div>
                </div>
                <a href="{{ route('admin.sample-requests.index', ['status' => 'pending']) }}" class="mt-3 sm:mt-0 inline-flex items-center justify-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 shrink-0">
                    Mở sample requests
                </a>
            </div>
            @if ($recentSampleRequests->isNotEmpty())
                <ul class="mt-4 divide-y divide-violet-200/60 border-t border-violet-200/60 pt-3 text-sm">
                    @foreach ($recentSampleRequests as $req)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <span>
                                <span class="font-mono text-violet-800">#{{ $req->id }}</span>
                                · {{ $req->affiliate?->display_name ?? $req->affiliate?->code }}
                                · {{ $req->product?->name ?? '—' }}
                            </span>
                            <a href="{{ route('admin.sample-requests.show', $req) }}" class="font-semibold text-violet-700 hover:underline">Review →</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <!-- Welcome Section -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Welcome back, {{ Auth::user()->name }}!</h1>
                <p class="text-gray-600">Here's what's happening with your admin panel today.</p>
            </div>
            <div class="hidden sm:block">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Users Card -->
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $totalUsers ?? 0 }}</p>
                        <p class="text-sm text-green-600 mt-1">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                            </svg>
                            +12% from last month
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles Card -->
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Roles</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $totalRoles ?? 0 }}</p>
                        <p class="text-sm text-green-600 mt-1">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                            </svg>
                            +3 new this week
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-xl">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Card -->
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Orders</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $totalOrders ?? 0 }}</p>
                        <p class="text-sm text-purple-600 mt-1">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            Trending up
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-xl">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Affiliate requests (pending)</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $pendingAffiliateApplicationsCount ?? 0 }}</p>
                        <a href="{{ route('admin.affiliate-applications.index', ['status' => 'pending']) }}" class="text-sm text-amber-700 font-medium mt-1 inline-block hover:underline">Review →</a>
                    </div>
                    <div class="p-3 bg-amber-100 rounded-xl">
                        <svg class="w-8 h-8 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Quick Actions</h2>
            <p class="text-gray-600 mt-1">Manage your system with these quick actions</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('admin.users.create') }}" 
                   class="group flex flex-col items-center p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all duration-300 transform hover:scale-105">
                    <div class="p-3 bg-blue-100 rounded-full mb-4 group-hover:bg-blue-200 transition-colors">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-blue-600">Add User</h3>
                    <p class="text-sm text-gray-600 text-center mt-1">Create new user account</p>
                </a>

                <a href="{{ route('admin.roles.create') }}" 
                   class="group flex flex-col items-center p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all duration-300 transform hover:scale-105">
                    <div class="p-3 bg-green-100 rounded-full mb-4 group-hover:bg-green-200 transition-colors">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-green-600">Add Role</h3>
                    <p class="text-sm text-gray-600 text-center mt-1">Create new role</p>
                </a>

                <a href="{{ route('admin.users.index') }}" 
                   class="group flex flex-col items-center p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-purple-500 hover:bg-purple-50 transition-all duration-300 transform hover:scale-105">
                    <div class="p-3 bg-purple-100 rounded-full mb-4 group-hover:bg-purple-200 transition-colors">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-purple-600">Manage Users</h3>
                    <p class="text-sm text-gray-600 text-center mt-1">View all users</p>
                </a>

                <a href="{{ route('admin.roles.index') }}" 
                   class="group flex flex-col items-center p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-orange-500 hover:bg-orange-50 transition-all duration-300 transform hover:scale-105">
                    <div class="p-3 bg-orange-100 rounded-full mb-4 group-hover:bg-orange-200 transition-colors">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-orange-600">Manage Roles</h3>
                    <p class="text-sm text-gray-600 text-center mt-1">View all roles</p>
                </a>
            </div>
        </div>
    </div>

    <!-- Affiliate / KOC requests (pending) -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="p-6 border-b border-gray-200 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Affiliate / KOC requests</h2>
                <p class="text-gray-600 mt-1">Đơn đăng ký gần nhất đang ở trạng thái pending</p>
            </div>
            <a href="{{ route('admin.affiliate-applications.index') }}" class="text-sm font-semibold text-sky-600 hover:underline">View all</a>
        </div>
        <div class="p-6">
            @forelse(($recentAffiliateApplications ?? collect()) as $app)
                <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg mb-3 last:mb-0">
                    <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $app->full_name }}</p>
                        <p class="text-sm text-gray-600 truncate">{{ $app->email }} · <span class="font-mono">{{ $app->proposed_ref_code }}</span></p>
                    </div>
                    <span class="text-xs text-gray-500 shrink-0">{{ $app->created_at?->diffForHumans() }}</span>
                    <a href="{{ route('admin.affiliate-applications.show', $app) }}" class="text-sm text-sky-600 hover:underline shrink-0">Open</a>
                </div>
            @empty
                <p class="text-sm text-gray-500 text-center py-6">Không có đơn pending.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection