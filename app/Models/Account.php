<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $guarded = [];
    protected $casts = ['registered_at' => 'date'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function premise()  { return $this->belongsTo(Premise::class); }
    public function bills()    { return $this->hasMany(Bill::class); }
    public function receipts() { return $this->hasMany(Receipt::class); }
    public function adjustments() { return $this->hasMany(Adjustment::class); }
    public function enquiries()   { return $this->hasMany(Enquiry::class); }
    public function arrangements(){ return $this->hasMany(Arrangement::class); }

    public function outstanding(): float
    {
        return (float) $this->bills()->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(amount - paid),0) as o')->value('o');
    }

    public function oldestUnpaidDays(): int
    {
        $b = $this->bills()->whereIn('status', ['unpaid', 'partial'])->orderBy('due_date')->first();
        return $b ? max(0, (int) $b->due_date->diffInDays(now(), false)) : 0;
    }
}
