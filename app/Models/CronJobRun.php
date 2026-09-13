<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronJobRun extends Model
{
    protected $fillable = [
        'job',
        'status',
        'started_at',
        'finished_at',
        'duration_ms',
        'error',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
    ];
}