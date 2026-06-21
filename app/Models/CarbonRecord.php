<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarbonRecord extends Model
{
    protected $fillable = [
        'user_id',
        'transportation',
        'electricity',
        'food',
        'waste',
        'total_emission',
        'record_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}