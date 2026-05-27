@extends('layouts.admin')

@section('title', 'Affiliate applications')

@section('content')
<div class="max-w-7xl mx-auto py-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">KOC / Affiliate applications</h1>
            <p class="text-gray-600">Đơn đăng ký từ creator portal — xem chi tiết và tạo affiliate trong <a href="{{ route('admin.affiliates.index') }}" class="text-sky-600 hover:underline">Affiliates</a> khi duyệt.</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('admin.affiliate-applications.index', ['status' => 'all']) }}" class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $statusFilter === 'all' ? 'bg-sky-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">All</a>
        <a href="{{ route('admin.affiliate-applications.index', ['status' => 'pending']) }}" class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Pending</a>
        <a href="{{ route('admin.affiliate-applications.index', ['status' => 'approved']) }}" class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $statusFilter === 'approved' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Approved</a>
        <a href="{{ route('admin.affiliate-applications.index', ['status' => 'rejected']) }}" class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $statusFilter === 'rejected' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Rejected</a>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Ref code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Submitted</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($applications as $app)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $app->full_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $app->email }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">{{ $app->proposed_ref_code }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold
                                    @if($app->status === \App\Models\AffiliateApplication::STATUS_APPROVED) bg-green-100 text-green-800
                                    @elseif($app->status === \App\Models\AffiliateApplication::STATUS_REJECTED) bg-red-100 text-red-800
                                    @else bg-amber-100 text-amber-900 @endif">
                                    {{ ucfirst($app->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $app->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('admin.affiliate-applications.show', $app) }}" class="text-sky-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">No applications.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $applications->links() }}
        </div>
    </div>
</div>
@endsection
