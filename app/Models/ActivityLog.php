<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'g_suite',
        'module',
        'activity',
        'description',
        'status',
        'record_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}