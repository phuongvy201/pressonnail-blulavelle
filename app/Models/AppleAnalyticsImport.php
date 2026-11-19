<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppleAnalyticsImport extends Model
{
    protected $fillable = [
        'instance_id',
        'report_name',
        'processing_date',
        'checksum',
        'imported_at',
    ];

    protected $casts = [
        'processing_date' => 'date',
        'imported_at' => 'datetime',
    ];
}
