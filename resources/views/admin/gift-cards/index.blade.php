@extends('layouts.admin')

@section('title', 'Gift Cards')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Gift Cards</h1>
            <p class="mt-1 text-sm text-gray-600">Search and manage all gift card codes.</p>
        </div>
        <a href="{{ route('admin.gift-cards.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            Create Gift Card
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.gift-cards.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Code / recipient / purchaser"
               class="md:col-span-2 px-3 py-2 border border-gray-300 rounded-lg">
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <select name="balance" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All balance</option>
            <option value="positive" {{ request('balance') === 'positive' ? 'selected' : '' }}>Balance &gt; 0</option>
            <option value="zero" {{ request('balance') === 'zero' ? 'selected' : '' }}>Balance = 0</option>
        </select>
        <div class="md:col-span-4 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm">Filter</button>
            <a href="{{ route('admin.gift-cards.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Reset</a>
        </div>
    </form>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Recipient</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($giftCards as $giftCard)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $giftCard->code }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ number_format((float) $giftCard->balance, 2) }} {{ $giftCard->currency }}
                                <div class="text-xs text-gray-500">Init: {{ number_format((float) $giftCard->initial_balance, 2) }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $giftCard->recipient_email ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($giftCard->is_active)
                                    <span class="px-2 py-1 text-xs rounded-full bg-emerald-50 text-emerald-700">Active</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-rose-50 text-rose-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-sm">{{ $giftCard->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.gift-cards.show', $giftCard) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">No gift cards found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $giftCards->links() }}
        </div>
    </div>
</div>
@endsection
