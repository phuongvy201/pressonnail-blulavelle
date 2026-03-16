<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bluprinter') }} - Admin</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js for interactivity -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        /* Custom Scrollbar for Sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: #f3f4f6;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #3b82f6, #8b5cf6);
            border-radius: 10px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #2563eb, #7c3aed);
        }
        /* Rung icon Live Chat khi có tin nhắn mới (như điện thoại đổ chuông) */
        @keyframes adminLiveChatRing {
            0%, 100% { transform: translateX(0) rotate(0deg); }
            10% { transform: translateX(-2px) rotate(-8deg); }
            20% { transform: translateX(2px) rotate(8deg); }
            30% { transform: translateX(-2px) rotate(-6deg); }
            40% { transform: translateX(2px) rotate(6deg); }
            50% { transform: translateX(-1px) rotate(-4deg); }
            60% { transform: translateX(1px) rotate(4deg); }
            70% { transform: translateX(-1px) rotate(-2deg); }
            80% { transform: translateX(1px) rotate(2deg); }
            90% { transform: translateX(0) rotate(0deg); }
        }
        #admin-live-chat-toggle-wrap.admin-live-chat-ring .admin-live-chat-ring-target {
            animation: adminLiveChatRing 0.5s ease-in-out 6 forwards;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50 overflow-x-hidden" style="font-family: 'Inter', sans-serif;" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex overflow-x-hidden max-w-full">
        <!-- Mobile sidebar overlay -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 lg:hidden">
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="sidebarOpen = false"></div>
        </div>

        <!-- Sidebar -->
        <div class="hidden lg:flex lg:flex-shrink-0">
            <div class="flex flex-col w-64 bg-white border-r border-gray-200 shadow-sm fixed left-0 top-0 h-screen overflow-y-auto sidebar-scroll z-40">
                <!-- Logo -->
                <div class="flex items-center h-16 px-6 border-b border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 overflow-hidden">
                                <img src="{{ asset('storage/images/logo (3).png') }}" 
                                     alt="Blu Lavelle Logo" 
                                     class="w-full h-full object-contain"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <!-- Fallback SVG if image fails to load -->
                                <div class="w-full h-full bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg" style="display: none;">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="ml-3">
                            <h1 class="text-lg font-bold text-gray-900">{{ config('app.name', 'Bluprinter') }}</h1>
                            <p class="text-xs text-gray-500">Admin Panel</p>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-1">
                    {{-- Dashboard link - Different for admin vs seller vs ad-partner --}}
                    @if(auth()->user()->hasRole('admin'))
                        <a href="{{ route('admin.dashboard') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                            </svg>
                            Dashboard
                        </a>
                    @elseif(auth()->user()->hasRole('seller'))
                        <a href="{{ route('admin.seller.dashboard') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.seller.dashboard') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.seller.dashboard') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                            </svg>
                            Dashboard
                        </a>
                   
                    @endif
                    
                    {{-- Admin Only Menu Items --}}
                    @if(auth()->user()->hasRole('admin'))
                        <a href="{{ route('admin.users.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.users.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            Users
                        </a>
                        
                        <a href="{{ route('admin.roles.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.roles.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.roles.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            Roles
                        </a>

                        <a href="{{ route('admin.seller-applications.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.seller-applications.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.seller-applications.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7M5 7h6m-6 4h3"></path>
                            </svg>
                            Seller Applications
                        </a>

                        <a href="{{ route('admin.analytics.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.analytics.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.analytics.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Google Analytics
                        </a>

                        <a href="{{ route('admin.settings.analytics.edit') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.settings.analytics.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.settings.analytics.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Analytics Settings
                        </a>

                    @endif
                    
                    <!-- Product Management Section - Admin and Seller Only -->
                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller'))
                    <div class="mt-6">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            {{ auth()->user()->hasRole('admin') ? 'Product Management' : 'My Products' }}
                        </h3>
                        
                        {{-- Categories - Admin Only --}}
                        @if(auth()->user()->hasRole('admin'))
                            <a href="{{ route('admin.categories.index') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.categories.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.categories.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                Categories
                            </a>
                        @endif
                        
                        {{-- Shops - Admin Only --}}
                        @if(auth()->user()->hasRole('admin'))
                            <a href="{{ route('admin.shops.index') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.shops.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.shops.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Shops
                            </a>
                        @elseif(auth()->user()->hasRole('seller'))
                            <a href="{{ route('seller.shop.dashboard') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('seller.shop.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('seller.shop.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Shop Profile
                            </a>
                        @endif
                        
                        {{-- Templates - Both Admin and Seller --}}
                        <a href="{{ route('admin.product-templates.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.product-templates.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.product-templates.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ auth()->user()->hasRole('admin') ? 'Templates' : 'My Templates' }}
                        </a>
                        
                        {{-- Products - Both Admin and Seller --}}
                        <a href="{{ route('admin.products.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.products.*') && !request()->routeIs('admin.products.show-delete-from-gmc') && !request()->routeIs('admin.reviews.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.products.*') && !request()->routeIs('admin.products.show-delete-from-gmc') && !request()->routeIs('admin.reviews.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            {{ auth()->user()->hasRole('admin') ? 'Products' : 'My Products' }}
                        </a>
                        {{-- Reviews (danh sách) - Admin + Seller --}}
                        <a href="{{ route('admin.reviews.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.reviews.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.reviews.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                            Reviews
                        </a>
                        {{-- Import Reviews - Admin + Seller --}}
                        <a href="{{ route('admin.reviews.import') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.reviews.import*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.reviews.import*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Import Reviews
                        </a>
                        
                        {{-- Delete from GMC - Admin Only --}}
                        @if(auth()->user()->hasRole('admin'))
                            <a href="{{ route('admin.products.show-delete-from-gmc') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.products.show-delete-from-gmc') ? 'bg-red-50 text-red-700 border-r-2 border-red-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.products.show-delete-from-gmc') ? 'text-red-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                               Remove Product GMC
                            </a>
                        @endif
                        
                        {{-- Collections - Both Admin and Seller --}}
                        <a href="{{ route('admin.collections.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.collections.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.collections.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            {{ auth()->user()->hasRole('admin') ? 'Collections' : 'My Collections' }}
                        </a>
                    </div>
                    @endif

                    <!-- Orders Section -->
                    <div class="mt-6">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            @if(auth()->user()->hasRole('admin'))
                                Sales Management
                            @elseif(auth()->user()->hasRole('ad-partner'))
                                Orders Management
                            @else
                                My Sales
                            @endif
                        </h3>
                        
                        {{-- Orders - Admin + Ad-Partner --}}
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('ad-partner'))
                            <a href="{{ route('admin.orders.index') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.orders.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.orders.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                <span class="flex items-center">
                                    Orders
                                    @if(isset($sidebarPendingOrders) && $sidebarPendingOrders > 0)
                                        <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                            {{ $sidebarPendingOrders }}
                                        </span>
                                    @endif
                                </span>
                            </a>
                            <a href="{{ route('admin.returns.index') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.returns.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.returns.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-7 7l3 3 4-6"></path>
                                </svg>
                                <span class="flex items-center">
                                    Returns
                                    @if(isset($sidebarPendingReturns) && $sidebarPendingReturns > 0)
                                        <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                            {{ $sidebarPendingReturns }}
                                        </span>
                                    @endif
                                </span>
                            </a>
                        @else
                            <a href="{{ route('seller.orders.index') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('seller.orders.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('seller.orders.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                My Orders
                            </a>
                        @endif
                    </div>

                    <!-- Shipping Management Section - Admin Only -->
                    @if(auth()->user()->hasRole('admin'))
                    <div class="mt-6">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            Shipping Management
                        </h3>
                        
                        <a href="{{ route('admin.shipping-zones.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.shipping-zones.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.shipping-zones.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Shipping Zones
                        </a>
                        
                        <a href="{{ route('admin.shipping-rates.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.shipping-rates.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.shipping-rates.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Shipping Rates
                        </a>

                        <a href="{{ route('admin.promo-codes.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.promo-codes.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.promo-codes.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            Promo Codes
                        </a>

                        <a href="{{ route('admin.live-chat.index') }}" 
                           class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.live-chat.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <span class="relative flex-shrink-0">
                                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('admin.live-chat.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                @if(($liveChatUnreadCount ?? 0) > 0)
                                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold">{{ $liveChatUnreadCount > 99 ? '99+' : $liveChatUnreadCount }}</span>
                                @endif
                            </span>
                            Live Chat
                        </a>
                    </div>
                    @endif

                    <!-- Content Management Section - Admin and Seller Only -->
                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller'))
                    <div class="mt-6">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            Content Management
                        </h3>
                        
                        {{-- Pages - Admin Only --}}
                        @if(auth()->user()->hasRole('admin'))
                            <a href="{{ route('admin.pages.index') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.pages.*') && !request()->routeIs('admin.site.home-preview') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.pages.*') && !request()->routeIs('admin.site.home-preview') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                Pages
                            </a>
                            <a href="{{ route('admin.site.home-preview') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.site.home-preview') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                               target="_blank" rel="noopener">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.site.home-preview') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Trang chủ (Preview & Chỉnh sửa)
                            </a>

                            <a href="{{ route('admin.api-token') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.api-token') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.api-token') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                                API Token
                            </a>
                        @endif
                        
                        {{-- Posts - Both Admin and Seller --}}
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller'))
                            <a href="{{ route('admin.posts.index') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.posts.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.posts.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                                {{ auth()->user()->hasRole('admin') ? 'Blog Posts' : 'My Posts' }}
                            </a>
                        @endif
                        
                        {{-- Post Categories - Admin Only --}}
                        @if(auth()->user()->hasRole('admin'))
                            <a href="{{ route('admin.post-categories.index') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.post-categories.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.post-categories.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                Post Categories
                            </a>
                            
                            <a href="{{ route('admin.post-tags.index') }}" 
                               class="group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('admin.post-tags.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.post-tags.*') ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                Post Tags
                            </a>
                        @endif
                    </div>
                    @endif
                </nav>
            </div>
        </div>

        <!-- Mobile sidebar -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-gray-900 to-gray-800 text-white lg:hidden">
            <div class="flex items-center justify-between h-16 px-6 bg-gray-900">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 overflow-hidden">
                            <img src="{{ asset('storage/images/logo (3).png') }}" 
                                 alt="Blu Lavelle Logo" 
                                 class="w-full h-full object-contain"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <!-- Fallback SVG if image fails to load -->
                            <div class="w-full h-full bg-blue-600 rounded-lg flex items-center justify-center" style="display: none;">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h1 class="text-lg font-bold text-white">{{ config('app.name', 'Bluprinter') }}</h1>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <nav class="px-4 py-6 space-y-2">
                {{-- Dashboard link - Different for admin vs seller vs ad-partner --}}
                @if(auth()->user()->hasRole('admin'))
                    <a href="{{ route('admin.dashboard') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>
                @elseif(auth()->user()->hasRole('seller'))
                    <a href="{{ route('admin.seller.dashboard') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.seller.dashboard') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>
                @endif
                
                {{-- Admin Only Menu Items --}}
                @if(auth()->user()->hasRole('admin'))
                    <a href="{{ route('admin.users.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Users
                    </a>
                    
                    <a href="{{ route('admin.roles.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.roles.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Roles
                    </a>

                    <a href="{{ route('admin.seller-applications.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.seller-applications.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7M5 7h6m-6 4h3"></path>
                        </svg>
                        Seller Applications
                    </a>
                    
                    <a href="{{ route('admin.categories.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.categories.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Categories
                    </a>
                    
                    <a href="{{ route('admin.products.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.products.*') && !request()->routeIs('admin.products.show-delete-from-gmc') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Products
                    </a>
                    
                    @if(auth()->user()->hasRole('admin'))
                        <a href="{{ route('admin.products.show-delete-from-gmc') }}" 
                           class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.products.show-delete-from-gmc') ? 'bg-red-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                           @click="sidebarOpen = false">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Xóa khỏi GMC
                        </a>
                    @endif

                    <a href="{{ route('admin.collections.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.collections.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Collections
                    </a>
                    
                    <a href="{{ route('admin.shops.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.shops.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Shops
                    </a>
                    
                    <a href="{{ route('admin.pages.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.pages.*') && !request()->routeIs('admin.site.home-preview') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Pages
                    </a>
                    <a href="{{ route('admin.site.home-preview') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.site.home-preview') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       target="_blank" rel="noopener" @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Trang chủ (Preview & Chỉnh sửa)
                    </a>

                    <a href="{{ route('admin.api-token') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.api-token') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        API Token
                    </a>
                    
                    <a href="{{ route('admin.posts.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.posts.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        Blog Posts
                    </a>
                    
                    <a href="{{ route('admin.post-categories.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.post-categories.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Post Categories
                    </a>
                    
                    <a href="{{ route('admin.post-tags.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.post-tags.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Post Tags
                    </a>
                    
                    
                    <a href="{{ route('admin.shipping-zones.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.shipping-zones.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Shipping Zones
                    </a>
                    
                    <a href="{{ route('admin.shipping-rates.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.shipping-rates.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Shipping Rates
                    </a>

                    <a href="{{ route('admin.promo-codes.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.promo-codes.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Promo Codes
                    </a>

                    <a href="{{ route('admin.live-chat.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.live-chat.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <span class="relative flex-shrink-0">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            @if(($liveChatUnreadCount ?? 0) > 0)
                                <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold">{{ $liveChatUnreadCount > 99 ? '99+' : $liveChatUnreadCount }}</span>
                            @endif
                        </span>
                        Live Chat
                    </a>
                @endif
                
                {{-- Seller Only Menu Items --}}
                @if(auth()->user()->hasRole('seller'))
                    <a href="{{ route('seller.shop.dashboard') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('seller.shop.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        My Shop
                    </a>
                    
                    <a href="{{ route('admin.product-templates.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.product-templates.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        My Templates
                    </a>
                    
                    <a href="{{ route('admin.products.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.products.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        My Products
                    </a>

                    <a href="{{ route('admin.collections.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.collections.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        My Collections
                    </a>
                    
                    <a href="{{ route('admin.posts.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.posts.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        My Posts
                    </a>
                @endif
                
                {{-- Orders - Admin + Ad-Partner (Mobile) --}}
                @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('ad-partner'))
                    <a href="{{ route('admin.orders.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.orders.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <span class="flex items-center">
                            Orders
                            @if(isset($sidebarPendingOrders) && $sidebarPendingOrders > 0)
                                <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    {{ $sidebarPendingOrders }}
                                </span>
                            @endif
                        </span>
                    </a>
                    <a href="{{ route('admin.returns.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.returns.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7l3 3 4-6"></path>
                        </svg>
                        <span class="flex items-center">
                            Returns
                            @if(isset($sidebarPendingReturns) && $sidebarPendingReturns > 0)
                                <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    {{ $sidebarPendingReturns }}
                                </span>
                            @endif
                        </span>
                    </a>
                @endif
                
                {{-- Post Management - Admin Only (Mobile) --}}
                @if(auth()->user()->hasRole('admin'))
                    <a href="{{ route('admin.post-categories.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.post-categories.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Post Categories
                    </a>
                    
                    <a href="{{ route('admin.post-tags.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.post-tags.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Post Tags
                    </a>
                    
                    <a href="{{ route('seller.orders.index') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('seller.orders.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
                       @click="sidebarOpen = false">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        My Orders
                    </a>
                @endif
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col lg:ml-64 overflow-x-hidden max-w-full">
            <!-- Top Navigation -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <!-- Mobile menu button -->
                        <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        
                        <div class="flex-1 lg:ml-0">
                            <div class="flex items-center">
                                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">@yield('title', 'Dashboard')</h1>
                                <div class="ml-4 hidden sm:block">
                                    @if(auth()->user()->hasRole('admin'))
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Admin
                                        </span>
                                    @elseif(auth()->user()->hasRole('ad-partner'))
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Ad Partner
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Seller
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <!-- Notifications -->
                            <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-6H4v6zM4 5h6V1H4v4zM15 3h5l-5-5v5z"></path>
                                </svg>
                            </button>
                            
                            <!-- User dropdown -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="flex items-center space-x-3 text-sm rounded-xl p-2 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-sm">
                                        <span class="text-white font-semibold text-sm">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                    </div>
                                    <div class="hidden sm:block text-left">
                                        <p class="text-gray-900 font-medium">{{ Auth::user()->name }}</p>
                                        <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <div x-show="open" 
                                     @click.away="open = false"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50">
                                    <div class="px-4 py-3 border-b border-gray-100">
                                        <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                                        <p class="text-sm text-gray-500">{{ Auth::user()->email }}</p>
                                    </div>
                                    <div class="py-1">
                                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            Profile
                                        </a>
                                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Settings
                                        </a>
                                    </div>
                                    <div class="border-t border-gray-100">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                                </svg>
                                                Sign out
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden max-w-full">
                @if (session('success'))
                    <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg" x-data="{ show: true }" x-show="show" x-transition>
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">{{ session('success') }}</p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button @click="show = false" class="text-green-400 hover:text-green-600">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg" x-data="{ show: true }" x-show="show" x-transition>
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">{{ session('error') }}</p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button @click="show = false" class="text-red-400 hover:text-red-600">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller')))
    {{-- Floating Live Chat widget (giống khách hàng) --}}
    <div id="admin-live-chat-widget" class="fixed bottom-6 right-6 z-[55]">
        <div id="admin-live-chat-toggle-wrap" class="relative inline-block">
            <button type="button" id="admin-live-chat-toggle" class="admin-live-chat-ring-target w-14 h-14 rounded-full shadow-lg flex items-center justify-center text-white hover:opacity-90 transition-opacity bg-blue-600" aria-label="Live Chat">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            </button>
            <span id="admin-live-chat-badge" class="absolute -top-0.5 -right-0.5 min-w-[20px] h-5 px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold hidden" aria-hidden="true">0</span>
        </div>
        <div id="admin-live-chat-panel" class="hidden absolute bottom-16 right-0 w-[380px] h-[520px] bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-blue-600">
                <span class="font-bold text-white">Live Chat</span>
                <button type="button" id="admin-live-chat-close" class="p-1 rounded-lg text-white/90 hover:bg-white/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div id="admin-live-chat-list-wrap" class="flex-1 overflow-y-auto p-2">
                <div id="admin-live-chat-list" class="space-y-1"></div>
                <p id="admin-live-chat-list-empty" class="text-sm text-gray-500 text-center py-6 hidden">No conversations yet.</p>
            </div>
            <div id="admin-live-chat-thread-wrap" class="hidden flex-1 flex flex-col min-h-0">
                <div class="px-3 py-2 border-b border-gray-200 flex items-center gap-2">
                    <button type="button" id="admin-live-chat-back" class="p-1 rounded hover:bg-gray-100 text-gray-600">←</button>
                    <span id="admin-live-chat-thread-title" class="font-semibold text-gray-900 text-sm truncate"></span>
                </div>
                <div id="admin-live-chat-messages" class="flex-1 overflow-y-auto p-3 space-y-2"></div>
                <div class="p-3 border-t border-gray-200">
                    <form id="admin-live-chat-reply-form" class="flex gap-2">
                        <input type="text" id="admin-live-chat-input" placeholder="Enter message..." class="flex-1 px-3 py-2 border border-gray-300 rounded-xl text-sm">
                        <button type="submit" id="admin-live-chat-send" class="px-4 py-2 rounded-xl font-semibold text-white bg-blue-600 hover:bg-blue-700">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <x-realtime-analytics />
    <!-- Scripts Stack -->
    @stack('scripts')

    @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller')))
    <script>
    (function() {
        var widget = document.getElementById('admin-live-chat-widget');
        if (!widget) return;
        var baseUrl = '{{ url("/admin/live-chat") }}';
        var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
        var panel = document.getElementById('admin-live-chat-panel');
        var listWrap = document.getElementById('admin-live-chat-list-wrap');
        var listEl = document.getElementById('admin-live-chat-list');
        var listEmpty = document.getElementById('admin-live-chat-list-empty');
        var threadWrap = document.getElementById('admin-live-chat-thread-wrap');
        var threadTitle = document.getElementById('admin-live-chat-thread-title');
        var messagesEl = document.getElementById('admin-live-chat-messages');
        var badgeEl = document.getElementById('admin-live-chat-badge');
        var currentConversationId = null;
        var currentCustomerName = '';
        var lastSeenMessageId = 0;
        var pollConvTimer = null;
        var pollMsgTimer = null;
        var prevTotalUnread = -1;

        function triggerChatRing() {
            var wrap = document.getElementById('admin-live-chat-toggle-wrap');
            if (!wrap) return;
            wrap.classList.remove('admin-live-chat-ring');
            wrap.offsetHeight;
            wrap.classList.add('admin-live-chat-ring');
            setTimeout(function() { wrap.classList.remove('admin-live-chat-ring'); }, 3200);
        }
        function playNewMessageSound() {
            try {
                var C = window.AudioContext || window.webkitAudioContext;
                if (!C) return;
                var ctx = new C();
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.frequency.value = 600; osc.type = 'sine';
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
                osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.12);
            } catch (e) {}
        }
        function updateBadge(totalUnread) {
            if (!badgeEl) return;
            badgeEl.textContent = totalUnread > 99 ? '99+' : totalUnread;
            badgeEl.classList.toggle('hidden', totalUnread <= 0);
            badgeEl.setAttribute('aria-hidden', totalUnread <= 0);
        }
        function fetchConversations(cb) {
            fetch(baseUrl + '/api/conversations', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.conversations) {
                        var total = data.conversations.reduce(function(s, c) { return s + (c.unread_count || 0); }, 0);
                        if (panel.classList.contains('hidden') && prevTotalUnread >= 0 && total > prevTotalUnread) {
                            triggerChatRing();
                            playNewMessageSound();
                        }
                        prevTotalUnread = total;
                        updateBadge(total);
                        if (typeof cb === 'function') cb(data.conversations);
                    }
                })
                .catch(function() {});
        }
        function renderList(conversations) {
            if (!conversations || !conversations.length) {
                listEl.innerHTML = '';
                listEmpty.classList.remove('hidden');
                return;
            }
            listEmpty.classList.add('hidden');
            listEl.innerHTML = conversations.map(function(c) {
                var last = c.last_message ? (c.last_message.body || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') : '—';
                var time = c.last_message && c.last_message.created_at ? new Date(c.last_message.created_at).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) : '';
                var unread = (c.unread_count || 0) > 0 ? '<span class="ml-2 w-2 h-2 rounded-full bg-red-500 inline-block"></span>' : '';
                return '<button type="button" class="admin-live-chat-conv w-full text-left px-3 py-3 rounded-xl hover:bg-gray-100 border border-transparent hover:border-gray-200 flex items-center justify-between gap-2" data-id="' + c.id + '" data-name="' + (c.customer_name || 'Customer').replace(/"/g, '&quot;') + '">' +
                    '<div class="min-w-0 flex-1"><p class="font-medium text-gray-900 truncate">' + (c.customer_name || 'Customer').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p><p class="text-xs text-gray-500 truncate">' + last + '</p></div>' + unread + '<span class="text-xs text-gray-400 flex-shrink-0">' + time + '</span></button>';
            }).join('');
        }
        function showList() {
            threadWrap.classList.add('hidden');
            listWrap.classList.remove('hidden');
            currentConversationId = null;
            stopPollMessages();
        }
        function showThread(id, name) {
            currentConversationId = id;
            currentCustomerName = name || 'Customer';
            threadTitle.textContent = currentCustomerName;
            listWrap.classList.add('hidden');
            threadWrap.classList.remove('hidden');
            lastSeenMessageId = 0;
            fetch(baseUrl + '/' + id + '/mark-read', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).catch(function() {});
            fetchMessages();
            startPollMessages();
        }
        function renderMessages(messages) {
            if (!messages || !messages.length) {
                messagesEl.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No messages yet.</p>';
                return;
            }
            messagesEl.innerHTML = messages.map(function(m) {
                var isCustomer = m.is_from_customer;
                var align = isCustomer ? 'justify-start' : 'justify-end';
                var bg = isCustomer ? 'bg-gray-100 text-gray-900' : 'text-white';
                var style = !isCustomer ? 'background:#2563eb' : '';
                var sender = isCustomer ? currentCustomerName : 'You';
                var time = new Date(m.created_at).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
                return '<div class="flex ' + align + '"><div class="max-w-[85%] rounded-xl px-3 py-2 text-sm ' + bg + '" style="' + style + '"><p class="text-xs font-semibold opacity-90 mb-1">' + sender + '</p><p class="whitespace-pre-wrap">' + (m.body || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p><p class="text-xs mt-1 opacity-80">' + time + '</p></div></div>';
            }).join('');
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
        function fetchMessages() {
            if (!currentConversationId) return;
            fetch(baseUrl + '/' + currentConversationId + '/messages', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success || !data.messages) return;
                    var prevId = lastSeenMessageId;
                    var maxId = Math.max(0, ...data.messages.map(function(m) { return m.id; }));
                    var last = data.messages[data.messages.length - 1];
                    if (last && last.is_from_customer && last.id > prevId && prevId > 0) playNewMessageSound();
                    lastSeenMessageId = maxId;
                    renderMessages(data.messages);
                })
                .catch(function() {});
        }
        function startPollMessages() {
            if (pollMsgTimer) clearInterval(pollMsgTimer);
            pollMsgTimer = setInterval(fetchMessages, 3000);
        }
        function stopPollMessages() {
            if (pollMsgTimer) { clearInterval(pollMsgTimer); pollMsgTimer = null; }
        }
        function fetchConversationsAndMaybeRender(cb) {
            fetchConversations(function(convs) {
                if (!panel.classList.contains('hidden') && typeof cb === 'function') cb(convs);
            });
        }
        function startPollConversations() {
            if (pollConvTimer) clearInterval(pollConvTimer);
            pollConvTimer = setInterval(function() { fetchConversationsAndMaybeRender(renderList); }, 5000);
        }
        function stopPollMessages() {
            if (pollMsgTimer) { clearInterval(pollMsgTimer); pollMsgTimer = null; }
        }
        document.getElementById('admin-live-chat-toggle').addEventListener('click', function() {
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden')) {
                fetchConversations(function(convs) { renderList(convs); });
                if (currentConversationId) fetchMessages();
            } else {
                stopPollMessages();
                var t = parseInt(badgeEl.textContent, 10) || 0;
                prevTotalUnread = t;
            }
            startPollConversations();
        });
        document.getElementById('admin-live-chat-close').addEventListener('click', function() {
            panel.classList.add('hidden');
            stopPollMessages();
            var t = parseInt(badgeEl.textContent, 10) || 0;
            prevTotalUnread = t;
        });
        listEl.addEventListener('click', function(e) {
            var btn = e.target.closest('.admin-live-chat-conv');
            if (!btn) return;
            var id = parseInt(btn.getAttribute('data-id'), 10);
            var name = btn.getAttribute('data-name') || 'Customer';
            showThread(id, name);
        });
        document.getElementById('admin-live-chat-back').addEventListener('click', function() {
            showList();
            fetchConversations(function(convs) { renderList(convs); });
        });
        document.getElementById('admin-live-chat-reply-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var input = document.getElementById('admin-live-chat-input');
            var body = (input && input.value) ? input.value.trim() : '';
            if (!body || !currentConversationId) return;
            var sendBtn = document.getElementById('admin-live-chat-send');
            sendBtn.disabled = true;
            fetch(baseUrl + '/' + currentConversationId + '/reply', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ body: body })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success && data.message) {
                    input.value = '';
                    fetchMessages();
                }
            }).catch(function() {}).finally(function() { sendBtn.disabled = false; });
        });
        fetchConversations();
        startPollConversations();
    })();
    </script>
    @endif
</body>
</html>