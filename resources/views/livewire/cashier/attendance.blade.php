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
    'isOutOfShift' => false,
    'shiftDate' => '',
    'requireLateReason' => false,
    'lateReason' => '',
    'shiftEndDisplay' => '',
]);

mount(function () {
    $this->currentTime = now()->format('H:i');

    $user = auth()->user();
    $now = now();

    $startTime = $user && $user->shift_start
        ? Carbon::parse($user->shift_start)->format('H:i:s')
        : '08:00:00';
    $endTime = $user && $user->shift_end
        ? Carbon::parse($user->shift_end)->format('H:i:s')
        : '23:00:00';

    $this->shiftEndDisplay = substr($endTime, 0, 5);

    $isOvernight = $endTime <= $startTime;
    $nowTime = $now->format('H:i:s');

    // For shifts that cross midnight (e.g. 16:00 → 02:00), check-out happens "tomorrow"
    // but belongs to the previous day's shift record.
    if ($isOvernight && $nowTime <= $endTime) {
        $this->shiftDate = $now->copy()->subDay()->toDateString();
    } else {
        $this->shiftDate = $now->toDateString();
    }

    $this->todayAttendance = Attendance::where('user_id', auth()->id())
        ->where('date', $this->shiftDate)
        ->first();

    $hasCheckIn = $this->todayAttendance && $this->todayAttendance->check_in;
    $needsCheckOut = $hasCheckIn && !$this->todayAttendance->check_out;

    if ($user && $user->is_attendance_debug) {
        $this->isOutOfShift = false;
        $this->requireLateReason = false;
    } elseif ($needsCheckOut) {
        // Already checked in — always allow check-out (no shift blocking).
        $this->isOutOfShift = false;

        // Compute grace deadline: shift_end + 15 min, on the shift date.
        $shiftEndTs = strtotime($this->shiftDate . ' ' . $endTime);
        if ($isOvernight) {
            $shiftEndTs += 86400;
        }
        $graceTs = $shiftEndTs + 15 * 60;
        $this->requireLateReason = $now->timestamp > $graceTs;
    } else {
        // Need to check in — must be within shift window.
        $inShift = $isOvernight
            ? ($nowTime >= $startTime || $nowTime <= $endTime)
            : ($nowTime >= $startTime && $nowTime <= $endTime);
        $this->isOutOfShift = !$inShift;
        $this->requireLateReason = false;
    }
});
$canCheckIn = computed(fn() => !$this->todayAttendance);
$canCheckOut = computed(fn() => $this->todayAttendance && !$this->todayAttendance->check_out);

$processAttendance = function () {
    if (!$this->capturedPhoto) return;

    if ($this->canCheckOut && $this->requireLateReason) {
        $reason = trim($this->lateReason);
        if (strlen($reason) < 5) {
            $this->addError('lateReason', 'Alasan minimal 5 karakter.');
            return;
        }
    }

    $imageData = $this->capturedPhoto;
    $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $imageName = 'attendance/' . Str::random(40) . '.jpg';
    Storage::disk('public')->put($imageName, base64_decode($imageData));

    if ($this->canCheckIn) {
        Attendance::create([
            'user_id' => auth()->id(),
            'date' => $this->shiftDate,
            'check_in' => now(),
            'check_in_photo' => $imageName,
        ]);
    } elseif ($this->canCheckOut) {
        $payload = [
            'check_out' => now(),
            'check_out_photo' => $imageName,
        ];
        if ($this->requireLateReason) {
            $reason = trim($this->lateReason);
            $existing = trim((string) $this->todayAttendance->notes);
            $payload['notes'] = ($existing !== '' ? $existing . ' | ' : '')
                . 'Late checkout: ' . $reason;
        }
        $this->todayAttendance->update($payload);
    }

    $this->status = 'success';
    $this->todayAttendance = Attendance::where('user_id', auth()->id())
        ->where('date', $this->shiftDate)
        ->first();
    $this->isCameraOpen = false;
    $this->capturedPhoto = null;
    $this->lateReason = '';
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
        @php
            $actionLabel = $this->canCheckIn ? 'Check In' : 'Check Out';
            $actionColor = $this->canCheckIn ? 'bg-emerald-500' : 'bg-rose-500';
        @endphp

        <div class="flex flex-col items-center text-center space-y-8">
            <div class="space-y-2">
                <h3 class="text-3xl font-black text-slate-900 tracking-tighter">Siap untuk {{ $actionLabel }}?</h3>
                <p class="text-slate-400 font-bold uppercase text-[10px] tracking-[0.2em]">Ambil foto wajah Anda untuk verifikasi</p>
            </div>

            @if($this->canCheckOut && $requireLateReason && !$capturedPhoto)
                <div class="w-full p-5 bg-amber-50 border border-amber-100 rounded-3xl text-left space-y-3">
                    <div class="flex items-center gap-2 text-amber-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.85-2.75L13.7 4a2 2 0 00-3.4 0L3.15 16.25A2 2 0 005 19z"/></svg>
                        <p class="font-black text-xs uppercase tracking-widest">Check-Out Terlambat</p>
                    </div>
                    <p class="text-xs font-bold text-amber-700/80">
                        Shift Anda berakhir {{ $shiftEndDisplay }} (toleransi 15 menit terlewat). Tulis alasan singkat sebelum melanjutkan.
                    </p>
                    <textarea wire:model.live="lateReason" rows="2" maxlength="200"
                              placeholder="Contoh: Ada pelanggan terakhir, tutup kasir overrun..."
                              class="w-full px-4 py-3 bg-white border border-amber-200 rounded-2xl text-sm font-bold text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-amber-400 transition-all resize-none"></textarea>
                    @error('lateReason') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                </div>
            @endif

            @if($capturedPhoto)
                <div class="w-full aspect-square bg-slate-900 rounded-[2rem] overflow-hidden shadow-xl ring-4 ring-slate-100">
                    <img src="{{ $capturedPhoto }}" class="w-full h-full object-cover" alt="Foto presensi">
                </div>
                <div class="w-full space-y-3">
                    <button wire:click="processAttendance"
                            wire:loading.attr="disabled"
                            class="w-full px-8 py-5 {{ $actionColor }} text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50">
                        <span wire:loading.remove wire:target="processAttendance">Konfirmasi {{ $actionLabel }}</span>
                        <span wire:loading wire:target="processAttendance">Menyimpan...</span>
                    </button>
                    <button type="button"
                            @click="$wire.set('capturedPhoto', null); $wire.set('isCameraOpen', true); $nextTick(() => initCamera())"
                            class="w-full px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-slate-200 transition-all">
                        Ambil Ulang
                    </button>
                </div>
            @elseif($isCameraOpen)
                <div class="w-full aspect-square bg-slate-900 rounded-[2rem] overflow-hidden shadow-xl ring-4 ring-slate-100 relative">
                    <video x-ref="video" autoplay playsinline class="w-full h-full object-cover"></video>
                </div>
                <div class="w-full space-y-3">
                    <button type="button" @click="takeSnapshot()"
                            class="w-full px-8 py-5 bg-[#0A2A2F] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Ambil Foto
                    </button>
                    <button type="button" @click="stopCamera(); $wire.set('isCameraOpen', false)"
                            class="w-full px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-slate-200 transition-all">
                        Batal
                    </button>
                </div>
            @else
                <div class="w-full aspect-square bg-slate-50 rounded-[2rem] flex flex-col items-center justify-center text-slate-300 border-2 border-dashed border-slate-200 gap-4">
                    <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <p class="text-xs font-bold uppercase tracking-widest">Kamera Belum Aktif</p>
                </div>
                @php
                    $reasonBlocked = $this->canCheckOut && $requireLateReason && strlen(trim($lateReason)) < 5;
                @endphp
                <button type="button"
                        @if(!$reasonBlocked) @click="$wire.set('isCameraOpen', true); $nextTick(() => initCamera())" @endif
                        @disabled($reasonBlocked)
                        class="w-full px-8 py-5 {{ $actionColor }} text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl transition-all flex items-center justify-center gap-3 {{ $reasonBlocked ? 'opacity-40 cursor-not-allowed' : 'hover:scale-[1.02] active:scale-95' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    {{ $reasonBlocked ? 'Tulis Alasan Dulu' : 'Buka Kamera untuk ' . $actionLabel }}
                </button>
            @endif
        </div>
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
