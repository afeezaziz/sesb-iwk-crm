<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    protected $guarded = [];
    protected $casts = ['effective_date' => 'date'];
    public function account() { return $this->belongsTo(Account::class); }
}
