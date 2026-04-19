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
        <aside class="hidden lg:flex w-20 bg-[#111111] flex-col items-center py-10 print:hidden shrink-0">
            <div class="w-10 h-10 bg-[#E97D5A] rounded-xl flex items-center justify-center shadow-lg shadow-orange-900/50 mb-12">
                <svg class="text-white w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            </div>

            <nav class="flex-1 flex flex-col gap-6">
                <!-- Dashboard -->
                <a title="Dashboard" href="{{ route('cashier.dashboard') }}" 
                   class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all {{ request()->routeIs('cashier.dashboard') ? 'bg-[#E97D5A] text-white shadow-lg shadow-orange-500/20' : 'text-gray-500 hover:text-white hover:bg-white/10' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                </a>
                <!-- POS -->
                <a title="Point of Sale" href="{{ route('cashier.pos') }}" 
                   class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all {{ request()->routeIs('cashier.pos') ? 'bg-[#E97D5A] text-white shadow-lg shadow-orange-500/20' : 'text-gray-500 hover:text-white hover:bg-white/10' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </a>
                <!-- Kitchen Display -->
                <a title="Layar Dapur" href="{{ route('cashier.kds') }}" 
                   class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all {{ request()->routeIs('cashier.kds') ? 'bg-[#E97D5A] text-white shadow-lg shadow-orange-500/20' : 'text-gray-500 hover:text-white hover:bg-white/10' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </a>
                <!-- Attendance -->
                <a title="Absensi" href="{{ route('cashier.attendance') }}" 
                   class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all {{ request()->routeIs('cashier.attendance') ? 'bg-[#E97D5A] text-white shadow-lg shadow-orange-500/20' : 'text-gray-500 hover:text-white hover:bg-white/10' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </a>
            </nav>

            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Logout" class="w-12 h-12 text-rose-500 bg-rose-500/10 hover:bg-rose-500 hover:text-white rounded-2xl flex items-center justify-center transition-all shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7"></path></svg>
                </button>
            </form>
        </aside>

        <!-- Bottom Navigation (Mobile Only) -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 h-20 bg-[#111111] flex items-center justify-around px-4 z-50 rounded-t-[2.5rem] shadow-[0_-10px_40px_rgba(0,0,0,0.3)]">
            <a href="{{ route('cashier.dashboard') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('cashier.dashboard') ? 'text-[#E97D5A]' : 'text-gray-500' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span class="text-[9px] font-black uppercase tracking-widest">Home</span>
            </a>
            <a href="{{ route('cashier.pos') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('cashier.pos') ? 'text-[#E97D5A]' : 'text-gray-500' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="text-[9px] font-black uppercase tracking-widest">Kasir</span>
            </a>
            <!-- Center Button Style for KDS -->
            <a href="{{ route('cashier.kds') }}" class="flex flex-col items-center gap-1 -mt-8">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-2xl transition-all {{ request()->routeIs('cashier.kds') ? 'bg-[#E97D5A] text-white' : 'bg-gray-800 text-gray-400' }}">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <span class="text-[9px] font-black uppercase tracking-widest mt-1 {{ request()->routeIs('cashier.kds') ? 'text-[#E97D5A]' : 'text-gray-500' }}">Dapur</span>
            </a>
            <a href="{{ route('cashier.attendance') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('cashier.attendance') ? 'text-[#E97D5A]' : 'text-gray-500' }}">
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
