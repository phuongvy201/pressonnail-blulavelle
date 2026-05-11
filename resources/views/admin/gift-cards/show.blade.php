@extends('layouts.admin')

@section('title', 'Gift Card Detail')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Gift Card Detail</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $giftCard->code }}</p>
        </div>
        <a href="{{ route('admin.gift-cards.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Back</a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500 uppercase">Current Balance</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format((float) $giftCard->balance, 2) }} {{ $giftCard->currency }}</p>
            <p class="text-sm text-gray-500 mt-1">Initial: {{ number_format((float) $giftCard->initial_balance, 2) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500 uppercase">Status</p>
            <p class="text-lg font-semibold mt-1 {{ $giftCard->is_active ? 'text-emerald-700' : 'text-rose-700' }}">{{ $giftCard->is_active ? 'Active' : 'Inactive' }}</p>
            <p class="text-sm text-gray-500 mt-1">Expires: {{ $giftCard->expires_at?->format('Y-m-d') ?: 'No expiry' }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500 uppercase">Contacts</p>
            <p class="text-sm text-gray-700 mt-1">Recipient: {{ $giftCard->recipient_email ?: '—' }}</p>
            <p class="text-sm text-gray-700">Purchaser: {{ $giftCard->purchaser_email ?: '—' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 mb-3">Toggle Active</h3>
            <form method="POST" action="{{ route('admin.gift-cards.toggle-active', $giftCard) }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium {{ $giftCard->is_active ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">
                    {{ $giftCard->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 mb-3">Adjust Balance</h3>
            <form method="POST" action="{{ route('admin.gift-cards.adjust-balance', $giftCard) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm text-gray-700 mb-1">New Balance</label>
                    <input type="number" name="new_balance" step="0.01" min="0" required value="{{ number_format((float) $giftCard->balance, 2, '.', '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Reason</label>
                    <input type="text" name="reason" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Manual adjustment reason">
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Save Adjustment</button>
            </form>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Transaction History</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Before</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">After</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Order</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($giftCard->transactions as $transaction)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $transaction->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ strtoupper($transaction->type) }}</td>
                            <td class="px-4 py-3 text-sm {{ (float)$transaction->amount >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ (float)$transaction->amount >= 0 ? '+' : '' }}{{ number_format((float) $transaction->amount, 2) }} {{ $transaction->currency }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $transaction->balance_before, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $transaction->balance_after, 2) }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($transaction->order)
                                    <a href="{{ route('admin.orders.show', $transaction->order) }}" class="text-blue-600 hover:text-blue-800">{{ $transaction->order->order_number }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No transactions.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
