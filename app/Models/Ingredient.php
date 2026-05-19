<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Ingredient extends Model
{
    protected $fillable = [
        'supplier_id', 'name', 'unit',
        'stock_qty', 'min_stock', 'cost_per_unit',
        'expiry_date', 'expired_processed_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'expired_processed_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock_qty <= 0) return 'Habis';
        if ($this->stock_qty <= $this->min_stock) return 'Menipis';
        return 'Aman';
    }

    public function getExpiryStatusAttribute(): string
    {
        if (!$this->expiry_date) return 'none';
        $today = Carbon::today();
        if ($this->expiry_date->lt($today)) return 'expired';
        if ($this->expiry_date->diffInDays($today) <= 3) return 'soon';
        return 'fresh';
    }

    public function logs()
    {
        return $this->hasMany(StockLog::class);
    }
}
