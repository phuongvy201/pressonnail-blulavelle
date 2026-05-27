@extends('layouts.admin')

@section('title', 'Sample requests')

@section('content')
<div class="max-w-6xl mx-auto py-8">
    <h1 class="text-2xl font-bold text-gray-900">Affiliate sample requests</h1>
    <p class="mt-1 text-sm text-gray-600">Review, approve, and fulfill creator sample orders.</p>

    @if (session('success'))
        <div class="mt-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form method="get" class="mt-6 flex flex-wrap gap-3 items-end">
        <div>
            <label for="status" class="block text-xs font-medium text-gray-600">Status</label>
            <select name="status" id="status" class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">All</option>
                @foreach (\App\Models\AffiliateSampleRequest::STATUSES as $s)
                    <option value="{{ $s }}" @selected($statusFilter === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Creator</th>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Submitted</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($requests as $req)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">#{{ $req->id }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{ $req->affiliate?->display_name ?? $req->user?->name }}</span>
                            <span class="block text-xs text-gray-500 font-mono">{{ $req->affiliate?->code }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $req->product?->name ?? '—' }}</td>
                        <td class="px-4 py-3 capitalize">{{ $req->status }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $req->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.sample-requests.show', $req) }}" class="text-blue-600 hover:underline font-medium">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No sample requests.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($requests->hasPages())
            <div class="border-t px-4 py-3">{{ $requests->links() }}</div>
        @endif
    </div>
</div>
@endsection
