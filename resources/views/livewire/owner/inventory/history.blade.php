<?php

use App\Models\StockLog;
use App\Services\ExpiredIngredientService;
use function Livewire\Volt\{state, layout, computed, mount};

layout('layouts.owner');

state([
    'search' => '',
    'type' => '',
]);

mount(function () {
    ExpiredIngredientService::processExpired(auth()->id());
});

$logs = computed(fn () =>
    StockLog::with(['ingredient', 'recorder'])
        ->when($this->search, function ($query) {
            $query->whereHas('ingredient', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        })
        ->when($this->type, fn ($q) => $q->where('type', $this->type))
        ->latest()
        ->paginate(15)
);

?>

<div class="space-y-8 pb-20">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tighter">Riwayat Stok</h1>
            <p class="text-slate-400 font-bold mt-1 uppercase text-[10px] tracking-[0.2em]">Inventori / Riwayat</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="relative w-full md:w-80">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input wire:model.live="search" type="text" placeholder="Cari bahan baku..." 
                   class="pl-12 pr-6 py-3 bg-slate-50 border-0 rounded-2xl w-full text-sm font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all">
        </div>

        <div class="flex items-center gap-4 w-full md:w-auto">
            <select wire:model.live="type" class="px-6 py-3 bg-slate-50 border-0 rounded-2xl text-sm font-bold text-slate-600 focus:ring-2 focus:ring-[#14B8A6] transition-all appearance-none cursor-pointer pr-10">
                <option value="">Semua Tipe</option>
                <option value="in">Barang Masuk</option>
                <option value="out">Barang Keluar</option>
                <option value="waste">Basi / Kadaluarsa</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Waktu</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Bahan Baku</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Tipe</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Jumlah</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Oleh</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Alasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($this->logs as $log)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-6">
                            <p class="text-sm font-black text-slate-700 leading-none mb-1">{{ $log->created_at->format('d M Y') }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">{{ $log->created_at->format('H:i') }} WIB</p>
                        </td>
                        <td class="px-8 py-6">
                             <span class="font-black text-slate-800">{{ $log->ingredient->name ?? 'Deleted Ingredient' }}</span>
                        </td>
                        <td class="px-8 py-6">
                            @php
                                $typeStyle = match($log->type) {
                                    'in'    => 'text-emerald-600 bg-emerald-50',
                                    'out'   => 'text-indigo-600 bg-indigo-50',
                                    'waste' => 'text-rose-600 bg-rose-50',
                                    default => 'text-slate-600 bg-slate-50',
                                };
                                $typeLabel = match($log->type) {
                                    'in'    => 'Masuk',
                                    'out'   => 'Keluar',
                                    'waste' => 'Kadaluarsa',
                                    default => $log->type,
                                };
                            @endphp
                            <span class="px-3 py-1.5 {{ $typeStyle }} rounded-xl text-[10px] font-black uppercase tracking-wider">{{ $typeLabel }}</span>
                        </td>
                        <td class="px-8 py-6 text-right">
                            @php
                                $qtyColor = match($log->type) {
                                    'in'    => 'text-emerald-600',
                                    'waste' => 'text-rose-600',
                                    default => 'text-indigo-600',
                                };
                            @endphp
                            <span class="text-lg font-black {{ $qtyColor }} tabular-nums">
                                {{ $log->type === 'in' ? '+' : '-' }}{{ number_format($log->qty, 2) }}
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase ml-1">{{ $log->ingredient->unit ?? '' }}</span>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-600">{{ $log->recorder->name ?? 'System' }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span class="text-sm font-bold text-slate-400 italic leading-tight block max-w-xs">{{ $log->reason ?: '—' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <p class="text-slate-400 font-bold italic">Belum ada riwayat pergerakan stok.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($this->logs->hasPages())
        <div class="p-8 border-t border-slate-50">
            {{ $this->logs->links() }}
        </div>
        @endif
    </div>
</div>
