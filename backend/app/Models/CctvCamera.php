<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CctvCamera extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id',
        'name',
        'province',
        'city',
        'district',
        'road',
        'latitude',
        'longitude',
        'stream_url',
        'thumbnail_url',
        'source_name',
        'source_url',
        'status',
        'last_checked_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'last_checked_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(CctvSource::class, 'source_id');
    }
}
