<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function Livewire\Volt\{state, layout, rules, computed};

layout('layouts.owner');

state([
    'search' => '',
    'name' => '',
    'email' => '',
    'password' => '',
    'editingUserId' => null,
    'showForm' => false,
    'resetPasswordId' => null,
    'newPassword' => '',
    'errorMessage' => '',
]);

rules([
    'name' => 'required|min:2',
    'email' => 'required|email',
    'password' => 'required|min:6',
]);

$employees = computed(fn () =>
    User::where('role', 'pegawai')
        ->where('name', 'like', '%' . $this->search . '%')
        ->latest()
        ->get()
);

$save = function () {
    try {
        if ($this->editingUserId) {
            $this->validate([
                'name' => 'required|min:2',
                'email' => 'required|email|unique:users,email,' . $this->editingUserId,
            ]);
            User::find($this->editingUserId)->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);
        } else {
            $this->validate([
                'name' => 'required|min:2',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
            ]);
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => 'pegawai',
                'is_active' => true,
            ]);
        }
        $this->errorMessage = '';
        $this->reset('name', 'email', 'password', 'editingUserId', 'showForm');
    } catch (\Exception $e) {
        $this->errorMessage = "Gagal menyimpan data: " . $e->getMessage();
    }
};

$edit = function ($id) {
    $u = User::find($id);
    $this->editingUserId = $id;
    $this->name = $u->name;
    $this->email = $u->email;
    $this->password = '';
    $this->showForm = true;
    $this->resetPasswordId = null;
};

$toggleActive = function ($id) {
    $u = User::find($id);
    $u?->update(['is_active' => !$u->is_active]);
};

$showResetForm = function ($id) {
    $this->resetPasswordId = $id;
    $this->newPassword = '';
    $this->showForm = false;
};

$doResetPassword = function () {
    $this->validate(['newPassword' => 'required|min:6'], [], ['newPassword' => 'Password Baru']);
    User::find($this->resetPasswordId)?->update([
        'password' => Hash::make($this->newPassword),
    ]);
    $this->reset('resetPasswordId', 'newPassword');
};

$delete = function ($id) {
    User::find($id)?->delete();
};

$cancel = function () {
    $this->reset('name', 'email', 'password', 'editingUserId', 'showForm', 'resetPasswordId', 'newPassword', 'errorMessage');
};

?>

<div class="space-y-8 pb-20">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tighter">Manajemen Pegawai</h1>
            <p class="text-slate-400 font-bold mt-1 uppercase text-[10px] tracking-[0.2em]">Manajemen / Pegawai</p>
        </div>
        <button wire:click="$toggle('showForm'); $set('resetPasswordId', null)"
                class="px-6 py-3 bg-[#14B8A6] text-white rounded-2xl font-black text-sm shadow-lg shadow-teal-100/50 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
            {{ $showForm ? 'Tutup Form' : 'Tambah Pegawai Baru' }}
        </button>
    </div>

    @if($showForm)
    <!-- Add/Edit Form -->
    <div class="bg-white rounded-[2.5rem] p-10 shadow-xl border border-teal-50">
        <h2 class="text-2xl font-black text-slate-800 mb-8">
            {{ $editingUserId ? 'Ubah Data Pegawai' : 'Daftarkan Pegawai Baru' }}
        </h2>

        @if($errorMessage)
        <div class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl flex items-center gap-4 text-rose-600">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="font-bold text-sm">{{ $errorMessage }}</p>
            <button type="button" @click="$wire.set('errorMessage', '')" class="ml-auto">✕</button>
        </div>
        @endif

        <form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Nama Lengkap</label>
                    <input wire:model="name" type="text" placeholder="Contoh: Andi Ramadhan"
                           class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl text-slate-800 font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all">
                    @error('name') <span class="text-rose-500 text-xs font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Email Login</label>
                    <input wire:model="email" type="email" placeholder="andi@northern.com"
                           class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl text-slate-800 font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all">
                    @error('email') <span class="text-rose-500 text-xs font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                </div>
                @if(!$editingUserId)
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Password Awal</label>
                    <input wire:model="password" type="password" placeholder="Min. 6 karakter"
                           class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl text-slate-800 font-bold focus:ring-2 focus:ring-[#14B8A6] transition-all">
                    @error('password') <span class="text-rose-500 text-xs font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                </div>
                @endif
            </div>
            <div class="flex justify-end gap-4 pt-4 border-t border-slate-50">
                <button type="button" wire:click="cancel" class="px-8 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-sm hover:bg-slate-200 transition-all">Batal</button>
                <button type="submit" class="px-8 py-4 bg-[#1A1A1A] text-white rounded-2xl font-black text-sm shadow-xl hover:scale-105 active:scale-95 transition-all">
                    {{ $editingUserId ? 'Perbarui Data' : 'Buat Akun Pegawai' }}
                </button>
            </div>
        </form>
    </div>
    @endif

    @if($resetPasswordId)
    <!-- Reset Password Panel -->
    <div class="bg-[#1A1A1A] rounded-[2.5rem] p-10 shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 opacity-5 p-10">
            <svg class="w-40 h-40 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"></path></svg>
        </div>
        <div class="relative z-10">
            <h2 class="text-2xl font-black text-white mb-2">Reset Password Pegawai</h2>
            <p class="text-gray-500 font-bold text-sm mb-8 uppercase tracking-widest">Masukkan password baru untuk pegawai ini.</p>
            <div class="flex items-start gap-6">
                <div class="flex-1">
                    <input wire:model="newPassword" type="password" placeholder="Password baru (min. 6 karakter)"
                           class="w-full px-6 py-4 bg-white/10 border border-white/10 rounded-2xl text-white font-bold placeholder:text-gray-600 focus:ring-2 focus:ring-[#14B8A6] transition-all">
                    @error('newPassword') <span class="text-rose-400 text-xs font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                </div>
                <button wire:click="doResetPassword" class="px-8 py-4 bg-[#14B8A6] text-white rounded-2xl font-black text-sm hover:scale-105 transition-all shadow-lg whitespace-nowrap">Set Password</button>
                <button wire:click="$set('resetPasswordId', null)" class="px-8 py-4 bg-white/10 text-white rounded-2xl font-black text-sm hover:bg-white/20 transition-all">Batal</button>
            </div>
        </div>
    </div>
    @endif

    <!-- Table -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-xl font-black text-slate-800 tracking-tight">Daftar Akun Pegawai</h3>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input wire:model.live="search" type="text" placeholder="Cari nama pegawai..."
                       class="pl-12 pr-6 py-3 bg-slate-50 border-0 rounded-2xl w-full md:w-72 text-sm font-bold placeholder:text-slate-300 focus:ring-2 focus:ring-[#14B8A6] transition-all">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Pegawai</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Email</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Bergabung</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status Akun</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($this->employees as $emp)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div class="w-11 h-11 bg-[#0A2A2F] rounded-2xl flex items-center justify-center text-white font-black text-sm group-hover:bg-[#14B8A6] transition-colors">
                                    {{ strtoupper(substr($emp->name, 0, 1)) }}{{ strtoupper(substr(strrchr($emp->name, ' ') ?: '', 1, 1)) }}
                                </div>
                                <span class="font-black text-slate-700">{{ $emp->name }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span class="font-bold text-slate-500">{{ $emp->email }}</span>
                        </td>
                        <td class="px-8 py-6">
                            <span class="font-bold text-slate-400 text-sm">{{ $emp->created_at?->format('d M Y') }}</span>
                        </td>
                        <td class="px-8 py-6">
                            <button wire:click="toggleActive({{ $emp->id }})"
                                    class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all hover:scale-105
                                    {{ $emp->is_active ? 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100' : 'text-rose-600 bg-rose-50 hover:bg-rose-100' }}">
                                {{ $emp->is_active ? '● Aktif' : '● Nonaktif' }}
                            </button>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="edit({{ $emp->id }})"
                                        class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button wire:click="showResetForm({{ $emp->id }})"
                                        class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center hover:bg-amber-500 hover:text-white transition-all shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                </button>
                                <button wire:confirm="Hapus akun pegawai {{ $emp->name }}?" wire:click="delete({{ $emp->id }})"
                                        class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <p class="text-slate-400 font-bold italic">Belum ada akun pegawai.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
