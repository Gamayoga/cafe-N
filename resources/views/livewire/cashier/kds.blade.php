<?php

use App\Models\Transaction;
use function Livewire\Volt\{state, layout, computed};

layout('layouts.cashier');

$orders = computed(function () {
    return Transaction::with('items.product')
        ->whereIn('status', ['pending', 'preparing', 'ready'])
        ->orderBy('created_at', 'asc')
        ->get();
});

$updateStatus = function ($id, $status) {
    $transaction = Transaction::find($id);
    if ($transaction) {
        $transaction->update(['status' => $status]);
    }
};

?>

<div class="h-full flex flex-col bg-slate-50 overflow-hidden" wire:poll.10s>
    <!-- Header -->
    <header class="h-24 bg-white border-b border-slate-100 flex items-center justify-between px-10 shrink-0">
        <div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tighter">Layar Dapur & Barista</h1>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Monitoring Pesanan Real-Time</p>
        </div>

        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 bg-teal-400 rounded-full animate-pulse"></span>
                <span class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ $this->orders->where('status', 'pending')->count() }} BARU</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 bg-blue-400 rounded-full"></span>
                <span class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ $this->orders->where('status', 'preparing')->count() }} DIPROSES</span>
            </div>
            <div class="h-8 w-px bg-slate-100"></div>
            <button wire:click="$refresh" class="w-12 h-12 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center hover:bg-[#14B8A6] hover:text-white transition-all group">
                <svg class="w-6 h-6 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
        </div>
    </header>

    <!-- Kanban/Order Grid -->
    <main class="flex-1 overflow-x-auto p-10 flex gap-8">
        
        <!-- PENDING ORDERS -->
        <div class="w-96 flex flex-col gap-6 shrink-0">
            <div class="flex items-center justify-between px-2">
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest">Pesanan Baru</h2>
                <span class="px-3 py-1 bg-teal-100 text-teal-600 rounded-lg text-[10px] font-black">{{ $this->orders->where('status', 'pending')->count() }}</span>
            </div>
            
            <div class="flex-1 space-y-6 overflow-y-auto no-scrollbar pb-10">
                @foreach($this->orders->where('status', 'pending') as $order)
                <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm hover:shadow-xl transition-all animate-in slide-in-from-left-4 duration-500">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <p class="text-[10px] font-black text-[#14B8A6] uppercase tracking-widest">{{ $order->transaction_code }}</p>
                            <p class="text-xs font-bold text-slate-400">{{ $order->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="px-3 py-1 bg-slate-100 rounded-lg text-[10px] font-black text-slate-500">#NEW</div>
                    </div>

                    <div class="space-y-4 mb-8">
                        @foreach($order->items as $item)
                        <div class="flex justify-between items-center bg-slate-50 p-3 rounded-2xl border border-slate-100">
                            <span class="text-sm font-black text-slate-800"><span class="text-[#14B8A6]">{{ $item->qty }}x</span> {{ $item->product->name }}</span>
                        </div>
                        @endforeach
                    </div>

                    <button wire:click="updateStatus({{ $order->id }}, 'preparing')" class="w-full py-4 bg-[#0A2A2F] text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-slate-200">
                        MULAI PROSES
                    </button>
                </div>
                @endforeach
            </div>
        </div>

        <!-- PREPARING ORDERS -->
        <div class="w-96 flex flex-col gap-6 shrink-0">
            <div class="flex items-center justify-between px-2">
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest">Sedang Dibuat</h2>
                <span class="px-3 py-1 bg-blue-100 text-blue-600 rounded-lg text-[10px] font-black">{{ $this->orders->where('status', 'preparing')->count() }}</span>
            </div>
            
            <div class="flex-1 space-y-6 overflow-y-auto no-scrollbar pb-10">
                @foreach($this->orders->where('status', 'preparing') as $order)
                <div class="bg-blue-50/50 rounded-[2.5rem] p-8 border border-blue-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-1 h-full bg-blue-400"></div>
                    
                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest">{{ $order->transaction_code }}</p>
                                <p class="text-xs font-bold text-blue-400">{{ $order->created_at->diffForHumans() }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 mb-8">
                            @foreach($order->items as $item)
                            <div class="flex justify-between items-center bg-white p-3 rounded-2xl border border-blue-50">
                                <span class="text-sm font-black text-slate-800"><span class="text-blue-500">{{ $item->qty }}x</span> {{ $item->product->name }}</span>
                            </div>
                            @endforeach
                        </div>

                        <button wire:click="updateStatus({{ $order->id }}, 'ready')" class="w-full py-4 bg-blue-500 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-blue-200">
                            PESANAN SIAP
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- READY TO SERVE -->
        <div class="w-96 flex flex-col gap-6 shrink-0">
            <div class="flex items-center justify-between px-2">
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest">Siap Disajikan</h2>
                <span class="px-3 py-1 bg-emerald-100 text-emerald-600 rounded-lg text-[10px] font-black">{{ $this->orders->where('status', 'ready')->count() }}</span>
            </div>
            
            <div class="flex-1 space-y-6 overflow-y-auto no-scrollbar pb-10">
                @foreach($this->orders->where('status', 'ready') as $order)
                <div class="bg-emerald-50/50 rounded-[2.5rem] p-8 border border-emerald-100 shadow-sm animate-pulse">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">{{ $order->transaction_code }}</p>
                            <p class="text-xs font-bold text-emerald-400">SIAP ANTAR</p>
                        </div>
                        <div class="w-10 h-10 bg-emerald-500 text-white rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                    </div>

                    <div class="space-y-4 mb-8">
                        @foreach($order->items as $item)
                        <div class="flex justify-between items-center bg-white p-3 rounded-2xl border border-emerald-50">
                            <span class="text-sm font-black text-slate-800"><span class="text-emerald-500">{{ $item->qty }}x</span> {{ $item->product->name }}</span>
                        </div>
                        @endforeach
                    </div>

                    <button wire:click="updateStatus({{ $order->id }}, 'served')" class="w-full py-4 bg-[#0A2A2F] text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg">
                        SUDAH DISAJIKAN
                    </button>
                </div>
                @endforeach
            </div>
        </div>

    </main>
</div>
