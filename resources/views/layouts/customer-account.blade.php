@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-background-light font-display text-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if (session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <p class="text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
                <span class="material-symbols-outlined text-red-600">error</span>
                <p class="text-red-800">{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-red-600 shrink-0">error</span>
                    <div>
                        <h3 class="font-semibold text-red-800">{{ __('Please fix the following errors:') }}</h3>
                        <ul class="list-disc list-inside text-sm text-red-700 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8">
            @include('customer.profile.partials.sidebar')

            <div class="flex-1 min-w-0 space-y-8">
                @yield('account-content')
            </div>
        </div>
    </div>
</div>
@endsection
