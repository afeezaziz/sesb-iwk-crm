<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instalment extends Model
{
    protected $guarded = [];
    protected $casts = ['due_date' => 'date'];
    public function arrangement() { return $this->belongsTo(Arrangement::class); }
}
