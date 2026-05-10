<?php

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Ingredient;
use App\Models\StockLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use function Livewire\Volt\{state, layout, computed, on};

layout('layouts.cashier');

state([
    'search' => '',
    'selectedCategory' => 'All',
    'cart' => [], // [ ['id' => 1, 'name' => 'Kopi', 'price' => 20000, 'qty' => 1] ]
    'paymentMethod' => 'cash',
    'customerName' => '',
    'showReceipt' => false,
    'lastTransaction' => null,
    'showMobileCart' => false,
]);

$categories = computed(fn () => 
    collect(['All'])->merge(Product::distinct()->pluck('category'))->toArray()
);

$products = computed(function () {
    return Product::where('is_available', true)
        ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
        ->when($this->selectedCategory !== 'All', fn($q) => $q->where('category', $this->selectedCategory))
        ->get();
});

$subtotal = computed(function () {
    return collect($this->cart)->sum(fn($item) => $item['price'] * $item['qty']);
});

$total = computed(fn() => $this->subtotal);

// Actions
$addToCart = function ($productId) {
    $product = Product::find($productId);
    if (!$product) return;

    $cart = collect($this->cart);
    $existing = $cart->firstWhere('id', $productId);

    if ($existing) {
        $this->cart = $cart->map(function ($item) use ($productId) {
            if ($item['id'] === $productId) {
                $item['qty']++;
            }
            return $item;
        })->toArray();
    } else {
        $this->cart[] = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'qty' => 1,
            'image' => $product->image_url ?? null
        ];
    }
};

$removeFromCart = function ($productId) {
    $this->cart = collect($this->cart)->filter(fn($item) => $item['id'] !== $productId)->toArray();
};

$updateQty = function ($productId, $delta) {
    $this->cart = collect($this->cart)->map(function ($item) use ($productId, $delta) {
        if ($item['id'] === $productId) {
            $item['qty'] = max(1, $item['qty'] + $delta);
        }
        return $item;
    })->toArray();
};

$clearCart = function () {
    $this->cart = [];
};

$checkout = function () {
    if (empty($this->cart)) return;

    DB::transaction(function () {
        // 1. Create Transaction
        $transaction = Transaction::create([
            'transaction_code' => 'TRX-' . strtoupper(Str::random(8)),
            'cashier_id' => auth()->id(),
            'total_amount' => $this->total,
            'payment_method' => $this->paymentMethod,
            'status' => 'pending',
        ]);

        foreach ($this->cart as $item) {
            // 2. Create Transaction Items
            TransactionItem::create([
                'transaction_id' => $transaction->id,
                'product_id' => $item['id'],
                'qty' => $item['qty'],
                'price_at_sale' => $item['price'],
            ]);

            // 3. Deduct Stock based on Recipe
            $product = Product::with('ingredients')->find($item['id']);
            foreach ($product->ingredients as $ingredient) {
                $qtyToDeduct = $ingredient->pivot->qty_used * $item['qty'];
                
                // Subtract
                $ingredient->decrement('stock_qty', $qtyToDeduct);

                // Log Stock
                StockLog::create([
                    'ingredient_id' => $ingredient->id,
                    'type' => 'out',
                    'qty' => $qtyToDeduct,
                    'recorded_by' => auth()->id(),
                    'reason' => 'Penjualan ' . $transaction->transaction_code,
                ]);
            }
        }

        $this->lastTransaction = $transaction->load('items.product');
        $this->showReceipt = true;
        $this->cart = [];
    });
};

?>

<div class="flex h-screen w-full bg-slate-100 overflow-hidden relative">
    <!-- Left Section: Product Browser -->
    <div class="flex-1 flex flex-col min-w-0 bg-white">
        <!-- Top Header & Search -->
        <header class="h-20 border-b border-slate-100 flex items-center justify-between px-8 bg-white sticky top-0 z-10">
            <div class="relative w-96 group">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 group-focus-within:text-[#E97D5A] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input wire:model.live="search" type="text" placeholder="Cari menu..." 
                       class="pl-12 pr-6 py-2.5 bg-slate-50 border-0 rounded-2xl w-full text-sm font-bold focus:ring-2 focus:ring-[#E97D5A] transition-all">
            </div>

            <!-- Mobile Cart Toggle -->
            <button @click="$wire.set('showMobileCart', true)" class="lg:hidden relative">
                <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-slate-100 text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    @if(count($cart) > 0)
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-[#E97D5A] rounded-full text-[10px] font-black text-white flex items-center justify-center border-2 border-white">
                            {{ collect($cart)->sum('qty') }}
                        </span>
                    @endif
                </div>
            </button>

            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Kasir Bertugas</p>
                    <p class="text-sm font-black text-slate-800 tracking-tight leading-loose">{{ auth()->user()->name }}</p>
                </div>
                <div class="w-10 h-10 bg-[#111111] rounded-xl flex items-center justify-center text-white font-bold text-xs uppercase">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </div>
        </header>

        <!-- Categories -->
        <div class="px-6 lg:px-8 py-4 lg:py-6 flex items-center gap-3 overflow-x-auto no-scrollbar border-b border-slate-100 bg-white shadow-sm">
            @foreach($this->categories as $cat)
            <button wire:click="$set('selectedCategory', '{{ $cat }}')" 
                    class="px-5 lg:px-6 py-2 rounded-xl text-[10px] lg:text-xs font-black uppercase tracking-widest whitespace-nowrap transition-all
                    {{ $selectedCategory == $cat ? 'bg-[#E97D5A] text-white shadow-lg shadow-orange-200' : 'bg-slate-50 text-slate-400 hover:bg-slate-100' }}">
                {{ $cat }}
            </button>
            @endforeach
        </div>

        <!-- Product Grid -->
        <div class="flex-1 overflow-y-auto p-4 lg:p-8 grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 xxl:grid-cols-5 gap-4 lg:gap-6 bg-slate-50/50">
            @forelse($this->products as $p)
            <button wire:click="addToCart({{ $p->id }})" 
                    class="bg-white rounded-[1.5rem] lg:rounded-[2rem] p-3 lg:p-5 shadow-sm border border-slate-100 flex flex-col hover:border-[#E97D5A] hover:bg-orange-50/10 transition-all group text-left relative overflow-hidden">
                <div class="w-full aspect-square bg-slate-50 rounded-xl lg:rounded-2xl mb-3 lg:mb-4 overflow-hidden relative">
                    @if($p->image_url)
                        <img src="{{ Storage::url($p->image_url) }}" alt="{{ $p->name }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    @else
                        <!-- Image Placeholder -->
                        <div class="absolute inset-0 flex items-center justify-center text-slate-200">
                            <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path></svg>
                        </div>
                    @endif
                </div>
                <div>
                    <span class="text-[9px] lg:text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $p->category }}</span>
                    <h3 class="text-xs lg:text-sm font-black text-slate-800 leading-tight mb-1 lg:mb-2 group-hover:text-[#E97D5A] transition-colors truncate">{{ $p->name }}</h3>
                    <p class="text-base lg:text-lg font-black text-[#E97D5A]">Rp {{ number_format($p->price, 0, ',', '.') }}</p>
                </div>
                <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                    <div class="w-8 h-8 bg-[#E97D5A] rounded-xl flex items-center justify-center text-white shadow-lg shadow-orange-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                </div>
            </button>
            @empty
            <div class="col-span-full py-20 text-center italic text-slate-300 font-bold">Menu tidak ditemukan.</div>
            @endforelse
        </div>
    </div>

    <!-- Right Section: Cart (Desktop Sidebar / Mobile Drawer) -->
    <div :class="$wire.showMobileCart ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
         class="fixed lg:static top-0 right-0 w-full lg:w-[400px] h-full bg-white lg:border-l border-slate-100 flex flex-col z-[100] lg:z-20 transition-transform duration-300">
        
        <!-- Mobile Cart Header -->
        <div class="lg:hidden p-6 bg-slate-900 flex items-center justify-between text-white">
            <div class="flex items-center gap-3">
                <button @click="$wire.set('showMobileCart', false)" class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <h2 class="text-lg font-black tracking-tight">Detail Pesanan</h2>
            </div>
            <span class="text-xs font-black px-3 py-1 bg-[#E97D5A] rounded-lg tabular-nums">{{ collect($cart)->sum('qty') }} Items</span>
        </div>

        <div class="p-6 lg:p-8 border-b border-slate-100 flex items-center justify-between bg-white hidden lg:flex">
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Pesanan</h2>
            <button wire:click="clearCart" class="text-[10px] font-black text-rose-500 uppercase tracking-widest hover:underline">Hapus Semua</button>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-8 space-y-6">
            @forelse($this->cart as $item)
            <div class="flex gap-4">
                <div class="w-16 h-16 bg-slate-50 rounded-2xl shrink-0 overflow-hidden flex items-center justify-center text-slate-200">
                    @if(!empty($item['image']))
                        <img src="{{ Storage::url($item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                    @else
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path></svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-start mb-1">
                        <p class="text-sm font-black text-slate-800 leading-none truncate pr-2">{{ $item['name'] }}</p>
                        <button wire:click="removeFromCart({{ $item['id'] }})" class="text-slate-300 hover:text-rose-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <p class="text-xs font-black text-[#E97D5A] mb-3">Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                    
                    <div class="flex items-center gap-3">
                        <button wire:click="updateQty({{ $item['id'] }}, -1)" class="w-7 h-7 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400 hover:bg-[#E97D5A] hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"></path></svg>
                        </button>
                        <span class="text-sm font-black text-slate-800 w-4 text-center tabular-nums">{{ $item['qty'] }}</span>
                        <button wire:click="updateQty({{ $item['id'] }}, 1)" class="w-7 h-7 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400 hover:bg-[#E97D5A] hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="flex flex-col items-center justify-center py-20 text-center space-y-4">
                <div class="w-20 h-20 bg-slate-50 rounded-[2rem] flex items-center justify-center text-slate-200">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <p class="text-sm font-bold text-slate-300 italic px-10">Pilih menu dari kiri untuk mulai memesan.</p>
            </div>
            @endforelse
        </div>

        <!-- Checkout Section -->
        <div class="p-8 bg-white border-t border-slate-100 space-y-6">
            <div class="space-y-3">
                <div class="flex justify-between text-sm font-bold text-slate-400 uppercase tracking-tight">
                    <span>Subtotal</span>
                    <span class="text-slate-800">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xl font-black text-slate-800 tracking-tighter">
                    <span>Total</span>
                    <span class="text-[#E97D5A]">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="space-y-4">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Metode Pembayaran</p>
                <div class="grid grid-cols-2 gap-3">
                    <button wire:click="$set('paymentMethod', 'cash')" class="py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest border-2 transition-all
                            {{ $paymentMethod == 'cash' ? 'border-[#E97D5A] bg-orange-50 text-[#E97D5A]' : 'border-slate-100 bg-white text-slate-400 hover:border-slate-200' }}">
                        TUNAI
                    </button>
                    <button wire:click="$set('paymentMethod', 'qris')" class="py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest border-2 transition-all
                            {{ $paymentMethod == 'qris' ? 'border-[#E97D5A] bg-orange-50 text-[#E97D5A]' : 'border-slate-100 bg-white text-slate-400 hover:border-slate-200' }}">
                        QRIS / NON-TUNAI
                    </button>
                </div>
            </div>

            <button wire:click="checkout" 
                    @if(empty($this->cart)) disabled @endif
                    class="w-full py-5 bg-[#111111] text-white rounded-[1.8rem] font-black text-sm uppercase tracking-widest hover:scale-[1.02] active:scale-95 disabled:opacity-30 disabled:hover:scale-100 shadow-xl shadow-slate-200 transition-all">
                Bayar & Pesan
            </button>
        </div>
    </div>

    <!-- Receipt Modal Overlay -->
    @if($showReceipt && $lastTransaction)
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-6">
        <div class="bg-white rounded-[2.5rem] w-full max-w-sm overflow-hidden shadow-2xl animate-in zoom-in-95 duration-200">
            <div class="p-8 text-center bg-[#E97D5A] text-white">
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="text-2xl font-black tracking-tighter">Pembayaran Berhasil!</h3>
                <p class="text-white/70 text-sm font-bold mt-1 uppercase tracking-widest">{{ $lastTransaction->transaction_code }}</p>
            </div>
            
            <div class="p-8 space-y-6">
                <div class="space-y-4 border-b border-dashed border-slate-200 pb-6">
                    @foreach($lastTransaction->items as $item)
                    <div class="flex justify-between items-center text-sm">
                        <span class="font-bold text-slate-600">{{ $item->qty }}x {{ $item->product->name }}</span>
                        <span class="font-black text-slate-800">Rp {{ number_format($item->qty * $item->price_at_sale, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>

                <div class="flex justify-between items-center text-xl font-black text-slate-800 tracking-tighter">
                    <span>Total</span>
                    <span class="text-[#E97D5A]">Rp {{ number_format($lastTransaction->total_amount, 0, ',', '.') }}</span>
                </div>

                <div class="flex flex-col gap-3 py-4">
                    <button onclick="window.print()" class="w-full py-4 bg-slate-100 text-slate-800 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-200 transition-all">Cetak Struk</button>
                    <button wire:click="$set('showReceipt', false)" class="w-full py-4 bg-[#111111] text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 transition-all">Pesanan Baru</button>
                </div>
            </div>
            
            <!-- Hidden Print Template -->
            <div class="hidden print:block p-10 text-slate-900">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-black uppercase tracking-tighter">NORTHERN CAFE</h2>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Resi Pembayaran Resmi</p>
                </div>
                <div class="space-y-4 mb-8">
                    @foreach($lastTransaction->items as $item)
                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                        <span>{{ $item->product->name }} x{{ $item->qty }}</span>
                        <span>Rp {{ number_format($item->qty * $item->price_at_sale, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="flex justify-between font-black text-lg pt-4 border-t-2 border-slate-900">
                    <span>TOTAL</span>
                    <span>Rp {{ number_format($lastTransaction->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="text-center mt-12 text-[10px] font-bold uppercase tracking-widest">
                    Terima kasih telah berkunjung!<br>
                    {{ $lastTransaction->created_at->format('d/m/Y H:i:s') }}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
