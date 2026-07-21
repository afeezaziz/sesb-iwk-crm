<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForecastRun extends Model
{
    protected $guarded = [];
    protected $casts = ['series' => 'array', 'frozen' => 'boolean', 'computed_at' => 'datetime'];
}
