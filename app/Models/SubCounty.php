<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubCounty extends Model
{
    use HasFactory;

    protected $fillable = [
        'county_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }
}
