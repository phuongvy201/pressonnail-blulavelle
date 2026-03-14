@extends('layouts.admin')

@section('title', 'Live Chat')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Live Chat</h1>
            <p class="mt-1 text-sm text-gray-600">Conversations between customers and sellers</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Customer</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Last message</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Updated</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($conversations as $conv)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="font-medium text-gray-900">{{ $conv->customer_name }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $conv->customer_email ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                            @if($conv->messages->isNotEmpty())
                                {{ Str::limit($conv->messages->first()->body, 50) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $conv->updated_at->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.live-chat.show', $conv) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                                Open chat
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">No conversations yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($conversations->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
