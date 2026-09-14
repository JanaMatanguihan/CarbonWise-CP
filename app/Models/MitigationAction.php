<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MitigationAction extends Model
{
    protected $table = 'mitigation_actions';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'carbon_reduced',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'carbon_reduced' => 'decimal:2',
        'completed_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}