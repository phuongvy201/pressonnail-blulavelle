<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UgcReport extends Model
{
    protected $fillable = [
        'target_type',
        'target_id',
        'reason',
        'details',
        'reporter_user_id',
        'reporter_guest_token',
        'status',
    ];
}
