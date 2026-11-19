<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppleAppDownload extends Model
{
    protected $fillable = [
        'stat_date',
        'download_type',
        'source_type',
        'campaign',
        'territory',
        'device',
        'counts',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'counts' => 'integer',
    ];
}
