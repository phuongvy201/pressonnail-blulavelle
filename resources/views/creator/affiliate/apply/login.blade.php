@extends('layouts.creator')

@section('title', 'Sign in — Creator application')

@section('content')
    <div class="mx-auto max-w-xl px-5 py-10 md:px-16">
        <a href="{{ route('creator.affiliate.apply.account') }}" class="text-sm font-semibold text-primary hover:underline">← Back</a>
        <h1 class="creator-font-headline mt-4 text-2xl font-bold text-[#0b1c30]">Sign in</h1>
        <p class="mt-2 text-sm text-[#404753]">Use your {{ config('app.name') }} customer account to submit your creator application.</p>

        <form method="post" action="{{ route('creator.affiliate.apply.login') }}" class="mt-8 space-y-5 rounded-2xl border border-[#bfc7d5] bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $draft['contact_email'] ?? '') }}" required autofocus
                       class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input id="password" type="password" name="password" required
                       class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-primary w-full rounded-xl py-3 text-sm font-semibold">Sign in & continue</button>
        </form>
        <p class="mt-4 text-center text-sm text-[#404753]">
            No account?
            <a href="{{ route('creator.affiliate.apply.register-account') }}" class="font-semibold text-primary underline">Create one</a>
        </p>
    </div>
@endsection
