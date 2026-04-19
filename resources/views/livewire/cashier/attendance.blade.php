<?php

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use function Livewire\Volt\{state, layout, computed, mount};

layout('layouts.cashier');

state([
    'currentTime' => '',
    'todayAttendance' => null,
    'capturedPhoto' => null, // Base64 string
    'isCameraOpen' => false,
    'status' => 'idle', // idle, success, error
]);

mount(function () {
    $this->currentTime = now()->format('H:i');
    $this->todayAttendance = Attendance::where('user_id', auth()->id())
        ->where('date', today())
        ->first();
});

$canCheckIn = computed(fn() => !$this->todayAttendance);
$canCheckOut = computed(fn() => $this->todayAttendance && !$this->todayAttendance->check_out);

$processAttendance = function () {
    if (!$this->capturedPhoto) return;

    // 1. Save photo to storage
    $imageData = $this->capturedPhoto;
    $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $imageName = 'attendance/' . Str::random(40) . '.jpg';
    Storage::disk('public')->put($imageName, base64_decode($imageData));

    if ($this->canCheckIn) {
        Attendance::create([
            'user_id' => auth()->id(),
            'date' => today(),
            'check_in' => now(),
            'check_in_photo' => $imageName,
        ]);
    } elseif ($this->canCheckOut) {
        $this->todayAttendance->update([
            'check_out' => now(),
            'check_out_photo' => $imageName,
        ]);
    }

    $this->status = 'success';
    $this->todayAttendance = Attendance::where('user_id', auth()->id())
        ->where('date', today())
        ->first();
    $this->isCameraOpen = false;
    $this->capturedPhoto = null;
};

?>

<div class="h-full flex flex-col md:flex-row bg-[#F8F9FB] overflow-hidden" 
     x-data="{ 
        stream: null,
        errorMsg: '',
        async initCamera() {
            this.errorMsg = '';
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'user',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                if ($refs.video) {
                    $refs.video.srcObject = this.stream;
                } else {
                    throw new Error('Elemen video tidak ditemukan di DOM.');
                }
            } catch (err) {
                console.error('Camera Error:', err);
                if (err.name === 'NotAllowedError') {
                    this.errorMsg = 'Akses kamera ditolak. Silakan izinkan di pengaturan browser Anda.';
                } else if (err.name === 'NotFoundError') {
                    this.errorMsg = 'Kamera tidak ditemukan pada perangkat ini.';
                } else if (err.name === 'NotReadableError') {
                    this.errorMsg = 'Kamera sedang digunakan oleh aplikasi lain.';
                } else {
                    this.errorMsg = 'Gagal mengakses kamera: ' + err.message;
                }
                alert(this.errorMsg);
                $wire.set('isCameraOpen', false);
            }
        },
        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
        },
        takeSnapshot() {
            const canvas = document.createElement('canvas');
            canvas.width = $refs.video.videoWidth;
            canvas.height = $refs.video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage($refs.video, 0, 0);
            const data = canvas.toDataURL('image/jpeg', 0.8);
            $wire.set('capturedPhoto', data);
            
            this.stopCamera();
            $wire.set('isCameraOpen', false);
        }
     }"
     x-init="$watch('$wire.isCameraOpen', value => { if(!value) stopCamera() })">
    
    <!-- Left: Content Info -->
    <div class="flex-1 p-6 lg:p-12 flex flex-col justify-between">
        <div class="space-y-2">
            <h1 class="text-4xl font-extrabold text-[#111111] tracking-tighter">Presensi Kehadiran</h1>
            <p class="text-slate-400 font-bold uppercase text-[10px] tracking-[0.2em]">Northern Cafe / Pegawai</p>
        </div>

        <div class="max-w-md space-y-10">
            <!-- Digital Clock View -->
            <div class="bg-white rounded-[2rem] lg:rounded-[3rem] p-8 lg:p-12 shadow-sm border border-slate-100 flex flex-col items-center text-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">{{ now()->translatedFormat('l, d F Y') }}</p>
                <h2 class="text-5xl lg:text-7xl font-black text-[#111111] tracking-tighter mb-2" id="live-clock">
                    {{ now()->format('H:i') }}
                </h2>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-600 rounded-2xl text-[10px] font-black tracking-widest">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span> WAKTU SERVER
                </div>
            </div>

            <!-- Status Cards -->
            <div class="grid grid-cols-2 gap-4 lg:gap-6">
                <div class="bg-white rounded-[1.5rem] lg:rounded-[2.5rem] p-6 lg:p-8 border border-slate-100 flex flex-col gap-4">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Check In</span>
                    <p class="text-xl lg:text-2xl font-black text-slate-800 tracking-tight">
                        {{ $todayAttendance ? \Carbon\Carbon::parse($todayAttendance->check_in)->format('H:i') : '--:--' }}
                    </p>
                </div>
                <div class="bg-white rounded-[1.5rem] lg:rounded-[2.5rem] p-6 lg:p-8 border border-slate-100 flex flex-col gap-4">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Check Out</span>
                    <p class="text-xl lg:text-2xl font-black text-slate-800 tracking-tight">
                        {{ ($todayAttendance && $todayAttendance->check_out) ? \Carbon\Carbon::parse($todayAttendance->check_out)->format('H:i') : '--:--' }}
                    </p>
                </div>
            </div>
        </div>

        <div>
            @if($status == 'success')
            <div class="max-w-md p-6 bg-emerald-500 rounded-3xl text-white flex items-center gap-4 animate-in slide-in-from-bottom-4 duration-500">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <p class="font-black text-sm uppercase tracking-widest">Presensi Berhasil Dicatat!</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Right: Interaction Area -->
    <div class="w-full lg:w-[600px] p-6 lg:p-8 flex items-center justify-center bg-white border-l border-slate-100 relative">
        <div class="w-full max-w-sm space-y-10">
            @if(!$this->todayAttendance || !$this->todayAttendance->check_out)
                <!-- Unified Action Button -->
                <div class="space-y-8 flex flex-col items-center">
                    @if(!$isCameraOpen && !$capturedPhoto)
                        <div class="w-full p-8 lg:p-12 rounded-[2.5rem] lg:rounded-[4rem] bg-[#111111] text-white flex flex-col items-center text-center shadow-2xl relative group overflow-hidden">
                            <div class="w-16 h-16 lg:w-20 lg:h-20 bg-[#E97D5A] rounded-2xl lg:rounded-3xl flex items-center justify-center mb-6 lg:mb-8 shadow-lg shadow-orange-500/30 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 lg:w-10 lg:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <h3 class="text-3xl font-black mb-2 tracking-tighter">Sudah Siap?</h3>
                            <p class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-10">Verifikasi Wajah Diperlukan</p>
                            
                            <button wire:click="$set('isCameraOpen', true)" class="w-full py-5 bg-[#E97D5A] hover:bg-[#d66a4a] text-white rounded-[2rem] font-black text-sm uppercase tracking-widest transition-all">
                                {{ $this->canCheckIn ? 'Mulai Check-In' : 'Lakukan Check-Out' }}
                            </button>
                            
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        </div>
                    @endif

                    <!-- Camera View -->
                    @if($isCameraOpen)
                        <div class="w-full rounded-[2.5rem] lg:rounded-[4rem] overflow-hidden bg-slate-900 aspect-[3/4] shadow-2xl relative border-4 lg:border-8 border-white">
                            <video x-ref="video" x-init="initCamera()" autoplay playsinline class="w-full h-full object-cover"></video>
                            <div class="absolute bottom-10 left-0 w-full px-8">
                                <button @click="takeSnapshot()" class="w-full py-5 bg-white text-slate-900 rounded-[2rem] font-black text-sm uppercase tracking-widest shadow-xl hover:scale-105 active:scale-95 transition-all">
                                    AMBIL FOTO
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Captured Photo Preview -->
                    @if($capturedPhoto)
                        <div class="w-full space-y-6 flex flex-col items-center">
                            <div class="w-full rounded-[2.5rem] lg:rounded-[4rem] overflow-hidden bg-slate-100 aspect-[3/4] shadow-2xl relative border-4 lg:border-8 border-white">
                                <img src="{{ $capturedPhoto }}" class="w-full h-full object-cover">
                                <div class="absolute top-6 right-6">
                                    <button wire:click="$set('capturedPhoto', null)" class="w-10 h-10 bg-rose-500 text-white rounded-xl flex items-center justify-center shadow-lg">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <button wire:click="processAttendance" class="w-full py-6 bg-[#111111] text-white rounded-[2.5rem] font-black text-sm uppercase tracking-widest shadow-xl hover:scale-105 transition-all">
                                Konfirmasi Hadir
                            </button>
                        </div>
                    @endif
                </div>
            @else
                <!-- Completed State -->
                <div class="flex flex-col items-center text-center space-y-6">
                    <div class="w-24 h-24 bg-emerald-100 text-emerald-600 rounded-[2.5rem] flex items-center justify-center shadow-lg">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-3xl font-black text-slate-800 tracking-tighter leading-tight">Presensi Hari Ini Selesai</h3>
                    <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest">Selamat Beristirahat!</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    setInterval(() => {
        const now = new Date();
        document.getElementById('live-clock').innerText = now.toLocaleTimeString('id-ID', { hour12: false, hour: '2-digit', minute: '2-digit' }).replace('.', ':');
    }, 1000);
</script>
