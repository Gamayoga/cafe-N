<?php

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use Carbon\Carbon;
use function Livewire\Volt\{state, layout, computed, mount};

layout('layouts.owner');

state([
    'startDate' => '',
    'endDate' => '',
]);

mount(function () {
    $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
    $this->endDate = Carbon::now()->format('Y-m-d');
});

$stats = computed(function () {
    $start = Carbon::parse($this->startDate)->startOfDay();
    $end = Carbon::parse($this->endDate)->endOfDay();

    $query = Transaction::whereBetween('created_at', [$start, $end]);

    $totalRevenue = $query->sum('total_amount');
    $totalOrders = $query->count();
    $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

    return [
        'revenue' => $totalRevenue,
        'orders' => $totalOrders,
        'avg' => $avgOrderValue,
    ];
});

$chartData = computed(function () {
    $start = Carbon::parse($this->startDate)->startOfDay();
    $end = Carbon::parse($this->endDate)->endOfDay();
    
    $daysCount = $start->diffInDays($end) + 1;
    
    // Interval agar titik tidak terlalu rapat jika rentang waktu lama
    $interval = $daysCount > 31 ? ceil($daysCount / 20) : 1; 

    $data = [];
    $raw = Transaction::whereBetween('created_at', [$start, $end])
        ->orderBy('created_at')
        ->get()
        ->groupBy(fn ($t) => $t->created_at->format('Y-m-d'));

    $points = "";
    $width = 1000; // Lebar virtual SVG
    $height = 100; // Tinggi virtual SVG

    $items = [];
    for ($i = 0; $i < $daysCount; $i += $interval) {
        $date = $start->copy()->addDays($i);
        $dateStr = $date->format('Y-m-d');
        $dayRevenue = $raw->has($dateStr) ? $raw->get($dateStr)->sum('total_amount') : 0;
        
        $items[] = [
            'label' => $date->format('d M'),
            'value' => (float)$dayRevenue,
        ];
    }

    $max = collect($items)->max('value') ?: 1;
    $count = count($items);

    foreach ($items as $idx => $item) {
        // Hitung posisi X (0 sampai 1000)
        $x = ($count > 1) ? ($idx * ($width / ($count - 1))) : 0;
        // Hitung posisi Y (Tinggi dikurangi persentase nilai)
        $y = $height - (($item['value'] / $max) * 80 + 10); 
        
        $points .= "$x,$y ";
        
        // Simpan posisi untuk Dot HTML
        $items[$idx]['x_pos'] = ($count > 1) ? ($idx / ($count - 1)) * 100 : 0;
        $items[$idx]['y_pos'] = (($height - $y) / $height) * 100; // Persentase dari bawah
    }

    return [
        'items' => $items,
        'points' => trim($points)
    ];
});

$topProducts = computed(function () {
    $start = Carbon::parse($this->startDate)->startOfDay();
    $end = Carbon::parse($this->endDate)->endOfDay();

    return TransactionItem::with('product')
        ->whereBetween('created_at', [$start, $end])
        ->select('product_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(qty * price_at_sale) as total_revenue'))
        ->groupBy('product_id')
        ->orderBy('total_qty', 'desc')
        ->take(10)
        ->get();
});

$detailedTransactions = computed(function () {
    $start = Carbon::parse($this->startDate)->startOfDay();
    $end = Carbon::parse($this->endDate)->endOfDay();

    return Transaction::with('cashier')
        ->whereBetween('created_at', [$start, $end])
        ->latest()
        ->paginate(15);
});

?>

<div class="space-y-10 pb-20">
    <!-- Header & Filter -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 print:hidden">
        <div>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tighter">Laporan Bisnis</h1>
            <p class="text-slate-400 font-bold mt-1 uppercase text-[10px] tracking-[0.2em]">Manajemen / Laporan</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-4 bg-white p-3 rounded-[2rem] shadow-sm border border-slate-100">
            <div class="flex items-center gap-2 px-4 border-r border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dari</span>
                <input type="date" wire:model.live="startDate" class="border-0 p-0 text-sm font-black text-slate-700 focus:ring-0 bg-transparent">
            </div>
            <div class="flex items-center gap-2 px-4">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Hingga</span>
                <input type="date" wire:model.live="endDate" class="border-0 p-0 text-sm font-black text-slate-700 focus:ring-0 bg-transparent">
            </div>
            <button wire:click="$refresh" class="w-10 h-10 bg-[#E97D5A] text-white rounded-xl flex items-center justify-center hover:scale-105 transition-all shadow-lg shadow-orange-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path></svg>
            </button>
        </div>
    </div>

    <!-- Print-only Title -->
    <div class="hidden print:block text-center pb-10 border-b-2 border-slate-200 mb-10">
        <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tighter">Laporan Penjualan Northern Cafe</h1>
        <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mt-2">
            Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
        </p>
    </div>

    <!-- Stats Snapshot -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-[#1A1A1A] rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden group">
            <div class="relative z-10 flex flex-col h-full justify-between">
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Total Pendapatan</p>
                    <p class="text-4xl font-black text-white tracking-tighter">Rp {{ number_format($this->stats['revenue'], 0, ',', '.') }}</p>
                </div>
                <div class="mt-6 flex items-center gap-2 text-emerald-400 text-xs font-bold">
                    <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span> Data Terkini
                </div>
            </div>
            <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-[#E97D5A] opacity-10 rounded-full blur-3xl group-hover:opacity-20 transition-opacity"></div>
        </div>

        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Pesanan</p>
            <p class="text-4xl font-black text-slate-800 tracking-tighter">{{ number_format($this->stats['orders']) }}</p>
            <p class="mt-6 text-sm font-bold text-slate-400 uppercase tracking-tight">Transaksi Terhitung</p>
        </div>

        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Pemasukan / Order</p>
            <p class="text-4xl font-black text-slate-800 tracking-tighter">Rp {{ number_format($this->stats['avg'], 0, ',', '.') }}</p>
            <p class="mt-6 text-sm font-bold text-slate-400 uppercase tracking-tight text-indigo-500">Average Basket Size</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Sales Trend -->
        <div class="lg:col-span-8 bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100">
    <div class="flex items-center justify-between mb-12">
        <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Tren Penjualan</h3>
        <span class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase">
            <span class="w-3 h-3 bg-[#E97D5A] rounded-full"></span> Pendapatan
        </span>
    </div>

    <div class="relative h-72 w-full">
        <div class="absolute inset-0 flex flex-col justify-between z-0">
            @foreach(range(0, 4) as $line)
                <div class="w-full border-t border-slate-50"></div>
            @endforeach
        </div>

        <div class="relative h-56 w-full z-10">
            <svg viewBox="0 0 1000 100" class="w-full h-full overflow-visible" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="reportGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" style="stop-color:#E97D5A;stop-opacity:0.2" />
                        <stop offset="100%" style="stop-color:#E97D5A;stop-opacity:0" />
                    </linearGradient>
                </defs>

                <path d="M 0,100 {{ $this->chartData['points'] }} L 1000,100 Z" fill="url(#reportGrad)" />

                <polyline fill="none" stroke="#E97D5A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                    vector-effect="non-scaling-stroke" points="{{ $this->chartData['points'] }}" />
            </svg>

            @foreach($this->chartData['items'] as $item)
            <div class="absolute group" style="left: {{ $item['x_pos'] }}%; bottom: {{ $item['y_pos'] }}%; transform: translate(-50%, 50%);">
                <div class="w-2.5 h-2.5 rounded-full bg-white border-2 border-[#E97D5A] shadow-sm transition-transform group-hover:scale-150 z-20"></div>

                <div class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none z-30">
                    <div class="bg-slate-900 text-white text-[10px] font-black px-3 py-2 rounded-xl whitespace-nowrap shadow-xl">
                        <p class="text-slate-400 text-[8px] mb-1 uppercase">{{ $item['label'] }}</p>
                        Rp {{ number_format($item['value'], 0, ',', '.') }}
                    </div>
                    <div class="w-2 h-2 bg-slate-900 rotate-45 mx-auto -mt-1"></div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="absolute bottom-0 left-0 w-full flex justify-between px-2">
            @php 
                // Hanya tampilkan beberapa label jika data terlalu banyak agar tidak overlap
                $step = count($this->chartData['items']) > 10 ? ceil(count($this->chartData['items']) / 10) : 1;
            @endphp
            @foreach($this->chartData['items'] as $idx => $item)
                @if($idx % $step == 0)
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">
                        {{ $item['label'] }}
                    </span>
                @else
                    <span></span>
                @endif
            @endforeach
        </div>
    </div>
</div>

        <!-- Top Products -->
        <div class="lg:col-span-4 bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100">
            <h3 class="text-xl font-extrabold text-slate-800 tracking-tight mb-8">Produk Terlaris</h3>
            <div class="space-y-6">
                @forelse($this->topProducts as $idx => $item)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-orange-50 text-[#E97D5A] flex items-center justify-center text-[10px] font-black">
                            {{ $idx + 1 }}
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-800 leading-none mb-1">{{ $item->product->name }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $item->total_qty }} terjual</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-black text-slate-700">Rp {{ number_format($item->total_revenue, 0, ',', '.') }}</span>
                </div>
                @empty
                <p class="text-center py-10 text-sm font-bold text-slate-300 italic">Tidak ada data produk.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div id="transaksi-section" class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden print:border-0 print:shadow-none">
        <div class="p-10 border-b border-slate-50 flex items-center justify-between print:pt-4">
            <h3 class="text-xl font-black text-slate-800 tracking-tight">Detail Transaksi Terakhir</h3>
            <button onclick="window.print()" class="px-6 py-3 bg-[#1A1A1A] text-white rounded-2xl font-black text-xs hover:scale-105 transition-all print:hidden flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Export Laporan (PDF)
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-10 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Waktu</th>
                        <th class="px-10 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Kode Transaksi</th>
                        <th class="px-10 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Kasir</th>
                        <th class="px-10 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Metode</th>
                        <th class="px-10 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($this->detailedTransactions as $tx)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-10 py-6">
                            <p class="text-sm font-black text-slate-700 leading-none mb-1">{{ $tx->created_at->format('d M Y') }}</p>
                            <p class="text-[10px] font-bold text-slate-400">{{ $tx->created_at->format('H:i') }} WIB</p>
                        </td>
                        <td class="px-10 py-6">
                            <span class="text-xs font-bold text-slate-600 font-mono">{{ $tx->transaction_code }}</span>
                        </td>
                        <td class="px-10 py-6 uppercase text-[10px] font-black text-slate-500">
                            {{ $tx->cashier->name ?? 'System' }}
                        </td>
                        <td class="px-10 py-6">
                            <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-[9px] font-black uppercase tracking-wider">
                                {{ $tx->payment_method }}
                            </span>
                        </td>
                        <td class="px-10 py-6 text-right">
                             <span class="text-lg font-black text-slate-800 tabular-nums">Rp {{ number_format($tx->total_amount, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-10 py-20 text-center">
                            <p class="text-sm font-bold text-slate-300 italic">Tidak ada transaksi dalam periode ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->detailedTransactions->hasPages())
        <div class="px-10 py-8 border-t border-slate-50">
            {{ $this->detailedTransactions->links() }}
        </div>
        @endif
    </div>
</div>

@script
<script>
    // Scroll to section on initial load if ?page= is already in the URL
    if (new URLSearchParams(window.location.search).has('page')) {
        document.getElementById('transaksi-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Scroll after wire:navigate pagination changes the URL
    if (!window.__txScrollListenerAdded) {
        window.__txScrollListenerAdded = true;
        document.addEventListener('livewire:navigated', () => {
            if (new URLSearchParams(window.location.search).has('page')) {
                document.getElementById('transaksi-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }
</script>
@endscript
