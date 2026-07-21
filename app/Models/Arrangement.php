<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Arrangement extends Model
{
    protected $guarded = [];
    protected $casts = ['start_date' => 'date'];
    public function account()     { return $this->belongsTo(Account::class); }
    public function instalments() { return $this->hasMany(Instalment::class)->orderBy('seq'); }
}
