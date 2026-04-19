<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Owner Northern Cafe',
            'email' => 'owner@cafe.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Pegawai Cafe',
            'email' => 'pegawai@cafe.com',
            'password' => Hash::make('password123'),
            'role' => 'pegawai',
            'is_active' => true,
        ]);

        $this->call(DemoDataSeeder::class);
    }
}
