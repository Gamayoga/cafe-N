<?php

use App\Models\Attendance;
use App\Models\User;
use function Livewire\Volt\{state, layout, computed, mount};

layout("layouts.owner");

state([
    "selectedDate" => "",
]);

mount(function () {
    $this->selectedDate = now()->format("Y-m-d");
});

$attendanceData = computed(function () {
    $date = $this->selectedDate ?: now()->format("Y-m-d");

    // Get all pegawai
    $employees = User::where("role", "pegawai")
        ->where("is_active", true)
        ->get();

    // Get attendance records for date
    $records = Attendance::with("user")
        ->whereDate("date", $date)
        ->get()
        ->keyBy("user_id");

    return $employees->map(function ($emp) use ($records) {
        $att = $records->get($emp->id);
        $shiftStart = $emp->shift_start
            ? \Carbon\Carbon::parse($emp->shift_start)->format('H:i')
            : '08:00';
        $lateThreshold = strtotime(
            $this->selectedDate . ' ' . $shiftStart
        ) + 15 * 60;

        return [
            "id" => $emp->id,
            "name" => $emp->name,
            "shift" => $emp->shift_start && $emp->shift_end
                ? \Carbon\Carbon::parse($emp->shift_start)->format('H:i') . ' – ' . \Carbon\Carbon::parse($emp->shift_end)->format('H:i')
                : ($emp->is_attendance_debug ? 'Debug' : '—'),
            "check_in" => $att?->check_in,
            "check_out" => $att?->check_out,
            "notes" => $att?->notes,
            "check_in_photo" => $att?->check_in_photo,
            "check_out_photo" => $att?->check_out_photo,
            "status" => $att
                ? ($att->check_in && !$emp->is_attendance_debug &&
                strtotime($att->check_in) > $lateThreshold
                    ? "Terlambat"
                    : "On Time")
                : "Tidak Hadir",
        ];
    });
});

$summary = computed(function () {
    $data = $this->attendanceData;
    return [
        "total" => $data->count(),
        "hadir" => $data->where("status", "!=", "Tidak Hadir")->count(),
        "ontime" => $data->where("status", "On Time")->count(),
        "terlambat" => $data->where("status", "Terlambat")->count(),
        "absen" => $data->where("status", "Tidak Hadir")->count(),
    ];
});
?>

<div class="space-y-8 pb-20">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tighter">Monitor Absensi</h1>
            <p class="text-slate-400 font-bold mt-1 uppercase text-[10px] tracking-[0.2em]">Manajemen / Absensi</p>
        </div>
        <!-- Date Picker -->
        <div class="flex items-center gap-3 bg-white px-6 py-3 rounded-2xl shadow-sm border border-slate-100">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <input wire:model.live="selectedDate" type="date"
                   class="text-sm font-black text-slate-700 border-0 bg-transparent focus:ring-0 p-0 cursor-pointer">
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-100 rounded-[2rem] p-6 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Total Pegawai</p>
            <p class="text-4xl font-black text-slate-800 tabular-nums">{{ $this->summary['total'] }}</p>
        </div>
        <div class="bg-emerald-50 border border-emerald-100 rounded-[2rem] p-6 shadow-sm">
            <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-3">On Time</p>
            <p class="text-4xl font-black text-emerald-600 tabular-nums">{{ $this->summary['ontime'] }}</p>
        </div>
        <div class="bg-amber-50 border border-amber-100 rounded-[2rem] p-6 shadow-sm">
            <p class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-3">Terlambat</p>
            <p class="text-4xl font-black text-amber-600 tabular-nums">{{ $this->summary['terlambat'] }}</p>
        </div>
        <div class="bg-rose-50 border border-rose-100 rounded-[2rem] p-6 shadow-sm">
            <p class="text-[10px] font-black text-rose-500 uppercase tracking-widest mb-3">Tidak Hadir</p>
            <p class="text-4xl font-black text-rose-600 tabular-nums">{{ $this->summary['absen'] }}</p>
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
            <table class="w-full text-left min-w-[800px] lg:min-w-full">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Pegawai</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Shift</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Masuk</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Foto</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Pulang</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Foto</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($this->attendanceData as $row)
                    @php
                        $statusStyle = match($row['status']) {
                            'On Time'     => 'text-emerald-600 bg-emerald-50',
                            'Terlambat'   => 'text-amber-600 bg-amber-50',
                            'Tidak Hadir' => 'text-rose-600 bg-rose-50',
                            default       => 'text-slate-600 bg-slate-50',
                        };
                        $statusIcon = match($row['status']) {
                            'On Time'     => '✓',
                            'Terlambat'   => '⚠',
                            'Tidak Hadir' => '✗',
                            default       => '—',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-white font-black text-sm transition-colors
                                    {{ $row['status'] === 'Tidak Hadir' ? 'bg-slate-300' : 'bg-[#0A2A2F] group-hover:bg-[#14B8A6]' }}">
                                    {{ strtoupper(substr($row['name'], 0, 1)) }}{{ strtoupper(substr(strrchr($row['name'], ' ') ?: '', 1, 1)) }}
                                </div>
                                <span class="font-black text-slate-700">{{ $row['name'] }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6 text-center">
                            @if($row['shift'] === 'Debug')
                                <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider text-indigo-600 bg-indigo-50">● Debug</span>
                            @elseif($row['shift'] === '—')
                                <span class="text-slate-300 font-bold italic">—</span>
                            @else
                                <span class="font-bold text-slate-600 text-sm tabular-nums">{{ $row['shift'] }}</span>
                            @endif
                        </td>
                        <td class="px-8 py-6 text-center">
                            @if($row['check_in'])
                                <span class="font-black text-slate-800 tabular-nums text-lg">
                                    {{ \Carbon\Carbon::parse($row['check_in'])->format('H:i') }}
                                </span>
                            @else
                                <span class="text-slate-300 font-bold italic">—</span>
                            @endif
                        </td>
                        <td class="px-8 py-6 text-center">
                            @if($row['check_in_photo'])
                                <div class="relative inline-block group/photo">
                                    <img src="{{ Storage::url($row['check_in_photo']) }}" class="w-12 h-12 rounded-xl object-cover border-2 border-white shadow-sm hover:scale-[3] hover:z-50 transition-all cursor-zoom-in">
                                </div>
                            @else
                                <span class="text-[10px] font-bold text-slate-300 italic">—</span>
                            @endif
                        </td>
                        <td class="px-8 py-6 text-center">
                            @if($row['check_out'])
                                <span class="font-black text-slate-800 tabular-nums text-lg">
                                    {{ \Carbon\Carbon::parse($row['check_out'])->format('H:i') }}
                                </span>
                            @else
                                <span class="text-xs font-bold text-slate-300 italic">—</span>
                            @endif
                        </td>
                        <td class="px-8 py-6 text-center">
                            @if($row['check_out_photo'])
                                <div class="relative inline-block group/photo">
                                    <img src="{{ Storage::url($row['check_out_photo']) }}" class="w-12 h-12 rounded-xl object-cover border-2 border-white shadow-sm hover:scale-[3] hover:z-50 transition-all cursor-zoom-in">
                                </div>
                            @else
                                <span class="text-[10px] font-bold text-slate-300 italic">—</span>
                            @endif
                        </td>

                        <td class="px-8 py-6 text-center">
                            <span class="inline-flex items-center px-3 py-1.5 {{ $statusStyle }} rounded-xl text-[10px] font-black uppercase tracking-wider">
                                {{ $statusIcon }} {{ $row['status'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-8 py-20 text-center">
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
