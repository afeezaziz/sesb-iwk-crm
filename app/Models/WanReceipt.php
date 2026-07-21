<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WanReceipt extends Model
{
    protected $guarded = [];
    protected $casts = ['received_at' => 'date'];
    public function process() { return $this->belongsTo(Process::class); }
}
