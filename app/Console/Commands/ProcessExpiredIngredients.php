<?php

namespace App\Console\Commands;

use App\Services\ExpiredIngredientService;
use Illuminate\Console\Command;

class ProcessExpiredIngredients extends Command
{
    protected $signature = 'ingredients:process-expired';

    protected $description = 'Cek bahan baku yang sudah kadaluarsa dan otomatis catat di riwayat stok';

    public function handle(): int
    {
        $count = ExpiredIngredientService::processExpired();
        $this->info("Selesai. {$count} bahan baku diproses sebagai kadaluarsa.");
        return self::SUCCESS;
    }
}
