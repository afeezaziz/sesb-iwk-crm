<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnomalyReview extends Model
{
    protected $guarded = [];
    // subject_type: bill|adjustment · rule: the detector rule key · verdict: confirmed|dismissed
}
