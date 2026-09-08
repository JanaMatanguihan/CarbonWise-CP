<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MitigationAction extends Model
{
    protected $table = 'mitigation_actions';

    protected $fillable = [
        'g_suite',
        'title',
        'description',
        'carbon_reduced',
        'status',
        'completed_at',
    ];
}