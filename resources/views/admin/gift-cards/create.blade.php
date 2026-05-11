@extends('layouts.admin')

@section('title', 'Create Gift Card')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Create Gift Card</h1>
            <p class="mt-1 text-sm text-gray-600">Create a manual gift card from admin.</p>
        </div>
        <a href="{{ route('admin.gift-cards.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Back</a>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.gift-cards.store') }}" class="bg-white border border-gray-200 rounded-lg p-6 space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Add to existing code (optional)</label>
                <input type="text" name="existing_code" value="{{ old('existing_code') }}" placeholder="e.g. GC-XXXX-XXXX-XXXX — leave empty to create a new card" class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm">
                <p class="mt-1 text-xs text-gray-500">If filled, the amount is added to this card’s balance (top-up). Otherwise a new gift card code is generated.</p>
                @error('existing_code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Currency *</label>
                <input type="text" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg uppercase">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Email</label>
                <input type="email" name="recipient_email" value="{{ old('recipient_email') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Name</label>
                <input type="text" name="recipient_name" value="{{ old('recipient_name') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purchaser Email</label>
                <input type="email" name="purchaser_email" value="{{ old('purchaser_email') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expires At</label>
                <input type="date" name="expires_at" value="{{ old('expires_at') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
            <textarea name="note" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('note') }}</textarea>
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="rounded border-gray-300">
            Active immediately
        </label>

        <div class="pt-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Create Gift Card</button>
        </div>
    </form>
</div>
@endsection
