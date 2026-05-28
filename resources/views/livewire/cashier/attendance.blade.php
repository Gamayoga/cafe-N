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
    'capturedPhoto' => null,
    'isCameraOpen' => false,
    'status' => 'idle',
    'isOutOfShift' => false, // State baru untuk cek jam
]);

mount(function () {
    $this->currentTime = now()->format('H:i');
    
    // Tentukan Batas Jam (Contoh: Jam Masuk max 09:00, Jam Pulang max 22:00)
    $now = now();
    $startLimit = Carbon::createFromTime(8, 0); // 08:00
    $endLimit = Carbon::createFromTime(23, 0);   // 23:00

    if ($now->lt($startLimit) || $now->gt($endLimit)) {
        $this->isOutOfShift = true;
    }

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
            <h1 class="text-4xl font-extrabold text-[#0A2A2F] tracking-tighter">Presensi Kehadiran</h1>
            <p class="text-slate-400 font-bold uppercase text-[10px] tracking-[0.2em]">Northern Cafe / Pegawai</p>
        </div>

        <div class="max-w-md space-y-10">
            <!-- Digital Clock View -->
            <div class="bg-white rounded-[2rem] lg:rounded-[3rem] p-8 lg:p-12 shadow-sm border border-slate-100 flex flex-col items-center text-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">{{ now()->translatedFormat('l, d F Y') }}</p>
                <h2 class="text-5xl lg:text-7xl font-black text-[#0A2A2F] tracking-tighter mb-2" id="live-clock">
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
    @if($isOutOfShift)
        <div class="flex flex-col items-center text-center space-y-8 animate-in fade-in zoom-in duration-500">
            <div class="relative">
                <div class="w-32 h-32 bg-rose-100 text-rose-500 rounded-[3rem] flex items-center justify-center shadow-2xl shadow-rose-200">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="absolute inset-0 w-32 h-32 bg-rose-500/20 rounded-[3rem] animate-ping"></div>
            </div>
            
            <div class="space-y-3">
                <h3 class="text-4xl font-black text-slate-900 tracking-tighter leading-none uppercase">
                    Akses<br><span class="text-rose-500">Dibatasi</span>
                </h3>
                <p class="text-slate-400 font-bold uppercase text-xs tracking-[0.2em]">Sistem Mengunci Presensi</p>
            </div>

            <div class="w-full p-6 bg-slate-50 rounded-3xl border border-slate-100">
                <p class="text-sm font-black text-slate-700 leading-relaxed">
                    "Anda melewati Jam Presensinya"
                </p>
                <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase">Silakan hubungi Owner untuk instruksi lebih lanjut.</p>
            </div>
        </div>

    @elseif(!$this->todayAttendance || !$this->todayAttendance->check_out)
        @else
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
