<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherLog extends Model
{
    protected $fillable = [
        'city',
        'latitude',
        'longitude',
        'response_status',
        'requested_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'response_status' => 'integer',
        'requested_at' => 'datetime',
    ];
}
