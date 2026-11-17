@extends('layouts.app')

@section('title', 'Support Ticket')
@section('robots', 'noindex, follow')

@section('content')
<div class="bg-gray-50 py-10">
  <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
    @if(session('success'))
      <div class="mb-6 p-4 rounded-lg bg-green-50 text-green-800 border border-green-200">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-800 border border-red-200">{{ session('error') }}</div>
    @endif
    @if($errors->any())
      <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-800 border border-red-200">
        <ul class="list-disc ml-5 space-y-1">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="bg-white rounded-2xl shadow p-6">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4-9 4-9-4zm0 6l9 4 9-4"/></svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900">Submit a Support Ticket</h1>
      </div>

      <form action="{{ route('support.ticket.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
            <input type="text" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 focus:border-blue-600 focus:outline-none" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 focus:border-blue-600 focus:outline-none" required>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
            <input type="text" name="subject" value="{{ old('subject') }}" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 focus:border-blue-600 focus:outline-none" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Order Number (optional)</label>
            <input type="text" name="order_number" value="{{ old('order_number') }}" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 focus:border-blue-600 focus:outline-none" placeholder="e.g. BP-123456">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
          <textarea name="message" rows="6" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 focus:border-blue-600 focus:outline-none" required>{{ old('message') }}</textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (optional)</label>
          <input type="file" name="attachment" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*,application/pdf,.doc,.docx,.txt">
          <p class="text-xs text-gray-500 mt-1">Max 5MB. Allowed: images, PDF, DOC/DOCX, TXT.</p>
        </div>

        <div class="pt-2">
          <button type="submit" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold">Submit Ticket</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
