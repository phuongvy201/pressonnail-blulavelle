@extends('layouts.admin')

@section('title', 'Add promo code')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Add promo code</h1>
            <p class="mt-1 text-sm text-gray-600">Create a new code: percentage or fixed amount, validity, email triggers</p>
        </div>
        <a href="{{ route('admin.promo-codes.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back
        </a>
    </div>

    <form action="{{ route('admin.promo-codes.store') }}" method="POST" class="space-y-6">
        @csrf
        @include('admin.promo-codes._form')
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.promo-codes.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
        </div>
    </form>
</div>
@endsection
