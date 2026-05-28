<?php

use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Transaction;
use App\Models\Attendance;
use Carbon\Carbon;
use function Livewire\Volt\{state, layout, computed};

layout('layouts.cashier');

$stats = computed(function () {
    $today = Carbon::today();
    
    // Stats for the current logged-in cashier
    $myTransactions = Transaction::where('cashier_id', auth()->id())
        ->whereDate('created_at', $today)
        ->get();

    return [
        'count' => $myTransactions->count(),
        'revenue' => $myTransactions->sum('total_amount'),
        'last_sync' => now()->format('H:i'),
    ];
});

$menuStock = computed(function () {
    // Get all products with their ingredients
    $products = Product::with('ingredients')->where('is_available', true)->get();
    
    return $products->map(function ($product) {
        $portions = [];
        
        foreach ($product->ingredients as $ingredient) {
            $qtyUsed = $ingredient->pivot->qty_used;
            if ($qtyUsed > 0) {
                $portions[] = floor($ingredient->stock_qty / $qtyUsed);
            }
        }
        
        // If no ingredients defined, we treat it as "unlimited" or manual
        $estimate = empty($portions) ? null : min($portions);
        
        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category,
            'image' => $product->image_url,
            'estimate' => $estimate,
            'status' => $estimate === 0 ? 'Habis' : ($estimate <= 10 ? 'Menipis' : 'Tersedia'),
        ];
    });
});

$criticalIngredients = computed(function () {
    return Ingredient::whereRaw('stock_qty <= min_stock')
        ->orderBy('stock_qty', 'asc')
        ->get();
});

?>

<div class="p-8 space-y-10 pb-24">
    <!-- Header Hero -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Halo, {{ explode(' ', auth()->user()->name)[0] }}! 👋</h1>
            <p class="text-slate-400 font-bold mt-1 uppercase text-[10px] tracking-[0.2em]">Dashboard Pegawai / Pantau Stok</p>
        </div>
        
        <div class="flex items-center gap-4 bg-white p-2 pr-6 rounded-[2rem] shadow-sm border border-slate-100">
            <div class="w-12 h-12 bg-teal-50 rounded-2xl flex items-center justify-center text-[#14B8A6]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Shift Anda</p>
                <p class="text-sm font-black text-slate-800 tracking-tight">{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-[#1A1A1A] rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden group">
            <div class="relative z-10">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Penjualan Anda (Hari Ini)</p>
                <p class="text-3xl font-black text-white tracking-tighter">Rp {{ number_format($this->stats['revenue'], 0, ',', '.') }}</p>
            </div>
            <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-[#14B8A6] opacity-10 rounded-full blur-3xl group-hover:opacity-20 transition-opacity"></div>
        </div>

        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Transaksi</p>
            <p class="text-3xl font-black text-slate-800 tracking-tighter">{{ $this->stats['count'] }} <span class="text-sm text-slate-300 font-bold tracking-normal underline underline-offset-4 decoration-teal-200">Pesanan</span></p>
        </div>

        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 flex items-center justify-between">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Update Terakhir</p>
                <p class="text-3xl font-black text-slate-800 tracking-tighter">{{ $this->stats['last_sync'] }} <span class="text-xs text-[#14B8A6]">WIB</span></p>
            </div>
            <button wire:click="$refresh" class="w-12 h-12 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center hover:bg-[#14B8A6] hover:text-white transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
        </div>
    </div>

    <!-- Main Content: Menu & Ingredients -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        
        <!-- Left: Menu Stock Grid -->
        <div class="lg:col-span-8 space-y-6">
            <div class="flex items-center justify-between px-4">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight">Ketersediaan Menu</h2>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Live Inventory</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($this->menuStock as $item)
                @php 
                    $colorClass = match($item['status']) {
                        'Habis' => 'bg-rose-50 border-rose-100',
                        'Menipis' => 'bg-amber-50 border-amber-100',
                        default => 'bg-white border-slate-100'
                    };
                    $badgeClass = match($item['status']) {
                        'Habis' => 'bg-rose-500 text-white',
                        'Menipis' => 'bg-amber-500 text-white',
                        default => 'bg-emerald-500 text-white'
                    };
                @endphp
                <div class="{{ $colorClass }} border rounded-[2.5rem] p-6 shadow-sm transition-all hover:shadow-md group relative overflow-hidden">
                    <div class="flex items-start gap-5 relative z-10">
                        <div class="w-20 h-20 bg-slate-100 rounded-3xl overflow-hidden shrink-0 flex items-center justify-center text-slate-300">
                            @if(!empty($item['image']))
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $item['category'] }}</span>
                            <h3 class="text-lg font-black text-slate-800 truncate mb-2">{{ $item['name'] }}</h3>
                            
                            <div class="flex items-center gap-2">
                                @if($item['estimate'] !== null)
                                    <span class="text-xl font-black {{ $item['status'] == 'Habis' ? 'text-rose-600' : ($item['status'] == 'Menipis' ? 'text-amber-600' : 'text-[#14B8A6]') }}">
                                        {{ $item['estimate'] }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Porsi Tersedia</span>
                                @else
                                    <span class="px-3 py-1 bg-slate-100 text-slate-400 rounded-lg text-[9px] font-black uppercase tracking-widest">Manual Stock</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="absolute top-6 right-6">
                        <span class="px-3 py-1 {{ $badgeClass }} rounded-xl text-[9px] font-black uppercase tracking-widest shadow-lg shadow-current/20">
                            {{ $item['status'] }}
                        </span>
                    </div>

                    @if($item['status'] == 'Habis')
                    <div class="absolute inset-0 bg-white/40 backdrop-blur-[1px] flex items-center justify-center">
                         <div class="rotate-[-10deg] px-6 py-2 border-4 border-rose-500 text-rose-500 font-black text-2xl rounded-2xl uppercase tracking-tighter opacity-80">SOLD OUT</div>
                    </div>
                    @endif
                </div>
                @empty
                <div class="col-span-full py-20 bg-white border-2 border-dashed border-slate-100 rounded-[2.5rem] flex flex-col items-center justify-center text-center">
                    <p class="text-slate-300 font-bold italic mb-4">Belum ada menu yang didaftarkan atau tersedia.</p>
                    <a href="{{ route('cashier.pos') }}" class="text-xs font-black text-[#14B8A6] uppercase tracking-widest underline underline-offset-4">Buka POS</a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Critical Ingredients Sidebar -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 h-fit sticky top-8">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-black text-slate-800 tracking-tight">Peringatan Bahan</h3>
                    <div class="w-8 h-8 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                </div>

                <div class="space-y-6">
                    @forelse($this->criticalIngredients as $ing)
                    <div class="flex items-center justify-between group">
                        <div class="flex items-center gap-4">
                            <div class="w-2 h-8 {{ $ing->stock_qty <= 0 ? 'bg-rose-500' : 'bg-amber-400' }} rounded-full"></div>
                            <div>
                                <p class="text-sm font-black text-slate-700 leading-none mb-1 group-hover:text-[#14B8A6] transition-colors">{{ $ing->name }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Batas Min: {{ $ing->min_stock }} {{ $ing->unit }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-black {{ $ing->stock_qty <= 0 ? 'text-rose-600' : 'text-amber-600' }} tabular-nums">{{ number_format($ing->stock_qty, 2) }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase">{{ $ing->unit }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center py-10 text-center">
                        <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-400">Semua bahan baku tersedia aman.</p>
                    </div>
                    @endforelse
                </div>

                <div class="mt-10 pt-8 border-t border-slate-50">
                    <p class="text-[10px] font-bold text-slate-300 italic text-center leading-relaxed">
                        Data stok diperbarui otomatis setiap kali ada transaksi di POS. Hubungi Owner jika ada ketidaksesuaian stok fisik.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
