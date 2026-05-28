<?php

use App\Models\Product;
use App\Models\Ingredient;
use Illuminate\Support\Facades\Storage;
use function Livewire\Volt\{state, layout, rules, computed, usesFileUploads};

layout('layouts.owner');
usesFileUploads();

state([
    'search' => '',
    'name' => '',
    'category' => 'Coffee',
    'price' => 0,
    'is_available' => true,
    'editingProductId' => null,
    'showForm' => false,
    // Recipe fields: array of [ingredient_id, qty_used]
    'recipeRows' => [],
    // Image upload (TemporaryUploadedFile) + path saved
    'image' => null,
    'existingImage' => null,
]);

rules([
    'name' => 'required|min:2',
    'category' => 'required',
    'price' => 'required|numeric|min:0',
    'is_available' => 'boolean',
    'image' => 'nullable|image|max:2048', // 2MB
    'recipeRows.*.ingredient_id' => 'required|exists:ingredients,id',
    'recipeRows.*.qty_used' => 'required|numeric|min:0.01',
]);

$products = computed(fn () =>
    Product::withCount('ingredients')
        ->where('name', 'like', '%' . $this->search . '%')
        ->latest()
        ->get()
);

$ingredients = computed(fn () => Ingredient::orderBy('name')->get());

$addRecipeRow = function () {
    $this->recipeRows[] = ['ingredient_id' => '', 'qty_used' => ''];
};

$removeRecipeRow = function ($index) {
    array_splice($this->recipeRows, $index, 1);
    $this->recipeRows = array_values($this->recipeRows);
};

$save = function () {
    $this->validate();

    $data = [
        'name' => $this->name,
        'category' => $this->category,
        'price' => $this->price,
        'is_available' => $this->is_available,
    ];

    // Handle image upload: store new file, hapus file lama jika diganti
    if ($this->image) {
        if ($this->existingImage) {
            Storage::disk('public')->delete($this->existingImage);
        }
        $data['image_url'] = $this->image->store('products', 'public');
    }

    if ($this->editingProductId) {
        $product = Product::find($this->editingProductId);
        $product->update($data);
    } else {
        $product = Product::create($data);
    }

    // Sync recipe (ingredients pivot)
    $sync = [];
    foreach ($this->recipeRows as $row) {
        if (!empty($row['ingredient_id'])) {
            $sync[$row['ingredient_id']] = ['qty_used' => $row['qty_used']];
        }
    }
    $product->ingredients()->sync($sync);

    $this->reset('name', 'category', 'price', 'is_available', 'editingProductId', 'showForm', 'recipeRows', 'image', 'existingImage');
    $this->category = 'Coffee';
    $this->is_available = true;
};

$edit = function ($id) {
    $p = Product::with('ingredients')->find($id);
    $this->editingProductId = $id;
    $this->name = $p->name;
    $this->category = $p->category;
    $this->price = $p->price;
    $this->is_available = $p->is_available;
    $this->existingImage = $p->image_url;
    $this->image = null;
    $this->recipeRows = $p->ingredients->map(fn ($i) => [
        'ingredient_id' => $i->id,
        'qty_used' => $i->pivot->qty_used,
    ])->toArray();
    $this->showForm = true;
};

$removeImage = function () {
    if ($this->existingImage) {
        Storage::disk('public')->delete($this->existingImage);
        if ($this->editingProductId) {
            Product::where('id', $this->editingProductId)->update(['image_url' => null]);
        }
        $this->existingImage = null;
    }
    $this->image = null;
};

$toggleAvailability = function ($id) {
    $p = Product::find($id);
    $p->update(['is_available' => !$p->is_available]);
};

$delete = function ($id) {
    $p = Product::find($id);
    if ($p->image_url) {
        Storage::disk('public')->delete($p->image_url);
    }
    $p->delete();
};

$cancel = function () {
    $this->reset('name', 'category', 'price', 'is_available', 'editingProductId', 'showForm', 'recipeRows', 'image', 'existingImage');
    $this->category = 'Coffee';
    $this->is_available = true;
};

?>

<div class="space-y-8 pb-20">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tighter">Produk Menu</h1>
            <p class="text-slate-400 font-bold mt-1 uppercase text-[10px] tracking-[0.2em]">Inventori / Produk Menu</p>
        </div>
        <button wire:click="$toggle('showForm')"
                class="px-6 py-3 bg-[#14B8A6] text-white rounded-2xl font-black text-sm shadow-lg shadow-teal-100/50 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
            {{ $showForm ? 'Tutup Form' : 'Tambah Produk Baru' }}
        </button>
    </div>

    @if($showForm)
    <!-- Form Panel -->
    <div class="bg-white rounded-[2.5rem] p-10 shadow-xl border border-teal-50">
        <h2 class="text-2xl font-black text-slate-800 mb-8">
            {{ $editingProductId ? 'Ubah Produk Menu' : 'Daftarkan Produk Baru' }}
        </h2>

        <form wire:submit="save">
            <!-- Image Upload -->
            <div class="mb-8">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Foto Produk</label>
                <div class="flex items-start gap-6">
                    <!-- Preview -->
                    <div class="w-32 h-32 rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                        @if($image)
                            <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover" alt="Preview">
                        @elseif($existingImage)
                            <img src="{{ Storage::url($existingImage) }}" class="w-full h-full object-cover" alt="Existing">
                        @else
                            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        @endif
                    </div>
                    <!-- Input -->
                    <div class="flex-1">
                        <label class="inline-block cursor-pointer">
                            <span class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-black text-xs uppercase tracking-widest transition-all inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                {{ $image || $existingImage ? 'Ganti Foto' : 'Pilih Foto' }}
                            </span>
                            <input wire:model="image" type="file" accept="image/*" class="hidden">
                        </label>
                        @if($image || $existingImage)
                            <button type="button" wire:click="removeImage"
                                    class="ml-2 px-5 py-3 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl font-black text-xs uppercase tracking-widest transition-all inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                Hapus
                            </button>
                        @endif
                        <p class="text-[11px] text-slate-400 font-bold mt-3">Format: JPG/PNG. Maksimal 2MB.</p>
                        <div wire:loading wire:target="image" class="text-[11px] text-[#14B8A6] font-black mt-1 uppercase tracking-widest">Mengunggah...</div>
                        @error('image') <span class="text-rose-500 text-xs font-bold mt-1 ml-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Nama Produk</label>
                    <input wire:model="name" type="text" placeholder="Contoh: Kopi Susu Gula Aren"
                           class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl text-slate-800 font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all">
                    @error('name') <span class="text-rose-500 text-xs font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Kategori</label>
                    <select wire:model="category"
                            class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl text-slate-800 font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all appearance-none">
                        @foreach(['Coffee', 'Non-Coffee', 'Tea', 'Snack', 'Meal', 'Dessert', 'Other'] as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Harga Jual (Rp)</label>
                    <input wire:model="price" type="number" min="0" step="500" placeholder="25000"
                           class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl text-slate-800 font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all">
                    @error('price') <span class="text-rose-500 text-xs font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center gap-4 mt-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tersedia untuk dijual?</label>
                    <button type="button" wire:click="$toggle('is_available')"
                            class="relative inline-flex items-center h-7 w-14 rounded-full transition-colors {{ $is_available ? 'bg-emerald-500' : 'bg-slate-200' }}">
                        <span class="inline-block w-5 h-5 bg-white rounded-full shadow transform transition-transform {{ $is_available ? 'translate-x-8' : 'translate-x-1' }}"></span>
                    </button>
                    <span class="text-sm font-black {{ $is_available ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $is_available ? 'Ya, Tersedia' : 'Tidak Tersedia' }}
                    </span>
                </div>
            </div>

            <!-- Recipe / Resep Section -->
            <div class="border-t border-slate-100 pt-8 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-black text-slate-800">Resep Bahan Baku</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Bahan yang digunakan per satu porsi</p>
                    </div>
                    <button type="button" wire:click="addRecipeRow"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-black text-xs transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                        Tambah Bahan
                    </button>
                </div>

                @if(count($recipeRows) === 0)
                <div class="text-center py-10 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-100">
                    <p class="text-slate-400 font-bold italic text-sm">Belum ada resep bahan baku.</p>
                    <button type="button" wire:click="addRecipeRow" class="mt-2 text-[#14B8A6] font-black text-xs uppercase tracking-widest">+ Klik untuk tambah bahan</button>
                </div>
                @else
                <div class="space-y-3">
                    @foreach($recipeRows as $idx => $row)
                    <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl">
                        <div class="flex-1">
                            <select wire:model="recipeRows.{{ $idx }}.ingredient_id"
                                    class="w-full px-4 py-3 bg-white border-0 rounded-xl text-slate-800 font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all appearance-none text-sm">
                                <option value="">— Pilih Bahan —</option>
                                @foreach($this->ingredients as $ing)
                                    <option value="{{ $ing->id }}">{{ $ing->name }} ({{ $ing->unit }})</option>
                                @endforeach
                            </select>
                            @error("recipeRows.{$idx}.ingredient_id") <span class="text-rose-500 text-[10px] font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-40">
                            <div class="relative">
                                <input wire:model="recipeRows.{{ $idx }}.qty_used" type="number" step="0.01" min="0" placeholder="Jumlah"
                                       class="w-full pl-4 pr-4 py-3 bg-white border-0 rounded-xl text-slate-800 font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all text-sm">
                            </div>
                            @error("recipeRows.{$idx}.qty_used") <span class="text-rose-500 text-[10px] font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                        </div>
                        <button type="button" wire:click="removeRecipeRow({{ $idx }})"
                                class="w-10 h-10 bg-white text-rose-400 rounded-xl flex items-center justify-center hover:bg-rose-50 transition-all flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="flex justify-end gap-4 pt-4 border-t border-slate-50">
                <button type="button" wire:click="cancel" class="px-8 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-sm hover:bg-slate-200 transition-all">Batal</button>
                <button type="submit" class="px-8 py-4 bg-[#1A1A1A] text-white rounded-2xl font-black text-sm shadow-xl hover:scale-105 active:scale-95 transition-all">
                    {{ $editingProductId ? 'Perbarui Produk' : 'Simpan Produk' }}
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Products Grid -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-xl font-black text-slate-800 tracking-tight">Daftar Produk Menu</h3>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input wire:model.live="search" type="text" placeholder="Cari nama produk..."
                       class="pl-12 pr-6 py-3 bg-slate-50 border-0 rounded-2xl w-full md:w-72 text-sm font-bold placeholder:text-slate-300 focus:ring-2 focus:ring-[#14B8A6] transition-all">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Produk</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Kategori</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Harga</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Bahan</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($this->products as $p)
                    @php
                        $catColors = [
                            'Coffee' => 'text-amber-700 bg-amber-50',
                            'Non-Coffee' => 'text-indigo-700 bg-indigo-50',
                            'Tea' => 'text-emerald-700 bg-emerald-50',
                            'Snack' => 'text-rose-700 bg-rose-50',
                            'Meal' => 'text-teal-700 bg-teal-50',
                            'Dessert' => 'text-pink-700 bg-pink-50',
                        ];
                        $catStyle = $catColors[$p->category] ?? 'text-slate-700 bg-slate-50';
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                @if($p->image_url)
                                    <img src="{{ Storage::url($p->image_url) }}" alt="{{ $p->name }}"
                                         class="w-11 h-11 rounded-2xl object-cover border border-slate-100">
                                @else
                                    <div class="w-11 h-11 bg-[#0A2A2F] rounded-2xl flex items-center justify-center text-white font-black text-sm group-hover:bg-[#14B8A6] transition-colors">
                                        {{ substr($p->name, 0, 1) }}
                                    </div>
                                @endif
                                <span class="font-black text-slate-700">{{ $p->name }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span class="px-3 py-1.5 {{ $catStyle }} rounded-xl text-[10px] font-black uppercase tracking-wider">{{ $p->category }}</span>
                        </td>
                        <td class="px-8 py-6">
                            <span class="font-black text-slate-800 tabular-nums">Rp {{ number_format($p->price, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-8 py-6">
                            <span class="font-bold text-slate-500">{{ $p->ingredients_count }} bahan</span>
                        </td>
                        <td class="px-8 py-6">
                            <button wire:click="toggleAvailability({{ $p->id }})"
                                    class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all hover:scale-105
                                    {{ $p->is_available ? 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100' : 'text-rose-600 bg-rose-50 hover:bg-rose-100' }}">
                                {{ $p->is_available ? '✓ Tersedia' : '✗ Tidak Tersedia' }}
                            </button>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="edit({{ $p->id }})" class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button wire:confirm="Hapus produk ini?" wire:click="delete({{ $p->id }})" class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 mb-4 border border-slate-100">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                <p class="text-slate-400 font-bold italic mb-4">Belum ada produk menu tercatat.</p>
                                <button wire:click="$set('showForm', true)" class="text-sm font-black text-[#14B8A6] uppercase tracking-widest border-b-2 border-teal-100 hover:border-teal-400 transition-colors">Klik untuk tambah</button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
