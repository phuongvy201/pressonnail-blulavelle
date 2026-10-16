<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UgcBlock extends Model
{
    protected $fillable = [
        'scope',
        'target_id',
        'blocker_key',
        'blocker_user_id',
        'blocker_guest_token',
    ];
}
