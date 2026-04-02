<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Auth') - {{ config('app.name', 'Bluprinter') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-background-light font-display text-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-[480px] bg-white rounded-xl shadow-sm border border-primary/10 overflow-hidden relative z-0">
        @yield('content')
        <div class="h-2 w-full bg-gradient-to-r from-primary/40 via-primary to-primary/40"></div>
    </div>
    {{-- Background decoration --}}
    <div class="fixed top-0 left-0 w-full h-full -z-10 opacity-30 pointer-events-none" aria-hidden="true">
        <div class="absolute top-[-10%] left-[-5%] w-[40%] h-[40%] rounded-full bg-primary/20 blur-[100px]"></div>
        <div class="absolute bottom-[-10%] right-[-5%] w-[40%] h-[40%] rounded-full bg-primary/10 blur-[100px]"></div>
    </div>
    @stack('scripts')
</body>
</html>
