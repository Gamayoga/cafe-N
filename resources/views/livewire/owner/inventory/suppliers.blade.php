<?php

use App\Models\Supplier;
use function Livewire\Volt\{state, layout, rules, computed};

layout('layouts.owner');

state([
    'search' => '',
    'name' => '',
    'contact' => '',
    'address' => '',
    'editingSupplierId' => null,
    'showForm' => false,
]);

rules([
    'name' => 'required|min:3',
    'contact' => 'nullable',
    'address' => 'nullable',
]);

$suppliers = computed(fn () => 
    Supplier::where('name', 'like', '%' . $this->search . '%')
        ->latest()
        ->get()
);

$save = function () {
    $this->validate();

    if ($this->editingSupplierId) {
        Supplier::find($this->editingSupplierId)->update([
            'name' => $this->name,
            'contact' => $this->contact,
            'address' => $this->address,
        ]);
    } else {
        Supplier::create([
            'name' => $this->name,
            'contact' => $this->contact,
            'address' => $this->address,
        ]);
    }

    $this->reset('name', 'contact', 'address', 'editingSupplierId', 'showForm');
};

$edit = function ($id) {
    $supplier = Supplier::find($id);
    $this->editingSupplierId = $id;
    $this->name = $supplier->name;
    $this->contact = $supplier->contact;
    $this->address = $supplier->address;
    $this->showForm = true;
};

$delete = function ($id) {
    Supplier::find($id)->delete();
};

$cancel = function () {
    $this->reset('name', 'contact', 'address', 'editingSupplierId', 'showForm');
};

?>

<div class="space-y-8 pb-20">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tighter">Manajemen Supplier</h1>
            <p class="text-slate-400 font-bold mt-1 uppercase text-[10px] tracking-[0.2em]">Inventori / Supplier</p>
        </div>
        <button wire:click="$toggle('showForm')" class="px-6 py-3 bg-[#14B8A6] text-white rounded-2xl font-black text-sm shadow-lg shadow-teal-100/50 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
            {{ $showForm ? 'Tutup Form' : 'Tambah Supplier Baru' }}
        </button>
    </div>

    @if($showForm)
    <!-- Form Area (Floating Style) -->
    <div class="bg-white rounded-[2.5rem] p-10 shadow-xl border border-teal-50 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-10 opacity-5">
            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path></svg>
        </div>
        
        <h2 class="text-2xl font-black text-slate-800 mb-8">{{ $editingSupplierId ? 'Ubah Supplier' : 'Daftarkan Supplier Baru' }}</h2>
        
        <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
            <div class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Nama Supplier / Perusahaan</label>
                    <input wire:model="name" type="text" placeholder="Contoh: PT. Kopi Nusantara" 
                           class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl text-slate-800 font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all">
                    @error('name') <span class="text-rose-500 text-xs font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Kontak (WhatsApp / Email)</label>
                    <input wire:model="contact" type="text" placeholder="0812XXXXXXXX / email@domain.com"
                           class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl text-slate-800 font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all">
                </div>
            </div>
            <div class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Alamat Kantor / Gudang</label>
                    <textarea wire:model="address" rows="4" placeholder="Masukkan alamat lengkap supplier..."
                              class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl text-slate-800 font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all"></textarea>
                </div>
            </div>
            
            <div class="md:col-span-2 flex justify-end gap-4 pt-4">
                <button type="button" wire:click="cancel" class="px-8 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-sm hover:bg-slate-200 transition-all">Batal</button>
                <button type="submit" class="px-8 py-4 bg-[#1A1A1A] text-white rounded-2xl font-black text-sm shadow-xl hover:scale-105 active:scale-95 transition-all">
                    {{ $editingSupplierId ? 'Perbarui Data' : 'Simpan Supplier' }}
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Table Section -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-xl font-black text-slate-800 tracking-tight">Daftar Rekanan Supplier</h3>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input wire:model.live="search" type="text" placeholder="Cari nama supplier..." 
                       class="pl-12 pr-6 py-3 bg-slate-50 border-0 rounded-2xl w-full md:w-80 text-sm font-bold placeholder:text-slate-300 focus:ring-2 focus:ring-[#14B8A6] transition-all">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Supplier</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Kontak</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Alamat</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($this->suppliers as $s)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-teal-50 rounded-2xl flex items-center justify-center text-[#14B8A6] font-black shadow-sm group-hover:bg-[#14B8A6] group-hover:text-white transition-all">
                                    {{ substr($s->name, 0, 1) }}
                                </div>
                                <span class="font-black text-slate-700">{{ $s->name }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span class="text-sm font-bold text-slate-500 tracking-tight">{{ $s->contact ?: '-' }}</span>
                        </td>
                        <td class="px-8 py-6">
                            <span class="text-sm font-bold text-slate-400 leading-tight max-w-xs block truncate">{{ $s->address ?: '-' }}</span>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="edit({{ $s->id }})" class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button wire:confirm="Hapus supplier ini?" wire:click="delete({{ $s->id }})" class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <p class="text-slate-400 font-bold italic mb-4">Belum ada data supplier.</p>
                                <button wire:click="$set('showForm', true)" class="text-sm font-black text-[#14B8A6] uppercase tracking-widest border-b-2 border-teal-100 hover:border-teal-200">Klik untuk tambah</button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
