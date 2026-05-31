<?php

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use function Livewire\Volt\{state, layout, computed, mount};

layout("layouts.owner");

state([
    "selectedDate" => "",
    "coveringEmpId" => null,
    "coveringEmpName" => "",
    "coverWithUserId" => null,
    "coverNote" => "",
    "flashMessage" => "",
]);

mount(function () {
    $this->selectedDate = now()->format("Y-m-d");
});

$attendanceData = computed(function () {
    $date = $this->selectedDate ?: now()->format("Y-m-d");

    $employees = User::where("role", "pegawai")
        ->where("is_active", true)
        ->orderBy('name')
        ->get();

    $records = Attendance::with(["user", "coveredBy"])
        ->whereDate("date", $date)
        ->get()
        ->keyBy("user_id");

    $now = now();

    return $employees->map(function ($emp) use ($records, $date, $now) {
        $att = $records->get($emp->id);

        $shiftStart = $emp->shift_start
            ? Carbon::parse($emp->shift_start)->format('H:i')
            : '08:00';
        $shiftEnd = $emp->shift_end
            ? Carbon::parse($emp->shift_end)->format('H:i')
            : '23:00';

        $lateThreshold = strtotime($date . ' ' . $shiftStart) + 15 * 60;

        // Shift end timestamp — for overnight shifts, add 1 day
        $shiftEndTs = strtotime($date . ' ' . $shiftEnd);
        $isOvernight = $shiftEnd <= $shiftStart;
        if ($isOvernight) {
            $shiftEndTs += 86400;
        }

        // Determine status
        if ($att && $att->covered_by_user_id) {
            $status = "Digantikan";
        } elseif (!$att || !$att->check_in) {
            $status = "Tidak Hadir";
        } elseif ($att->check_out) {
            $status = (!$emp->is_attendance_debug && strtotime($att->check_in) > $lateThreshold)
                ? "Terlambat"
                : "On Time";
        } else {
            // Has check_in, no check_out
            if ($emp->is_attendance_debug || $now->timestamp <= $shiftEndTs) {
                $status = "Sedang Bekerja";
            } else {
                $status = "Tidak Lengkap";
            }
        }

        return [
            "id" => $emp->id,
            "att_id" => $att?->id,
            "name" => $emp->name,
            "shift" => $emp->shift_start && $emp->shift_end
                ? Carbon::parse($emp->shift_start)->format('H:i') . ' – ' . Carbon::parse($emp->shift_end)->format('H:i')
                : ($emp->is_attendance_debug ? 'Debug' : '—'),
            "check_in" => $att?->check_in,
            "check_out" => $att?->check_out,
            "notes" => $att?->notes,
            "check_in_photo" => $att?->check_in_photo,
            "check_out_photo" => $att?->check_out_photo,
            "covered_by_name" => $att?->coveredBy?->name,
            "manual_close" => (bool) ($att?->manual_close),
            "status" => $status,
        ];
    });
});

$summary = computed(function () {
    $data = $this->attendanceData;
    return [
        "total" => $data->count(),
        "ontime" => $data->where("status", "On Time")->count(),
        "terlambat" => $data->where("status", "Terlambat")->count(),
        "working" => $data->where("status", "Sedang Bekerja")->count(),
        "incomplete" => $data->where("status", "Tidak Lengkap")->count(),
        "absen" => $data->where("status", "Tidak Hadir")->count(),
        "covered" => $data->where("status", "Digantikan")->count(),
    ];
});

$availableCovers = computed(function () {
    return User::where("role", "pegawai")
        ->where("is_active", true)
        ->where("id", "!=", $this->coveringEmpId)
        ->orderBy('name')
        ->get(['id', 'name']);
});

$openCoverForm = function ($empId, $empName) {
    $this->coveringEmpId = $empId;
    $this->coveringEmpName = $empName;
    $this->coverWithUserId = null;
    $this->coverNote = '';
};

$cancelCover = function () {
    $this->reset('coveringEmpId', 'coveringEmpName', 'coverWithUserId', 'coverNote');
};

$saveCover = function () {
    $this->validate([
        'coverWithUserId' => 'required|exists:users,id',
        'coverNote' => 'nullable|string|max:255',
    ]);

    $note = trim($this->coverNote);

    Attendance::updateOrCreate(
        ['user_id' => $this->coveringEmpId, 'date' => $this->selectedDate],
        [
            'covered_by_user_id' => $this->coverWithUserId,
            'notes' => $note !== '' ? $note : 'Digantikan oleh rekan kerja',
        ]
    );

    $this->flashMessage = "Berhasil ditandai digantikan.";
    $this->reset('coveringEmpId', 'coveringEmpName', 'coverWithUserId', 'coverNote');
};

$clearCover = function ($attId) {
    $att = Attendance::find($attId);
    if (!$att) return;

    if (!$att->check_in && !$att->check_out) {
        $att->delete();
    } else {
        $att->update(['covered_by_user_id' => null]);
    }
    $this->flashMessage = "Penandaan digantikan dibatalkan.";
};

$closeManual = function ($attId) {
    $att = Attendance::with('user')->find($attId);
    if (!$att || !$att->check_in || $att->check_out) return;

    $shiftEnd = $att->user?->shift_end
        ? Carbon::parse($att->user->shift_end)->format('H:i:s')
        : '23:00:00';

    $checkInTs = strtotime($att->check_in);
    $closeTs = strtotime($att->date->format('Y-m-d') . ' ' . $shiftEnd);
    if ($closeTs < $checkInTs) {
        $closeTs += 86400; // overnight shift
    }

    $att->update([
        'check_out' => Carbon::createFromTimestamp($closeTs),
        'manual_close' => true,
        'notes' => trim(($att->notes ? $att->notes . ' | ' : '') . 'Ditutup manual oleh owner'),
    ]);

    $this->flashMessage = "Absensi {$att->user?->name} ditutup manual.";
};
?>

<div class="space-y-8 pb-20">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tighter">Monitor Absensi</h1>
            <p class="text-slate-400 font-bold mt-1 uppercase text-[10px] tracking-[0.2em]">Manajemen / Absensi</p>
        </div>
        <div class="flex items-center gap-3 bg-white px-6 py-3 rounded-2xl shadow-sm border border-slate-100">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <input wire:model.live="selectedDate" type="date"
                   class="text-sm font-black text-slate-700 border-0 bg-transparent focus:ring-0 p-0 cursor-pointer">
        </div>
    </div>

    @if($flashMessage)
        <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center gap-4 text-emerald-700">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            <p class="font-bold text-sm">{{ $flashMessage }}</p>
            <button type="button" wire:click="$set('flashMessage', '')" class="ml-auto font-black">✕</button>
        </div>
    @endif

    <!-- Cover Form -->
    @if($coveringEmpId)
        <div class="bg-[#1A1A1A] rounded-[2.5rem] p-8 shadow-xl">
            <h2 class="text-xl font-black text-white mb-2">Tandai Digantikan</h2>
            <p class="text-slate-400 text-sm font-bold mb-6">
                <span class="text-[#14B8A6]">{{ $coveringEmpName }}</span> berhalangan pada
                {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}.
                Pilih pegawai pengganti:
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Pegawai Pengganti</label>
                    <select wire:model="coverWithUserId"
                            class="w-full px-5 py-4 bg-white/10 border border-white/10 rounded-2xl text-white font-bold focus:ring-2 focus:ring-[#14B8A6]">
                        <option value="">— Pilih Pegawai —</option>
                        @foreach($this->availableCovers as $cover)
                            <option value="{{ $cover->id }}" class="text-slate-800">{{ $cover->name }}</option>
                        @endforeach
                    </select>
                    @error('coverWithUserId') <span class="text-rose-400 text-xs font-bold mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Catatan (opsional)</label>
                    <input wire:model="coverNote" type="text" placeholder="Contoh: Sakit, izin keluarga"
                           class="w-full px-5 py-4 bg-white/10 border border-white/10 rounded-2xl text-white font-bold placeholder:text-slate-600 focus:ring-2 focus:ring-[#14B8A6]">
                </div>
            </div>

            <div class="flex gap-3 justify-end">
                <button wire:click="cancelCover" class="px-6 py-3 bg-white/10 text-white rounded-2xl font-black text-sm hover:bg-white/20">Batal</button>
                <button wire:click="saveCover" class="px-6 py-3 bg-[#14B8A6] text-white rounded-2xl font-black text-sm hover:scale-105">Simpan</button>
            </div>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white border border-slate-100 rounded-[2rem] p-5 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total</p>
            <p class="text-3xl font-black text-slate-800 tabular-nums">{{ $this->summary['total'] }}</p>
        </div>
        <div class="bg-emerald-50 border border-emerald-100 rounded-[2rem] p-5 shadow-sm">
            <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-2">On Time</p>
            <p class="text-3xl font-black text-emerald-600 tabular-nums">{{ $this->summary['ontime'] }}</p>
        </div>
        <div class="bg-amber-50 border border-amber-100 rounded-[2rem] p-5 shadow-sm">
            <p class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-2">Terlambat</p>
            <p class="text-3xl font-black text-amber-600 tabular-nums">{{ $this->summary['terlambat'] }}</p>
        </div>
        <div class="bg-sky-50 border border-sky-100 rounded-[2rem] p-5 shadow-sm">
            <p class="text-[10px] font-black text-sky-500 uppercase tracking-widest mb-2">Sedang Bekerja</p>
            <p class="text-3xl font-black text-sky-600 tabular-nums">{{ $this->summary['working'] }}</p>
        </div>
        <div class="bg-rose-50 border border-rose-100 rounded-[2rem] p-5 shadow-sm">
            <p class="text-[10px] font-black text-rose-500 uppercase tracking-widest mb-2">Perlu Tindakan</p>
            <p class="text-3xl font-black text-rose-600 tabular-nums">{{ $this->summary['incomplete'] + $this->summary['absen'] }}</p>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex items-center justify-between">
            <h3 class="text-xl font-black text-slate-800 tracking-tight">
                Kehadiran {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}
            </h3>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Batas Terlambat: <span class="text-slate-700">Shift + 15 menit</span>
            </span>
        </div>

        <div class="overflow-x-auto -mx-8 lg:mx-0">
            <table class="w-full text-left min-w-[1000px] lg:min-w-full">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Pegawai</th>
                        <th class="px-4 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Shift</th>
                        <th class="px-4 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Masuk</th>
                        <th class="px-4 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Foto</th>
                        <th class="px-4 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Pulang</th>
                        <th class="px-4 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Foto</th>
                        <th class="px-4 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($this->attendanceData as $row)
                    @php
                        $statusStyle = match($row['status']) {
                            'On Time'        => 'text-emerald-600 bg-emerald-50',
                            'Terlambat'      => 'text-amber-600 bg-amber-50',
                            'Sedang Bekerja' => 'text-sky-600 bg-sky-50',
                            'Tidak Lengkap'  => 'text-orange-600 bg-orange-50',
                            'Tidak Hadir'    => 'text-rose-600 bg-rose-50',
                            'Digantikan'     => 'text-purple-600 bg-purple-50',
                            default          => 'text-slate-600 bg-slate-50',
                        };
                        $statusIcon = match($row['status']) {
                            'On Time'        => '✓',
                            'Terlambat'      => '⚠',
                            'Sedang Bekerja' => '⏳',
                            'Tidak Lengkap'  => '⌛',
                            'Tidak Hadir'    => '✗',
                            'Digantikan'     => '↻',
                            default          => '—',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-white font-black text-xs transition-colors
                                    {{ in_array($row['status'], ['Tidak Hadir', 'Digantikan']) ? 'bg-slate-300' : 'bg-[#0A2A2F] group-hover:bg-[#14B8A6]' }}">
                                    {{ strtoupper(substr($row['name'], 0, 1)) }}{{ strtoupper(substr(strrchr($row['name'], ' ') ?: '', 1, 1)) }}
                                </div>
                                <div>
                                    <div class="font-black text-slate-700 text-sm">{{ $row['name'] }}</div>
                                    @if($row['covered_by_name'])
                                        <div class="text-[10px] font-bold text-purple-500 uppercase tracking-wider mt-0.5">
                                            ↻ Digantikan {{ $row['covered_by_name'] }}
                                        </div>
                                    @endif
                                    @if($row['manual_close'])
                                        <div class="text-[10px] font-bold text-orange-500 uppercase tracking-wider mt-0.5">
                                            ⌛ Tutup manual
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-5 text-center">
                            @if($row['shift'] === 'Debug')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider text-indigo-600 bg-indigo-50">Debug</span>
                            @elseif($row['shift'] === '—')
                                <span class="text-slate-300 font-bold italic">—</span>
                            @else
                                <span class="font-bold text-slate-600 text-xs tabular-nums">{{ $row['shift'] }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-5 text-center">
                            @if($row['check_in'])
                                <span class="font-black text-slate-800 tabular-nums">
                                    {{ \Carbon\Carbon::parse($row['check_in'])->format('H:i') }}
                                </span>
                            @else
                                <span class="text-slate-300 font-bold italic">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-5 text-center">
                            @if($row['check_in_photo'])
                                <img src="{{ Storage::url($row['check_in_photo']) }}" class="w-10 h-10 inline-block rounded-xl object-cover border-2 border-white shadow-sm hover:scale-[3] hover:z-50 transition-all cursor-zoom-in">
                            @else
                                <span class="text-[10px] font-bold text-slate-300 italic">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-5 text-center">
                            @if($row['check_out'])
                                <span class="font-black text-slate-800 tabular-nums">
                                    {{ \Carbon\Carbon::parse($row['check_out'])->format('H:i') }}
                                </span>
                            @else
                                <span class="text-xs font-bold text-slate-300 italic">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-5 text-center">
                            @if($row['check_out_photo'])
                                <img src="{{ Storage::url($row['check_out_photo']) }}" class="w-10 h-10 inline-block rounded-xl object-cover border-2 border-white shadow-sm hover:scale-[3] hover:z-50 transition-all cursor-zoom-in">
                            @else
                                <span class="text-[10px] font-bold text-slate-300 italic">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-5 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 {{ $statusStyle }} rounded-xl text-[10px] font-black uppercase tracking-wider whitespace-nowrap">
                                {{ $statusIcon }} {{ $row['status'] }}
                            </span>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($row['status'] === 'Tidak Hadir')
                                    <button wire:click="openCoverForm({{ $row['id'] }}, '{{ addslashes($row['name']) }}')"
                                            class="px-3 py-2 bg-purple-50 text-purple-600 rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-purple-600 hover:text-white transition-all whitespace-nowrap">
                                        ↻ Digantikan
                                    </button>
                                @elseif($row['status'] === 'Digantikan')
                                    <button wire:confirm="Batalkan penandaan digantikan?"
                                            wire:click="clearCover({{ $row['att_id'] }})"
                                            class="px-3 py-2 bg-slate-50 text-slate-500 rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-slate-200 transition-all whitespace-nowrap">
                                        Batalkan
                                    </button>
                                @elseif($row['status'] === 'Tidak Lengkap')
                                    <button wire:confirm="Tutup absensi {{ $row['name'] }} secara manual (set check-out ke akhir shift)?"
                                            wire:click="closeManual({{ $row['att_id'] }})"
                                            class="px-3 py-2 bg-orange-50 text-orange-600 rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-orange-600 hover:text-white transition-all whitespace-nowrap">
                                        ⌛ Tutup Manual
                                    </button>
                                @else
                                    <span class="text-slate-300 text-xs font-bold italic">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 mb-4 border border-slate-100">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <p class="text-slate-400 font-bold italic">Tidak ada data pegawai aktif.</p>
                                <p class="text-slate-300 text-sm mt-1">Tambah pegawai terlebih dahulu di menu Pegawai.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
