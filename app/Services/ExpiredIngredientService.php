<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class ExpiredIngredientService
{
    public static function processExpired(?int $userId = null): int
    {
        $today = Carbon::today();

        $userId = $userId
            ?? auth()->id()
            ?? optional(User::where('role', 'owner')->first())->id
            ?? optional(User::first())->id;

        if (!$userId) {
            return 0;
        }

        $ingredients = Ingredient::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', $today)
            ->whereNull('expired_processed_at')
            ->where('stock_qty', '>', 0)
            ->get();

        $count = 0;
        foreach ($ingredients as $ingredient) {
            $qty = $ingredient->stock_qty;

            StockLog::create([
                'ingredient_id' => $ingredient->id,
                'type' => 'waste',
                'qty' => $qty,
                'recorded_by' => $userId,
                'reason' => 'Kadaluarsa / Rusak (Otomatis - tanggal kadaluarsa ' . $ingredient->expiry_date->format('d M Y') . ')',
            ]);

            $ingredient->update([
                'stock_qty' => 0,
                'expired_processed_at' => now(),
            ]);

            $count++;
        }

        return $count;
    }
}
