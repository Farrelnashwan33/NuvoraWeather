<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedCity extends Model
{
    protected $fillable = [
        'city_name',
        'country',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];
}
