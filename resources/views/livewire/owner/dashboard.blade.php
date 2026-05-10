<?php

use App\Models\Transaction;
use App\Models\Ingredient;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Product;
use function Livewire\Volt\{state, layout, computed};

layout('layouts.owner');

$stats = computed(function () {
    $today = today();
    $yesterday = today()->subDay();

    // Revenue
    $revenueToday = Transaction::whereDate('created_at', $today)->sum('total_amount');
    $revenueYesterday = Transaction::whereDate('created_at', $yesterday)->sum('total_amount');
    $revenueDiff = $revenueYesterday > 0 ? (($revenueToday - $revenueYesterday) / $revenueYesterday) * 100 : 0;

    // Transactions
    $transToday = Transaction::whereDate('created_at', $today)->count();
    $transYesterday = Transaction::whereDate('created_at', $yesterday)->count();
    $transDiff = $transToday - $transYesterday;

    // Attendance
    $activeEmployees = User::where('role', 'pegawai')->where('is_active', true)->count();
    $presentToday = Attendance::whereDate('date', $today)->count();

    // Low Stock
    $lowStockCount = Ingredient::whereRaw('stock_qty <= min_stock')->count();

    return [
        'revenue' => $revenueToday,
        'revenue_diff' => round($revenueDiff, 1),
        'transactions' => $transToday,
        'trans_diff' => $transDiff,
        'present' => $presentToday,
        'total_employees' => $activeEmployees,
        'low_stock' => $lowStockCount
    ];
});

$chartData = computed(function () {
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = today()->subDays($i);
        $data[] = [
            'day' => $date->translatedFormat('D'),
            'revenue' => Transaction::whereDate('created_at', $date)->sum('total_amount'),
        ];
    }
    
    $max = collect($data)->max('revenue') ?: 1;
    foreach ($data as &$d) {
        $d['height'] = $max > 0 ? ($d['revenue'] / $max) * 100 : 0;
    }
    
    return $data;
});

$topProducts = computed(function () {
    // This will be more accurate once POS is implemented
    return Product::withCount('ingredients') // Placeholder ordering
        ->latest()
        ->take(4)
        ->get();
});

$lowStockItems = computed(function () {
    return Ingredient::whereRaw('stock_qty <= min_stock')
        ->orderBy('stock_qty', 'asc')
        ->take(4)
        ->get();
});

$recentAttendance = computed(function () {
    return Attendance::with('user')
        ->whereDate('date', today())
        ->latest('check_in')
        ->take(3)
        ->get();
});

?>

<div class="space-y-8">
    <!-- Header: Overview only -->
    <div class="print:hidden">
        <h1 class="text-4xl font-extrabold text-slate-900 tracking-tighter">Halo, {{ explode(' ', auth()->user()->name)[0] }}</h1>
        <p class="text-slate-400 font-bold mt-1 text-sm">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    <!-- Top Metrics Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Revenue Card -->
        <div class="bg-[#1A1A1A] rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden group">
            <div class="relative z-10 flex flex-col h-full justify-between">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center text-[#E97D5A]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Pendapatan <br>Hari Ini</p>
                </div>
                <div>
                    <p class="text-3xl font-black text-white tracking-tighter mb-2">Rp {{ number_format($this->stats['revenue'], 0, ',', '.') }}</p>
                    @if($this->stats['revenue_diff'] >= 0)
                        <span class="inline-flex items-center px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg text-[10px] font-black tracking-wider">
                            +{{ $this->stats['revenue_diff'] }}% vs kemarin
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 bg-rose-500/20 text-rose-400 rounded-lg text-[10px] font-black tracking-wider">
                            {{ $this->stats['revenue_diff'] }}% vs kemarin
                        </span>
                    @endif
                </div>
            </div>
            <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-[#E97D5A] opacity-10 rounded-full blur-3xl group-hover:opacity-20 transition-opacity"></div>
        </div>

        <!-- Transaction Card -->
        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 flex flex-col justify-between">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-orange-50 rounded-2xl flex items-center justify-center text-[#E97D5A]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Total <br>Transaksi</p>
            </div>
            <div>
                <p class="text-3xl font-black text-slate-800 tracking-tighter mb-2">{{ $this->stats['transactions'] }}</p>
                <span class="inline-flex items-center px-2 py-1 {{ $this->stats['trans_diff'] >= 0 ? 'bg-emerald-50 text-emerald-500' : 'bg-rose-50 text-rose-500' }} rounded-lg text-[10px] font-black tracking-wider">
                    {{ $this->stats['trans_diff'] >= 0 ? '+' : '' }}{{ $this->stats['trans_diff'] }} dari kemarin
                </span>
            </div>
        </div>

        <!-- Attendance Card -->
        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 flex flex-col justify-between">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Pegawai <br>Hadir</p>
            </div>
            <div>
                <div class="flex items-end gap-1 mb-2">
                    <p class="text-3xl font-black text-slate-800 tracking-tighter">{{ $this->stats['present'] }}</p>
                    <p class="text-lg font-bold text-slate-400 mb-0.5">/{{ $this->stats['total_employees'] }}</p>
                </div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">
                    {{ $this->stats['present'] == $this->stats['total_employees'] ? 'Semua hadir' : ($this->stats['present'] == 0 ? 'Semua belum check-in' : ($this->stats['total_employees'] - $this->stats['present']) . ' belum hadir') }}
                </span>
            </div>
        </div>

        <!-- Low Stock Card -->
        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 flex flex-col justify-between">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-rose-50 rounded-2xl flex items-center justify-center text-rose-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Stok <br>Menipis</p>
            </div>
            <div>
                <p class="text-3xl font-black {{ $this->stats['low_stock'] > 0 ? 'text-rose-600' : 'text-slate-800' }} tracking-tighter mb-2">{{ $this->stats['low_stock'] }}</p>
                <span class="inline-flex items-center px-2 py-1 {{ $this->stats['low_stock'] > 0 ? 'bg-rose-50 text-rose-500' : 'bg-emerald-50 text-emerald-500' }} rounded-lg text-[10px] font-black tracking-wider uppercase">
                    {{ $this->stats['low_stock'] > 0 ? 'Segera Restock' : 'Aman' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Middle Row: Charts & Top Selling -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Chart Panel -->
        <div class="lg:col-span-8 bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-10">
                <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Pendapatan 7 hari terakhir</h3>
                <span class="text-[10px] font-black text-[#E97D5A] uppercase tracking-widest">Update Real-time</span>
            </div>
            <!-- Visual Chart -->
            <div class="flex items-end justify-between h-48 px-2 gap-4">
                @foreach($this->chartData as $data)
                <div class="flex-1 flex flex-col items-center gap-4 group h-full justify-end">
                    <div class="w-full bg-slate-50 rounded-2xl relative h-full overflow-hidden border border-slate-50">
                        <div class="absolute bottom-0 left-0 w-full bg-[#E97D5A] transition-all duration-700 rounded-t-xl" style="height: {{ $data['height'] }}%">
                            <div class="absolute top-0 left-0 w-full h-1 bg-white/20"></div>
                        </div>
                        <!-- Tooltip on hover -->
                        <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[9px] font-black px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-20">
                            Rp {{ number_format($data['revenue'], 0, ',', '.') }}
                        </div>
                    </div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">{{ $data['day'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Top Selling Panel -->
        <div class="lg:col-span-4 bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100">
    <div class="flex items-center justify-between mb-10">
        <h3 class="text-xl font-extrabold text-slate-800 tracking-tight leading-tight">Menu unggulan</h3>
        <a href="{{ route('owner.inventory.products') }}" class="text-xs font-black text-[#E97D5A] uppercase tracking-widest text-right hover:underline">Semua</a>
    </div>
    <div class="space-y-6">
        @forelse($this->topProducts as $idx => $product)
            @php
                // Definisi warna berbeda untuk tiap peringkat
                $bgColors = ['bg-orange-100', 'bg-rose-100', 'bg-amber-100', 'bg-emerald-100'];
                $textColors = ['text-orange-600', 'text-rose-600', 'text-amber-600', 'text-emerald-600'];
                $borderColors = ['border-orange-200', 'border-rose-200', 'border-amber-200', 'border-emerald-200'];
                $hoverColors = ['group-hover:bg-orange-600', 'group-hover:bg-rose-600', 'group-hover:bg-amber-600', 'group-hover:bg-emerald-600'];
                
                $i = $idx % 4; // Supaya tidak error jika data lebih dari 4
            @endphp
            
            <div class="group cursor-default">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="w-9 h-9 rounded-2xl {{ $bgColors[$i] }} border {{ $borderColors[$i] }} flex items-center justify-center text-sm font-black {{ $textColors[$i] }} shadow-sm {{ $hoverColors[$i] }} group-hover:text-white group-hover:-rotate-6 transition-all duration-300">
                            {{ $idx + 1 }}
                        </div>
                        
                        <div class="flex flex-col">
                            <span class="text-xs font-black text-slate-700 uppercase tracking-tight group-hover:text-slate-900 transition-colors">{{ $product->name }}</span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Terlaris #{{ $idx + 1 }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-black text-slate-800 block">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                        <span class="text-[9px] font-bold text-slate-300 uppercase tracking-tighter">Per Porsi</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center py-10 text-center">
                <p class="text-sm font-bold text-slate-300 italic">Belum ada data produk.</p>
            </div>
        @endforelse
    </div>
</div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Ingredient Status -->
        <div class="lg:col-span-4 bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Status stok kritis</h3>
                <a href="{{ route('owner.inventory.ingredients') }}" class="text-xs font-black text-[#E97D5A] uppercase tracking-widest hover:underline">Detail</a>
            </div>
            <div class="space-y-4">
                @forelse($this->lowStockItems as $i)
                @php $status = $i->stock_status; $style = $status == 'Habis' ? 'text-rose-600 bg-rose-50' : 'text-amber-600 bg-amber-50'; @endphp
                <div class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0 border-dashed">
                    <div>
                         <p class="text-xs font-black text-slate-700 uppercase tracking-tighter">{{ $i->name }}</p>
                         <p class="text-[10px] font-bold text-slate-400">{{ $i->stock_qty }} {{ $i->unit }}</p>
                    </div>
                    <span class="px-2 py-1 {{ $style }} rounded-lg text-[9px] font-black uppercase tracking-wider">{{ $status }}</span>
                </div>
                @empty
                <div class="flex flex-col items-center py-10 text-center">
                    <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-500 mb-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <p class="text-xs font-bold text-slate-400">Semua stok aman</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Presence Today -->
        <div class="lg:col-span-4 bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Presensi hari ini</h3>
                <a href="{{ route('owner.attendance') }}" class="text-xs font-black text-[#E97D5A] uppercase tracking-widest hover:underline">Lengkap</a>
            </div>
            <div class="space-y-4">
                @forelse($this->recentAttendance as $att)
                <div class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 group">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-[#111111] text-white flex items-center justify-center text-[10px] font-black uppercase group-hover:bg-[#E97D5A] transition-colors">
                            {{ substr($att->user->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-700 leading-none mb-1">{{ $att->user->name }}</p>
                            <p class="text-[10px] font-bold text-slate-400 tracking-tight">{{ \Carbon\Carbon::parse($att->check_in)->format('H:i') }} WIB</p>
                        </div>
                    </div>
                    <span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[9px] font-black uppercase tracking-wider leading-none">Hadir</span>
                </div>
                @empty
                <div class="flex flex-col items-center py-10 text-center">
                    <p class="text-xs font-bold text-slate-300 italic">Belum ada pegawai check-in hari ini</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="lg:col-span-4 bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100">
            <h3 class="text-xl font-extrabold text-slate-800 tracking-tight mb-8">Aksi cepat</h3>
            <div class="grid grid-cols-1 gap-4">
                <a href="{{ route('owner.inventory.suppliers') }}" class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-3xl group hover:border-[#E97D5A] hover:bg-orange-50/20 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-50 rounded-2xl border border-slate-100 flex items-center justify-center text-[#E97D5A] group-hover:bg-[#E97D5A] group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-black text-slate-800 leading-tight">Supplier</p>
                            <p class="text-[10px] font-bold text-slate-400 leading-tight uppercase tracking-widest mt-0.5">Kelola Rekanan</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-[#E97D5A] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>
                
                <a href="{{ route('owner.employees') }}" class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-3xl group hover:border-[#E97D5A] hover:bg-orange-50/20 transition-all">
                    <div class="flex items-center gap-4 text-left">
                        <div class="w-12 h-12 bg-gray-50 rounded-2xl border border-slate-100 flex items-center justify-center text-[#E97D5A] group-hover:bg-[#E97D5A] group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-800 leading-tight">Pegawai</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Atur Akun Staff</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
