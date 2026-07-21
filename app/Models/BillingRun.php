<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingRun extends Model
{
    protected $guarded = [];
    protected $casts = ['ran_at' => 'datetime'];
}
