<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Northern Cafe') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #F8F9FB;
        }
        .sidebar {
            background-color: #111111;
        }
        .sidebar-item-active {
            background-color: #E97D5A;
            color: white;
            box-shadow: 0 10px 20px -3px rgba(233, 125, 90, 0.4);
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased text-slate-800" x-data="{ mobileMenu: false }">
    <div class="flex min-h-screen">
        <!-- Sidebar Overlay (Mobile) -->
        <div x-show="mobileMenu" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenu = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[40] lg:hidden" x-cloak></div>

        <!-- Sidebar -->
        <aside :class="mobileMenu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed lg:sticky top-0 left-0 w-72 sidebar flex flex-col h-screen z-[50] transition-transform duration-300 ease-in-out print:hidden">
            
            <!-- Sidebar Header -->
            <div class="p-8 mb-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#E97D5A] rounded-xl flex items-center justify-center shadow-lg shadow-orange-900/50">
                        <svg class="text-white w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    </div>
                    <span class="text-2xl font-extrabold text-white tracking-tighter">Northern<span class="text-[#E97D5A]">.</span></span>
                </div>
                <!-- Close Button (Mobile) -->
                <button @click="mobileMenu = false" class="lg:hidden text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="px-4 flex-1 overflow-y-auto">
                <!-- Nav Section: Main -->
                <div class="mb-8">
                    <p class="px-6 mb-4 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Main</p>
                    <div class="space-y-2">
                        <x-owner-nav-link href="{{ route('owner.dashboard') }}" :active="request()->routeIs('owner.dashboard')" icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            Overview
                        </x-owner-nav-link>
                        <x-owner-nav-link href="{{ route('owner.reports') }}" :active="request()->routeIs('owner.reports')" icon="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            Laporan
                        </x-owner-nav-link>
                        <x-owner-nav-link href="{{ route('owner.inventory.suppliers') }}" :active="request()->routeIs('owner.inventory.suppliers')" icon="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                            Supplier
                        </x-owner-nav-link>
                        <x-owner-nav-link href="{{ route('owner.inventory.ingredients') }}" :active="request()->routeIs('owner.inventory.ingredients')" icon="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                            Bahan Baku
                        </x-owner-nav-link>
                        <x-owner-nav-link href="{{ route('owner.inventory.products') }}" :active="request()->routeIs('owner.inventory.products')" icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            Produk Menu
                        </x-owner-nav-link>
                        <x-owner-nav-link href="{{ route('owner.inventory.history') }}" :active="request()->routeIs('owner.inventory.history')" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
                            Riwayat Stok
                        </x-owner-nav-link>
                    </div>
                </div>

                <!-- Nav Section: Manajemen -->
                <div>
                    <p class="px-6 mb-4 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Manajemen</p>
                    <div class="space-y-2">
                        <x-owner-nav-link href="{{ route('owner.employees') }}" :active="request()->routeIs('owner.employees')" icon="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            Pegawai
                        </x-owner-nav-link>
                        <x-owner-nav-link href="{{ route('owner.attendance') }}" :active="request()->routeIs('owner.attendance')" icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            Absensi
                        </x-owner-nav-link>
                    </div>
                </div>
            </div>

            <!-- Profile Bottom Area -->
            <div class="p-6">
                <div class="bg-gray-800/40 p-4 rounded-3xl flex items-center justify-between group overflow-hidden relative">
                    <div class="flex items-center gap-3 relative z-10 text-left">
                        <div class="w-10 h-10 bg-[#E97D5A] rounded-2xl flex items-center justify-center text-white font-bold shadow-lg shadow-orange-900/20">
                            {{ substr(auth()->user()->name, 0, 1) }}{{ substr(strrchr(auth()->user()->name, " "), 1, 1) ?: '' }}
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs font-black text-white leading-none mb-1">{{ auth()->user()->name }}</span>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Owner</span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="relative z-10">
                        @csrf
                        <button type="submit" class="w-8 h-8 rounded-xl bg-gray-700/50 flex items-center justify-center text-gray-400 hover:text-rose-400 hover:bg-gray-700 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Main Scroll Content -->
            <main class="px-6 lg:px-12 pb-12 flex-1 pt-6 lg:pt-10 overflow-x-hidden">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
