<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $guarded = [];
    protected $casts = ['posted_at' => 'datetime'];
    public function account()     { return $this->belongsTo(Account::class); }
    public function allocations() { return $this->hasMany(ReceiptAllocation::class); }
}
