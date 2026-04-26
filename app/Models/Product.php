<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = ['name', 'category', 'price', 'image_url', 'is_available'];

    protected $casts = ['is_available' => 'boolean', 'price' => 'decimal:2'];

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
                    ->withPivot('qty_used');
    }
}
    