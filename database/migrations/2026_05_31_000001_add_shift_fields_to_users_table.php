<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->time('shift_start')->nullable()->after('is_active');
            $table->time('shift_end')->nullable()->after('shift_start');
            $table->boolean('is_attendance_debug')->default(false)->after('shift_end');
        });

        DB::table('users')->updateOrInsert(
            ['email' => 'debug@cafe.com'],
            [
                'name' => 'Debug Pegawai',
                'password' => Hash::make('debug123'),
                'role' => 'pegawai',
                'is_active' => true,
                'shift_start' => null,
                'shift_end' => null,
                'is_attendance_debug' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'debug@cafe.com')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['shift_start', 'shift_end', 'is_attendance_debug']);
        });
    }
};
