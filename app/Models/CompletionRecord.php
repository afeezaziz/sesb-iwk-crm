<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompletionRecord extends Model
{
    protected $guarded = [];
    protected $casts = ['payload' => 'array'];
    public function process() { return $this->belongsTo(Process::class); }
}
