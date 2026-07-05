<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MitigationStrategy extends Model
{

    protected $table = 'mitigation_strategies';


    protected $fillable = [

        'title',

        'description',

        'carbon_reduced',

        'status',

        'completed_at',

        'g_suite',

    ];

}