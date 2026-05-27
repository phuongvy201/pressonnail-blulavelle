@extends('layouts.admin')

@section('title', 'Edit affiliate')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Edit affiliate: {{ $affiliate->code }}</h1>
        <a href="{{ route('admin.affiliates.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Back to list</a>
    </div>
    <form method="POST" action="{{ route('admin.affiliates.update', $affiliate) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.affiliates._form', ['affiliate' => $affiliate])
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.affiliates.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update</button>
        </div>
    </form>
</div>
@endsection
