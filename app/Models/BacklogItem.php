<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BacklogItem extends Model
{
    protected $guarded = [];
    // classification: missing_process | enhancement | defect | net_new (AI proposed)
    // triage_state: pending | confirmed | overridden
}
