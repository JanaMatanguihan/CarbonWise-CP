<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MitigationStrategy extends Model
{

    protected $table = 'mitigation_strategies';


    protected $fillable = [

        'title',

        'description',

        'category',

        'target_areas',

        'participants',

        'carbon_reduced',

        'progress',

        'status',

        'completed_at',

        'g_suite',

    ];
}