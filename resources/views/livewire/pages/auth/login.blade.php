<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login()
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();

        if (auth()->user()->role === 'owner') {
            return $this->redirect(route('owner.dashboard', absolute: false), navigate: true);
        }

        return $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="w-full max-w-[340px] mx-auto flex flex-col justify-center">
    <!-- Logo & Header -->
    <div class="text-center mb-10 flex flex-col items-center">
        <!-- Optional animated coffee icon to match "Cafe Beans" -->
        <div class="w-16 h-16 flex items-center justify-center mb-2">
            <svg class="text-white w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m0 0l-3-3m3 3l3-3"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3C8.784 3 8 3.784 8 4.75v10.5C8 17.433 9.79 19 12 19s4-1.567 4-3.75V4.75C16 3.784 15.216 3 14.25 3h-4.5zM16 8h2a2 2 0 012 2v2a2 2 0 01-2 2h-2"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-black text-white tracking-tighter mb-4">Northern<br>Cafe</h1>
        
        <h2 class="text-xl font-bold text-gray-200 mt-6 tracking-tight leading-snug max-w-[280px]">
            Welcome Back, Please login to your account
        </h2>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    @if(session('error'))
        <div class="mb-4 text-xs font-bold text-rose-500 text-center bg-rose-500/10 p-3 rounded-xl border border-rose-500/20">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit="login" class="space-y-5">
        <!-- Email Address -->
        <div class="space-y-1">
            <label for="email" class="text-[10px] font-bold text-gray-400 pl-1">Email address</label>
            <input wire:model="form.email" id="email" class="w-full px-5 py-4 bg-[#262626] border-0 rounded-xl text-sm font-bold text-white placeholder:text-gray-500 focus:ring-2 focus:ring-white transition-all shadow-inner" type="email" name="email" required autofocus autocomplete="username" placeholder="johndoe@gmail.com">
            <x-input-error :messages="$errors->get('form.email')" class="mt-1" />
        </div>

        <!-- Password -->
        <div class="space-y-1" x-data="{ show: false }">
    <label for="password" class="text-[10px] font-bold text-gray-400 pl-1">Password</label>
    <div class="relative">
        <input wire:model="form.password" 
               id="password" 
               :type="show ? 'text' : 'password'" 
               class="w-full px-5 py-4 bg-[#262626] border-0 rounded-xl text-sm font-bold text-white placeholder:text-gray-500 focus:ring-2 focus:ring-white transition-all shadow-inner" 
               name="password" 
               required 
               autocomplete="current-password" 
               placeholder="••••••••">
        
        <button type="button" 
                @click="show = !show" 
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors focus:outline-none">
            
            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
            </svg>

            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
        </button>
    </div>
    <x-input-error :messages="$errors->get('form.password')" class="mt-1" />
</div>

        <div class="flex items-center justify-between pt-1 px-1">
            <div class="flex items-center gap-2">
                <div class="relative flex items-center">
                    <input wire:model="form.remember" id="remember" type="checkbox"
                           class="w-4 h-4 rounded bg-[#262626] border-0 text-white focus:ring-white cursor-pointer appearance-none checked:bg-emerald-500">
                    <svg class="w-3 h-3 text-white absolute left-0.5 pointer-events-none opacity-0 peer-checked:opacity-100" style="opacity: {{ $form->remember ? '1' : '0' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <label for="remember" class="text-[10px] font-bold text-gray-400 cursor-pointer select-none">Remember me</label>
            </div>
            <a href="{{ route('password.request') }}" class="text-[10px] font-bold text-gray-400 hover:text-white transition-colors">Forgot password?</a>
        </div>

        <!-- Submit -->
        <div class="pt-4 flex flex-col items-center">
            <button type="submit"
                    class="w-40 py-3.5 bg-white hover:bg-gray-100 text-[#1A1A1A] font-black rounded-[2rem] shadow-lg active:scale-95 transition-all flex items-center justify-center text-sm">
                Sign In
            </button>
        </div>
    </form>
</div>
