<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SdoReport extends Model
{
    protected $fillable = [
        'title',
        'description',
        'report_date',
        'status',
        'file_path',
        'total_emissions',
        'total_users',
    ];
}