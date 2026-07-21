<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessRecord extends Model
{
    protected $guarded = [];
    protected $casts = ['payload' => 'array', 'effective_date' => 'date'];
    public function process() { return $this->belongsTo(Process::class); }
}
