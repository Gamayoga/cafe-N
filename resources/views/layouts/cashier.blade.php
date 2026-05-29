<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Northern POS') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #F0F2f5;
            height: 100vh;
        }
        .sidebar-item-active {
            background-color: #14B8A6;
            color: white;
            box-shadow: 0 10px 20px -3px rgba(20, 184, 166, 0.4);
        }
        .bg-glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        @media (max-width: 1024px) {
            body {
                overflow-y: auto;
                height: auto;
            }
        }
    </style>
</head>
<body class="antialiased text-slate-800">
    <div class="flex flex-col lg:flex-row h-screen lg:overflow-hidden pb-20 lg:pb-0">
        <!-- Sidebar Navigation (Desktop) -->
        <aside class="hidden lg:flex w-72 bg-[#0A2A2F] flex-col h-screen print:hidden shrink-0">
            <!-- Sidebar Header -->
            <div class="px-6 pt-6 pb-4 mb-2 flex items-center justify-center">
                <a href="{{ route('cashier.dashboard') }}" class="flex items-center">
                    <img src="{{ asset('images/logo.png') }}" alt="Northern Coffe & Burger" class="h-20 w-auto object-contain">
                </a>
            </div>

            <div class="px-4 flex-1 overflow-y-auto">
                <div class="mb-2">
                    <p class="px-6 mb-4 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Kasir</p>
                    <div class="space-y-2">
                        <x-cashier-nav-link href="{{ route('cashier.dashboard') }}" :active="request()->routeIs('cashier.dashboard')" icon="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                            Dashboard
                        </x-cashier-nav-link>
                        <x-cashier-nav-link href="{{ route('cashier.pos') }}" :active="request()->routeIs('cashier.pos')" icon="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                            Point of Sale
                        </x-cashier-nav-link>
                        <x-cashier-nav-link href="{{ route('cashier.kds') }}" :active="request()->routeIs('cashier.kds')" icon="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            Layar Dapur
                        </x-cashier-nav-link>
                        <x-cashier-nav-link href="{{ route('cashier.attendance') }}" :active="request()->routeIs('cashier.attendance')" icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            Absensi
                        </x-cashier-nav-link>
                    </div>
                </div>
            </div>

            <!-- Profile Bottom Area -->
            <div class="p-6">
                <div class="bg-gray-800/40 rounded-3xl flex items-stretch overflow-hidden">
                    <!-- Account Info (Left) -->
                    <div class="flex-1 px-4 py-4 flex items-center justify-start gap-2 min-w-0">
                        <div class="w-7 h-7 bg-[#14B8A6] rounded-lg flex items-center justify-center text-white font-bold text-[10px] shrink-0">
                            {{ substr(auth()->user()->name, 0, 1) }}{{ substr(strrchr(auth()->user()->name, " "), 1, 1) ?: '' }}
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-[11px] font-black text-white leading-none truncate">{{ explode(' ', auth()->user()->name)[0] }}</span>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider mt-0.5">Kasir</span>
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

        <!-- Bottom Navigation (Mobile Only) -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 h-20 bg-[#0A2A2F] flex items-center justify-around px-4 z-50 rounded-t-[2.5rem] shadow-[0_-10px_40px_rgba(0,0,0,0.3)]">
            <a href="{{ route('cashier.dashboard') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('cashier.dashboard') ? 'text-[#14B8A6]' : 'text-gray-500' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span class="text-[9px] font-black uppercase tracking-widest">Home</span>
            </a>
            <a href="{{ route('cashier.pos') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('cashier.pos') ? 'text-[#14B8A6]' : 'text-gray-500' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="text-[9px] font-black uppercase tracking-widest">Kasir</span>
            </a>
            <!-- Center Button Style for KDS -->
            <a href="{{ route('cashier.kds') }}" class="flex flex-col items-center gap-1 -mt-8">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-2xl transition-all {{ request()->routeIs('cashier.kds') ? 'bg-[#14B8A6] text-white' : 'bg-gray-800 text-gray-400' }}">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <span class="text-[9px] font-black uppercase tracking-widest mt-1 {{ request()->routeIs('cashier.kds') ? 'text-[#14B8A6]' : 'text-gray-500' }}">Dapur</span>
            </a>
            <a href="{{ route('cashier.attendance') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('cashier.attendance') ? 'text-[#14B8A6]' : 'text-gray-500' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span class="text-[9px] font-black uppercase tracking-widest">Absen</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex flex-col items-center gap-1 text-rose-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"></path></svg>
                    <span class="text-[9px] font-black uppercase tracking-widest">Logout</span>
                </button>
            </form>
        </nav>

        <!-- Main Content Area -->
        <main class="flex-1 lg:overflow-auto p-4 lg:p-0">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
