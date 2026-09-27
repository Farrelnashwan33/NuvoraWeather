<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CctvSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'region',
        'base_url',
        'api_url',
        'status',
        'last_sync_at',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
    ];

    public function cameras(): HasMany
    {
        return $this->hasMany(CctvCamera::class, 'source_id');
    }
}
