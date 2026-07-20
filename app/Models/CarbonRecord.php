<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarbonRecord extends Model
{
    protected $table = 'carbon_records';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'g_suite',
        'record_date',
        'transportation',
        'electricity',
        'food',
        'total_emission',
        'ai_recommendation',
        'transport_item',
        'office_item',
        'food_item',
    ];
}