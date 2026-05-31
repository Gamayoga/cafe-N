<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id', 'covered_by_user_id', 'check_in', 'check_out', 'date', 'notes',
        'check_in_photo', 'check_out_photo', 'manual_close',
    ];

    protected $casts = [
        'date' => 'date',
        'manual_close' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'covered_by_user_id');
    }
}
