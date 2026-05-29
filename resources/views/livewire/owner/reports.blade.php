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
    $end   = Carbon::parse($this->endDate)->endOfDay();

    $daysCount = $start->diffInDays($end) + 1;
    $interval  = $daysCount > 60 ? (int)ceil($daysCount / 30) : 1;

    $raw = Transaction::whereBetween('created_at', [$start, $end])
        ->orderBy('created_at')
        ->get()
        ->groupBy(fn ($t) => $t->created_at->format('Y-m-d'));

    $items = [];
    for ($i = 0; $i < $daysCount; $i += $interval) {
        $date    = $start->copy()->addDays($i);
        $dateStr = $date->format('Y-m-d');
        $items[] = [
            'label'     => $date->format('d'),
            'month'     => $date->format('M'),
            'full_date' => $date->format('d M Y'),
            'value'     => (float)($raw->has($dateStr) ? $raw->get($dateStr)->sum('total_amount') : 0),
            'height'    => 0,
            'x_pct'     => 0,
            'y_pct'     => 0,
        ];
    }

    $count = count($items);
    $max   = collect($items)->max('value') ?: 1;

    // Nice rounded max for Y-axis
    $magnitude = pow(10, floor(log10(max($max, 1))));
    $niceMax   = ceil($max / $magnitude) * $magnitude ?: 100000;

    // Y ticks: 5 levels, rendered top→bottom
    $yTicks = [];
    for ($i = 4; $i >= 0; $i--) {
        $val  = ($niceMax / 4) * $i;
        $pct  = ($val / $niceMax) * 100;
        $svgY = 100 - ($pct * 0.8 + 10);
        $yTicks[] = [
            'value' => $val,
            'svgY'  => $svgY,
            'label' => $val >= 1000000
                ? number_format($val / 1000000, 1, ',', '.') . 'jt'
                : ($val >= 1000 ? number_format($val / 1000, 0) . 'rb' : number_format($val, 0)),
        ];
    }

    // SVG points & per-item coords
    $labelInterval = $count > 15 ? (int)ceil($count / 15) : 1;
    $points = '';
    foreach ($items as $idx => &$d) {
        $pct         = ($d['value'] / $niceMax) * 100;
        $d['height'] = $pct;
        $x           = $count > 1 ? ($idx / ($count - 1)) * 700 : 350;
        $y           = 100 - ($pct * 0.8 + 10);
        $points     .= "$x,$y ";
        $d['x_pct']      = $count > 1 ? ($idx / ($count - 1)) * 100 : 50;
        $d['y_pct']      = $y;
        $d['show_label'] = ($idx % $labelInterval === 0) || ($idx === $count - 1);
    }

    // Month groups for x-axis separator
    $months    = [];
    $prevMonth = null;
    $mStart    = 0;
    foreach ($items as $idx => $d) {
        if ($d['month'] !== $prevMonth) {
            if ($prevMonth !== null) {
                $months[] = ['label' => $prevMonth, 'count' => $idx - $mStart];
            }
            $prevMonth = $d['month'];
            $mStart    = $idx;
        }
    }
    if ($prevMonth !== null) {
        $months[] = ['label' => $prevMonth, 'count' => $count - $mStart];
    }

    return compact('items', 'points', 'niceMax', 'yTicks', 'months', 'count');
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
            <button wire:click="$refresh" class="w-10 h-10 bg-[#14B8A6] text-white rounded-xl flex items-center justify-center hover:scale-105 transition-all shadow-lg shadow-teal-100">
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
            <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-[#14B8A6] opacity-10 rounded-full blur-3xl group-hover:opacity-20 transition-opacity"></div>
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
        <!-- Sales Trend - Line Chart -->
        <div class="lg:col-span-8 bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Tren Penjualan</h3>
                <span class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase">
                    <span class="w-3 h-3 bg-[#14B8A6] rounded-full"></span> Pendapatan
                </span>
            </div>

            <div class="flex gap-3">
                {{-- Y-axis labels --}}
                <div class="relative shrink-0 h-48" style="width:52px">
                    @foreach($this->chartData['yTicks'] as $tick)
                    <span class="absolute right-0 text-[9px] font-black text-slate-400 leading-none -translate-y-1/2 text-right"
                          style="top:{{ $tick['svgY'] }}%">{{ $tick['label'] }}</span>
                    @endforeach
                </div>

                {{-- Chart + X-axis --}}
                <div class="flex-1 min-w-0">
                    {{-- SVG area --}}
                    <div class="relative h-48 w-full">
                        {{-- Grid lines --}}
                        @foreach($this->chartData['yTicks'] as $tick)
                        <div class="absolute w-full border-t border-dashed border-slate-100 pointer-events-none"
                             style="top:{{ $tick['svgY'] }}%"></div>
                        @endforeach

                        {{-- SVG line + fill --}}
                        <svg viewBox="0 0 700 100" class="w-full h-full overflow-visible" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="grad-rpt" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:#14B8A6;stop-opacity:0.22"/>
                                    <stop offset="100%" style="stop-color:#14B8A6;stop-opacity:0"/>
                                </linearGradient>
                            </defs>
                            @if($this->chartData['count'] > 1)
                            <polyline fill="url(#grad-rpt)" stroke="none"
                                points="0,100 {{ $this->chartData['points'] }} 700,100"/>
                            <polyline fill="none" stroke="#14B8A6" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round"
                                vector-effect="non-scaling-stroke"
                                points="{{ $this->chartData['points'] }}"/>
                            @endif
                        </svg>

                        {{-- Dots & tooltips --}}
                        @foreach($this->chartData['items'] as $d)
                        <div class="absolute group cursor-pointer"
                             style="left:{{ $d['x_pct'] }}%;top:{{ $d['y_pct'] }}%;transform:translate(-50%,-50%);z-index:20">
                            <div class="w-2.5 h-2.5 rounded-full bg-white border-2 border-[#14B8A6]"></div>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-slate-900 text-white text-[10px] font-black px-3 py-2 rounded-2xl opacity-0 group-hover:opacity-100 transition-all whitespace-nowrap z-30 shadow-xl scale-90 group-hover:scale-100 pointer-events-none">
                                <p class="text-[9px] text-slate-400 mb-0.5">{{ $d['full_date'] }}</p>
                                Rp {{ number_format($d['value'], 0, ',', '.') }}
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- X-axis: tanggal --}}
                    <div class="relative h-5 mt-2">
                        @foreach($this->chartData['items'] as $d)
                        @if($d['show_label'])
                        <span class="absolute text-[9px] font-black text-slate-400 -translate-x-1/2"
                              style="left:{{ $d['x_pct'] }}%">{{ $d['label'] }}</span>
                        @endif
                        @endforeach
                    </div>

                    {{-- X-axis: bulan --}}
                    <div class="flex border-t border-slate-100 pt-1.5 mt-0.5">
                        @foreach($this->chartData['months'] as $month)
                        <div class="overflow-hidden min-w-0" style="flex:{{ $month['count'] }}">
                            <span class="text-[9px] font-black text-[#14B8A6] uppercase tracking-widest">{{ $month['label'] }}</span>
                        </div>
                        @endforeach
                    </div>
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
                        <div class="w-8 h-8 rounded-xl bg-teal-50 text-[#14B8A6] flex items-center justify-center text-[10px] font-black">
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
            <div class="flex items-center gap-3 print:hidden">
                <a x-bind:href="`/owner/reports/export/pdf?startDate={{ $startDate }}&endDate={{ $endDate }}`"
                   class="px-6 py-3 bg-[#1A1A1A] text-white rounded-2xl font-black text-xs hover:scale-105 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    Export PDF
                </a>
                <a x-bind:href="`/owner/reports/export/excel?startDate={{ $startDate }}&endDate={{ $endDate }}`"
                   class="px-6 py-3 bg-emerald-600 text-white rounded-2xl font-black text-xs hover:scale-105 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export Excel
                </a>
            </div>
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
