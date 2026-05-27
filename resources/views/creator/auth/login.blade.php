@extends('layouts.creator')

@section('title', 'Sign in — Creator portal')

@section('content')
    <div class="mx-auto max-w-md px-5 py-12 md:px-16">
        <h1 class="creator-font-headline text-2xl font-bold text-[#0b1c30]">Creator sign in</h1>
        <p class="mt-2 text-sm text-[#404753]">
            Use the same email and password as the main {{ config('app.name') }} store. One account works on both the shop and this creator portal.
        </p>

        @if (session('error'))
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        <form method="post" action="{{ route('creator.login') }}" class="mt-8 space-y-5 rounded-2xl border border-[#bfc7d5] bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input id="password" type="password" name="password" required
                       class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-primary w-full rounded-xl py-3 text-sm font-semibold">Sign in</button>
        </form>

        <p class="mt-6 text-center text-sm text-[#404753]">
            Not a creator yet?
            <a href="{{ route('creator.affiliate.apply') }}" class="font-semibold text-primary underline">Apply to the program</a>
        </p>
    </div>
@endsection
