<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptAllocation extends Model
{
    protected $guarded = [];
    public function receipt() { return $this->belongsTo(Receipt::class); }
    public function bill()    { return $this->belongsTo(Bill::class); }
}
