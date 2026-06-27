<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarbonRecord extends Model
{
    protected $fillable = [
        'g_suite',
        'transportation',
        'electricity',
        'food',
        'waste',
        'total_emission',
        'record_date',
    ];

    public function student()
    {
        return $this->belongsTo(UserInfo::class, 'g_suite', 'g_suite');
    }
}