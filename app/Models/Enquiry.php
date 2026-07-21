<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    protected $guarded = [];
    protected $casts = ['sla_due' => 'date'];
    public function account() { return $this->belongsTo(Account::class); }
}
