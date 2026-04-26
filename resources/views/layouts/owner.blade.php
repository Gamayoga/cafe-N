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
                <div class="bg-gray-800/40 rounded-3xl flex items-stretch overflow-hidden">
                    <!-- Account Info (Left) -->
                    <div class="flex-1 px-4 py-4 flex items-center justify-start gap-2 min-w-0">
                        <div class="w-7 h-7 bg-[#E97D5A] rounded-lg flex items-center justify-center text-white font-bold text-[10px] shrink-0">
                            {{ substr(auth()->user()->name, 0, 1) }}{{ substr(strrchr(auth()->user()->name, " "), 1, 1) ?: '' }}
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-[11px] font-black text-white leading-none truncate">{{ explode(' ', auth()->user()->name)[0] }}</span>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider mt-0.5">Owner</span>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="w-px bg-gray-700/50 my-3"></div>

                    <!-- Logout Button (Right) -->
                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button type="submit" title="Logout" class="w-full h-full px-5 flex items-center justify-center text-rose-400 hover:bg-rose-500/20 transition-all">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13 2C10.2386 2 8 4.23858 8 7C8 7.55228 8.44772 8 9 8C9.55228 8 10 7.55228 10 7C10 5.34315 11.3431 4 13 4H17C18.6569 4 20 5.34315 20 7V17C20 18.6569 18.6569 20 17 20H13C11.3431 20 10 18.6569 10 17C10 16.4477 9.55228 16 9 16C8.44772 16 8 16.4477 8 17C8 19.7614 10.2386 22 13 22H17C19.7614 22 22 19.7614 22 17V7C22 4.23858 19.7614 2 17 2H13Z"/>
                                <path d="M3 11C2.44772 11 2 11.4477 2 12C2 12.5523 2.44772 13 3 13H11.2821C11.1931 13.1098 11.1078 13.2163 11.0271 13.318C10.7816 13.6277 10.5738 13.8996 10.427 14.0945C10.3536 14.1921 10.2952 14.2705 10.255 14.3251L10.2084 14.3884L10.1959 14.4055L10.1915 14.4115C10.1914 14.4116 10.191 14.4122 11 15L10.1915 14.4115C9.86687 14.8583 9.96541 15.4844 10.4122 15.809C10.859 16.1336 11.4843 16.0346 11.809 15.5879L11.8118 15.584L11.822 15.57L11.8638 15.5132C11.9007 15.4632 11.9553 15.3897 12.0247 15.2975C12.1637 15.113 12.3612 14.8546 12.5942 14.5606C13.0655 13.9663 13.6623 13.2519 14.2071 12.7071L14.9142 12L14.2071 11.2929C13.6623 10.7481 13.0655 10.0337 12.5942 9.43937C12.3612 9.14542 12.1637 8.88702 12.0247 8.7025C11.9553 8.61033 11.9007 8.53682 11.8638 8.48679L11.822 8.43002L11.8118 8.41602L11.8095 8.41281C11.4848 7.96606 10.859 7.86637 10.4122 8.19098C9.96541 8.51561 9.86636 9.14098 10.191 9.58778L11 9C10.191 9.58778 10.1909 9.58773 10.191 9.58778L10.1925 9.58985L10.1959 9.59454L10.2084 9.61162L10.255 9.67492C10.2952 9.72946 10.3536 9.80795 10.427 9.90549C10.5738 10.1004 10.7816 10.3723 11.0271 10.682C11.1078 10.7837 11.1931 10.8902 11.2821 11H3Z"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Hamburger Button (Mobile only, fixed top-left) -->
        <button @click="mobileMenu = true"
                class="lg:hidden fixed top-4 left-4 z-[45] w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-md border border-slate-100 text-slate-600 print:hidden">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Main Scroll Content -->
            <main class="px-6 lg:px-12 pb-12 flex-1 pt-16 lg:pt-10 overflow-x-hidden">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
