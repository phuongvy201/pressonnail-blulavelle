@extends('layouts.creator')

@section('title', 'Create account — Creator application')

@section('content')
    <div class="mx-auto max-w-xl px-5 py-10 md:px-16">
        <a href="{{ route('creator.affiliate.apply.account') }}" class="text-sm font-semibold text-primary hover:underline">← Back</a>
        <h1 class="creator-font-headline mt-4 text-2xl font-bold text-[#0b1c30]">Create your account</h1>
        <p class="mt-2 text-sm text-[#404753]">
            Free shopper account on {{ config('app.name') }}. After signup you will submit your creator application — no seller role until approved.
        </p>

        <form method="post" action="{{ route('creator.affiliate.apply.register-account') }}" class="mt-8 space-y-5 rounded-2xl border border-[#bfc7d5] bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" type="text" required value="{{ old('name', $draft['full_name'] ?? '') }}"
                       class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email', $draft['contact_email'] ?? '') }}"
                       class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input id="password" name="password" type="password" required
                       class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
            </div>
            <button type="submit" class="btn-primary w-full rounded-xl py-3 text-sm font-semibold">Create account & verify email</button>
        </form>
        <p class="mt-4 text-center text-sm text-[#404753]">
            Already have an account?
            <a href="{{ route('creator.affiliate.apply.login') }}" class="font-semibold text-primary underline">Sign in</a>
        </p>
    </div>
@endsection
