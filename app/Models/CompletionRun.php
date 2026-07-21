<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompletionRun extends Model
{
    protected $guarded = [];
    protected $casts = ['params' => 'array', 'ran_at' => 'datetime'];
    public function process() { return $this->belongsTo(Process::class); }
}
