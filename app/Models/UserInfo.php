<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    protected $table = 'user_info';

    protected $primaryKey = 'g_suite';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'sr_code',
        'full_name',
        'g_suite',
        'password',
        'campus',
        'year_level',
        'department',
        'role',
        'status',
    ];

    public function carbonRecords()
    {
        return $this->hasMany(CarbonRecord::class, 'g_suite', 'g_suite');
    }
}