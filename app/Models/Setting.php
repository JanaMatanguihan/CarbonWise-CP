<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [

        'system_name',

        'organization',

        'email',

        'phone',

        'timezone',

        'date_format',

        'language',

        'theme',

        'accent_color',

        'session_timeout',

        'remember_days',

        'default_dashboard',

        'records_per_page',

    ];
}