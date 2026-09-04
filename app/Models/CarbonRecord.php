<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarbonRecord extends Model
{
    protected $table = 'carbon_records';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'record_date',
        'transportation',
        'electricity',
        'food',
        'total_emission',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}