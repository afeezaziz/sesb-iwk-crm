<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $guarded = [];
    protected $casts = ['bill_date' => 'date', 'due_date' => 'date'];
    public function account() { return $this->belongsTo(Account::class); }
    public function due(): float { return round($this->amount - $this->paid, 2); }
}
